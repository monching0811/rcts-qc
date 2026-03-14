<?php
/**
 * Payment Gateway Abstraction
 *
 * This provides a consistent interface for different payment providers.
 * The current implementation includes a "Mock" gateway for local development.
 *
 * Real gateway implementations should be added under api/lib/gateways/.
 */

require_once __DIR__ . '/../config/payment_gateways.php';

interface PaymentGatewayInterface {
    /**
     * Create a payment session (checkout) for the given transaction.
     * Returns an array containing at least:
     *   - transaction_id
     *   - status (Pending)
     *   - provider (e.g., 'GCash')
     *   - redirect_url (if applicable)
     *   - extra (any provider-specific data)
     */
    public function createPayment(array $transaction): array;

    /**
     * Handle a webhook callback from the provider.
     * Returns an array with keys:
     *   - transaction_id
     *   - status (e.g., 'Success' | 'Failed')
     *   - provider_reference (optional)
     *   - metadata (optional)
     */
    public function handleWebhook(array $payload, array $headers = [], string $rawBody = null): array;
}

/**
 * Simple factory for payment gateway instances.
 */
class PaymentGatewayFactory {
    public static function normalizeProvider(string $provider): string {
        $provider = trim($provider);
        if ($provider === '') {
            return PAYMENT_GATEWAY_DEFAULT;
        }

        // Case-insensitive match against configured gateways
        foreach (PAYMENT_GATEWAYS as $key => $cfg) {
            if (strcasecmp($key, $provider) === 0) {
                return $key;
            }
        }

        return PAYMENT_GATEWAY_DEFAULT;
    }

    public static function create(string $provider): PaymentGatewayInterface {
        $provider = self::normalizeProvider($provider);
        $config = PAYMENT_GATEWAYS[$provider] ?? null;

        switch ($provider) {
            case 'GCash':
                require_once __DIR__ . '/gateways/GCashGateway.php';
                return new GCashGateway($config);
            case 'Maya':
                require_once __DIR__ . '/gateways/MayaGateway.php';
                return new MayaGateway($config);
            case 'Bank':
                require_once __DIR__ . '/gateways/BankGateway.php';
                return new BankGateway($config);
            case 'PayMongo':
                require_once __DIR__ . '/gateways/PayMongoGateway.php';
                return new PayMongoGateway($config);
            case 'Stripe':
                require_once __DIR__ . '/gateways/StripeGateway.php';
                return new StripeGateway($config);
            case 'Mock':
            default:
                require_once __DIR__ . '/gateways/MockGateway.php';
                return new MockGateway($config);
        }
    }
}
