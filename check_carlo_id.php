<?php
require_once 'includes/db.php';

$result = db_select('rcts_citizen_registry', ['full_name' => 'eq.Carlo Nicolas']);
if ($result['success'] && count($result['data']) > 0) {
    echo 'Carlo Nicolas citizen ID: ' . $result['data'][0]['qcitizen_id'] . PHP_EOL;
} else {
    echo 'Carlo Nicolas not found' . PHP_EOL;
}
?>