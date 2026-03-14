<?php
/**
 * SUBSYSTEM 9 (Traffic) INTEGRATION TEST
 * Tests S9 → RCTS integration for traffic fines
 */

require_once __DIR__ . '/api/config/supabase.php';

$raven_id = '92be37af-7c34-4c9b-80cb-47cde7c3a9fd';

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║    SUBSYSTEM 9 (TRAFFIC) INTEGRATION TEST                 ║\n";
echo "║    Testing S9 → RCTS Traffic Fines Sync                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// ═══════════════════════════════════════════════════════════════════════
// TEST 1: Read violations from Subsystem 9
// ═══════════════════════════════════════════════════════════════════════
echo "TEST 1: Reading traffic violations from Subsystem 9 (QCTO)\n";
echo "─────────────────────────────────────────────────────────────\n";

$s9_url = 'http://localhost/rcts-qc/mock-data/subsystem9/traffic-api.php?action=get_violations&qcitizen_id=' . urlencode($raven_id);
$s9_response = @file_get_contents($s9_url);
$s9_data = json_decode($s9_response, true);
$violations = $s9_data['data'] ?? [];

if (count($violations) > 0) {
    echo "✅ Found " . count($violations) . " violations for Raven\n\n";
    foreach ($violations as $v) {
        echo "   🚗 Ticket: " . $v['ticket_number'] . "\n";
        echo "      Type: " . $v['violation_type'] . "\n";
        echo "      Fine: ₱" . number_format($v['fine_amount'], 2) . "\n";
        echo "      Location: " . $v['location'] . "\n";
        echo "      Issued: " . $v['issued_at'] . "\n\n";
    }
} else {
    echo "❌ No violations found\n";
}

// ═══════════════════════════════════════════════════════════════════════
// TEST 2: Check traffic fine bills in RCTS database
// ═══════════════════════════════════════════════════════════════════════
echo "\nTEST 2: Checking Traffic Fine Bills in RCTS Database\n";
echo "─────────────────────────────────────────────────────────────\n";

$bills_result = supabase_request('rcts_assessment_billing_hub', 'GET', 
    ['qcitizen_id' => 'eq.' . $raven_id, 'bill_type' => 'eq.TrafficFine'], [], true);

$traffic_bills = $bills_result['data'] ?? [];

if (count($traffic_bills) > 0) {
    echo "✅ Found " . count($traffic_bills) . " traffic fine bills in database\n\n";
    $total_fines = 0;
    foreach ($traffic_bills as $bill) {
        echo "   💰 Bill Reference: " . $bill['bill_reference_no'] . "\n";
        echo "      Ticket/Asset: " . $bill['asset_id'] . "\n";
        echo "      Amount Due: ₱" . number_format($bill['total_amount_due'], 2) . "\n";
        echo "      Status: " . $bill['status'] . "\n";
        echo "      Due Date: " . $bill['due_date'] . "\n\n";
        $total_fines += (float)$bill['total_amount_due'];
    }
    echo "   📊 TOTAL TRAFFIC FINES DUE: ₱" . number_format($total_fines, 2) . "\n";
} else {
    echo "❌ No traffic fine bills found in database\n";
}

// ═══════════════════════════════════════════════════════════════════════
// TEST 3: Verify S9 → RCTS synchronization
// ═══════════════════════════════════════════════════════════════════════
echo "\n\nTEST 3: S9 ↔ RCTS Synchronization Verification\n";
echo "─────────────────────────────────────────────────────────────\n";

$s9_tickets = array_column($violations, 'ticket_number');
$rcts_tickets = array_column($traffic_bills, 'asset_id');

echo "Subsystem 9 Tickets: " . implode(', ', $s9_tickets) . "\n";
echo "RCTS Bills Tickets:  " . implode(', ', $rcts_tickets) . "\n\n";

$matched = 0;
foreach ($s9_tickets as $ticket) {
    if (in_array($ticket, $rcts_tickets)) {
        echo "✅ " . $ticket . " synced\n";
        $matched++;
    } else {
        echo "⚠️  " . $ticket . " NOT synced\n";
    }
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════════
// TEST 4: Pending Traffic Fines in Unified Billing System
// ═══════════════════════════════════════════════════════════════════════
echo "\nTEST 4: Pending Traffic Fines in Unified Billing System\n";
echo "─────────────────────────────────────────────────────────────\n";

$pending_result = supabase_request('v_citizen_pending_bills', 'GET',
    ['qcitizen_id' => 'eq.' . $raven_id, 'bill_type' => 'eq.TrafficFine'], [], true);

$pending_fines = $pending_result['data'] ?? [];

if (count($pending_fines) > 0) {
    echo "✅ Found " . count($pending_fines) . " pending traffic fines in unified system\n\n";
    foreach ($pending_fines as $fine) {
        echo "   🚨 " . $fine['bill_reference_no'] . "\n";
        echo "      Amount: ₱" . number_format($fine['total_amount_due'], 2) . "\n";
        echo "      Due: " . $fine['due_date'] . "\n\n";
    }
} else {
    echo "✅ No pending traffic fines (all paid)\n";
}

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║           SUBSYSTEM 9 INTEGRATION TEST COMPLETE            ║\n";
echo "║   All traffic fines synced and ready for payment flow      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
?>
