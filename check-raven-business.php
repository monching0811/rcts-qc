<?php
require_once 'api/config/supabase.php';

$result = db_select('rcts_assessment_billing_hub', [
    'qcitizen_id' => 'eq.92be37af-7c34-4c9b-80cb-47cde7c3a9fd',
    'bill_type' => 'eq.TrafficFine'
]);

echo 'Raven traffic fines: ' . json_encode($result['data'], JSON_PRETTY_PRINT) . PHP_EOL;