<?php
require_once 'includes/db.php';

$result = db_select('rcts_assessment_billing_hub', [
    'qcitizen_id' => 'eq.QC-2024-000001',
    'bill_type' => 'eq.TrafficFine'
]);

if ($result['success']) {
    echo 'Total TrafficFine bills for QC-2024-000001: ' . count($result['data']) . PHP_EOL;
    foreach ($result['data'] as $bill) {
        echo 'Reference: ' . $bill['bill_reference_no'] . ', Amount: ' . $bill['total_amount_due'] . ', Status: ' . $bill['status'] . PHP_EOL;
    }
} else {
    echo 'Query failed. Result: ' . print_r($result, true) . PHP_EOL;
}
?>