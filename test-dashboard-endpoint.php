<?php
require_once 'api/config/supabase.php';

$citizen_id = 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5';

echo "========================================\n";
echo "TESTING: DASHBOARD PENDING BILLS ENDPOINT\n";
echo "========================================\n\n";

// Simulate what the dashboard does
$url = 'http://localhost/rcts-qc/api/endpoints/payment.php?action=get_pending_bills&qcitizen_id=' . $citizen_id;
echo "Calling: " . str_replace($citizen_id, '***', $url) . "\n\n";

$response = file_get_contents($url);
$data = json_decode($response, true);

if ($data['success']) {
    echo "✅ SUCCESS\n\n";
    echo "Bills returned: " . count($data['data']['bills'] ?? []) . "\n";
    echo "Grand total: ₱" . number_format($data['data']['grand_total'] ?? 0, 2) . "\n\n";
    
    echo "Bills list:\n";
    foreach ($data['data']['bills'] ?? [] as $bill) {
        echo "  • " . $bill['bill_reference_no'] . "\n";
        echo "    Type: " . $bill['bill_type'] . "\n";
        echo "    Amount: ₱" . number_format($bill['total_amount_due'] ?? 0, 2) . "\n";
        echo "    Status: " . $bill['status'] . "\n\n";
    }
} else {
    echo "❌ FAILED\n";
    echo "Error: " . $data['message'] . "\n";
}

echo "========================================\n";
echo "VERIFICATION:\n";
echo "========================================\n";
echo "These " . count($data['data']['bills'] ?? []) . " bills should appear in:\n";
echo "1. ✓ Dashboard → Pending Bills section\n";
echo "2. ✓ RPT Payment → Your Registered Properties\n";
echo "3. ✓ Payment Gateway → Available Bills\n";
