<?php
/**
 * Script to find and delete Carlo's incorrect RPT bills
 */
require_once __DIR__ . '/api/config/supabase.php';

echo "Finding Carlo's RPT bills (searching all RPT bills)...\n";

// First, let's find Carlo Nicolas's qcitizen_id
$citizenResult = supabase_request('rcts_citizen_registry', 'GET', [
    'select' => 'qcitizen_id,full_name',
    'or' => '(full_name.ilike.*Carlo*,email.ilike.*carlo*)'
], [], true);

echo "Searching for Carlo Nicolas...\n";
if ($citizenResult['success'] && !empty($citizenResult['data'])) {
    foreach ($citizenResult['data'] as $c) {
        echo "  Found: " . $c['qcitizen_id'] . " - " . $c['full_name'] . "\n";
    }
}

// Let's also get all RPT bills with amounts around 21k, 135k, etc.
echo "\nSearching for RPT bills with high amounts...\n";
$allRpt = supabase_request('rcts_assessment_billing_hub', 'GET', [
    'select' => 'bill_reference_no,qcitizen_id,bill_type,asset_id,total_amount_due,due_date,status',
    'bill_type' => 'eq.RPT',
    'order' => 'total_amount_due.desc',
    'limit' => 50
], [], true);

if ($allRpt['success'] && !empty($allRpt['data'])) {
    echo "Found " . count($allRpt['data']) . " RPT bills:\n";
    foreach ($allRpt['data'] as $bill) {
        $amount = floatval($bill['total_amount_due']);
        // Show bills around the amounts mentioned
        if (in_array($amount, [21000, 135000, 16800, 15600, 120000])) {
            echo "  ★ " . $bill['bill_reference_no'] . " | " . $bill['qcitizen_id'] . " | " . $bill['bill_type'] . " | " . $bill['asset_id'] . " | ₱" . number_format($amount, 2) . " | " . ($bill['due_date'] ?? 'null') . " | " . $bill['status'] . "\n";
        }
    }
}

// Also search by asset_id containing CARLO
echo "\nSearching for bills with CARLO in asset_id...\n";
$carloBills = supabase_request('rcts_assessment_billing_hub', 'GET', [
    'select' => 'bill_reference_no,qcitizen_id,bill_type,asset_id,total_amount_due,due_date,status',
    'asset_id' => 'like.*CARLO*'
], [], true);

if ($carloBills['success'] && !empty($carloBills['data'])) {
    echo "Found " . count($carloBills['data']) . " bills with CARLO:\n";
    foreach ($carloBills['data'] as $bill) {
        echo "  - " . $bill['bill_reference_no'] . " | " . $bill['qcitizen_id'] . " | " . $bill['bill_type'] . " | " . $bill['asset_id'] . " | ₱" . number_format($bill['total_amount_due'], 2) . "\n";
    }
} else {
    echo "No bills found with CARLO in asset_id\n";
}
