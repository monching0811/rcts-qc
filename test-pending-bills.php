<?php
require_once 'api/config/supabase.php';

$citizen_id = 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5';

// Check pending bills
$pending_bills = supabase_request(
    'v_citizen_pending_bills?qcitizen_id=eq.' . $citizen_id,
    'GET',
    []
);

echo "========================================\n";
echo "PENDING BILLS FOR CITIZEN\n";
echo "========================================\n\n";

$bills = $pending_bills['data'] ?? [];

if (count($bills) > 0) {
    echo "✅ Found " . count($bills) . " pending bills:\n\n";
    $total = 0;
    foreach ($bills as $bill) {
        echo "  • " . $bill['bill_reference_no'] . "\n";
        echo "    Amount: ₱" . number_format($bill['total_amount_due'] ?? 0, 2) . "\n";
        echo "    Status: " . $bill['status'] . "\n";
        echo "    Dept: " . ($bill['originating_dept_id'] ?? 'N/A') . "\n\n";
        $total += $bill['total_amount_due'] ?? 0;
    }
    echo "Total Due: ₱" . number_format($total, 2) . "\n";
} else {
    echo "❌ No pending bills found\n";
}

echo "\n========================================\n";
echo "EXPECTED FLOW:\n";
echo "========================================\n";
echo "1. ✅ Subsystem 7 returns 3 properties\n";
echo "2. ✅ Database has 3 pending bills\n";
echo "3. ✅ Properties show in 'Your Registered Properties'\n";
echo "4. ✅ User can pay bills\n";
echo "5. ✅ Bills move to 'Paid' status\n";
echo "6. ✅ Properties disappear from pending section\n";
echo "\nNow visit: http://localhost/rcts-qc/pages/citizen/rpt-payment.html\n";
