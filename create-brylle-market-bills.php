<?php
/**
 * Script to generate Market Stall Rental Bills for Brylle Kenneth (QC-2026-00156)
 * Module 3: Market Stall Rental & Billing
 */

require_once 'api/config/supabase.php';

$qcitizen_id = 'QC-2026-00156'; // Brylle Kenneth
$stalls = [
    [
        'stall_asset_id' => 'STL-QC-2026-BRYLLE-001',
        'stall_name' => "Brylle's Fresh Vegetable Stand",
        'monthly_rental_rate' => 2500,
        'facility_name' => 'Lungsod Public Market'
    ],
    [
        'stall_asset_id' => 'STL-QC-2026-BRYLLE-002',
        'stall_name' => "Brylle's Dry Goods & Spice Shop",
        'monthly_rental_rate' => 2800,
        'facility_name' => 'Lungsod Public Market'
    ]
];

echo "=== Creating Market Stall Rental Bills for Brylle Kenneth ===\n";
echo "Citizen ID: $qcitizen_id\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$due_date = '2026-04-11';

foreach ($stalls as $index => $stall) {
    $bill_ref = 'MKT-QC-2026-BRYLLE-' . ($index + 1);
    $amount = $stall['monthly_rental_rate'];
    
    // Prepare bill data - explicitly calculate all financial fields
    $discount_rate = 0;  // No discount for market rentals
    $penalty_rate = 0;   // No penalty (new/current bills)
    $discount_amount = $amount * $discount_rate;
    $penalty_amount = $amount * $penalty_rate;
    $total_due = $amount - $discount_amount + $penalty_amount;
    
    $bill_data = [
        'bill_reference_no'   => $bill_ref,
        'qcitizen_id'         => $qcitizen_id,
        'bill_type'           => 'MarketRental',
        'originating_dept_id' => 8,  // RCTS is dept 8
        'asset_id'            => $stall['stall_asset_id'],
        'tax_year'            => 2026,
        'base_amount'         => $amount,
        'discount_rate'       => $discount_rate,
        'discount_amount'     => $discount_amount,
        'penalty_rate'        => $penalty_rate,
        'penalty_amount'      => $penalty_amount,
        'total_amount_due'    => $total_due,
        'status'              => 'Pending',
        'due_date'            => $due_date
    ];
    
    // Use supabase_request function
    $result = supabase_request('rcts_assessment_billing_hub', 'POST', [], $bill_data, false);
    
    if ($result['success']) {
        echo "✓ Bill Created: $bill_ref\n";
        echo "  Stall: {$stall['stall_name']}\n";
        echo "  Amount: ₱" . number_format($amount, 2) . "\n";
        echo "  Status: Pending\n";
        echo "  Due Date: $due_date\n\n";
    } else {
        echo "✗ Error creating bill $bill_ref\n";
        echo "  Status Code: " . $result['http_code'] . "\n";
        if (is_array($result['data'])) {
            echo "  Error: " . json_encode($result['data']) . "\n";
        }
        echo "\n";
    }
}

$total_bills = 2;
$total_amount = 2500 + 2800;

echo "=== Summary ===\n";
echo "Bills Created: $total_bills\n";
echo "Total Amount Due: ₱" . number_format($total_amount, 2) . "\n";
echo "Status: All bills are PENDING for payment\n";
echo "\nThese bills should now appear in Brylle's consolidated bills view.\n";
?>
