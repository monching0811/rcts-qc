<?php
require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/includes/db.php';

// Query traffic violations with qcitizen_id
$result = db_select('rcts_traffic_violation', []);
$violations = $result['data'] ?? [];

$citizens = array_unique(array_column($violations, 'qcitizen_id'));

echo "All citizens with traffic violations:\n";
foreach ($citizens as $cid) {
    if ($cid) echo "- $cid\n";
}
?>