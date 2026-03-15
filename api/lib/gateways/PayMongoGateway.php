<?php
/**
 * PayMongo payment gateway integration (test/live).
 *
 * PayMongo provides a simple REST API to create payment links, payment intents, and
 * handle webhooks. This implementation uses the Payment Links API for easiest
 * integration.
 *
 * Docs: https://developers.paymongo.com/docs
 */

require_once __DIR__ . '/../PaymentGateway.php';
require_once __DIR__ . '/BaseGateway.php';

class PayMongoGateway extends BaseGateway {
    public function createPayment(array $transaction): array {
        $txnId = $transaction['transaction_id'] ?? $this->generateTransactionId();
        $amount = $this->normalizeAmount($transaction['amount_settled'] ?? 0);

        $apiKey = $this->getConfig('api_key') ?: getenv('PAYMONGO_API_KEY');
        if (!$apiKey) {
            return [
                'transaction_id' => $txnId,
                'status' => 'Failed',
                'error' => 'Missing PayMongo API key (PAYMONGO_API_KEY)',
            ];
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        // PayMongo requires the redirect URLs to be reachable by the user's browser.
        // For local development, you can configure a public URL (e.g. ngrok) via
        // PAYMENT_GATEWAYS['PayMongo']['redirect_base_url'] or the env var
        // PAYMONGO_REDIRECT_BASE_URL.
        $redirectBase = $this->getConfig('redirect_base_url') ?: getenv('PAYMONGO_REDIRECT_BASE_URL');
        if (!$redirectBase) {
            $redirectBase = "$scheme://$host/rcts-qc";
        }

        // Ensure we don't end up with double slashes when joining paths
        $redirectBase = rtrim($redirectBase, "/");

        $payload = json_encode([
            'data' => [
                'attributes' => [
                    'amount' => intval(round($amount * 100)),
                    'currency' => 'PHP',
                    'description' => 'RCTS-QC Payment',
                    'redirect' => [
                        'success' => "{$redirectBase}/pages/citizen/payment-gateway.html?payment=success&transaction_id=$txnId&use_polling=true",
                        'failed' => "{$redirectBase}/pages/citizen/payment-gateway.html?payment=failed&transaction_id=$txnId",
                    ],
                    'metadata' => [
                        'transaction_id' => $txnId,
                        'use_polling' => 'true', // Mark for polling since webhooks are blocked
                    ],
                ],
            ],
        ]);

        $response = $this->httpRequest('POST', 'https://api.paymongo.com/v1/links', [
            "Authorization: Basic " . base64_encode($apiKey . ':'),
            'Content-Type: application/json',
        ], $payload);

        $body = json_decode($response['body'] ?? '', true);
        $link = $body['data']['attributes']['checkout_url'] ?? $body['data']['attributes']['url'] ?? null;

        if (!$response['success'] || !$link) {
            return [
                'transaction_id' => $txnId,
                'status' => 'Failed',
                'error' => $body ?: $response,
            ];
        }

        return [
            'transaction_id' => $txnId,
            'status' => 'Pending',
            'provider' => 'PayMongo',
            'redirect_url' => $link,
            'provider_reference' => $body['data']['id'] ?? null,
            'extra' => $body,
        ];
    }

    public function handleWebhook(array $payload, array $headers = [], string $rawBody = null): array {
        $secret = $this->getConfig('webhook_secret') ?: getenv('PAYMONGO_WEBHOOK_SECRET');
        $sigHeader = $headers['Paymongo-Signature'] ?? $headers['paymongo-signature'] ?? '';

        if ($secret && $sigHeader && !empty($rawBody)) {
            $expected = hash_hmac('sha256', $rawBody, $secret);
            if (!hash_equals($expected, $sigHeader)) {
                return [
                    'transaction_id' => null,
                    'status' => 'Failed',
                    'error' => 'Invalid PayMongo webhook signature',
                    'signature_provided' => $sigHeader,
                ];
            }
        }

        $txnId = $payload['data']['attributes']['metadata']['transaction_id'] ?? null;
        $status = $payload['data']['attributes']['status'] ?? null;
        $providerRef = $payload['data']['id'] ?? null;

        // Normalize PayMongo statuses to our system
        $successStatuses = ['paid', 'succeeded'];
        $resultStatus = in_array(strtolower($status), $successStatuses, true) ? 'Success' : 'Failed';

        return [
            'transaction_id' => $txnId,
            'status' => $resultStatus,
            'provider_reference' => $providerRef,
            'metadata' => $payload,
        ];
    }

    public function pollPaymentStatus(string $providerReference): array {
        $apiKey = $this->getConfig('api_key') ?: getenv('PAYMONGO_API_KEY');
        if (!$apiKey) {
            return [
                'status' => 'Failed',
                'error' => 'Missing PayMongo API key (PAYMONGO_API_KEY)',
            ];
        }

        $response = $this->httpRequest('GET', "https://api.paymongo.com/v1/links/{$providerReference}", [
            "Authorization: Basic " . base64_encode($apiKey . ':'),
        ]);

        if (!$response['success']) {
            return [
                'status' => 'Failed',
                'error' => 'Failed to poll payment status',
                'response' => $response,
            ];
        }

        $body = json_decode($response['body'] ?? '', true);
        $status = $body['data']['attributes']['status'] ?? null;
        $txnId = $body['data']['attributes']['metadata']['transaction_id'] ?? null;

        // Normalize PayMongo statuses to our system
        $successStatuses = ['paid', 'succeeded'];
        $resultStatus = in_array(strtolower($status), $successStatuses, true) ? 'Success' : 'Pending';

        return [
            'transaction_id' => $txnId,
            'status' => $resultStatus,
            'provider_reference' => $providerReference,
            'raw_status' => $status,
            'metadata' => $body,
        ];
    }
}
