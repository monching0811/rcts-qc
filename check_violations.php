<?php
require_once 'includes/db.php';

$result = db_select('rcts_traffic_violation', []);
if ($result['success']) {
    foreach ($result['data'] as $v) {
        echo $v['qcitizen_id'] . ' - ' . $v['violation_ticket_id'] . PHP_EOL;
    }
}
?>