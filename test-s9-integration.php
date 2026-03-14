<?php
/**
 * Test S9 Traffic Violation Integration
 * test-s9-integration.php
 *
 * Simulates S9 sending a traffic violation signal to RCTS.
 */

require_once 'api/config/supabase.php';
require_once 'api/config/api-keys.php';

echo "Testing S9 Traffic Violation Integration\n";
echo "=======================================\n\n";

// Simulate S9 POST to inbound.php
$payload = [
    'violation_ticket_id' => 'TV-2025-001',
    'qcitizen_id' => '92be37af-7c34-4c9b-80cb-47cde7c3a9fd', // Raven
    'vehicle_plate_no' => 'ABC-123',
    'violation_type' => 'Illegal Parking',
    'fine_amount' => 500.00,
    'apprehension_date' => '2025-03-14'
];

$url = 'http://localhost/rcts-qc/api/endpoints/inbound.php?action=s9_violation_issued';
$headers = [
    'Content-Type: application/json',
    'X-API-Key: ' . API_KEYS['S9']
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "S9 Signal Sent:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

echo "RCTS Response:\n";
echo "HTTP Code: $http_code\n";
echo $response . "\n\n";

// Check if bill was created
$bill_check = db_select('rcts_assessment_billing_hub', ['originating_dept_id' => 'eq.9']);
echo "Traffic Fine Bills in RCTS:\n";
if (!empty($bill_check['data'])) {
    foreach ($bill_check['data'] as $bill) {
        echo "- " . $bill['bill_reference_no'] . ": ₱" . $bill['total_amount_due'] . " (" . $bill['status'] . ")\n";
    }
} else {
    echo "No traffic fine bills found.\n";
}

echo "\nTest complete.\n";
?>