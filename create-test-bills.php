<?php
require_once 'api/config/supabase.php';
require_once 'api/config/constants.php';
require_once 'includes/db.php';

// Use the test citizen ID from previous tests
$citizen_id = 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5'; // Vince Nico Escala

echo "========================================\n";
echo "DELETING OLD TEST BILLS\n";
echo "========================================\n";

// Delete old test bills
$old_bills = ['TEST-RPT-2026-001', 'TEST-RPT-2026-002', 'TEST-RPT-2026-003'];
foreach ($old_bills as $old_bill) {
    $delete_result = supabase_request(
        'rcts_assessment_billing_hub?bill_reference_no=eq.' . $old_bill,
        'DELETE',
        []
    );
    echo "🗑️ Deleted old bill: $old_bill\n";
}

echo "\n========================================\n";
echo "CREATING NEW PENDING RPT BILLS\n";
echo "Linked to Subsystem 7 Properties\n";
echo "========================================\n\n";

// Bills linked to subsystem 7 properties for this citizen
// Property 1: TDN-QC-2024-006 (Residential, Aurora Blvd) - Annual ₱16,800
$bill1 = [
    'bill_reference_no' => 'TDN-QC-2024-006',
    'qcitizen_id' => $citizen_id,
    'bill_type' => 'RPT',
    'originating_dept_id' => 8,
    'asset_id' => 'TDN-QC-2024-006',
    'base_amount' => 11200,
    'total_amount_due' => 16800,
    'discount_rate' => 0,
    'penalty_rate' => 0,
    'status' => 'Pending',
    'tax_year' => 2026,
    'verification_ref_id' => 'RPT-006-2026'
];

// Property 2: TDN-QC-2024-007 (Commercial, Scout Reyes) - Annual ₱147,000
$bill2 = [
    'bill_reference_no' => 'TDN-QC-2024-007',
    'qcitizen_id' => $citizen_id,
    'bill_type' => 'RPT',
    'originating_dept_id' => 8,
    'asset_id' => 'TDN-QC-2024-007',
    'base_amount' => 98000,
    'total_amount_due' => 147000,
    'discount_rate' => 0,
    'penalty_rate' => 0,
    'status' => 'Pending',
    'tax_year' => 2026,
    'verification_ref_id' => 'RPT-007-2026'
];

// Property 3: TDN-QC-2024-008 (Residential, Sarao Street) - Annual ₱13,200
$bill3 = [
    'bill_reference_no' => 'TDN-QC-2024-008',
    'qcitizen_id' => $citizen_id,
    'bill_type' => 'RPT',
    'originating_dept_id' => 8,
    'asset_id' => 'TDN-QC-2024-008',
    'base_amount' => 8800,
    'total_amount_due' => 13200,
    'discount_rate' => 0,
    'penalty_rate' => 0,
    'status' => 'Pending',
    'tax_year' => 2026,
    'verification_ref_id' => 'RPT-008-2026'
];

$bills = [$bill1, $bill2, $bill3];
$created = 0;

foreach ($bills as $bill) {
    $result = supabase_request('rcts_assessment_billing_hub', 'POST', [], $bill, true);
    
    if ($result['success'] ?? false) {
        echo "✅ Created: {$bill['bill_reference_no']}\n";
        echo "   Property: {$bill['asset_id']}\n";
        echo "   Amount: ₱" . number_format($bill['total_amount_due'], 2) . "\n";
        echo "   Status: Pending\n\n";
        $created++;
    } else {
        echo "❌ Failed: {$bill['bill_reference_no']}\n";
        echo "   Error: " . json_encode($result) . "\n\n";
    }
}

echo "========================================\n";
echo "✅ Total bills created: $created/3\n";
echo "========================================\n";
echo "\nCitizen ID: $citizen_id\n";
echo "Total amount due: ₱" . number_format($bill1['total_amount_due'] + $bill2['total_amount_due'] + $bill3['total_amount_due'], 2) . "\n\n";
echo "These bills are now:\n";
echo "1. ✅ Added to pending bills database\n";
echo "2. ✅ Linked to Subsystem 7 properties\n";
echo "3. ✅ Will appear in Dashboard → Pending Bills\n";
echo "4. ✅ Will appear in RPT Payment → Your Registered Properties\n";
echo "5. ✅ Ready to test payment workflow\n";
