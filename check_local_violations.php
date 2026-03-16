<?php
require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/includes/db.php';

$v = db_select('rcts_traffic_violation', []);
echo 'Total violations in local DB: ' . count($v['data'] ?? []) . "\n";
foreach($v['data'] ?? [] as $violation) {
    echo $violation['violation_ticket_id'] . ' - ' . $violation['qcitizen_id'] . "\n";
}
?>