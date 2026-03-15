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

        // For mock behavior, we provide a direct URL to the execute endpoint
        $baseUrl = rtrim((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']), '/');
        $executeUrl = $baseUrl . '/payment.php?action=execute&transaction_id=' . urlencode($txnId);

        return [
            'transaction_id' => $txnId,
            'status' => 'Pending',
            'provider' => 'GCash',
            'redirect_url' => $executeUrl,
            'extra' => [
                'execute_method' => 'POST',
                'execute_payload' => ['transaction_id' => $txnId],
            ]
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
