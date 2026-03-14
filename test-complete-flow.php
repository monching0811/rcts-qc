<?php
require_once 'includes/db.php';

$raven_id = '92be37af-7c34-4c9b-80cb-47cde7c3a9fd';

echo "═══════════════════════════════════════════════════════════════\n";
echo "Complete Business Tax Data Flow for Raven\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. Get businesses
$bizResult = db_select('rcts_business_entity', ['qcitizen_id' => 'eq.' . $raven_id]);
echo "1. BUSINESSES (from rcts_business_entity / S2):\n";
foreach ($bizResult['data'] as $biz) {
    echo "  • BIN: " . $biz['bin_number'] . " | Name: " . $biz['business_name'] . "\n";
}

// 2. Get bills
echo "\n2. BILLS (status mapping):\n";
$billResult = db_select('rcts_assessment_billing_hub', [
    'qcitizen_id' => 'eq.' . $raven_id,
    'bill_type'   => 'eq.BusinessTax'
]);
$billsByBin = [];
foreach ($billResult['data'] as $bill) {
    $binKey = $bill['asset_id'];
    if (!isset($billsByBin[$binKey])) {
        $billsByBin[$binKey] = [];
    }
    $billsByBin[$binKey][] = $bill;
    echo "  • Asset/BIN: " . $bill['asset_id'] . " → Status: " . $bill['status'] . " | Updated: " . $bill['updated_at'] . "\n";
}

// 3. Show final mapping
echo "\n3. BUSINESS-TO-BILL MAPPING:\n";
foreach ($bizResult['data'] as $biz) {
    $bin = $biz['bin_number'];
    $bills = $billsByBin[$bin] ?? [];
    $pendingBill = null;
    $paidBill = null;
    foreach ($bills as $b) {
        if ($b['status'] === 'Pending') $pendingBill = $b;
        if ($b['status'] === 'Paid') $paidBill = $b;
    }
    
    echo "\n  Business: " . $biz['business_name'] . " (BIN: $bin)\n";
    if ($paidBill) {
        $paidDate = date('m/d/Y', strtotime($paidBill['updated_at']));
        echo "    → 💳 Payment Status: ✅ Bill Paid on $paidDate | OP: " . $paidBill['bill_reference_no'] . "\n";
    } elseif ($pendingBill) {
        echo "    → 💡 Zero-Touch Billing: ✅ All clearances passed. Your Unified OP (" . $pendingBill['bill_reference_no'] . ") has been auto-generated.\n";
    } else {
        echo "    → No bill found\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n";
