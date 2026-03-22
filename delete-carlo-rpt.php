<?php
/**
 * Script to delete Carlo Nicolas's RPT bills
 */
require_once __DIR__ . '/api/config/supabase.php';

$carlo_id = 'QC-2024-000009';

echo "Finding Carlo Nicolas (QC-2024-000009) RPT bills...\n";

// Get all RPT bills for Carlo Nicolas
$result = supabase_request('rcts_assessment_billing_hub', 'GET', [
    'select' => 'bill_reference_no,qcitizen_id,bill_type,asset_id,total_amount_due,due_date,status',
    'qcitizen_id' => 'eq.' . $carlo_id,
    'bill_type' => 'eq.RPT'
], [], true);

if (!$result['success']) {
    echo "Error fetching bills: " . json_encode($result) . "\n";
    exit;
}

echo "Found " . count($result['data']) . " RPT bills for Carlo Nicolas:\n";
foreach ($result['data'] as $bill) {
    echo "  - " . $bill['bill_reference_no'] . " | " . $bill['bill_type'] . " | " . $bill['asset_id'] . " | ₱" . number_format($bill['total_amount_due'], 2) . " | due: " . ($bill['due_date'] ?? 'null') . " | " . $bill['status'] . "\n";
}

echo "\nDeleting these bills...\n";
$deleted = 0;
foreach ($result['data'] as $bill) {
    $billRef = $bill['bill_reference_no'];
    $deleteResult = supabase_request('rcts_assessment_billing_hub', 'DELETE', [
        'bill_reference_no' => 'eq.' . $billRef
    ], [], true);
    
    if ($deleteResult['success']) {
        echo "  ✓ Deleted: $billRef\n";
        $deleted++;
    } else {
        echo "  ✗ Failed to delete: $billRef - " . json_encode($deleteResult) . "\n";
    }
}

echo "\nDone! Deleted $deleted RPT bills for Carlo Nicolas.\n";
