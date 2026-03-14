<?php
require_once 'api/config/supabase.php';

$manual_bills = ['TDN-VINCE-1', 'TDN-VINCE-2', 'TDN-VINCE-3', 'TDN-VINCE-4', 'TDN-VINCE-5', 'BIN-RAVEN-1', 'BIN-RAVEN-2', 'BIN-RAVEN-3', 'BIN-RAVEN-4', 'BIN-RAVEN-5', 'TF-RAVEN-1', 'TF-RAVEN-2', 'TF-RAVEN-3', 'TF-RAVEN-4', 'TF-RAVEN-5', 'MKT-BRYLLE-1', 'MKT-BRYLLE-2', 'MKT-BRYLLE-3', 'MKT-BRYLLE-4', 'MKT-BRYLLE-5'];

echo "Deleting manual bills...\n";
foreach ($manual_bills as $bill) {
    $result = supabase_request('rcts_assessment_billing_hub?bill_reference_no=eq.' . $bill, 'DELETE', []);
    echo "Deleted $bill: " . ($result['success'] ? 'OK' : 'Failed') . "\n";
}
echo "Manual bills deleted.\n";
?>