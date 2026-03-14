<?php
// Test script to verify bill matching logic using API calls

$citizen_id = '92be37af-7c34-4c9b-80cb-47cde7c3a9fd';

// Get bills via API
$bill_url = "http://localhost/rcts-qc/api/endpoints/business-tax.php?action=get_bills&qcitizen_id=$citizen_id";
$bill_json = file_get_contents($bill_url);
$bill_data = json_decode($bill_json, true);
$bills = $bill_data['data'] ?? [];

echo "Bills for Raven:\n";
foreach ($bills as $bill) {
    echo "- Asset ID: {$bill['asset_id']}, Status: {$bill['status']}, Reference: {$bill['bill_reference_no']}\n";
}

// Get businesses from mock data
$businesses_file = 'mock-data/subsystem2/businesses.json';
$businesses_data = json_decode(file_get_contents($businesses_file), true);
$businesses = $businesses_data['businesses'] ?? [];

echo "\nBusinesses for Raven:\n";
foreach ($businesses as $biz) {
    echo "- BIN: {$biz['bin_number']}, Name: {$biz['business_name']}\n";
}

// Simulate the matching logic
echo "\nMatching simulation:\n";
foreach ($businesses as $biz) {
    $bin = $biz['bin_number'];
    $matching_bills = array_filter($bills, function($b) use ($bin) {
        return $b['asset_id'] === $bin;
    });

    $pending = array_filter($matching_bills, function($b) { return $b['status'] === 'Pending'; });
    $paid = array_filter($matching_bills, function($b) { return $b['status'] === 'Paid'; });

    echo "Business {$biz['business_name']} (BIN: $bin):\n";
    echo "  - Matching bills: " . count($matching_bills) . "\n";
    echo "  - Pending bills: " . count($pending) . "\n";
    echo "  - Paid bills: " . count($paid) . "\n";

    if ($pending) {
        $bill = reset($pending);
        echo "  - Will show: PENDING bill {$bill['bill_reference_no']}\n";
    } elseif ($paid) {
        $bill = reset($paid);
        echo "  - Will show: PAID bill {$bill['bill_reference_no']}\n";
    } else {
        echo "  - Will show: No bill message\n";
    }
}
?>