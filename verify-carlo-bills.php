<?php
require_once __DIR__ . '/api/config/supabase.php';

$carlo_id = 'QC-2024-000009';

echo "═══════════════════════════════════════════════════════════════\n";
echo "VERIFICATION: Pending Bills Created for Carlo Nicolas\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Query pending bills for Carlo directly from Supabase
echo "Querying Supabase for Carlo's Pending Bills...\n";
echo "───────────────────────────────────────────────────────────────\n\n";

// Direct Supabase API call
$url = SUPABASE_URL . '/rest/v1/rcts_assessment_billing_hub?qcitizen_id=eq.' . urlencode($carlo_id) . '&status=eq.Pending&order=bill_type.asc,bill_reference_no.asc';
$headers = supabase_headers(true);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    echo "❌ Failed to query database (HTTP $http_code)\n";
    exit(1);
}

$bills = json_decode($response, true) ?? [];
if (!is_array($bills)) {
    $bills = [];
}
$bill_count = count($bills);

echo "✅ Successfully retrieved $bill_count pending bills for Carlo Nicolas\n\n";

if ($bill_count === 0) {
    echo "❌ No bills found! Expected 17 bills.\n";
    exit(1);
}

// Group by bill type
$bills_by_type = [];
$total_amount = 0;

foreach ($bills as $bill) {
    $type = $bill['bill_type'];
    if (!isset($bills_by_type[$type])) {
        $bills_by_type[$type] = [];
    }
    $bills_by_type[$type][] = $bill;
    $total_amount += $bill['total_amount_due'];
}

// Display summary
echo "BILLS SUMMARY BY TYPE:\n";
echo "───────────────────────────────────────────────────────────────\n\n";

foreach ($bills_by_type as $type => $type_bills) {
    $type_total = array_reduce($type_bills, function($sum, $bill) {
        return $sum + $bill['total_amount_due'];
    }, 0);
    
    echo "✓ $type: " . count($type_bills) . " bills\n";
    
    foreach ($type_bills as $idx => $bill) {
        echo "    " . ($idx + 1) . ". " . $bill['bill_reference_no'] . "\n";
        echo "       Amount: ₱" . number_format($bill['total_amount_due'], 2) . "\n";
        echo "       Due: " . $bill['due_date'] . "\n";
    }
    echo "    Subtotal: ₱" . number_format($type_total, 2) . "\n\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ VERIFICATION COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\nFinal Summary:\n";
echo "  Citizen: Carlo Nicolas (QC-2024-000009)\n";
echo "  Email: jackbobert24@gmail.com\n";
echo "  Total Pending Bills: $bill_count\n";
echo "  Total Amount Due: ₱" . number_format($total_amount, 2) . "\n";
echo "\nBreakdown:\n";

foreach ($bills_by_type as $type => $type_bills) {
    $type_total = array_reduce($type_bills, function($sum, $bill) {
        return $sum + $bill['total_amount_due'];
    }, 0);
    echo "  - $type: " . count($type_bills) . " bills (₱" . number_format($type_total, 2) . ")\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
?>
