<?php
require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/includes/db.php';

// Create traffic violation for Raven Pogi
// Using existing citizen ID that fits VARCHAR(20)
$raven_id = 'QC-2024-000001'; // Use existing test citizen
$ticket_id = 'TV-RAVEN-002'; // Changed to avoid duplicate
$violation_data = [
    'violation_ticket_id' => $ticket_id,
    'qcitizen_id' => $raven_id,
    'vehicle_plate_no' => 'RVN-2024',
    'violation_type' => 'Illegal Parking',
    'fine_amount' => 1500.00,
    'apprehension_date' => '2026-03-15',
    'payment_status' => 'Unpaid',
    'source_subsystem_id' => 9
];

// Insert violation
$violation_result = db_insert('rcts_traffic_violation', $violation_data);
if (!$violation_result['success']) {
    echo "Failed to create violation: " . json_encode($violation_result) . "\n";
    exit;
}
echo "Created violation: $ticket_id for Raven Pogi\n";

// Create bill
$bill_ref = 'RCTS-TF-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
$bill_data = [
    'bill_reference_no' => $bill_ref,
    'qcitizen_id' => $raven_id,
    'bill_type' => 'TrafficFine',
    'originating_dept_id' => 9,
    'asset_id' => $ticket_id,
    'tax_year' => date('Y'),
    'base_amount' => 1500.00,
    'discount_rate' => 0.0,
    'penalty_rate' => 0.0,
    'total_amount_due' => 1500.00, // Set explicitly
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

echo "\n✅ Traffic violation created for Raven Pogi:\n";
echo "- Citizen ID: $raven_id\n";
echo "- Ticket: $ticket_id\n";
echo "- Violation: Illegal Parking\n";
echo "- Fine: ₱1,500\n";
echo "- Bill Reference: $bill_ref\n";
?>