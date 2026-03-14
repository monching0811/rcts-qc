<?php
/**
 * Mock payment gateway for local development and testing.
 *
 * This gateway mimics a real payment provider by returning a redirect URL
 * to the local execute endpoint. It allows the rest of the system to remain
 * unchanged while providing a clear integration point for real providers.
 */

require_once __DIR__ . '/../PaymentGateway.php';
require_once __DIR__ . '/BaseGateway.php';

class MockGateway extends BaseGateway {
    public function createPayment(array $transaction): array {
        $txnId = $transaction['transaction_id'] ?? $this->generateTransactionId();

        // For local testing, we provide a direct URL to the execute endpoint
        $baseUrl = rtrim((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']), '/');
        $executeUrl = $baseUrl . '/payment.php?action=execute';

        return [
            'transaction_id' => $txnId,
            'status' => 'Pending',
            'provider' => 'Mock',
            'redirect_url' => $executeUrl,
            'extra' => [
                'execute_method' => 'POST',
                'execute_payload' => ['transaction_id' => $txnId],
            ]
        ];
    }

    public function handleWebhook(array $payload, array $headers = [], string $rawBody = null): array {
        // For mock, we simply assume success and echo back the transaction_id.
        return [
            'transaction_id' => $payload['transaction_id'] ?? null,
            'status' => 'Success',
            'provider_reference' => $payload['transaction_id'] ?? null,
            'metadata' => $payload,
        ];
    }
}
