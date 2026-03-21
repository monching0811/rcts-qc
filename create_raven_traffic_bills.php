<?php
/**
 * CREATE TRAFFIC FINE BILLS FOR RAVEN POGI
 * Creates bills in the assessment billing hub for Raven's traffic violations
 */

require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/includes/db.php';

// Raven Pogi's citizen ID
$raven_id = '92be37af-7c34-4c9b-80cb-47cde7c3a9fd';

echo "═══════════════════════════════════════════════════════════════\n";
echo "Creating Traffic Fine Bills for Raven Pogi\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Get Raven's traffic violations
$violations_result = db_select('rcts_traffic_violation', [
    'qcitizen_id' => 'eq.' . $raven_id
]);

if (!$violations_result['success']) {
    echo "Failed to get violations: " . json_encode($violations_result) . "\n";
    exit;
}

$violations = $violations_result['data'];
echo "Found " . count($violations) . " violations for Raven Pogi\n\n";

$created_bills = 0;
foreach ($violations as $violation) {
    $ticket_id = $violation['violation_ticket_id'];

    // Check if bill already exists for this violation
    $existing_bill = db_select('rcts_assessment_billing_hub', [
        'asset_id' => 'eq.' . $ticket_id,
        'bill_type' => 'eq.TrafficFine'
    ]);

    if ($existing_bill['success'] && count($existing_bill['data']) > 0) {
        echo "Bill already exists for violation $ticket_id, skipping...\n";
        continue;
    }

    // Create bill reference
    $bill_ref = 'RCTS-TF-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));

    $bill_data = [
        'bill_reference_no' => $bill_ref,
        'qcitizen_id' => $raven_id,
        'bill_type' => 'TrafficFine',
        'originating_dept_id' => 9,
        'asset_id' => $ticket_id,
        'tax_year' => date('Y'),
        'base_amount' => $violation['fine_amount'],
        'discount_amount' => 0,
        'penalty_amount' => 0,
        'total_amount_due' => $violation['fine_amount'],
        'status' => 'Pending',
        'due_date' => date('Y-m-d', strtotime('+7 days'))
    ];

    $result = db_insert('rcts_assessment_billing_hub', $bill_data);
    if ($result['success']) {
        echo "✅ Created bill: $bill_ref for violation $ticket_id\n";
        $created_bills++;
    } else {
        echo "❌ Failed to create bill for $ticket_id: " . json_encode($result) . "\n";
    }
}

echo "\nCreated $created_bills traffic fine bills for Raven Pogi\n";
?>