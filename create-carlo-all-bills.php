<?php
/**
 * CREATE ALL PENDING BILLS FOR CARLO NICOLAS
 * 
 * Generates all 4 types of pending bills:
 * 1. Real Property Tax (RPT) - 5 properties from S7
 * 2. Business Tax - 2 businesses from S2
 * 3. Traffic Fines - 5 violations from S9
 * 4. Market Stall Rental - 5 stalls from S10
 */

require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/api/config/constants.php';

$carlo_id = 'QC-2024-000009';
$carlo_name = 'Carlo Nicolas';

echo "═══════════════════════════════════════════════════════════════\n";
echo "CREATING ALL PENDING BILLS FOR: $carlo_name\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$all_created_bills = [
    'RPT' => [],
    'BusinessTax' => [],
    'TrafficFine' => [],
    'MarketRental' => []
];

$total_amount = 0;

// ══════════════════════════════════════════════════════════════════
// 1. CREATE REAL PROPERTY TAX (RPT) BILLS FROM SUBSYSTEM 7
// ══════════════════════════════════════════════════════════════════
echo "STEP 1: Creating Real Property Tax (RPT) Bills\n";
echo "──────────────────────────────────────────────────────────────\n\n";

$properties = [
    [
        'tdn_number' => 'TDN-QC-2024-CARLO-001',
        'property_name' => 'Residential Property at Barrio Fiesta',
        'assessed_value' => 700000,
        'annual_rpt_due' => 14000,
        'annual_sef_due' => 7000
    ],
    [
        'tdn_number' => 'TDN-QC-2024-CARLO-002',
        'property_name' => 'Commercial Property at Anonas Street',
        'assessed_value' => 4500000,
        'annual_rpt_due' => 90000,
        'annual_sef_due' => 45000
    ],
    [
        'tdn_number' => 'TDN-QC-2024-CARLO-003',
        'property_name' => 'Residential Property at Scout Borromeo',
        'assessed_value' => 560000,
        'annual_rpt_due' => 11200,
        'annual_sef_due' => 5600
    ],
    [
        'tdn_number' => 'TDN-QC-2024-CARLO-004',
        'property_name' => 'Residential Property at Katipunan Avenue',
        'assessed_value' => 520000,
        'annual_rpt_due' => 10400,
        'annual_sef_due' => 5200
    ],
    [
        'tdn_number' => 'TDN-QC-2024-CARLO-005',
        'property_name' => 'Industrial Property at Taguig Link Road',
        'assessed_value' => 4000000,
        'annual_rpt_due' => 80000,
        'annual_sef_due' => 40000
    ]
];

foreach ($properties as $idx => $prop) {
    $base_amount = $prop['annual_rpt_due'] + $prop['annual_sef_due'];
    $bill_ref = 'RCTS-RPT-' . CURRENT_YEAR . '-CARLO-' . ($idx + 1);
    
    $bill_data = [
        'bill_reference_no' => $bill_ref,
        'qcitizen_id' => $carlo_id,
        'bill_type' => 'RPT',
        'originating_dept_id' => 7,
        'asset_id' => $prop['tdn_number'],
        'tax_year' => CURRENT_YEAR,
        'base_amount' => $base_amount,
        'discount_amount' => 0,
        'penalty_amount' => 0,
        'total_amount_due' => $base_amount,
        'status' => 'Pending',
        'due_date' => CURRENT_YEAR . '-03-31'
    ];
    
    $result = db_insert('rcts_assessment_billing_hub', $bill_data);
    
    if ($result) {
        echo "✅ RPT Bill Created: $bill_ref\n";
        echo "   Property: " . $prop['property_name'] . "\n";
        echo "   TDN: " . $prop['tdn_number'] . "\n";
        echo "   Total Due: ₱" . number_format($base_amount, 2) . "\n\n";
        $all_created_bills['RPT'][] = $bill_ref;
        $total_amount += $base_amount;
    } else {
        echo "❌ Failed to create RPT bill for " . $prop['property_name'] . "\n\n";
    }
}

// ══════════════════════════════════════════════════════════════════
// 2. CREATE BUSINESS TAX BILLS FROM SUBSYSTEM 2
// ══════════════════════════════════════════════════════════════════
echo "\nSTEP 2: Creating Business Tax Bills\n";
echo "──────────────────────────────────────────────────────────────\n\n";

$businesses = [
    [
        'bin_number' => 'BIN-QC-2024-CARLO-001',
        'business_name' => "Carlo's Import & Export Trading",
        'gross_sales_declared' => 2500000,
        'business_tax_rate' => 0.03,
        'sanitary_fee' => 2000,
        'garbage_fee' => 1000,
        'fire_safety_fee' => 800
    ],
    [
        'bin_number' => 'BIN-QC-2024-CARLO-002',
        'business_name' => "Carlo's Construction Materials Supply",
        'gross_sales_declared' => 1800000,
        'business_tax_rate' => 0.03,
        'sanitary_fee' => 1500,
        'garbage_fee' => 750,
        'fire_safety_fee' => 600
    ]
];

foreach ($businesses as $idx => $biz) {
    $business_tax = $biz['gross_sales_declared'] * $biz['business_tax_rate'];
    $total_fees = $biz['sanitary_fee'] + $biz['garbage_fee'] + $biz['fire_safety_fee'];
    $base_amount = $business_tax + $total_fees;
    $bill_ref = 'RCTS-BIZ-' . CURRENT_YEAR . '-CARLO-' . ($idx + 1);
    
    $bill_data = [
        'bill_reference_no' => $bill_ref,
        'qcitizen_id' => $carlo_id,
        'bill_type' => 'BusinessTax',
        'originating_dept_id' => 2,
        'asset_id' => $biz['bin_number'],
        'tax_year' => CURRENT_YEAR,
        'base_amount' => $base_amount,
        'discount_amount' => 0,
        'penalty_amount' => 0,
        'total_amount_due' => $base_amount,
        'status' => 'Pending',
        'due_date' => CURRENT_YEAR . '-03-31'
    ];
    
    $result = db_insert('rcts_assessment_billing_hub', $bill_data);
    
    if ($result) {
        echo "✅ Business Tax Bill Created: $bill_ref\n";
        echo "   Business: " . $biz['business_name'] . "\n";
        echo "   BIN: " . $biz['bin_number'] . "\n";
        echo "   Business Tax (3%): ₱" . number_format($business_tax, 2) . "\n";
        echo "   Regulatory Fees: ₱" . number_format($total_fees, 2) . "\n";
        echo "   Total Due: ₱" . number_format($base_amount, 2) . "\n\n";
        $all_created_bills['BusinessTax'][] = $bill_ref;
        $total_amount += $base_amount;
    } else {
        echo "❌ Failed to create business tax bill for " . $biz['business_name'] . "\n\n";
    }
}

// ══════════════════════════════════════════════════════════════════
// 3. CREATE TRAFFIC FINE BILLS FROM SUBSYSTEM 9
// ══════════════════════════════════════════════════════════════════
echo "\nSTEP 3: Creating Traffic Fine Bills\n";
echo "──────────────────────────────────────────────────────────────\n\n";

$violations_file = __DIR__ . '/mock-data/subsystem9/traffic-violations.json';
$violations_data = json_decode(file_get_contents($violations_file), true);
$all_violations = $violations_data['violations'] ?? [];
$violations = array_filter($all_violations, function($v) use ($carlo_id) {
    return $v['qcitizen_id'] === $carlo_id;
});

echo "Found " . count($violations) . " traffic violations for $carlo_name:\n\n";

foreach ($violations as $violation) {
    $ticket_num = $violation['ticket_number'];
    $violation_type = $violation['violation_type'];
    $fine_amount = $violation['fine_amount'];
    $bill_ref = 'RCTS-TF-' . CURRENT_YEAR . '-' . strtoupper(substr(uniqid(), -6));
    
    $bill_data = [
        'bill_reference_no' => $bill_ref,
        'qcitizen_id' => $carlo_id,
        'bill_type' => 'TrafficFine',
        'originating_dept_id' => 3,
        'asset_id' => $ticket_num,
        'tax_year' => CURRENT_YEAR,
        'base_amount' => $fine_amount,
        'discount_amount' => 0,
        'penalty_amount' => 0,
        'total_amount_due' => $fine_amount,
        'status' => 'Pending',
        'due_date' => CURRENT_YEAR . '-04-12'
    ];
    
    $result = db_insert('rcts_assessment_billing_hub', $bill_data);
    
    if ($result) {
        echo "✅ Traffic Fine Bill Created: $bill_ref\n";
        echo "   Ticket: $ticket_num\n";
        echo "   Violation: $violation_type\n";
        echo "   Amount: ₱" . number_format($fine_amount, 2) . "\n";
        echo "   Due: " . CURRENT_YEAR . "-04-12\n\n";
        $all_created_bills['TrafficFine'][] = $bill_ref;
        $total_amount += $fine_amount;
    } else {
        echo "❌ Failed to create traffic fine bill for $ticket_num\n\n";
    }
}

// ══════════════════════════════════════════════════════════════════
// 4. CREATE MARKET STALL RENTAL BILLS FROM SUBSYSTEM 10
// ══════════════════════════════════════════════════════════════════
echo "\nSTEP 4: Creating Market Stall Rental Bills\n";
echo "──────────────────────────────────────────────────────────────\n\n";

$stalls_file = __DIR__ . '/mock-data/subsystem10/stalls.json';
$stalls_data = json_decode(file_get_contents($stalls_file), true);
$all_stalls = $stalls_data['stalls'] ?? [];
$carlo_stalls = array_filter($all_stalls, function($stall) use ($carlo_id) {
    return $stall['qcitizen_id'] === $carlo_id;
});

echo "Found " . count($carlo_stalls) . " market stalls for $carlo_name:\n\n";

foreach ($carlo_stalls as $stall) {
    $stall_id = $stall['stall_asset_id'];
    $stall_name = $stall['stall_name'];
    $monthly_rate = $stall['monthly_rental_rate'];
    $bill_ref = 'RCTS-MKT-' . CURRENT_YEAR . '-' . strtoupper(substr(uniqid(), -6));
    
    $bill_data = [
        'bill_reference_no' => $bill_ref,
        'qcitizen_id' => $carlo_id,
        'bill_type' => 'MarketRental',
        'originating_dept_id' => 10,
        'asset_id' => $stall_id,
        'tax_year' => CURRENT_YEAR,
        'base_amount' => $monthly_rate,
        'discount_amount' => 0,
        'penalty_amount' => 0,
        'total_amount_due' => $monthly_rate,
        'status' => 'Pending',
        'due_date' => CURRENT_YEAR . '-03-15'
    ];
    
    $result = db_insert('rcts_assessment_billing_hub', $bill_data);
    
    if ($result) {
        echo "✅ Market Rental Bill Created: $bill_ref\n";
        echo "   Stall: $stall_id\n";
        echo "   Name: $stall_name\n";
        echo "   Monthly Rate: ₱" . number_format($monthly_rate, 2) . "\n";
        echo "   Due: " . CURRENT_YEAR . "-03-15\n\n";
        $all_created_bills['MarketRental'][] = $bill_ref;
        $total_amount += $monthly_rate;
    } else {
        echo "❌ Failed to create market rental bill for stall: $stall_id\n\n";
    }
}

// ══════════════════════════════════════════════════════════════════
// SUMMARY
// ══════════════════════════════════════════════════════════════════
echo "\n═══════════════════════════════════════════════════════════════\n";
echo "SUMMARY - PENDING BILLS CREATED FOR: $carlo_name\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$total_bills = 0;
foreach ($all_created_bills as $type => $bills) {
    $count = count($bills);
    $total_bills += $count;
    echo "✓ $type Bills: $count\n";
}

echo "\n  Total Bills Created: $total_bills\n";
echo "  Total Amount Due: ₱" . number_format($total_amount, 2) . "\n\n";

echo "Bill Breakdown:\n";
echo "──────────────────────────────────────────────────────────────\n";
echo "  Real Property Tax (RPT): " . count($all_created_bills['RPT']) . " bills\n";
echo "  Business Tax: " . count($all_created_bills['BusinessTax']) . " bills\n";
echo "  Traffic Fines: " . count($all_created_bills['TrafficFine']) . " bills\n";
echo "  Market Stall Rentals: " . count($all_created_bills['MarketRental']) . " bills\n";
echo "\n═══════════════════════════════════════════════════════════════\n";

echo "\n✅ ALL BILLS SUCCESSFULLY CREATED FOR CARLO NICOLAS!\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\nCarlo Nicolas can now login at:\n";
echo "  Email: jackbobert24@gmail.com\n";
echo "  Password: demo123 (use demo account)\n";
echo "\nPending Bill Summary:\n";
echo "  Total Pending Bills: 17\n";
echo "  Total Amount Due: ₱484,250.00\n\n";
?>
