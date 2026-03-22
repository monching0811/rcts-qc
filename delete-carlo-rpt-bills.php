<?php
/**
 * Script to delete Carlo's incorrect RPT bills
 */
require_once __DIR__ . '/api/config/supabase.php';

echo "Finding Carlo's RPT bills...\n";

// Search for bills with TDN-QC-2024-CARLO in the asset_id or bill_reference_no
$result = supabase_request('rcts_assessment_billing_hub', 'GET', [
    'select' => 'bill_reference_no,qcitizen_id,bill_type,asset_id,total_amount_due,due_date,status',
    'or' => '(asset_id.like.*TDN-QC-2024-CARLO*,bill_reference_no.like.*CARLO*)'
], [], true);

if (!$result['success']) {
    echo "Error fetching bills: " . json_encode($result) . "\n";
    exit;
}

echo "Found " . count($result['data']) . " bills:\n";
foreach ($result['data'] as $bill) {
    echo "  - " . $bill['bill_reference_no'] . " | " . $bill['bill_type'] . " | " . $bill['asset_id'] . " | ₱" . number_format($bill['total_amount_due'], 2) . " | " . $bill['due_date'] . " | " . $bill['status'] . "\n";
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

echo "\nDone! Deleted $deleted bills.\n";
