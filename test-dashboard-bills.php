<?php
require_once 'api/config/supabase.php';

$citizen_id = 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5';

echo "========================================\n";
echo "DASHBOARD BILL LOADING TEST\n";
echo "========================================\n\n";

// 1. Get pending bills from database
echo "1. FETCHING PENDING BILLS FROM DATABASE:\n";
$pending_bills_res = supabase_request(
    'v_citizen_pending_bills?qcitizen_id=eq.' . $citizen_id,
    'GET',
    []
);
$pending_bills = $pending_bills_res['data'] ?? [];
echo "   Found: " . count($pending_bills) . " pending bills\n";
foreach ($pending_bills as $bill) {
    echo "   - " . $bill['bill_reference_no'] . " (₱" . number_format($bill['total_amount_due'] ?? 0, 2) . ")\n";
}

// 2. Get all bills (debug status)
echo "\n2. FETCHING ALL BILLS (DEBUG):\n";
$all_bills_res = supabase_request(
    'rcts_assessment_billing_hub?qcitizen_id=eq.' . $citizen_id,
    'GET',
    []
);
$all_bills = $all_bills_res['data'] ?? [];
echo "   Total bills in DB: " . count($all_bills) . "\n";
$bill_refs = [];
foreach ($all_bills as $bill) {
    echo "   - " . $bill['bill_reference_no'] . " (Status: " . $bill['status'] . ", Amount: ₱" . number_format($bill['total_amount_due'] ?? 0, 2) . ")\n";
    $bill_refs[] = $bill['bill_reference_no'];
}

// 3. Get S7 properties
echo "\n3. FETCHING SUBSYSTEM 7 PROPERTIES:\n";
$s7_response = file_get_contents('http://localhost/rcts-qc/api/endpoints/subsystem7.php?action=get_property_by_citizen&qcitizen_id=' . $citizen_id);
$s7_data = json_decode($s7_response, true);
$s7_props = $s7_data['data']['properties'] ?? [];
echo "   Found: " . count($s7_props) . " S7 properties\n";
foreach ($s7_props as $prop) {
    echo "   - " . $prop['tdn_number'] . " (" . $prop['property_class'] . ", ₱" . number_format($prop['total_annual_tax'] ?? 0, 2) . ")\n";
}

// 4. Check which S7 properties are NOT in DB (filtered out)
echo "\n4. FILTERING LOGIC:\n";
$bill_refs_set = array_flip($bill_refs);
$s7_not_in_db = [];
foreach ($s7_props as $prop) {
    if (!isset($bill_refs_set[$prop['tdn_number']])) {
        $s7_not_in_db[] = $prop['tdn_number'];
    }
}
echo "   S7 properties NOT in DB: " . count($s7_not_in_db) . "\n";
if (count($s7_not_in_db) > 0) {
    foreach ($s7_not_in_db as $tdn) {
        echo "   - " . $tdn . " (will be added as pending bill)\n";
    }
} else {
    echo "   ✓ All S7 properties are already in DB as bills\n";
}

// 5. Final bill count
echo "\n5. FINAL DASHBOARD BILL COUNT:\n";
$total_bills = count($pending_bills) + count($s7_not_in_db);
echo "   Database pending bills: " . count($pending_bills) . "\n";
echo "   S7 properties (not in DB): " . count($s7_not_in_db) . "\n";
echo "   Total shown in dashboard: " . $total_bills . "\n";

echo "\n========================================\n";
echo "EXPECTED RESULT:\n";
echo "========================================\n";
echo "Dashboard should show " . $total_bills . " pending bills:\n";
foreach ($pending_bills as $bill) {
    echo "  ✓ " . $bill['bill_reference_no'] . "\n";
}
foreach ($s7_not_in_db as $tdn) {
    echo "  ✓ " . $tdn . " (from S7)\n";
}

if ($total_bills > 0) {
    echo "\n✅ Bills will appear in Dashboard Pending Bills section\n";
} else {
    echo "\n❌ No bills found - check database and S7 properties\n";
}
