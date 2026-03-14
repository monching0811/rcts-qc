<?php
require_once 'api/config/supabase.php';
$result = db_select('rcts_public_asset_stall');
echo 'Stalls in database: ' . count($result['data']) . PHP_EOL;
foreach ($result['data'] as $stall) {
    echo 'Stall: ' . $stall['stall_asset_id'] . ' - ' . $stall['qcitizen_id'] . ' - ' . $stall['occupancy_status_flag'] . PHP_EOL;
}
?>