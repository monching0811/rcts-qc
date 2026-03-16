<?php
require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/includes/db.php';

$r = db_select('rcts_traffic_violation', []);
echo 'Total violations: ' . count($r['data'] ?? []) . "\n";

foreach ($r['data'] ?? [] as $v) {
    echo $v['violation_ticket_id'] . ' - ' . ($v['qcitizen_id'] ?: 'NULL') . "\n";
}
?>