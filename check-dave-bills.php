<?php
require_once 'api/config/supabase.php';
$result = db_select('rcts_assessment_billing_hub', ['qcitizen_id' => 'eq.eacd934b-0195-4640-b37c-aa0a8b40a9d2', 'bill_type' => 'eq.MarketRental']);
echo 'Market bills for Dave: ' . count($result['data']) . PHP_EOL;
foreach ($result['data'] as $bill) {
    echo 'Bill: ' . $bill['bill_reference_no'] . ' - ' . $bill['status'] . ' - ' . $bill['asset_id'] . PHP_EOL;
}
?>