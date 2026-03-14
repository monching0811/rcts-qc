<?php
require_once 'api/config/supabase.php';

echo "Checking citizen registry...\n\n";

// Get all citizens
$citizens = supabase_request('rcts_citizen_registry', 'GET', ['select' => 'qcitizen_id,full_name', 'limit' => '10']);

if ($citizens['success']) {
    echo "Citizens in registry:\n";
    foreach ($citizens['data'] as $citizen) {
        echo "  • {$citizen['qcitizen_id']}: {$citizen['full_name']}\n";
    }
    echo "Total: " . count($citizens['data']) . "\n";
} else {
    echo "Error fetching citizens\n";
}

echo "\n\nChecking pending bills...\n\n";

// Get all pending bills
$bills = supabase_request('rcts_assessment_billing_hub', 'GET', ['status' => 'eq.Pending', 'select' => 'bill_reference_no,qcitizen_id,bill_type,total_amount_due']);

if ($bills['success']) {
    echo "Pending bills in database:\n";
    $bill_citizens = [];
    foreach ($bills['data'] as $bill) {
        $id = $bill['qcitizen_id'];
        if (!in_array($id, $bill_citizens)) {
            $bill_citizens[] = $id;
        }
        echo "  • {$bill['bill_reference_no']} ({$bill['bill_type']}): Citizen {$id} = ₱{$bill['total_amount_due']}\n";
    }
    
    echo "\nUnique citizen IDs with pending bills:\n";
    foreach ($bill_citizens as $id) {
        echo "  • $id\n";
    }
} else {
    echo "Error fetching bills\n";
}

echo "\n\nChecking if Brylle (QC-2026-00156) exists in citizen registry...\n";

$brylle_check = supabase_request('rcts_citizen_registry', 'GET', ['qcitizen_id' => 'eq.QC-2026-00156']);

if ($brylle_check['success'] && count($brylle_check['data']) > 0) {
    echo "✓ Brylle found in registry\n";
    print_r($brylle_check['data'][0]);
} else {
    echo "✗ Brylle NOT found in citizen registry!\n";
    echo "This explains why the view v_citizen_pending_bills returns no results.\n";
}

?>
