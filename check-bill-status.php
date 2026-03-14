<?php
require_once 'includes/db.php';

$raven_id = '92be37af-7c34-4c9b-80cb-47cde7c3a9fd';

echo "═══════════════════════════════════════════════════════════════\n";
echo "Bill Status Check for Raven's Businesses\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Get all bills for Raven (regardless of status)
$result = supabase_request('rcts_assessment_billing_hub', 'GET', 
    ['qcitizen_id' => 'eq.' . $raven_id], [], true);

if ($result['success']) {
    $bills = $result['data'];
    echo "Total Bills: " . count($bills) . "\n\n";
    
    foreach ($bills as $bill) {
        echo "Bill Reference: " . $bill['bill_reference_no'] . "\n";
        echo "  Asset ID (BIN): " . $bill['asset_id'] . "\n";
        echo "  Type: " . $bill['bill_type'] . "\n";
        echo "  Status: " . $bill['status'] . "\n";
        echo "  Base Amount: ₱" . number_format($bill['base_amount'], 2) . "\n";
        echo "  Discount: ₱" . number_format($bill['discount_amount'] ?? 0, 2) . "\n";
        echo "  Penalty: ₱" . number_format($bill['penalty_amount'] ?? 0, 2) . "\n";
        echo "  Total Due: ₱" . number_format($bill['total_amount_due'], 2) . "\n";
        echo "  Due Date: " . $bill['due_date'] . "\n";
        echo "  Created: " . $bill['created_at'] . "\n\n";
    }
} else {
    echo "Error: " . $result['message'];
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "Pending Bills Only (Status = 'Pending')\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$pending = supabase_request('v_citizen_pending_bills', 'GET',
    ['qcitizen_id' => 'eq.' . $raven_id], [], true);

if ($pending['success']) {
    echo "Pending Bills Count: " . count($pending['data']) . "\n\n";
    foreach ($pending['data'] as $bill) {
        echo "• " . $bill['bill_reference_no'] . " - ₱" . number_format($bill['total_amount_due'], 2) . "\n";
    }
} else {
    echo "Error fetching pending bills";
}
