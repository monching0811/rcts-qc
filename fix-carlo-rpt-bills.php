<?php
require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/api/config/constants.php';

$carlo_id = 'QC-2024-000009';

// Read Carlo's properties from the mock data
$properties_file = __DIR__ . '/mock-data/subsystem7/properties.json';
$properties_data = json_decode(file_get_contents($properties_file), true);
$all_properties = $properties_data['properties'] ?? [];
$carlo_properties = array_filter($all_properties, function($p) use ($carlo_id) {
    return $p['qcitizen_id'] === $carlo_id;
});

echo "Found " . count($carlo_properties) . " properties for Carlo\n\n";

// Delete existing RPT bills first
db_delete('rcts_assessment_billing_hub', ['qcitizen_id' => 'eq.' . $carlo_id, 'bill_type' => 'eq.RPT']);
echo "Deleted existing RPT bills\n\n";

// Create RPT bills with correct amounts (including SEF and early bird discount)
foreach ($carlo_properties as $property) {
    $tdn = $property['tdn_number'];
    $assessed_value = $property['assessed_value'];
    $annual_rpt_due = $property['annual_rpt_due'];  // Basic RPT
    $annual_sef_due = $property['annual_sef_due'];  // SEF
    $total_annual_tax = $property['total_annual_tax'];  // Basic + SEF
    
    // Apply early bird discount (20%) if within window
    $discount_rate = 0.20;  // 20% early bird
    $discount_amount = $total_annual_tax * $discount_rate;
    $total_after_discount = $total_annual_tax - $discount_amount;
    
    $bill_ref = 'RCTS-RPT-' . CURRENT_YEAR . '-' . strtoupper(substr(uniqid(), -6));
    
    $bill_data = [
        'bill_reference_no'   => $bill_ref,
        'qcitizen_id'         => $carlo_id,
        'bill_type'           => 'RPT',
        'originating_dept_id' => 7,
        'asset_id'            => $tdn,
        'tax_year'            => CURRENT_YEAR,
        'base_amount'         => $total_annual_tax,  // Basic + SEF before discount
        'discount_rate'       => $discount_rate,
        'penalty_rate'        => 0,
        'total_amount_due'   => $total_after_discount,  // After early bird discount
        'status'              => 'Pending',
        'due_date'            => CURRENT_YEAR . '-03-31'
    ];
    
    $result = db_insert('rcts_assessment_billing_hub', $bill_data);
    
    if ($result['success']) {
        echo "✅ Created: $bill_ref\n";
        echo "   TDN: $tdn\n";
        echo "   Basic RPT: ₱" . number_format($annual_rpt_due, 2) . "\n";
        echo "   SEF: ₱" . number_format($annual_sef_due, 2) . "\n";
        echo "   Total Before Discount: ₱" . number_format($total_annual_tax, 2) . "\n";
        echo "   Early Bird Discount (20%): -₱" . number_format($discount_amount, 2) . "\n";
        echo "   Total Amount Due: ₱" . number_format($total_after_discount, 2) . "\n\n";
    } else {
        echo "❌ Failed: $tdn - " . json_encode($result) . "\n";
    }
}

echo "RPT bills created successfully!";
?>
