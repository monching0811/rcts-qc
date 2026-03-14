<?php
/**
 * CREATE MARKET RENTAL BILLS FOR DAVE
 * Generates market stall rental bills for Dave Mercado
 */

require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/api/config/constants.php';

$dave_id = 'eacd934b-0195-4640-b37c-aa0a8b40a9d2';

echo "═══════════════════════════════════════════════════════════════\n";
echo "Creating Market Rental Bills for Dave Mercado\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Get Dave's market stalls from S10 API
// For testing, directly read the JSON file
$stalls_file = __DIR__ . '/mock-data/subsystem10/stalls.json';
$stalls_data = json_decode(file_get_contents($stalls_file), true);
$all_stalls = $stalls_data['stalls'] ?? [];
$dave_stalls = array_filter($all_stalls, function($stall) use ($dave_id) {
    return $stall['qcitizen_id'] === $dave_id;
});

echo "Found " . count($dave_stalls) . " market stalls for Dave:\n\n";

$created_bills = [];

foreach ($dave_stalls as $stall) {
    $stall_id = $stall['stall_asset_id'];
    $stall_name = $stall['stall_name'];
    $monthly_rate = $stall['monthly_rental_rate'];

    // Create unique bill reference
    $bill_ref = 'RCTS-MKT-' . CURRENT_YEAR . '-' . strtoupper(substr(uniqid(), -6));

    // Insert bill
    $bill_data = [
        'bill_reference_no'   => $bill_ref,
        'qcitizen_id'         => $dave_id,
        'bill_type'           => 'MarketRental',
        'originating_dept_id' => 10, // S10 - Public Assets
        'asset_id'            => $stall_id,
        'tax_year'            => CURRENT_YEAR,
        'base_amount'         => $monthly_rate,
        'discount_amount'     => 0,
        'penalty_amount'      => 0,
        'total_amount_due'    => $monthly_rate,
        'status'              => 'Pending',
        'due_date'            => CURRENT_YEAR . '-03-15'
    ];

    $result = db_insert('rcts_assessment_billing_hub', $bill_data);

    if ($result) {
        $created_bills[] = $bill_ref;
        echo "✅ Bill Created: {$bill_ref}\n";
        echo "   Stall: {$stall_id}\n";
        echo "   Name: {$stall_name}\n";
        echo "   Monthly Rate: ₱" . number_format($monthly_rate, 2) . "\n";
        echo "   Due: " . CURRENT_YEAR . "-03-15\n\n";
    } else {
        echo "❌ Failed to create bill for stall: {$stall_id}\n\n";
    }
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "Summary:\n";
echo "  Created Bills: " . count($created_bills) . "\n";
if (!empty($created_bills)) {
    echo "  Bill References:\n";
    foreach ($created_bills as $ref) {
        echo "    - {$ref}\n";
    }
}
echo "═══════════════════════════════════════════════════════════════\n";