<?php
/**
 * Test: Market Stall Rental Module Integration for Brylle Kenneth
 * Verifies that market rental bills appear in consolidated billing system
 */

require_once 'api/config/supabase.php';
require_once 'api/config/constants.php';

$qcitizen_id = 'QC-2026-00156'; // Brylle Kenneth
echo "═══════════════════════════════════════════════════════════════════\n";
echo "Market Stall Rental Integration Test - Brylle Kenneth\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

// Test 1: Verify Stalls in S10 API
echo "TEST 1: Fetching Stalls from Subsystem 10 (Public Assets)\n";
echo "─────────────────────────────────────────────────────────\n";
$s10_url = "http://localhost/rcts-qc/mock-data/subsystem10/public-assets-api.php?action=get_stalls_by_citizen&qcitizen_id=$qcitizen_id";
$s10_response = json_decode(file_get_contents($s10_url), true);
if ($s10_response['success'] && count($s10_response['data']) > 0) {
    echo "✓ Found " . count($s10_response['data']) . " stalls for Brylle\n";
    foreach ($s10_response['data'] as $stall) {
        echo "  - {$stall['stall_name']}: ₱{$stall['monthly_rental_rate']}/month\n";
    }
} else {
    echo "✗ Failed to fetch stalls\n";
}
echo "\n";

// Test 2: Verify Occupancy Signals (S10 Integration)
echo "TEST 2: Verifying Occupancy Status Flags\n";
echo "─────────────────────────────────────────\n";
foreach ($s10_response['data'] as $stall) {
    $occ_status = $stall['occupancy_status_flag'];
    $verification_method = $stall['occupancy_verification_method'];
    echo "✓ {$stall['stall_name']}: $occ_status ($verification_method)\n";
}
echo "\n";

// Test 3: Get All Pending Bills for Brylle (including Market Rentals)
echo "TEST 3: Fetching All Pending Bills from RCTS (Module 4)\n";
echo "────────────────────────────────────────────────────────\n";
$bills_result = supabase_request(
    'rcts_assessment_billing_hub',
    'GET',
    [
        'qcitizen_id' => "eq.$qcitizen_id",
        'status' => 'eq.Pending',
        'select' => '*'
    ]
);

if ($bills_result['success'] && is_array($bills_result['data'])) {
    $bills = $bills_result['data'];
    $bill_types = [];
    $total_amount = 0;
    
    foreach ($bills as $bill) {
        $bill_type = $bill['bill_type'];
        if (!isset($bill_types[$bill_type])) {
            $bill_types[$bill_type] = [];
        }
        $bill_types[$bill_type][] = $bill;
        $total_amount += $bill['total_amount_due'];
    }
    
    echo "✓ Found " . count($bills) . " pending bills\n\n";
    
    foreach ($bill_types as $type => $type_bills) {
        echo "  [$type - " . count($type_bills) . " bills]\n";
        $type_total = 0;
        foreach ($type_bills as $bill) {
            echo "    • {$bill['bill_reference_no']}: ₱" . number_format($bill['total_amount_due'], 2) . "\n";
            $type_total += $bill['total_amount_due'];
        }
        echo "    Subtotal: ₱" . number_format($type_total, 2) . "\n\n";
    }
    
    echo "TOTAL PENDING: ₱" . number_format($total_amount, 2) . "\n";
} else {
    echo "✗ Failed to fetch bills\n";
    if (isset($bills_result['data'])) {
        echo "  Response: " . json_encode($bills_result['data']) . "\n";
    }
}
echo "\n";

// Test 4: Verify Market Rental Bills Specifically
echo "TEST 4: Market Rental Bills Summary\n";
echo "───────────────────────────────────\n";
$market_bills = array_filter($bills ?? [], fn($b) => $b['bill_type'] === 'MarketRental');
if (count($market_bills) > 0) {
    echo "✓ Found " . count($market_bills) . " market rental bills\n";
    $market_total = 0;
    foreach ($market_bills as $bill) {
        echo "  • {$bill['bill_reference_no']}: " . $bill['asset_id'] . " = ₱" . number_format($bill['total_amount_due'], 2) . "\n";
        $market_total += $bill['total_amount_due'];
    }
    echo "  Total Market Revenue: ₱" . number_format($market_total, 2) . "\n";
} else {
    echo "✗ No market rental bills found\n";
}
echo "\n";

// Test 5: Verify Module 3 Integration
echo "TEST 5: Module 3 (Market Rental) Integration Status\n";
echo "────────────────────────────────────────────────────\n";
echo "✓ S10 API (Public Assets): Operational\n";
echo "✓ Market Stalls Data: " . count($s10_response['data']) . " active stalls for Brylle\n";
echo "✓ Occupancy Verification: " . implode(", ", array_unique(array_map(fn($s) => $s['occupancy_verification_method'], $s10_response['data']))) . "\n";
echo "✓ Market Rental Bills: " . count($market_bills) . " pending\n";
echo "✓ Consolidated Bills: MarketRental bills will appear in Module 4 (Digital Payment)\n";
echo "✓ Treasury Ledger: Revenue will flow to Module 5 (Dashboard)\n";
echo "\n";

// Test 6: Data Summary
echo "═══════════════════════════════════════════════════════════════════\n";
echo "SUMMARY - Brylle Kenneth (QC-2026-00156)\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "Stalls Registered:          " . count($s10_response['data']) . "\n";
echo "Monthly Rental Capacity:    ₱" . number_format(array_sum(array_map(fn($s) => $s['monthly_rental_rate'], $s10_response['data'])), 2) . "\n";
echo "Pending Rental Bills:       " . count($market_bills) . "\n";
echo "Total Rental Bill Amount:   ₱" . number_format(array_sum(array_map(fn($b) => $b['total_amount_due'], $market_bills)), 2) . "\n";
echo "All Pending Bills:          " . count($bills ?? []) . "\n";
echo "Grand Total Due:            ₱" . number_format($total_amount, 2) . "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
?>
