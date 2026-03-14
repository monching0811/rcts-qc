<?php
require_once 'includes/db.php';

$raven_id = '92be37af-7c34-4c9b-80cb-47cde7c3a9fd';

echo "═══════════════════════════════════════════════════════════════\n";
echo "Business Tax Bills Returned by API\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Simulate the API call that business-tax.html makes
$result = db_select('rcts_assessment_billing_hub', [
    'qcitizen_id' => 'eq.' . $raven_id,
    'bill_type'   => 'eq.BusinessTax',
]);

echo "API Returns:\n";
foreach ($result['data'] as $bill) {
    echo "\nBill: " . $bill['bill_reference_no'] . "\n";
    foreach ($bill as $key => $value) {
        echo "  $key: " . ($value ?? 'NULL') . "\n";
    }
}
