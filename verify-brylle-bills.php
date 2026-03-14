<?php
// Quick verification - test the payment endpoint for Brylle
$qcitizen_id = 'QC-2026-00156';
echo "Testing payment API endpoint for Brylle...\n\n";

$url = "http://localhost/rcts-qc/api/endpoints/payment.php?action=get_pending_bills&qcitizen_id=" . $qcitizen_id;
$response = json_decode(file_get_contents($url), true);

if ($response['success']) {
    echo "✓ API Response Successful\n";
    $bills = $response['data']['bills'] ?? [];
    echo "✓ Found " . count($bills) . " pending bills\n\n";
    
    foreach ($bills as $bill) {
        echo "• {$bill['bill_reference_no']} ({$bill['bill_type']}): ₱" . number_format($bill['total_amount_due'], 2) . "\n";
    }
} else {
    echo "✗ API Error\n";
    print_r($response);
}
?>
