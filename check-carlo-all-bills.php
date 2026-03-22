<?php
/**
 * Script to find all bills for Carlo Nicolas
 */
require_once __DIR__ . '/api/config/supabase.php';

$carlo_id = 'QC-2024-000009';

echo "Finding ALL bills for Carlo Nicolas (QC-2024-000009)...\n";

// Get ALL bills for Carlo Nicolas
$result = supabase_request('rcts_assessment_billing_hub', 'GET', [
    'select' => 'bill_reference_no,qcitizen_id,bill_type,asset_id,total_amount_due,due_date,status',
    'qcitizen_id' => 'eq.' . $carlo_id
], [], true);

if (!$result['success']) {
    echo "Error fetching bills: " . json_encode($result) . "\n";
    exit;
}

echo "Found " . count($result['data']) . " total bills for Carlo Nicolas:\n\n";

// Group by bill_type
$byType = [];
foreach ($result['data'] as $bill) {
    $type = $bill['bill_type'];
    if (!isset($byType[$type])) $byType[$type] = [];
    $byType[$type][] = $bill;
}

foreach ($byType as $type => $bills) {
    echo "=== $type (" . count($bills) . " bills) ===\n";
    foreach ($bills as $bill) {
        echo "  " . $bill['bill_reference_no'] . " | " . $bill['asset_id'] . " | ₱" . number_format($bill['total_amount_due'], 2) . " | due: " . ($bill['due_date'] ?? 'null') . " | " . $bill['status'] . "\n";
    }
    echo "\n";
}

// Also check if there are any bills with TDN-QC-2024-CARLO anywhere
echo "\n\nSearching for any bills with CARLO anywhere...\n";
$searchResult = supabase_request('rcts_assessment_billing_hub', 'GET', [
    'select' => 'bill_reference_no,qcitizen_id,bill_type,asset_id,total_amount_due,due_date,status',
    'or' => '(asset_id.like.*CARLO*,bill_reference_no.like.*CARLO*)'
], [], true);

if ($searchResult['success'] && !empty($searchResult['data'])) {
    echo "Found " . count($searchResult['data']) . " bills with CARLO:\n";
    foreach ($searchResult['data'] as $bill) {
        echo "  " . $bill['bill_reference_no'] . " | " . $bill['qcitizen_id'] . " | " . $bill['bill_type'] . " | " . $bill['asset_id'] . " | ₱" . number_format($bill['total_amount_due'], 2) . "\n";
    }
} else {
    echo "No bills found with CARLO anywhere.\n";
}
