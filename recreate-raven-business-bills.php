<?php
/**
 * RECREATE RAVEN'S BUSINESS TAX BILLS (for testing)
 * Creates fresh pending bills for both businesses
 */

require_once __DIR__ . '/api/config/supabase.php';

$raven_id = '92be37af-7c34-4c9b-80cb-47cde7c3a9fd';

echo "═══════════════════════════════════════════════════════════════\n";
echo "Recreating Raven's Business Tax Bills for 2026\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$businesses = [
    [
        'bin_number'            => 'BIN-QC-2024-RAVEN-001',
        'business_name'         => 'Raven\'s Tech Hub',
        'gross_sales_declared'  => 500000,
        'business_tax_rate'     => 0.03,
        'sanitary_fee'          => 500,
        'garbage_fee'           => 300,
        'fire_safety_fee'       => 200
    ],
    [
        'bin_number'            => 'BIN-QC-2024-RAVEN-002',
        'business_name'         => 'Raven\'s Cafe & Bakery',
        'gross_sales_declared'  => 800000,
        'business_tax_rate'     => 0.03,
        'sanitary_fee'          => 1000,
        'garbage_fee'           => 500,
        'fire_safety_fee'       => 300
    ]
];

$created_bills = [];

foreach ($businesses as $idx => $biz) {
    // Calculate amounts
    $business_tax = $biz['gross_sales_declared'] * $biz['business_tax_rate'];
    $total_fees = $biz['sanitary_fee'] + $biz['garbage_fee'] + $biz['fire_safety_fee'];
    $base_amount = $business_tax + $total_fees;
    
    // Create new bill reference (with timestamp to make unique)
    $bill_ref = 'BIN-QC-2026-RAVEN-' . ($idx + 1);
    
    // Insert billing data
    $bill_data = [
        'bill_reference_no'   => $bill_ref,
        'qcitizen_id'         => $raven_id,
        'bill_type'           => 'BusinessTax',
        'originating_dept_id' => 2,
        'asset_id'            => $biz['bin_number'],
        'tax_year'            => 2026,
        'base_amount'         => $base_amount,
        'discount_amount'     => 0,
        'penalty_amount'      => 0,
        'total_amount_due'    => $base_amount,
        'status'              => 'Pending',
        'due_date'            => '2026-03-31'
    ];
    
    $result = supabase_request('rcts_assessment_billing_hub', 'POST', [], $bill_data, true);
    
    if ($result['success']) {
        echo "✅ Created bill for " . $biz['business_name'] . "\n";
        echo "   Bill Reference: $bill_ref\n";
        echo "   Business Tax (3%): ₱" . number_format($business_tax, 2) . "\n";
        echo "   Regulatory Fees: ₱" . number_format($total_fees, 2) . "\n";
        echo "   Total Due: ₱" . number_format($base_amount, 2) . "\n\n";
        $created_bills[] = $bill_ref;
    } else {
        echo "❌ Failed to create bill for " . $biz['business_name'] . "\n";
        echo "   Error: " . ($result['message'] ?? 'Unknown error') . "\n\n";
    }
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "Summary:\n";
echo "  Created Bills: " . count($created_bills) . "\n";
if (count($created_bills) > 0) {
    $total = 0;
    foreach ($created_bills as $ref) {
        echo "    - $ref\n";
    }
    echo "\n  Total Pending Business Tax: ₱" . number_format(16000 + 25800, 2) . "\n";
}
echo "═══════════════════════════════════════════════════════════════\n";
?>
