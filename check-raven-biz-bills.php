<?php
require_once 'api/config/supabase.php';

$raven_id = '92be37af-7c34-4c9b-80cb-47cde7c3a9fd';

$result = supabase_request('rcts_assessment_billing_hub?qcitizen_id=eq.' . $raven_id, 'GET', []);
$bills = $result['data'] ?? [];

echo "Raven's All Bills:\n";
foreach ($bills as $bill) {
    if ($bill['bill_type'] == 'BusinessTax') {
        echo $bill['bill_reference_no'] . ' - ' . $bill['status'] . ' - ' . $bill['asset_id'] . "\n";
    }
}
?>