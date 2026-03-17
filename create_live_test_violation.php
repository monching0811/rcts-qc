<?php
// Create test traffic violation on live server
require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/includes/db.php';

// NOTE: If this fails with "value too long", you need to manually run this in Supabase SQL Editor:
// ALTER TABLE rcts_traffic_violation ALTER COLUMN qcitizen_id TYPE VARCHAR(50);

// Create test violation for a citizen
$test_citizen_id = '92be37af-7c34-4c9b-80cb-47cde7c3a9fd'; // Raven Pogi
$ticket_id = 'LIVE-TV-' . strtoupper(substr(uniqid(), -6)); // Generate unique ticket ID
$violation_data = [
    'violation_ticket_id' => $ticket_id,
    'qcitizen_id' => $test_citizen_id,
    'vehicle_plate_no' => 'ABC-123',
    'violation_type' => 'Illegal Parking',
    'fine_amount' => 1500.00,
    'total_amount_due' => 1500.00, // Set total_amount_due to match fine_amount
    'apprehension_date' => '2026-03-17',
    'payment_status' => 'Unpaid',
    'source_subsystem_id' => 9
];

// Insert violation
$violation_result = db_insert('rcts_traffic_violation', $violation_data);
if (!$violation_result['success']) {
    echo "Failed to create violation: " . json_encode($violation_result) . "\n";
    exit;
}
echo "Created violation: $ticket_id for $test_citizen_id\n";

// Create bill
$bill_ref = 'RCTS-TF-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
$bill_data = [
    'bill_reference_no' => $bill_ref,
    'qcitizen_id' => $test_citizen_id,
    'bill_type' => 'TrafficFine',
    'originating_dept_id' => 9,
    'asset_id' => $ticket_id,
    'tax_year' => date('Y'),
    'base_amount' => 1500.00,
    'discount_rate' => 0.0,
    'penalty_rate' => 0.0,
    'total_amount_due' => 1500.00,
    'status' => 'Pending',
    'due_date' => date('Y-m-d', strtotime('+7 days'))
];

$bill_result = db_insert('rcts_assessment_billing_hub', $bill_data);
if (!$bill_result['success']) {
    echo "Failed to create bill: " . json_encode($bill_result) . "\n";
    exit;
}
echo "Created bill: $bill_ref\n";

// Link bill to violation
$update_result = db_update('rcts_traffic_violation',
    ['violation_ticket_id' => 'eq.' . $ticket_id],
    ['bill_reference_no' => $bill_ref]
);

if (!$update_result['success']) {
    echo "Failed to link bill to violation: " . json_encode($update_result) . "\n";
    exit;
}
echo "Linked bill to violation\n";

echo "\n✅ Test traffic violation created for live testing!\n";
echo "Citizen ID: $test_citizen_id\n";
echo "Ticket: $ticket_id\n";
echo "Fine: ₱1,500\n";
echo "Bill: $bill_ref\n";
?>