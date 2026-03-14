<?php
require_once 'includes/db.php';

// Test getting pending bills for Raven
$raven_id = '92be37af-7c34-4c9b-80cb-47cde7c3a9fd';
$response = supabase_request('v_citizen_pending_bills', 'GET', ['qcitizen_id' => 'eq.' . $raven_id], [], true);

echo "═══════════════════════════════════════════\n";
echo "Fields in v_citizen_pending_bills View\n";
echo "═══════════════════════════════════════════\n\n";

if ($response['success'] && count($response['data']) > 0) {
    echo "✅ Pending bills found for Raven:\n\n";
    foreach ($response['data'] as $i => $bill) {
        echo "Bill " . ($i + 1) . ":\n";
        foreach ($bill as $field => $value) {
            echo "  • $field: " . ($value ?? 'NULL') . "\n";
        }
        echo "\n";
    }
} else {
    echo "❌ No pending bills found for Raven\n";
}
