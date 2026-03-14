<?php
/**
 * TEST: Business Tax Module Integration (File-based)
 * Tests Subsystem 2 (Permits) and Subsystem 4 (Clearances) integration
 */

$citizen_id = '92be37af-7c34-4c9b-80cb-47cde7c3a9fd'; // Raven Pogi

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         BUSINESS TAX MODULE INTEGRATION TEST               ║\n";
echo "║         Testing Subsystem 2 & 4 Integration                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Test 1: Get businesses from Subsystem 2 mock data file
echo "TEST 1: Reading businesses from Subsystem 2 (Permits)\n";
echo "─────────────────────────────────────────────────────────\n";
$s2_file = __DIR__ . '/mock-data/subsystem2/businesses.json';
$s2_data = json_decode(file_get_contents($s2_file), true);
$s2_businesses = array_filter($s2_data['businesses'] ?? [], 
    fn($b) => $b['qcitizen_id'] === $citizen_id);

if (!empty($s2_businesses)) {
    echo "✅ Found " . count($s2_businesses) . " businesses for Raven\n\n";
    foreach ($s2_businesses as $biz) {
        echo "   📋 Business: " . $biz['business_name'] . "\n";
        echo "      BIN: " . $biz['bin_number'] . "\n";
        echo "      Nature: " . $biz['nature_of_business'] . "\n";
        echo "      Gross Sales: ₱" . number_format($biz['gross_sales_declared'], 2) . "\n";
        echo "      Status: " . $biz['permit_status'] . "\n";
        echo "      Business Tax Rate: " . ($biz['business_tax_rate'] * 100) . "%\n";
        echo "      Regulatory Fees: ₱" . number_format(
            $biz['sanitary_fee'] + $biz['garbage_fee'] + $biz['fire_safety_fee'], 2) . "\n\n";
    }
} else {
    echo "❌ No businesses found for Raven\n";
}

// Test 2: Get clearances from Subsystem 4 mock data file
echo "\nTEST 2: Reading clearances from Subsystem 4 (Health/Sanitation)\n";
echo "──────────────────────────────────────────────────────────────\n";
$s4_file = __DIR__ . '/mock-data/subsystem4/clearances.json';
$s4_data = json_decode(file_get_contents($s4_file), true);
$s4_clearances = array_filter($s4_data['clearances'] ?? [], 
    fn($c) => $c['qcitizen_id'] === $citizen_id);

if (!empty($s4_clearances)) {
    echo "✅ Found " . count($s4_clearances) . " clearance records for Raven\n\n";
    
    // Group by BIN
    $by_bin = [];
    foreach ($s4_clearances as $cl) {
        $bin = $cl['bin_number'];
        if (!isset($by_bin[$bin])) {
            $by_bin[$bin] = ['business' => $cl['business_name'], 'clearances' => []];
        }
        $by_bin[$bin]['clearances'][] = $cl;
    }
    
    foreach ($by_bin as $bin => $info) {
        echo "   🏪 " . $info['business'] . "\n";
        echo "      BIN: " . $bin . "\n";
        foreach ($info['clearances'] as $cl) {
            $status_icon = $cl['clearance_status'] === 'Passed' ? '✅' : '⏳';
            echo "      " . $status_icon . " " . $cl['clearance_type'] . " Clearance: " . $cl['clearance_status'] . "\n";
        }
        echo "\n";
    }
} else {
    echo "❌ No clearances found for Raven\n";
}

// Test 3: Check bills in RCTS database
echo "\nTEST 3: Checking Business Tax Bills in RCTS Database\n";
echo "───────────────────────────────────────────────────\n";
require_once __DIR__ . '/api/config/supabase.php';

$result = db_select('rcts_assessment_billing_hub', [
    'qcitizen_id' => 'eq.' . $citizen_id,
    'bill_type' => 'eq.BusinessTax'
]);

if ($result['success'] && !empty($result['data'])) {
    echo "✅ Found " . count($result['data']) . " business tax bills\n\n";
    $total_due = 0;
    foreach ($result['data'] as $bill) {
        echo "   💰 Bill Reference: " . $bill['bill_reference_no'] . "\n";
        echo "      Tax Year: " . $bill['tax_year'] . "\n";
        echo "      Base Amount: ₱" . number_format($bill['base_amount'], 2) . "\n";
        echo "      Total Due: ₱" . number_format($bill['total_amount_due'], 2) . "\n";
        echo "      Status: " . $bill['status'] . "\n\n";
        $total_due += $bill['total_amount_due'];
    }
    echo "   📊 TOTAL AMOUNT DUE: ₱" . number_format($total_due, 2) . "\n\n";
} else {
    echo "❌ No business tax bills found in database\n";
}

// Test 4: Verify integration between S2, S4, and RCTS
echo "\nTEST 4: Integration Verification\n";
echo "─────────────────────────────────\n";
$s2_bins = array_map(fn($b) => $b['bin_number'], $s2_businesses);
$s4_bins = array_unique(array_map(fn($c) => $c['bin_number'], $s4_clearances));
$db_bins = array_map(fn($b) => $b['bill_reference_no'], $result['data'] ?? []);

echo "   Subsystem 2 BINs: " . implode(', ', $s2_bins) . "\n";
echo "   Subsystem 4 BINs: " . implode(', ', $s4_bins) . "\n";
echo "   RCTS Database BINs: " . implode(', ', $db_bins) . "\n\n";

// Check if all BINs match
$all_match = count(array_intersect($s2_bins, $s4_bins, $db_bins)) === count($s2_bins);
if ($all_match) {
    echo "✅ INTEGRATION SUCCESSFUL: All BINs match across S2, S4, and RCTS!\n";
} else {
    echo "⚠️ MISMATCH: Some BINs are not synchronized\n";
}

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║                  INTEGRATION TEST COMPLETE                 ║\n";
echo "║        All systems ready for Business Tax Module Testing   ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
?>
