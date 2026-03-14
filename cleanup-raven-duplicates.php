<?php
/**
 * CLEAN UP DUPLICATE BUSINESS TAX BILLS FOR RAVEN
 * Remove the old BIN-QC-2024 bills, keep only BIN-QC-2026 bills
 */

require_once __DIR__ . '/api/config/supabase.php';

echo "═══════════════════════════════════════════════════════════════\n";
echo "CLEANING UP DUPLICATE BUSINESS TAX BILLS FOR RAVEN\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Bills to delete (the old ones)
$duplicate_refs = [
    'BIN-QC-2024-RAVEN-001',
    'BIN-QC-2024-RAVEN-002'
];

echo "Deleting duplicate bills...\n";
$deleted_count = 0;

foreach ($duplicate_refs as $bill_ref) {
    $delete_result = supabase_request(
        'rcts_assessment_billing_hub?bill_reference_no=eq.' . urlencode($bill_ref),
        'DELETE',
        []
    );

    if ($delete_result['success']) {
        echo "✅ Deleted duplicate bill: {$bill_ref}\n";
        $deleted_count++;
    } else {
        echo "❌ Failed to delete bill: {$bill_ref}\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "CLEANUP COMPLETE\n";
echo "  Deleted Bills: {$deleted_count}\n";
echo "═══════════════════════════════════════════════════════════════\n";