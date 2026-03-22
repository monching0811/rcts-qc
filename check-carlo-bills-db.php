<?php
require_once 'includes/db.php';

// Check all bills for Carlo (QC-2024-000009)
echo "Checking all bills for Carlo (QC-2024-000009)...\n\n";

$result = db_select('rcts_bills', [
    'qcitizen_id' => 'eq.QC-2024-000009'
]);

if ($result['success']) {
    if (empty($result['data'])) {
        echo "No bills found for Carlo in rcts_bills table.\n";
    } else {
        echo "Found " . count($result['data']) . " bills:\n";
        foreach ($result['data'] as $bill) {
            echo "  - Reference: {$bill['bill_reference_no']}\n";
            echo "    Amount: ₱" . number_format($bill['total_amount_due'] ?? 0, 2) . "\n";
            echo "    Status: {$bill['payment_status']}\n";
            echo "    Type: {$bill['bill_type']}\n\n";
        }
    }
} else {
    echo "Error querying database: " . ($result['error'] ?? 'Unknown error') . "\n";
    echo "Full result: " . print_r($result, true) . "\n";
}

// Also check what tables exist
echo "\n\nChecking if bills might be in properties data...\n";
$props = json_decode(file_get_contents('mock-data/subsystem7/properties.json'), true);
$carlo_props = array_filter($props, function($p) { return $p['qcitizen_id'] === 'QC-2024-000009'; });
echo "CARLO properties found: " . count($carlo_props) . "\n";
foreach ($carlo_props as $prop) {
    echo "  - {$prop['tdn_number']}\n";
}
?>
