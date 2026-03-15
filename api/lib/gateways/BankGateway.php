<?php
/**
 * Bank payment gateway integration (stub).
 *
 * This is a placeholder implementation for bank API integration (e.g., Landbank).
 * Implement createPayment() and handleWebhook() based on the bank's API.
 */

require_once __DIR__ . '/../PaymentGateway.php';
require_once __DIR__ . '/BaseGateway.php';

class BankGateway extends BaseGateway {
    public function createPayment(array $transaction): array {
        $txnId = $transaction['transaction_id'] ?? $this->generateTransactionId();

        // For mock behavior, we provide a direct URL to the execute endpoint
        $baseUrl = rtrim((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']), '/');
        $executeUrl = $baseUrl . '/payment.php?action=execute&transaction_id=' . urlencode($txnId);

        return [
            'transaction_id' => $txnId,
            'status' => 'Pending',
            'provider' => 'Bank',
            'redirect_url' => $executeUrl,
            'extra' => [
                'execute_method' => 'POST',
                'execute_payload' => ['transaction_id' => $txnId],
            ]
        ];
    }

    public function handleWebhook(array $payload, array $headers = [], string $rawBody = null): array {
        $secret = $this->getConfig('webhook_secret');
        $sigHeader = $headers['X-BANK-SIGNATURE'] ?? $headers['x-bank-signature'] ?? '';

        if ($secret && $sigHeader) {
            $expected = hash_hmac('sha256', $rawBody ?? json_encode($payload), $secret);
            if (!hash_equals($expected, $sigHeader)) {
                return [
                    'transaction_id' => null,
                    'status' => 'Failed',
                    'error' => 'Invalid Bank webhook signature',
                    'signature_provided' => $sigHeader,
                ];
            }
        }

        return [
            'transaction_id' => $payload['transaction_id'] ?? null,
            'status' => $payload['status'] ?? 'Success',
            'provider_reference' => $payload['provider_reference'] ?? ($payload['transaction_id'] ?? null),
            'metadata' => $payload,
        ];
    }
}
