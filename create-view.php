<?php
require_once 'api/config/supabase.php';
$query = "CREATE OR REPLACE VIEW v_active_market_stalls AS SELECT s.stall_asset_id, s.facility_name, s.stall_number, s.qcitizen_id, c.full_name AS vendor_name, c.mobile_no, s.monthly_rental_rate, s.occupancy_status_flag, s.occupancy_last_verified, s.occupancy_verification_method FROM rcts_public_asset_stall s LEFT JOIN rcts_citizen_registry c ON s.qcitizen_id = c.qcitizen_id WHERE s.occupancy_status_flag = 'Active' ORDER BY s.facility_name, s.stall_number;";
$result = supabase_request('rpc/exec_sql', 'POST', [], ['query' => $query], true);
echo 'Create view result: ' . json_encode($result) . PHP_EOL;
?>