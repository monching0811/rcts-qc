<?php
/**
 * Fix Brylle's Market Stall Rental Bills  
 * Update from incorrect ID (QC-2026-00156) to correct UUID (a135da1e-6727-430e-9771-e15688e6f79e)
 */

require_once 'api/config/supabase.php';

$wrong_id = 'QC-2026-00156';
$correct_id = 'a135da1e-6727-430e-9771-e15688e6f79e';

echo "═══════════════════════════════════════════════════════════════\n";
echo "Fixing Brylle's Market Rental Bills\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Incorrect QCitizen ID: $wrong_id\n";
echo "Correct QCitizen ID: $correct_id\n";
echo "Citizen Name: Brylle Kenneth Mendez\n\n";

// Find bills with wrong ID
echo "Finding bills to update...\n";
$find_result = supabase_request('rcts_assessment_billing_hub', 'GET', [
    'qcitizen_id' => 'eq.' . $wrong_id,
    'bill_type' => 'eq.MarketRental'
]);

if ($find_result['success'] && count($find_result['data']) > 0) {
    echo "✓ Found " . count($find_result['data']) . " market rental bills with wrong ID\n\n";
    
    foreach ($find_result['data'] as $bill) {
        echo "Updating: {$bill['bill_reference_no']}\n";
        
        $update_result = supabase_request(
            'rcts_assessment_billing_hub?bill_reference_no=eq.' . $bill['bill_reference_no'],
            'PATCH',
            [],
            ['qcitizen_id' => $correct_id]
        );
        
        if ($update_result['success']) {
            echo "  ✓ Updated successfully\n";
        } else {
            echo "  ✗ Update failed\n";
            if (isset($update_result['data'])) {
                echo "    Error: " . json_encode($update_result['data']) . "\n";
            }
        }
    }
} else {
    echo "✗ No bills found with incorrect ID\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Verifying update...\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Verify
$verify_result = supabase_request('rcts_assessment_billing_hub', 'GET', [
    'qcitizen_id' => 'eq.' . $correct_id,
    'bill_type' => 'eq.MarketRental'
]);

if ($verify_result['success']) {
    $count = count($verify_result['data']);
    echo "✓ Found $count market rental bills for correct Brylle ID\n\n";
    
    $total = 0;
    foreach ($verify_result['data'] as $bill) {
        echo "  • {$bill['bill_reference_no']}: " . $bill['asset_id'] . " = ₱" . number_format($bill['total_amount_due'], 2) . "\n";
        $total += $bill['total_amount_due'];
    }
    echo "\n  Total: ₱" . number_format($total, 2) . "\n";
} else {
    echo "Error verifying update\n";
}

?>
