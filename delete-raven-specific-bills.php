<?php
require_once __DIR__ . '/api/config/supabase.php';

echo "Deleting specific Raven business bills...\n";

$bill_refs = [
    'BIN-QC-2024-RAVEN-003',
    'BIN-QC-2024-RAVEN-004',
    'BIN-QC-2024-RAVEN-005',
    'BIN-QC-2026-RAVEN-1',
    'BIN-QC-2026-RAVEN-2'
];

$deleted_count = 0;

foreach ($bill_refs as $ref) {
    $result = supabase_request('rcts_assessment_billing_hub', 'DELETE', ['bill_reference_no' => 'eq.' . $ref], [], true);
    if ($result['success']) {
        echo "Deleted bill: $ref\n";
        $deleted_count++;
    } else {
        echo "Failed to delete bill: $ref - " . ($result['error'] ?? 'Unknown error') . "\n";
    }
}

echo "Total bills deleted: $deleted_count\n";
?>