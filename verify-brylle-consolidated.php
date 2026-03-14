<?php
// Verify Brylle's market rental bills now appear in consolidated view
$brylle_correct_id = 'a135da1e-6727-430e-9771-e15688e6f79e';

echo "Testing payment API endpoint for Brylle Kenneth...\n";
echo "Correct UUID: $brylle_correct_id\n\n";

$url = "http://localhost/rcts-qc/api/endpoints/payment.php?action=get_pending_bills&qcitizen_id=" . $brylle_correct_id;
$response = json_decode(file_get_contents($url), true);

if ($response['success']) {
    echo "✓ API Response Successful\n\n";
    $bills = $response['data']['bills'] ?? [];
    echo "Found " . count($bills) . " pending bills\n\n";
    
    $bill_types = [];
    $total = 0;
    foreach ($bills as $bill) {
        $type = $bill['bill_type'];
        if (!isset($bill_types[$type])) {
            $bill_types[$type] = [];
        }
        $bill_types[$type][] = $bill;
        $total += $bill['total_amount_due'];
    }
    
    foreach ($bill_types as $type => $type_bills) {
        echo "[$type - " . count($type_bills) . " bills]\n";
        $subtotal = 0;
        foreach ($type_bills as $bill) {
            echo "  • {$bill['bill_reference_no']}: ₱" . number_format($bill['total_amount_due'], 2) . "\n";
            $subtotal += $bill['total_amount_due'];
        }
        echo "  Subtotal: ₱" . number_format($subtotal, 2) . "\n\n";
    }
    
    echo "═══════════════════════════════════════════\n";
    echo "GRAND TOTAL: ₱" . number_format($total, 2) . "\n";
    echo "═══════════════════════════════════════════\n";
} else {
    echo "✗ API Error\n";
    print_r($response);
}
?>
