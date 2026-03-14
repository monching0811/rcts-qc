<?php
require_once 'api/config/supabase.php';

$citizen_id = 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5';

echo "========================================\n";
echo "AMOUNT COMPARISON TEST\n";
echo "========================================\n\n";

// 1. Get Pending Bills from database
echo "1. PENDING RPT BILLS (from database):\n";
$pending_bills = supabase_request(
    'v_citizen_pending_bills?qcitizen_id=eq.' . $citizen_id,
    'GET',
    []
);

$bills = $pending_bills['data'] ?? [];
$bill_amounts = [];
foreach ($bills as $bill) {
    echo "  • " . $bill['bill_reference_no'] . ": ₱" . number_format($bill['total_amount_due'] ?? 0, 2) . "\n";
    $bill_amounts[$bill['bill_reference_no']] = $bill['total_amount_due'] ?? 0;
}

// 2. Get Subsystem 7 Properties
echo "\n2. YOUR REGISTERED PROPERTIES (from Subsystem 7):\n";
$s7_response = file_get_contents('http://localhost/rcts-qc/api/endpoints/subsystem7.php?action=get_property_by_citizen&qcitizen_id=' . $citizen_id);
$s7_data = json_decode($s7_response, true);

$prop_amounts = [];
foreach ($s7_data['data']['properties'] ?? [] as $p) {
    echo "  • " . $p['tdn_number'] . ": ₱" . number_format($p['total_annual_tax'] ?? 0, 2) . "\n";
    $prop_amounts[$p['tdn_number']] = $p['total_annual_tax'] ?? 0;
}

// 3. Compare
echo "\n3. COMPARISON:\n";
$match = true;
foreach ($bill_amounts as $tdn => $bill_amt) {
    $prop_amt = $prop_amounts[$tdn] ?? 0;
    $diff = abs($bill_amt - $prop_amt);
    
    if ($diff > 0) {
        echo "  ⚠️ " . $tdn . ":\n";
        echo "     Bill Amount: ₱" . number_format($bill_amt, 2) . "\n";
        echo "     S7 Amount:   ₱" . number_format($prop_amt, 2) . "\n";
        echo "     Difference: ₱" . number_format($diff, 2) . "\n";
        $match = false;
    } else {
        echo "  ✅ " . $tdn . ": ₱" . number_format($bill_amt, 2) . " (MATCH)\n";
    }
}

echo "\n========================================\n";
if ($match) {
    echo "✅ ALL AMOUNTS MATCH!\n";
    echo "Both sections should show same amounts.\n";
} else {
    echo "❌ AMOUNTS DO NOT MATCH\n";
    echo "Need to synchronize the values.\n";
}
echo "========================================\n";
