<?php
require_once 'includes/db.php';

$raven_id = '92be37af-7c34-4c9b-80cb-47cde7c3a9fd';

echo "Frontend Data Flow Test (Post-Fix)\n";
echo "═════════════════════════════════════════\n\n";

// Fetch businesses from S2 API
$s2_url = 'http://localhost/rcts-qc/mock-data/subsystem2/permits-api.php?action=get_permits&qcitizen_id=' . urlencode($raven_id);
$bizResponse = @file_get_contents($s2_url);
$bizData = json_decode($bizResponse, true);
$businesses = $bizData['data'] ?? [];

// Fetch bills (now includes both Pending and Paid)
$billResult = db_select('rcts_assessment_billing_hub', [
    'qcitizen_id' => 'eq.' . $raven_id,
    'bill_type'   => 'eq.BusinessTax'
]);
$allBills = $billResult['data'] ?? [];

// Group bills by asset_id
$billsByBin = [];
foreach ($allBills as $b) {
    if (!isset($billsByBin[$b['asset_id']])) {
        $billsByBin[$b['asset_id']] = [];
    }
    $billsByBin[$b['asset_id']][] = $b;
}

// Match and display
foreach ($businesses as $biz) {
    $bizBills = $billsByBin[$biz['bin_number']] ?? [];
    $pendingBill = null;
    $paidBill = null;
    foreach ($bizBills as $b) {
        if ($b['status'] === 'Pending') $pendingBill = $b;
        if ($b['status'] === 'Paid') $paidBill = $b;
    }
    
    echo $biz['business_name'] . " (BIN: " . $biz['bin_number'] . ")\n";
    if ($paidBill) {
        $paidDate = date('m/d/Y', strtotime($paidBill['updated_at']));
        echo "  💳 PAID on $paidDate: " . $paidBill['bill_reference_no'] . "\n";
    } elseif ($pendingBill) {
        echo "  💡 PENDING: " . $pendingBill['bill_reference_no'] . "\n";
    }
    echo "\n";
}
?>
