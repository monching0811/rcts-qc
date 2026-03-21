<?php
require_once 'includes/db.php';

$result = db_select('rcts_traffic_violation', ['limit' => 5]);
if ($result['success']) {
    foreach ($result['data'] as $violation) {
        echo 'Citizen ID: ' . $violation['qcitizen_id'] . ', Ticket: ' . $violation['violation_ticket_id'] . PHP_EOL;
    }
} else {
    echo 'Error: ' . $result['error'] . PHP_EOL;
}
?>