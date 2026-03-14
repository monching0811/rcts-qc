<?php
require_once 'api/config/supabase.php';

$result = db_select('rcts_assessment_billing_hub', [
    'qcitizen_id' => 'eq.eacd934b-0195-4640-b37c-aa0a8b40a9d2',
    'bill_type' => 'eq.MarketRental'
]);

echo 'Dave market rental bills: ' . json_encode($result['data'], JSON_PRETTY_PRINT) . PHP_EOL;