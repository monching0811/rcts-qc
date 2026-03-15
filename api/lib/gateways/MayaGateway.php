<?php
/**
 * Maya payment gateway integration (stub).
 *
 * This is a placeholder implementation. To integrate with the real Maya API,
 * implement createPayment() and handleWebhook() using the vendor's API.
 */

require_once __DIR__ . '/../PaymentGateway.php';
require_once __DIR__ . '/BaseGateway.php';

class MayaGateway extends BaseGateway {
    public function createPayment(array $transaction): array {
        $txnId = $transaction['transaction_id'] ?? $this->generateTransactionId();

        // For mock behavior, we provide a direct URL to the execute endpoint
        $baseUrl = rtrim((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']), '/');
        $executeUrl = $baseUrl . '/payment.php?action=execute';

        return [
            'transaction_id' => $txnId,
            'status' => 'Pending',
            'provider' => 'Maya',
            'redirect_url' => $executeUrl,
            'extra' => [
                'execute_method' => 'POST',
                'execute_payload' => ['transaction_id' => $txnId],
            ]
        ];
    }

    public function handleWebhook(array $payload, array $headers = [], string $rawBody = null): array {
        $secret = $this->getConfig('webhook_secret');
        $sigHeader = $headers['X-MAYA-SIGNATURE'] ?? $headers['x-maya-signature'] ?? '';

        if ($secret && $sigHeader) {
            $expected = hash_hmac('sha256', $rawBody ?? json_encode($payload), $secret);
            if (!hash_equals($expected, $sigHeader)) {
                return [
                    'transaction_id' => null,
                    'status' => 'Failed',
                    'error' => 'Invalid Maya webhook signature',
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
