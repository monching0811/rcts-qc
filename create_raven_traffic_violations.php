<?php
/**
 * CREATE TRAFFIC VIOLATIONS FOR RAVEN POGI
 * Creates traffic violations in the rcts_traffic_violation table for Raven Pogi
 */

require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/includes/db.php';

// Raven Pogi's actual citizen ID from registry
$raven_id = '92be37af-7c34-4c9b-80cb-47cde7c3a9fd';

echo "═══════════════════════════════════════════════════════════════\n";
echo "Creating Traffic Violations for Raven Pogi\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$violations = [
    [
        'violation_ticket_id' => 'TV-RAVEN-001',
        'qcitizen_id' => $raven_id,
        'vehicle_plate_no' => 'RVN-2024',
        'violation_type' => 'Illegal Parking',
        'fine_amount' => 1500.00,
        'apprehension_date' => '2026-03-15',
        'payment_status' => 'Unpaid',
        'source_subsystem_id' => 9
    ],
    [
        'violation_ticket_id' => 'TV-RAVEN-002',
        'qcitizen_id' => $raven_id,
        'vehicle_plate_no' => 'RVN-2024',
        'violation_type' => 'Overtaking',
        'fine_amount' => 1000.00,
        'apprehension_date' => '2026-03-16',
        'payment_status' => 'Unpaid',
        'source_subsystem_id' => 9
    ],
    [
        'violation_ticket_id' => 'TV-RAVEN-003',
        'qcitizen_id' => $raven_id,
        'vehicle_plate_no' => 'RVN-2024',
        'violation_type' => 'No Seatbelt',
        'fine_amount' => 500.00,
        'apprehension_date' => '2026-03-17',
        'payment_status' => 'Unpaid',
        'source_subsystem_id' => 9
    ]
];

$created_count = 0;
foreach ($violations as $violation) {
    // Check if violation already exists
    $existing = db_select('rcts_traffic_violation', [
        'violation_ticket_id' => 'eq.' . $violation['violation_ticket_id']
    ]);

    if ($existing['success'] && count($existing['data']) > 0) {
        echo "Violation {$violation['violation_ticket_id']} already exists, skipping...\n";
        continue;
    }

    $result = db_insert('rcts_traffic_violation', $violation);
    if ($result['success']) {
        echo "✅ Created violation: {$violation['violation_ticket_id']} - {$violation['violation_type']}\n";
        $created_count++;
    } else {
        echo "❌ Failed to create violation {$violation['violation_ticket_id']}: " . json_encode($result) . "\n";
    }
}

echo "\nCreated $created_count traffic violations for Raven Pogi (ID: $raven_id)\n";
?>