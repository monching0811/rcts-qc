<?php
/**
 * CREATE ALL BILLS FOR CARLO NICOLAS
 * Generates RPT, Business Tax, Market Stall, and Traffic Fine bills
 * Also adds Traffic Violations to database
 * Citizen: Carlo Nicolas (QC-2024-000009)
 * 
 * USAGE:
 * - Run in terminal: php create-carlo-all-bills.php
 * - Run in browser: http://localhost/rcts-qc/create-carlo-all-bills.php
 */

header('Content-Type: text/plain');

require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/api/config/constants.php';

$carlo_id = 'QC-2024-000009';

echo "═══════════════════════════════════════════════════════════════\n";
echo "Creating Bills for Carlo Nicolas (QC-2024-000009)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ============================================
// PART 1: CREATE RPT BILLS FROM PROPERTIES
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "PART 1: REAL PROPERTY TAX (RPT) BILLS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$properties_file = __DIR__ . '/mock-data/subsystem7/properties.json';
$properties_data = json_decode(file_get_contents($properties_file), true);
$all_properties = $properties_data['properties'] ?? [];
$carlo_properties = array_filter($all_properties, function($p) use ($carlo_id) {
    return $p['qcitizen_id'] === $carlo_id;
});

echo "Found " . count($carlo_properties) . " properties for Carlo:\n\n";

$rpt_bills = [];

foreach ($carlo_properties as $property) {
    $tdn = $property['tdn_number'];
    $address = $property['property_address'];
    $annual_rpt_due = $property['annual_rpt_due'];  // Basic RPT (2%)
    $annual_sef_due = $property['annual_sef_due'];  // SEF (1%)
    $total_tax = $property['total_annual_tax'];  // Basic + SEF
    
    // Apply Early Bird Discount (20%) - matches dashboard computation
    $discount_rate = 0.20;  // 20% early bird discount
    $discount_amount = $total_tax * $discount_rate;
    $total_after_discount = $total_tax - $discount_amount;
    
    // Create unique bill reference
    $bill_ref = 'RCTS-RPT-' . CURRENT_YEAR . '-' . strtoupper(substr(uniqid(), -6));
    
    // Insert bill - use correct column names per schema
    // total_amount_due should match the computation (with early bird discount applied)
    $bill_data = [
        'bill_reference_no'   => $bill_ref,
        'qcitizen_id'         => $carlo_id,
        'bill_type'           => 'RPT',
        'originating_dept_id' => 7, // S7 - Urban Planning
        'asset_id'            => $tdn,
        'tax_year'            => CURRENT_YEAR,
        'base_amount'         => $total_tax,  // Basic + SEF before discount
        'discount_rate'       => $discount_rate,
        'penalty_rate'        => 0,
        'total_amount_due'    => $total_after_discount,  // After early bird discount
        'status'              => 'Pending',
        'due_date'            => CURRENT_YEAR . '-03-31'
    ];
    
    $result = db_insert('rcts_assessment_billing_hub', $bill_data);
    
    if ($result['success']) {
        $rpt_bills[] = $bill_ref;
        echo "✅ RPT Bill Created: {$bill_ref}\n";
        echo "   TDN: {$tdn}\n";
        echo "   Address: {$address}\n";
        echo "   Basic RPT: ₱" . number_format($annual_rpt_due, 2) . "\n";
        echo "   SEF: ₱" . number_format($annual_sef_due, 2) . "\n";
        echo "   Subtotal: ₱" . number_format($total_tax, 2) . "\n";
        echo "   Early Bird (20%): -₱" . number_format($discount_amount, 2) . "\n";
        echo "   Total Due: ₱" . number_format($total_after_discount, 2) . "\n";
        echo "   Due: " . CURRENT_YEAR . "-03-31\n\n";
    } else {
        echo "❌ Failed to create RPT bill for {$tdn}\n";
        echo "   HTTP Code: " . ($result['http_code'] ?? 'N/A') . "\n";
        echo "   Response: " . json_encode($result) . "\n\n";
    }
}

// ============================================
// PART 2: CREATE BUSINESS TAX BILLS
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "PART 2: BUSINESS TAX BILLS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$businesses_file = __DIR__ . '/mock-data/subsystem2/businesses.json';
$businesses_data = json_decode(file_get_contents($businesses_file), true);
$all_businesses = $businesses_data['businesses'] ?? [];
$carlo_businesses = array_filter($all_businesses, function($b) use ($carlo_id) {
    return $b['qcitizen_id'] === $carlo_id;
});

echo "Found " . count($carlo_businesses) . " businesses for Carlo:\n\n";

$biz_bills = [];

foreach ($carlo_businesses as $biz) {
    $bin = $biz['bin_number'];
    $biz_name = $biz['business_name'];
    
    // Calculate amounts
    $business_tax = $biz['gross_sales_declared'] * $biz['business_tax_rate'];
    $total_fees = $biz['sanitary_fee'] + $biz['garbage_fee'] + $biz['fire_safety_fee'];
    $base_amount = $business_tax + $total_fees;
    
    // Create unique bill reference
    $bill_ref = 'RCTS-BIZ-' . CURRENT_YEAR . '-' . strtoupper(substr(uniqid(), -6));
    
    // Insert bill - use correct column names per schema
    $bill_data = [
        'bill_reference_no'   => $bill_ref,
        'qcitizen_id'         => $carlo_id,
        'bill_type'           => 'BusinessTax',
        'originating_dept_id' => 2, // S2 - Business
        'asset_id'            => $bin,
        'tax_year'            => CURRENT_YEAR,
        'base_amount'         => $base_amount,
        'discount_rate'       => 0,
        'penalty_rate'        => 0,
        'total_amount_due'    => $base_amount,
        'status'              => 'Pending',
        'due_date'            => CURRENT_YEAR . '-03-31'
    ];
    
    $result = db_insert('rcts_assessment_billing_hub', $bill_data);
    
    if ($result['success']) {
        $biz_bills[] = $bill_ref;
        echo "✅ Business Tax Bill Created: {$bill_ref}\n";
        echo "   BIN: {$bin}\n";
        echo "   Business: {$biz_name}\n";
        echo "   Business Tax: ₱" . number_format($business_tax, 2) . "\n";
        echo "   Regulatory Fees: ₱" . number_format($total_fees, 2) . "\n";
        echo "   Total Due: ₱" . number_format($base_amount, 2) . "\n";
        echo "   Due: " . CURRENT_YEAR . "-03-31\n\n";
    } else {
        echo "❌ Failed to create business tax bill for {$bin}\n";
        echo "   HTTP Code: " . ($result['http_code'] ?? 'N/A') . "\n";
        echo "   Response: " . json_encode($result) . "\n\n";
    }
}

// ============================================
// PART 3: CREATE MARKET STALL BILLS
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "PART 3: MARKET STALL RENTAL BILLS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$stalls_file = __DIR__ . '/mock-data/subsystem10/stalls.json';
$stalls_data = json_decode(file_get_contents($stalls_file), true);
$all_stalls = $stalls_data['stalls'] ?? [];
$carlo_stalls = array_filter($all_stalls, function($s) use ($carlo_id) {
    return $s['qcitizen_id'] === $carlo_id;
});

echo "Found " . count($carlo_stalls) . " market stalls for Carlo:\n\n";

$stall_bills = [];

foreach ($carlo_stalls as $stall) {
    $stall_id = $stall['stall_asset_id'];
    $stall_name = $stall['stall_name'];
    $monthly_rate = $stall['monthly_rental_rate'];
    
    // Create unique bill reference
    $bill_ref = 'RCTS-MKT-' . CURRENT_YEAR . '-' . strtoupper(substr(uniqid(), -6));
    
    // Insert bill - use correct column names per schema
    $bill_data = [
        'bill_reference_no'   => $bill_ref,
        'qcitizen_id'         => $carlo_id,
        'bill_type'           => 'MarketRental',
        'originating_dept_id' => 10, // S10 - Public Assets
        'asset_id'            => $stall_id,
        'tax_year'            => CURRENT_YEAR,
        'base_amount'         => $monthly_rate,
        'discount_rate'       => 0,
        'penalty_rate'        => 0,
        'total_amount_due'    => $monthly_rate,
        'status'              => 'Pending',
        'due_date'            => CURRENT_YEAR . '-03-15'
    ];
    
    $result = db_insert('rcts_assessment_billing_hub', $bill_data);
    
    if ($result['success']) {
        $stall_bills[] = $bill_ref;
        echo "✅ Market Stall Bill Created: {$bill_ref}\n";
        echo "   Stall ID: {$stall_id}\n";
        echo "   Name: {$stall_name}\n";
        echo "   Monthly Rate: ₱" . number_format($monthly_rate, 2) . "\n";
        echo "   Due: " . CURRENT_YEAR . "-03-15\n\n";
    } else {
        echo "❌ Failed to create market stall bill for {$stall_id}\n";
        echo "   HTTP Code: " . ($result['http_code'] ?? 'N/A') . "\n";
        echo "   Response: " . json_encode($result) . "\n\n";
    }
}

// ============================================
// PART 4: CREATE TRAFFIC VIOLATIONS IN DATABASE
// ============================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "PART 4: ADD TRAFFIC VIOLATIONS TO DATABASE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$violations_file = __DIR__ . '/mock-data/subsystem9/traffic-violations.json';
$violations_data = json_decode(file_get_contents($violations_file), true);
$all_violations = $violations_data['violations'] ?? [];
$carlo_violations = array_filter($all_violations, function($v) use ($carlo_id) {
    return $v['qcitizen_id'] === $carlo_id;
});

echo "Adding " . count($carlo_violations) . " traffic violations to database:\n\n";

$violations_added = [];

foreach ($carlo_violations as $violation) {
    $ticket_num = $violation['ticket_number'];
    $violation_type = $violation['violation_type'];
    $fine_amount = $violation['fine_amount'];
    $plate = $violation['plate_number'];
    $issued_at = $violation['issued_at'];
    $apprehension_date = date('Y-m-d', strtotime($issued_at));
    
    // Insert into traffic violations table
    $violation_data = [
        'violation_ticket_id' => $ticket_num,
        'qcitizen_id'         => $carlo_id,
        'vehicle_plate_no'    => $plate,
        'violation_type'     => $violation_type,
        'fine_amount'         => $fine_amount,
        'apprehension_date'   => $apprehension_date,
        'total_amount_due'    => $fine_amount,
        'payment_status'      => 'Unpaid',
        'source_subsystem_id' => 9
    ];
    
    $result = db_insert('rcts_traffic_violation', $violation_data);
    
    if ($result['success']) {
        $violations_added[] = $ticket_num;
        echo "✅ Violation Added: {$ticket_num} - {$violation_type} - ₱{$fine_amount}\n";
    } else {
        echo "⚠️  Already exists or failed: {$ticket_num}\n";
    }
}

// ============================================
// PART 5: CREATE TRAFFIC FINE BILLS
// ============================================
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "PART 5: CREATE TRAFFIC FINE BILLS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$traffic_bills = [];

foreach ($carlo_violations as $violation) {
    $ticket_num = $violation['ticket_number'];
    $violation_type = $violation['violation_type'];
    $fine_amount = $violation['fine_amount'];
    
    // Create unique bill reference
    $bill_ref = 'RCTS-TF-' . CURRENT_YEAR . '-' . strtoupper(substr(uniqid(), -6));
    
    // Insert bill - use correct column names per schema
    $bill_data = [
        'bill_reference_no'   => $bill_ref,
        'qcitizen_id'         => $carlo_id,
        'bill_type'           => 'TrafficFine',
        'originating_dept_id' => 3, // S9 - Traffic
        'asset_id'            => $ticket_num,
        'tax_year'            => CURRENT_YEAR,
        'base_amount'         => $fine_amount,
        'discount_rate'       => 0,
        'penalty_rate'        => 0,
        'total_amount_due'    => $fine_amount,
        'status'              => 'Pending',
        'due_date'            => CURRENT_YEAR . '-04-12'
    ];
    
    $result = db_insert('rcts_assessment_billing_hub', $bill_data);
    
    if ($result['success']) {
        $traffic_bills[] = $bill_ref;
        echo "✅ Traffic Fine Bill Created: {$bill_ref}\n";
        echo "   Ticket: {$ticket_num}\n";
        echo "   Violation: {$violation_type}\n";
        echo "   Amount: ₱" . number_format($fine_amount, 2) . "\n";
        echo "   Due: " . CURRENT_YEAR . "-04-12\n\n";
    } else {
        echo "❌ Failed to create traffic fine bill for {$ticket_num}\n";
        echo "   HTTP Code: " . ($result['http_code'] ?? 'N/A') . "\n";
        echo "   Response: " . json_encode($result) . "\n\n";
    }
}

// ============================================
// SUMMARY
// ============================================
echo "═══════════════════════════════════════════════════════════════\n";
echo "SUMMARY - BILLS CREATED FOR CARLO NICOLAS\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Citizen ID: {$carlo_id}\n";
echo "Email: jackbobert24@gmail.com\n\n";

echo "RPT Bills: " . count($rpt_bills) . "\n";
if (!empty($rpt_bills)) {
    echo "  References:\n";
    foreach ($rpt_bills as $ref) {
        echo "    - {$ref}\n";
    }
}

echo "\nBusiness Tax Bills: " . count($biz_bills) . "\n";
if (!empty($biz_bills)) {
    echo "  References:\n";
    foreach ($biz_bills as $ref) {
        echo "    - {$ref}\n";
    }
}

echo "\nMarket Stall Bills: " . count($stall_bills) . "\n";
if (!empty($stall_bills)) {
    echo "  References:\n";
    foreach ($stall_bills as $ref) {
        echo "    - {$ref}\n";
    }
}

echo "\nTraffic Fine Bills: " . count($traffic_bills) . "\n";
if (!empty($traffic_bills)) {
    echo "  References:\n";
    foreach ($traffic_bills as $ref) {
        echo "    - {$ref}\n";
    }
}

$total_bills = count($rpt_bills) + count($biz_bills) + count($stall_bills) + count($traffic_bills);
echo "\n═══════════════════════════════════════════════════════════════\n";
echo "TOTAL BILLS CREATED: {$total_bills}\n";
echo "═══════════════════════════════════════════════════════════════\n";
?>
