<?php
/**
 * CHECK ALL PENDING BILLS FOR THE THREE ORIGINAL CITIZENS
 */

require_once 'api/config/supabase.php';

$citizens = [
    ['id' => '92be37af-7c34-4c9b-80cb-47cde7c3a9fd', 'name' => 'Raven Pogi'],
    ['id' => 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5', 'name' => 'Vince Nico Escala'],
    ['id' => 'eacd934b-0195-4640-b37c-aa0a8b40a9d2', 'name' => 'Dave Mercado']
];

echo "═══════════════════════════════════════════════════════════════\n";
echo "PENDING BILLS SUMMARY FOR ALL THREE CITIZENS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$total_bills = 0;
$total_amount = 0;

foreach ($citizens as $citizen) {
    $citizen_id = $citizen['id'];
    $citizen_name = $citizen['name'];

    // Check pending bills
    $pending_bills = supabase_request(
        'v_citizen_pending_bills?qcitizen_id=eq.' . $citizen_id,
        'GET',
        []
    );

    $bills = $pending_bills['data'] ?? [];
    $citizen_total = 0;

    echo "👤 {$citizen_name} ({$citizen_id})\n";
    echo "   Found " . count($bills) . " pending bills:\n";

    if (count($bills) > 0) {
        foreach ($bills as $bill) {
            $amount = $bill['total_amount_due'] ?? 0;
            $bill_type = $bill['bill_type'] ?? 'Unknown';
            echo "   • {$bill['bill_reference_no']} - {$bill_type}: ₱" . number_format($amount, 2) . "\n";
            $citizen_total += $amount;
        }
    } else {
        echo "   • No pending bills\n";
    }

    echo "   Total Due: ₱" . number_format($citizen_total, 2) . "\n\n";

    $total_bills += count($bills);
    $total_amount += $citizen_total;
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "GRAND TOTAL:\n";
echo "  Total Bills: {$total_bills}\n";
echo "  Total Amount Due: ₱" . number_format($total_amount, 2) . "\n";
echo "═══════════════════════════════════════════════════════════════\n";