<?php
require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/includes/db.php';

$r = db_select('rcts_citizen_registry', []);
foreach($r['data'] ?? [] as $c) {
    echo $c['qcitizen_id'] . ' - ' . $c['full_name'] . "\n";
}
?>