<?php
/**
 * Payment Gateway Configuration
 *
 * Define the available payment gateway providers and their configuration.
 *
 * To implement a real gateway, add a new provider entry with the required
 * API keys and endpoints, then implement a corresponding gateway class in
 * api/lib/gateways/.
 */

// Default gateway used when no gateway_provider is specified.
// Change this to 'Mock' for local testing, or to 'PayMongo' / 'GCash' for real provider flows.
define('PAYMENT_GATEWAY_DEFAULT', 'PayMongo');

// Enable mock mode for local development (does not call external APIs).
define('PAYMENT_GATEWAY_MOCK_ENABLED', true);

// Gateway configuration values. Put real API keys / endpoints here in production.
// You can also use environment variables (getenv) to avoid committing secrets.
define('PAYMENT_GATEWAYS', [
    'Mock' => [
        'name' => 'Mock Payment Gateway',
        'description' => 'Local mock gateway for development and testing.',
    ],

    // Example: GCash (placeholder values)
    'GCash' => [
        'name' => 'GCash',
        'api_base' => getenv('GCASH_API_BASE') ?: 'https://sandbox.gcash.com/v1',
        'client_id' => getenv('GCASH_CLIENT_ID') ?: '',
        'client_secret' => getenv('GCASH_CLIENT_SECRET') ?: '',
        'webhook_secret' => getenv('GCASH_WEBHOOK_SECRET') ?: '',
    ],

    // Example: Maya (placeholder values)
    'Maya' => [
        'name' => 'Maya',
        'api_base' => getenv('MAYA_API_BASE') ?: 'https://sandbox.maya.ph/v1',
        'api_key' => getenv('MAYA_API_KEY') ?: '',
        'webhook_secret' => getenv('MAYA_WEBHOOK_SECRET') ?: '',
    ],

    // Example: Bank (Landbank, etc.)
    'Bank' => [
        'name' => 'Bank API',
        'api_base' => getenv('BANK_API_BASE') ?: 'https://sandbox.bank.com/v1',
        'api_key' => getenv('BANK_API_KEY') ?: '',
        'webhook_secret' => getenv('BANK_WEBHOOK_SECRET') ?: '',
    ],

    // PayMongo (PH credit card / e-wallet gateway)
    // You can store these in environment variables or set them here for local testing.
    // NOTE: Never commit real production keys into source control.
    'PayMongo' => [
        'name' => 'PayMongo',
        'api_key' => getenv('PAYMONGO_API_KEY') ?: '',
        'webhook_secret' => getenv('PAYMONGO_WEBHOOK_SECRET') ?: '',
        // For local development, set this to your publicly reachable URL (ngrok) so that
        // PayMongo can redirect back to your portal after payment.
        // Example: https://9169-120-29-78-157.ngrok-free.app/rcts-qc
        'redirect_base_url' => 'https://rcts-qc.wuaze.com',
    ],

    // Example: Stripe (credit card payments)
    // Use Stripe test keys for local development. Set STRIPE_API_KEY and STRIPE_WEBHOOK_SECRET in your environment.
    'Stripe' => [
        'name' => 'Stripe (Credit Card)',
        'api_key' => getenv('STRIPE_API_KEY') ?: '',
        'webhook_secret' => getenv('STRIPE_WEBHOOK_SECRET') ?: '',
    ],
]);
