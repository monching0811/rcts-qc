<?php
require_once __DIR__ . '/api/config/supabase.php';

$carlo_id = 'QC-2024-000009';

$violations = [
    ['ticket' => 'TKT-20260310-CARLO-001', 'plate' => 'CN-2024', 'type' => 'Illegal Parking', 'amount' => 500, 'date' => '2026-03-10'],
    ['ticket' => 'TKT-20260312-CARLO-001', 'plate' => 'CN-2024', 'type' => 'Expired Registration', 'amount' => 1500, 'date' => '2026-03-12'],
    ['ticket' => 'TKT-20260315-CARLO-001', 'plate' => 'CN-2024', 'type' => 'No Seatbelt', 'amount' => 500, 'date' => '2026-03-15'],
];

foreach ($violations as $v) {
    $data = [
        'violation_ticket_id' => $v['ticket'],
        'qcitizen_id' => $carlo_id,
        'vehicle_plate_no' => $v['plate'],
        'violation_type' => $v['type'],
        'fine_amount' => $v['amount'],
        'apprehension_date' => $v['date'],
        'total_amount_due' => $v['amount'],
        'payment_status' => 'Unpaid',
        'source_subsystem_id' => 9
    ];
    $result = db_insert('rcts_traffic_violation', $data);
    if ($result['success']) {
        echo "✅ Added: {$v['ticket']} - {$v['type']} - ₱{$v['amount']}\n";
    } else {
        echo "❌ Failed: {$v['ticket']} - " . json_encode($result) . "\n";
    }
}

echo "\nDone adding traffic violations for Carlo Nicolas!";
?>
