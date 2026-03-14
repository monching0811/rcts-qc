<?php
require_once 'api/config/supabase.php';
$result = db_select('rcts_citizen_registry');
echo 'Citizens in registry: ' . count($result['data']) . PHP_EOL;
foreach ($result['data'] as $citizen) {
    echo 'ID: ' . $citizen['qcitizen_id'] . ' - Name: ' . $citizen['full_name'] . PHP_EOL;
}
?>