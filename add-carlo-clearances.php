<?php
require_once __DIR__ . '/api/config/supabase.php';

$carlo_id = 'QC-2024-000009';

$clearances = [
    // Nicolas Printing Services
    ['bin' => 'BIN-QC-2024-CARLO-001', 'type' => 'Health', 'date' => '2024-02-10', 'valid' => '2025-12-31', 'issuer' => 'QC Health Dept'],
    ['bin' => 'BIN-QC-2024-CARLO-001', 'type' => 'Sanitary', 'date' => '2024-02-10', 'valid' => '2025-12-31', 'issuer' => 'QC Sanitation Office'],
    ['bin' => 'BIN-QC-2024-CARLO-001', 'type' => 'Fire', 'date' => '2024-02-11', 'valid' => '2025-12-31', 'issuer' => 'BFP-QC'],
    // Nicolas Tech Solutions
    ['bin' => 'BIN-QC-2024-CARLO-002', 'type' => 'Health', 'date' => '2024-03-01', 'valid' => '2025-12-31', 'issuer' => 'QC Health Dept'],
    ['bin' => 'BIN-QC-2024-CARLO-002', 'type' => 'Sanitary', 'date' => '2024-03-01', 'valid' => '2025-12-31', 'issuer' => 'QC Sanitation Office'],
    ['bin' => 'BIN-QC-2024-CARLO-002', 'type' => 'Fire', 'date' => '2024-03-02', 'valid' => '2025-12-31', 'issuer' => 'BFP-QC'],
    // Nicolas Mini Mart
    ['bin' => 'BIN-QC-2024-CARLO-003', 'type' => 'Health', 'date' => '2024-02-20', 'valid' => '2025-12-31', 'issuer' => 'QC Health Dept'],
    ['bin' => 'BIN-QC-2024-CARLO-003', 'type' => 'Sanitary', 'date' => '2024-02-20', 'valid' => '2025-12-31', 'issuer' => 'QC Sanitation Office'],
    ['bin' => 'BIN-QC-2024-CARLO-003', 'type' => 'Fire', 'date' => '2024-02-21', 'valid' => '2025-12-31', 'issuer' => 'BFP-QC'],
];

foreach ($clearances as $idx => $c) {
    $ref_id = 'CLR-C' . ($idx + 1);
    $data = [
        'clearance_ref_id' => $ref_id,
        'qcitizen_id' => $carlo_id,
        'business_bin' => $c['bin'],
        'clearance_type' => $c['type'],
        'inspection_date' => $c['date'],
        'valid_until' => $c['valid'],
        'status_flag' => 'Passed',
        'inspector_name' => $c['issuer'],
        'source_subsystem_id' => 4
    ];
    $result = db_insert('rcts_regulatory_clearance', $data);
    if ($result['success']) {
        echo "✅ Added: $ref_id - {$c['type']} for {$c['bin']}\n";
    } else {
        echo "❌ Failed: $ref_id - " . json_encode($result) . "\n";
    }
}

echo "\nDone adding regulatory clearances for Carlo Nicolas!";
?>
