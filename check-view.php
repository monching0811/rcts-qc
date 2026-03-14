<?php
require_once 'api/config/supabase.php';
$result = db_select('rcts_public_asset_stall', ['occupancy_status_flag' => 'eq.Active']);
if (!$result['success']) {
    echo 'Error: ' . json_encode($result) . PHP_EOL;
    exit;
}
echo 'Active stalls from table: ' . count($result['data']) . PHP_EOL;
foreach ($result['data'] as $stall) {
    echo 'Stall: ' . $stall['stall_asset_id'] . ' - ' . $stall['qcitizen_id'] . PHP_EOL;
}
?>