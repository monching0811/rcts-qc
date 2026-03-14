<?php
require_once __DIR__ . '/includes/db.php';

$result = db('rcts_assessment_billing_hub', ['qcitizen_id'=>'eq.b529bf30-50bf-43ab-a314-cc4c2f79c3f5', 'bill_type'=>'eq.RPT']);

echo "Vince's RPT bills:\n";
foreach ($result['data'] as $bill) {
    echo "- {$bill['bill_reference_no']}: {$bill['status']} - ₱" . number_format($bill['total_amount_due'], 2) . "\n";
}
?>