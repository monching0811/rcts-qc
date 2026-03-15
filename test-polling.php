<?php
/**
 * Test script for PayMongo polling functionality
 * Run this to verify the polling implementation works
 */

require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/api/config/payment_gateways.php';
require_once __DIR__ . '/api/lib/gateways/PayMongoGateway.php';

// Test polling with a sample transaction ID
$testTxnId = $_GET['txn_id'] ?? '';

if (empty($testTxnId)) {
    echo "<h2>PayMongo Polling Test</h2>";
    echo "<p>Usage: ?txn_id=YOUR_TRANSACTION_ID</p>";
    echo "<p>This will test the polling functionality for a PayMongo transaction.</p>";
    exit;
}

echo "<h2>Testing PayMongo Polling for Transaction: $testTxnId</h2>";

// Get transaction details
$txn_result = supabase_request('rcts_payment_transaction', 'GET', [
    'transaction_id' => 'eq.' . $testTxnId
], [], true);

if (empty($txn_result['data'])) {
    echo "<p style='color: red;'>❌ Transaction not found</p>";
    exit;
}

$txn = $txn_result['data'][0];
echo "<h3>Transaction Details:</h3>";
echo "<pre>" . json_encode($txn, JSON_PRETTY_PRINT) . "</pre>";

// Check if it's a PayMongo transaction
if ($txn['gateway_provider'] !== 'PayMongo') {
    echo "<p style='color: orange;'>⚠️ Not a PayMongo transaction (provider: {$txn['gateway_provider']})</p>";
    exit;
}

// Extract provider reference
$bankRefRaw = $txn['bank_reference_no'] ?? '';
$bankRefParsed = json_decode($bankRefRaw, true);
$providerRef = null;

if (is_array($bankRefParsed) && isset($bankRefParsed['provider_reference'])) {
    $providerRef = $bankRefParsed['provider_reference'];
} elseif (is_string($bankRefRaw) && !empty($bankRefRaw)) {
    $providerRef = $bankRefRaw;
}

if (!$providerRef) {
    echo "<p style='color: red;'>❌ No provider reference found in bank_reference_no</p>";
    exit;
}

echo "<h3>Provider Reference: $providerRef</h3>";

// Initialize gateway and poll
$gateway = new PayMongoGateway(PAYMENT_GATEWAYS['PayMongo'] ?? []);

echo "<h3>Polling PayMongo API...</h3>";
$pollResult = $gateway->pollPaymentStatus($providerRef);

echo "<h3>Poll Result:</h3>";
echo "<pre>" . json_encode($pollResult, JSON_PRETTY_PRINT) . "</pre>";

if ($pollResult['status'] === 'Success') {
    echo "<p style='color: green;'>✅ Payment detected as successful!</p>";
} elseif ($pollResult['status'] === 'Pending') {
    echo "<p style='color: blue;'>⏳ Payment still pending</p>";
} else {
    echo "<p style='color: red;'>❌ Payment failed or error occurred</p>";
}
?>