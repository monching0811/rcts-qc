<?php
/**
 * GCash payment gateway integration (sandbox / production).
 *
 * NOTE: GCash does not publish a public REST API like Stripe. Typically GCash
 * integrations are done via a merchant onboarding process, where you obtain
 * sandbox credentials and API endpoints from Globe (GCash) directly.
 *
 * This class provides a realistic scaffold and signature verification flow.
 * Replace the `TODO` sections with actual API calls once you have the spec.
 */

require_once __DIR__ . '/../PaymentGateway.php';
require_once __DIR__ . '/BaseGateway.php';

class GCashGateway extends BaseGateway {
    private ?string $accessToken = null;
    private ?int $accessTokenExpiry = null;

    public function createPayment(array $transaction): array {
        $txnId = $transaction['transaction_id'] ?? $this->generateTransactionId();
        $amount = $this->normalizeAmount($transaction['amount_settled'] ?? 0);

        // CONFIG
        $apiBase       = rtrim($this->getConfig('api_base') ?: 'https://sandbox.gcash.com/v1', '/');
        $clientId      = $this->getConfig('client_id');
        $clientSecret  = $this->getConfig('client_secret');
        $webhookSecret = $this->getConfig('webhook_secret');

        // If you don't have sandbox creds yet, return a placeholder URL so the
        // frontend flow can still be tested without real GCash.
        if (!$clientId || !$clientSecret) {
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $callbackUrl = urlencode("$scheme://$host/api/endpoints/payment.php?action=webhook&gateway_provider=GCash");

            return [
                'transaction_id' => $txnId,
                'status' => 'Pending',
                'provider' => 'GCash',
                'redirect_url' => "$apiBase/checkout?transaction_id=" . urlencode($txnId)
                    . "&amount=" . urlencode($amount)
                    . "&callback=" . $callbackUrl,
                'extra' => [
                    'api_base' => $apiBase,
                    'callback_url' => $callbackUrl,
                    'note' => 'Missing client_id/client_secret; this is a placeholder URL. Replace with real GCash checkout call once you have credentials.',
                ],
            ];
        }

        // 1) Obtain an access token (client credentials grant, or whatever GCash uses)
        $token = $this->getAccessToken($apiBase, $clientId, $clientSecret);
        if (!$token) {
            return [
                'transaction_id' => $txnId,
                'status' => 'Failed',
                'error' => 'Unable to obtain access token for GCash API',
            ];
        }

        // 2) Create a checkout session / payment request
        // TODO: Replace the URL + payload with actual GCash Checkout API spec.
        $checkoutUrl = "$apiBase/payments";
        $payload = json_encode([
            'merchant_transaction_id' => $txnId,
            'amount' => $amount,
            'currency' => 'PHP',
            'description' => 'QC-PAY Billing Payment',
            'callback_url' => "https://{$_SERVER['HTTP_HOST']}/rcts-qc/api/endpoints/payment.php?action=webhook&gateway_provider=GCash",
            'metadata' => [
                'transaction_id' => $txnId,
            ],
        ]);

        $response = $this->httpRequest('POST', $checkoutUrl, [
            "Authorization: Bearer $token",
            'Content-Type: application/json',
        ], $payload);

        $body = json_decode($response['body'] ?? '', true);

        // TODO: adjust these according to actual response fields
        $redirectUrl = $body['redirect_url'] ?? ($body['payment_url'] ?? null);

        if (!$response['success'] || !$redirectUrl) {
            return [
                'transaction_id' => $txnId,
                'status' => 'Failed',
                'error' => $body ?: $response,
            ];
        }

        return [
            'transaction_id' => $txnId,
            'status' => 'Pending',
            'provider' => 'GCash',
            'redirect_url' => $redirectUrl,
            'provider_reference' => $body['payment_id'] ?? null,
            'extra' => $body,
        ];
    }

    public function handleWebhook(array $payload, array $headers = [], string $rawBody = null): array {
        $secret = $this->getConfig('webhook_secret');
        $sigHeader = $headers['X-GCASH-SIGNATURE'] ?? $headers['x-gcash-signature'] ?? '';

        // Signature verification (if webhook secret is provided)
        if ($secret && $sigHeader) {
            $expected = hash_hmac('sha256', $rawBody ?? json_encode($payload), $secret);
            if (!hash_equals($expected, $sigHeader)) {
                return [
                    'transaction_id' => null,
                    'status' => 'Failed',
                    'error' => 'Invalid GCash webhook signature',
                    'signature_provided' => $sigHeader,
                ];
            }
        }

        // TODO: Map these fields to the actual GCash webhook schema
        $txnId = $payload['metadata']['transaction_id'] ?? $payload['transaction_id'] ?? null;
        $status = $payload['status'] ?? $payload['payment_status'] ?? 'Success';
        $providerRef = $payload['payment_id'] ?? $payload['reference_id'] ?? $txnId;

        return [
            'transaction_id' => $txnId,
            'status' => $status,
            'provider_reference' => $providerRef,
            'metadata' => $payload,
        ];
    }

    private function getAccessToken(string $apiBase, string $clientId, string $clientSecret): ?string {
        if ($this->accessToken && $this->accessTokenExpiry && time() < $this->accessTokenExpiry - 30) {
            return $this->accessToken;
        }

        // TODO: Replace with actual token endpoint and parameter names
        $tokenUrl = rtrim($apiBase, '/') . '/oauth/token';
        $resp = $this->httpRequest('POST', $tokenUrl, [
            'Content-Type: application/x-www-form-urlencoded',
        ], http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]));

        $body = json_decode($resp['body'] ?? '', true);
        if (!$resp['success'] || empty($body['access_token'])) {
            return null;
        }

        $this->accessToken = $body['access_token'];
        $this->accessTokenExpiry = time() + (($body['expires_in'] ?? 3600) - 60);
        return $this->accessToken;
    }
}
