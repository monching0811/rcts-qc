<?php
/**
 * Test webhook accessibility on InfinityFree
 * This script tests if the webhook endpoint is accessible
 */

$testTxnId = $_GET['txn_id'] ?? 'TEST-' . time();

// Simulate a PayMongo webhook payload
$webhookPayload = [
    'data' => [
        'id' => 'evt_test_' . time(),
        'type' => 'source.chargeable',
        'attributes' => [
            'type' => 'source.chargeable',
            'livemode' => false,
            'data' => [
                'id' => 'src_test_' . time(),
                'type' => 'gcash',
                'attributes' => [
                    'status' => 'chargeable',
                    'metadata' => [
                        'transaction_id' => $testTxnId
                    ]
                ]
            ],
            'created_at' => time(),
            'updated_at' => time()
        ]
    ]
];

echo "<h2>Testing Webhook Accessibility</h2>";
echo "<p>Testing transaction ID: <strong>$testTxnId</strong></p>";

// Test the webhook endpoint
$webhookUrl = 'https://rcts-qc.wuaze.com/api/endpoints/payment.php?action=webhook';

echo "<h3>Sending test webhook to:</h3>";
echo "<code>$webhookUrl</code><br><br>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookPayload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'User-Agent: PayMongo-Webhook/1.0'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<h3>Response:</h3>";
echo "<strong>HTTP Code:</strong> $httpCode<br>";
echo "<strong>Curl Error:</strong> " . ($curlError ?: 'None') . "<br><br>";

if ($httpCode === 200) {
    echo "<span style='color: green;'>✅ HTTP 200 - Webhook endpoint is accessible</span><br>";
    echo "<strong>Response Body:</strong><br>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
} else {
    echo "<span style='color: red;'>❌ HTTP $httpCode - Webhook endpoint blocked</span><br>";
    echo "<strong>Response Body:</strong><br>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// Check if response contains bot protection HTML
if (stripos($response, '<html') !== false || stripos($response, 'bot') !== false) {
    echo "<br><span style='color: orange;'>⚠️ Response contains HTML/bot protection - webhook is blocked</span>";
} else {
    echo "<br><span style='color: green;'>✅ Response looks like valid API response</span>";
}

echo "<br><br><h3>Test Payload Sent:</h3>";
echo "<pre>" . json_encode($webhookPayload, JSON_PRETTY_PRINT) . "</pre>";
?>