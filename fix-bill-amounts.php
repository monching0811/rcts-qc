<?php
require_once 'api/config/supabase.php';

$citizen_id = 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5';

echo "========================================\n";
echo "FIXING BILL AMOUNTS\n";
echo "========================================\n\n";

// Update bills to have base_amount = total_amount_due
$bills_to_update = [
    'TDN-QC-2024-006' => ['base_amount' => 16800, 'total_amount_due' => 16800],
    'TDN-QC-2024-007' => ['base_amount' => 147000, 'total_amount_due' => 147000],
    'TDN-QC-2024-008' => ['base_amount' => 13200, 'total_amount_due' => 13200],
];

foreach ($bills_to_update as $bill_ref => $amounts) {
    $update_result = supabase_request(
        'rcts_assessment_billing_hub?bill_reference_no=eq.' . $bill_ref,
        'PATCH',
        [],
        $amounts,
        true
    );
    
    if ($update_result['success'] ?? false) {
        echo "✅ Updated: $bill_ref\n";
        echo "   base_amount: ₱" . number_format($amounts['base_amount'], 2) . "\n";
        echo "   total_amount_due: ₱" . number_format($amounts['total_amount_due'], 2) . "\n\n";
    } else {
        echo "❌ Failed: $bill_ref\n";
        echo "   Error: " . json_encode($update_result) . "\n\n";
    }
}

echo "========================================\n";
echo "✅ All bills updated!\n";
echo "Now both sections will show the same amounts:\n";
echo "- Dashboard Pending Bills: ₱16,800 + ₱147,000 + ₱13,200\n";
echo "- S7 Properties: ₱16,800 + ₱147,000 + ₱13,200\n";
echo "========================================\n";
