<?php
require_once 'includes/db.php';

$result = db_select('rcts_traffic_violation', []);

if ($result['success']) {
    $byCitizen = [];
    foreach ($result['data'] as $violation) {
        $citizenId = $violation['qcitizen_id'];
        if (!isset($byCitizen[$citizenId])) {
            $byCitizen[$citizenId] = 0;
        }
        $byCitizen[$citizenId]++;
    }
    
    echo 'Traffic violations by citizen:' . PHP_EOL;
    foreach ($byCitizen as $citizenId => $count) {
        echo "$citizenId: $count violations" . PHP_EOL;
    }
} else {
    echo 'Query failed. Result: ' . print_r($result, true) . PHP_EOL;
}
?>