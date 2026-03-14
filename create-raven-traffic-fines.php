<?php
/**
 * CREATE TRAFFIC FINES FOR RAVEN
 * Generates traffic fine bills in the database for Raven Pogi
 */

require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/api/config/constants.php';

$raven_id = '92be37af-7c34-4c9b-80cb-47cde7c3a9fd';

echo "═══════════════════════════════════════════════════════════════\n";
echo "Creating Traffic Fine Bills for Raven Pogi\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Get traffic violations for Raven from S9 API
$s9_url = 'http://localhost/rcts-qc/mock-data/subsystem9/traffic-api.php?action=get_violations&qcitizen_id=' . urlencode($raven_id);
$s9_response = @file_get_contents($s9_url);
$s9_data = json_decode($s9_response, true);
$violations = $s9_data['data'] ?? [];

echo "Found " . count($violations) . " violations from S9 for Raven:\n\n";

$created_bills = [];

foreach ($violations as $i => $violation) {
    $ticket_num = $violation['ticket_number'];
    $violation_type = $violation['violation_type'];
    $fine_amount = $violation['fine_amount'];
    
    // Create unique bill reference
    $bill_ref = 'RCTS-TF-' . CURRENT_YEAR . '-' . strtoupper(substr(uniqid(), -6));
    
    // Insert bill
    $bill_data = [
        'bill_reference_no'   => $bill_ref,
        'qcitizen_id'         => $raven_id,
        'bill_type'           => 'TrafficFine',
        'originating_dept_id' => 3,
        'asset_id'            => $ticket_num,
        'tax_year'            => 2026,
        'base_amount'         => $fine_amount,
        'discount_amount'     => 0,
        'penalty_amount'      => 0,
        'total_amount_due'    => $fine_amount,
        'status'              => 'Pending',
        'due_date'            => '2026-04-12'
    ];
    
    $result = db_insert('rcts_assessment_billing_hub', $bill_data);
    
    if ($result['success']) {
        echo "✅ Bill Created: $bill_ref\n";
        echo "   Ticket: $ticket_num\n";
        echo "   Violation: $violation_type\n";
        echo "   Amount: ₱" . number_format($fine_amount, 2) . "\n";
        echo "   Due: 2026-04-12\n\n";
        $created_bills[] = $bill_ref;
    } else {
        echo "❌ Failed to create bill for $ticket_num\n";
        echo "   Error: " . ($result['message'] ?? 'Unknown error') . "\n\n";
    }
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "Summary:\n";
echo "  Created Bills: " . count($created_bills) . "\n";
if (count($created_bills) > 0) {
    echo "  Bill References:\n";
    foreach ($created_bills as $ref) {
        echo "    - $ref\n";
    }
}
echo "═══════════════════════════════════════════════════════════════\n";
?>
