<?php
/**
 * List all bills in the database
 */
require_once __DIR__ . '/api/config/supabase.php';

echo "Fetching all bills from rcts_assessment_billing_hub...\n";

$result = supabase_request('rcts_assessment_billing_hub', 'GET', [
    'select' => 'bill_reference_no,asset_id,qcitizen_id,bill_type,total_amount_due,due_date,status',
    'limit' => 200
], [], true);

if (!$result['success']) {
    echo "Error: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    exit;
}

echo "Found " . count($result['data']) . " bills:\n\n";

// Show first 50 bills
$count = 0;
foreach ($result['data'] as $bill) {
    $count++;
    echo "$count. " . $bill['bill_reference_no'] . " | " . $bill['asset_id'] . " | " . $bill['qcitizen_id'] . " | " . $bill['bill_type'] . " | ₱" . number_format($bill['total_amount_due'], 2) . " | " . ($bill['due_date'] ?? 'null') . " | " . $bill['status'] . "\n";
    
    // Stop after 50
    if ($count >= 50) {
        echo "\n... (showing first 50 of " . count($result['data']) . " total bills)\n";
        break;
    }
}
