<?php
/**
 * Delete specific CARLO bills from rcts_bills table
 * Removes: TDN-QC-2024-CARLO-001 through TDN-QC-2024-CARLO-005
 */
require_once 'includes/db.php';

// Bill reference numbers to delete
$bills_to_delete = [
    'TDN-QC-2024-CARLO-001',
    'TDN-QC-2024-CARLO-002',
    'TDN-QC-2024-CARLO-003',
    'TDN-QC-2024-CARLO-004',
    'TDN-QC-2024-CARLO-005'
];

echo "Starting deletion of CARLO bills...\n\n";

foreach ($bills_to_delete as $bill_ref) {
    // First, fetch the bill to confirm it exists
    $check = db_select('rcts_bills', [
        'bill_reference_no' => "eq.$bill_ref"
    ]);

    if ($check['success'] && !empty($check['data'])) {
        $bill = $check['data'][0];
        echo "Found: $bill_ref (Amount: ₱" . number_format($bill['total_amount_due'], 2) . ", Status: {$bill['payment_status']})\n";
        
        // Delete the bill
        $delete_result = db_delete('rcts_bills', [
            'bill_reference_no' => "eq.$bill_ref"
        ]);

        if ($delete_result['success']) {
            echo "✓ Deleted: $bill_ref\n";
        } else {
            echo "✗ Failed to delete: $bill_ref - " . ($delete_result['error'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "✗ Not found: $bill_ref\n";
    }
    echo "\n";
}

echo "\n=== Deletion Complete ===\n";

// Verify remaining bills for Carlo (QC-2024-000009)
echo "\nVerifying remaining bills for Carlo...\n";
$remaining = db_select('rcts_bills', [
    'qcitizen_id' => 'eq.QC-2024-000009'
]);

if ($remaining['success']) {
    if (empty($remaining['data'])) {
        echo "✓ No bills remaining for Carlo (QC-2024-000009)\n";
    } else {
        echo "Remaining bills for Carlo: " . count($remaining['data']) . "\n";
        foreach ($remaining['data'] as $bill) {
            echo "  - {$bill['bill_reference_no']}\n";
        }
    }
} else {
    echo "Could not verify: " . ($remaining['error'] ?? 'Unknown error') . "\n";
}
?>
