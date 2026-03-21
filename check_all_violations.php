<?php
require_once 'includes/db.php';

$result = db_select('rcts_traffic_violation', [
    'qcitizen_id' => 'eq.QC-2024-000001'
]);

if ($result['success']) {
    echo 'Total violations for QC-2024-000001: ' . count($result['data']) . PHP_EOL;
    foreach ($result['data'] as $violation) {
        echo 'Ticket: ' . $violation['violation_ticket_id'] . ', Type: ' . $violation['violation_type'] . ', Status: ' . $violation['payment_status'] . PHP_EOL;
    }
} else {
    echo 'Error: ' . $result['error'] . PHP_EOL;
}
?>