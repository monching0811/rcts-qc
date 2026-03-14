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
        $amount = $this->normalizeAmount($transaction['amount_settled'] ?? 0);

        $apiBase = rtrim($this->getConfig('api_base') ?: 'https://sandbox.maya.ph/v1', '/');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        // Simplified placeholder URL for Maya checkout.
        $callbackUrl = urlencode("$scheme://$host/api/endpoints/payment.php?action=webhook&gateway_provider=Maya");
        $redirectUrl = "$apiBase/checkout?transaction_id=" . urlencode($txnId)
            . "&amount=" . urlencode($amount)
            . "&callback=" . $callbackUrl;

        return [
            'transaction_id' => $txnId,
            'status' => 'Pending',
            'provider' => 'Maya',
            'redirect_url' => $redirectUrl,
            'extra' => [
                'api_base' => $apiBase,
                'callback_url' => $callbackUrl,
            ],
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
