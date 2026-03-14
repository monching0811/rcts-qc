<?php
require_once __DIR__ . '/api/config/supabase.php';

$citizen_id = '92be37af-7c34-4c9b-80cb-47cde7c3a9fd'; // Raven Pogi

// Calculate tax for each business
$businesses = [
    [
        'bin_number' => 'BIN-QC-2024-RAVEN-001',
        'business_name' => 'Raven\'s Tech Hub',
        'gross_sales_declared' => 500000,
        'business_tax_rate' => 0.03,
        'sanitary_fee' => 500,
        'garbage_fee' => 300,
        'fire_safety_fee' => 200
    ],
    [
        'bin_number' => 'BIN-QC-2024-RAVEN-002',
        'business_name' => 'Raven\'s Cafe & Bakery',
        'gross_sales_declared' => 800000,
        'business_tax_rate' => 0.03,
        'sanitary_fee' => 1000,
        'garbage_fee' => 500,
        'fire_safety_fee' => 300
    ]
];

foreach ($businesses as $biz) {
    // Calculate base amount: (gross_sales * tax_rate) + regulatory fees
    $business_tax = $biz['gross_sales_declared'] * $biz['business_tax_rate'];
    $total_fees = $biz['sanitary_fee'] + $biz['garbage_fee'] + $biz['fire_safety_fee'];
    $base_amount = $business_tax + $total_fees;
    $total_amount_due = $base_amount; // No discount yet
    
    $bill_data = [
        'qcitizen_id' => $citizen_id,
        'bill_reference_no' => $biz['bin_number'],
        'bill_type' => 'BusinessTax',
        'asset_id' => $biz['bin_number'],
        'tax_year' => 2026,
        'base_amount' => $base_amount,
        'discount_amount' => 0,
        'penalty_amount' => 0,
        'total_amount_due' => $total_amount_due,
        'due_date' => '2026-03-31',
        'status' => 'Pending',
        'originating_dept_id' => 2
    ];
    
    $result = db_insert('rcts_assessment_billing_hub', $bill_data);
    
    if ($result['success']) {
        echo "✅ Created bill for {$biz['business_name']}\n";
        echo "   Bill Reference: {$biz['bin_number']}\n";
        echo "   Business Tax: ₱" . number_format($business_tax, 2) . "\n";
        echo "   Regulatory Fees: ₱" . number_format($total_fees, 2) . "\n";
        echo "   Total Due: ₱" . number_format($total_amount_due, 2) . "\n\n";
    } else {
        echo "❌ Failed to create bill for {$biz['business_name']}: " . $result['message'] . "\n\n";
    }
}

echo "✅ Business tax bill creation completed for Raven!\n";
?>
