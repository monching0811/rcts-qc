<?php
/**
 * Stripe payment gateway integration (test mode).
 *
 * This implementation uses Stripe Checkout Sessions and expects the
 * following environment variables in development:
 *   - STRIPE_API_KEY (secret key)
 *   - STRIPE_WEBHOOK_SECRET (for webhook signature verification)
 *
 * In production, set these values securely and never commit them to source control.
 */

require_once __DIR__ . '/../PaymentGateway.php';
require_once __DIR__ . '/BaseGateway.php';

class StripeGateway extends BaseGateway {
    public function createPayment(array $transaction): array {
        $txnId = $transaction['transaction_id'] ?? $this->generateTransactionId();
        $amount = $this->normalizeAmount($transaction['amount_settled'] ?? 0);
        $amountCents = intval(round($amount * 100));

        $apiKey = $this->getConfig('api_key') ?: getenv('STRIPE_API_KEY');
        if (!$apiKey) {
            return [
                'transaction_id' => $txnId,
                'status' => 'Failed',
                'error' => 'Missing Stripe API key (STRIPE_API_KEY)',
            ];
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $successUrl = "$scheme://$host/?payment=success&transaction_id=$txnId";
        $cancelUrl = "$scheme://$host/?payment=cancel&transaction_id=$txnId";

        $payload = http_build_query([
            'payment_method_types[]' => 'card',
            'line_items[0][price_data][currency]' => 'php',
            'line_items[0][price_data][product_data][name]' => 'RCTS-QC Payment',
            'line_items[0][price_data][unit_amount]' => $amountCents,
            'line_items[0][quantity]' => 1,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata[transaction_id]' => $txnId,
        ]);

        $response = $this->httpRequest('POST', 'https://api.stripe.com/v1/checkout/sessions', [
            "Authorization: Bearer $apiKey",
            'Content-Type: application/x-www-form-urlencoded',
        ], $payload);

        $body = json_decode($response['body'] ?? '', true);

        if (!$response['success'] || empty($body['url'])) {
            return [
                'transaction_id' => $txnId,
                'status' => 'Failed',
                'error' => $body ?? $response,
            ];
        }

        return [
            'transaction_id' => $txnId,
            'status' => 'Pending',
            'provider' => 'Stripe',
            'redirect_url' => $body['url'],
            'provider_reference' => $body['id'] ?? null,
            'extra' => $body,
        ];
    }

    public function handleWebhook(array $payload, array $headers = [], string $rawBody = null): array {
        $secret = $this->getConfig('webhook_secret') ?: getenv('STRIPE_WEBHOOK_SECRET');
        $sigHeader = $headers['Stripe-Signature'] ?? $headers['stripe-signature'] ?? '';

        // If we don't have a webhook secret, accept the payload (useful for local mocks)
        if (!$secret || !$sigHeader) {
            return [
                'transaction_id' => $payload['data']['object']['metadata']['transaction_id'] ?? null,
                'status' => ($payload['type'] ?? '') === 'checkout.session.completed' ? 'Success' : 'Failed',
                'provider_reference' => $payload['data']['object']['id'] ?? null,
                'metadata' => $payload,
                'warning' => 'Signature validation skipped (missing webhook secret)',
            ];
        }

        // Basic signature parsing (Stripe uses a more complex scheme, so this is a best-effort check)
        if (!empty($rawBody)) {
            $expectedSignature = hash_hmac('sha256', $rawBody, $secret);
            if (strpos($sigHeader, $expectedSignature) === false) {
                return [
                    'transaction_id' => null,
                    'status' => 'Failed',
                    'error' => 'Invalid Stripe webhook signature',
                    'signature_header' => $sigHeader,
                ];
            }
        }

        return [
            'transaction_id' => $payload['data']['object']['metadata']['transaction_id'] ?? null,
            'status' => ($payload['type'] ?? '') === 'checkout.session.completed' ? 'Success' : 'Failed',
            'provider_reference' => $payload['data']['object']['id'] ?? null,
            'metadata' => $payload,
        ];
    }
}
