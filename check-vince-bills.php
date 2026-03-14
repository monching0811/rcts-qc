<?php
require_once 'api/config/supabase.php';

$result = db_select('rcts_assessment_billing_hub', [
    'qcitizen_id' => 'eq.b529bf30-50bf-43ab-a314-cc4c2f79c3f5',
    'bill_type' => 'eq.RPT',
    'status' => 'eq.Pending'
]);

echo 'Pending RPT bills for Vince: ' . json_encode($result['data'], JSON_PRETTY_PRINT) . PHP_EOL;