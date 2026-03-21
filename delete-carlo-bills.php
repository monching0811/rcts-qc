<?php
/**
 * Delete Carlo Nicolas Bills
 * 
 * Clears all pending bills for Carlo Nicolas (QC-2024-000009)
 * Run this before creating new bills to avoid duplicates
 * 
 * Usage: php delete-carlo-bills.php
 * Or access in browser: http://localhost/rcts-qc/delete-carlo-bills.php
 */

require_once __DIR__ . '/api/config/supabase.php';

$carlo_id = 'QC-2024-000009';

echo "═══════════════════════════════════════════════════════════════\n";
echo "DELETING BILLS FOR CARLO NICOLAS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// First, get current bills count
$currentBills = db_select('rcts_assessment_billing_hub', [
    'qcitizen_id' => 'eq.' . $carlo_id
]);

if ($currentBills['success'] && count($currentBills['data']) > 0) {
    $count = count($currentBills['data']);
    echo "Found $count existing bills for Carlo Nicolas (QC-2024-000009)\n\n";
    
    // Delete all bills for Carlo
    $deleteResult = db_delete('rcts_assessment_billing_hub', [
        'qcitizen_id' => 'eq.' . $carlo_id
    ]);
    
    if ($deleteResult['success']) {
        echo "✅ Successfully deleted $count bills for Carlo Nicolas!\n\n";
        
        // Verify deletion
        $verifyBills = db_select('rcts_assessment_billing_hub', [
            'qcitizen_id' => 'eq.' . $carlo_id
        ]);
        
        if ($verifyBills['success'] && count($verifyBills['data']) === 0) {
            echo "✅ Verification: No more bills for Carlo Nicolas in database.\n";
        } else {
            echo "⚠️ Warning: Some bills may still exist.\n";
        }
    } else {
        echo "❌ Failed to delete bills: " . json_encode($deleteResult) . "\n";
    }
} else {
    echo "ℹ️ No existing bills found for Carlo Nicolas.\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "You can now create new bills using:\n";
echo "  - Browser: http://localhost/rcts-qc/allocate-carlo-bills-browser.php\n";
echo "  - CLI: php create-carlo-all-bills.php\n";
echo "═══════════════════════════════════════════════════════════════\n";
?>
