<?php
// Push violations script
require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/includes/db.php';

$violations_file = __DIR__ . '/mock-data/subsystem9/traffic-violations.json';
$violations_data = json_decode(file_get_contents($violations_file), true);
$VIOLATIONS = $violations_data['violations'] ?? [];

foreach ($VIOLATIONS as $v) {
    $body = [
        'violation_ticket_id' => substr($v['ticket_number'], -10), // Last 10 chars
        'vehicle_plate_no' => $v['plate_number'],
        'qcitizen_id' => $v['qcitizen_id'],
        'violation_type' => $v['violation_type'],
        'fine_amount' => $v['fine_amount'],
        'apprehension_date' => $v['issued_at']
    ];

    // Simulate inbound logic
    $ticket_id = $body['violation_ticket_id'];
    $qcitizen_id = $body['qcitizen_id'];
    $plate_no = $body['vehicle_plate_no'];
    $violation_type = $body['violation_type'];
    $fine_amount = (float)($body['fine_amount'] ?? 0);
    $apprehension = $body['apprehension_date'] ?? CURRENT_DATE;

    if (!$ticket_id || !$plate_no || $fine_amount <= 0) {
        echo "Invalid data for $ticket_id\n";
        continue;
    }

    // Check if already exists
    $existing = db_select('rcts_traffic_violation', ['violation_ticket_id' => 'eq.' . $ticket_id]);
    if (!empty($existing['data'])) {
        echo "Already exists: $ticket_id\n";
        continue;
    }

    // Save violation record
    $insert_violation = db_insert('rcts_traffic_violation', [
        'violation_ticket_id' => $ticket_id,
        'qcitizen_id' => $qcitizen_id ?: null,
        'vehicle_plate_no' => $plate_no,
        'violation_type' => $violation_type,
        'fine_amount' => $fine_amount,
        'apprehension_date' => $apprehension,
        'payment_status' => 'Unpaid',
        'source_subsystem_id' => 9
    ]);

    if (!$insert_violation['success']) {
        echo "Failed to insert violation $ticket_id: " . json_encode($insert_violation) . "\n";
        continue;
    }

    // Auto-generate a bill if citizen is identified
    if ($qcitizen_id) {
        $bill_ref = 'RCTS-TF-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
        $days_late = max(0, (strtotime(CURRENT_DATE) - strtotime($apprehension)) / 86400 - TRAFFIC_GRACE_PERIOD_DAYS);
        $penalty = $days_late > 0 ? min(TRAFFIC_LATE_RATE * $days_late, 1.0) : 0.0;

        $insert_bill = db_insert('rcts_assessment_billing_hub', [
            'bill_reference_no' => $bill_ref,
            'qcitizen_id' => $qcitizen_id,
            'bill_type' => 'TrafficFine',
            'originating_dept_id' => 9,
            'asset_id' => $ticket_id,
            'tax_year' => CURRENT_YEAR,
            'base_amount' => $fine_amount,
            'discount_rate' => 0.0,
            'penalty_rate' => $penalty,
            'status' => 'Pending',
            'due_date' => date('Y-m-d', strtotime($apprehension . ' + ' . TRAFFIC_GRACE_PERIOD_DAYS . ' days'))
        ]);

        if (!$insert_bill['success']) {
            echo "Failed to insert bill for $ticket_id: " . json_encode($insert_bill) . "\n";
            continue;
        }

        // Link ticket to bill
        db_update('rcts_traffic_violation',
            ['violation_ticket_id' => 'eq.' . $ticket_id],
            ['bill_reference_no' => $bill_ref]
        );

        echo "Pushed $ticket_id: Bill $bill_ref created\n";
    } else {
        echo "Pushed $ticket_id: No citizen ID, no bill\n";
    }
}
?>