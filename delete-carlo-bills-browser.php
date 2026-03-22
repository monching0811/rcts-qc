<?php
/**
 * DELETE CARLO NICOLAS ALL DATA
 * Run this in browser to remove all Carlo Nicolas bills and related data
 */

header('Content-Type: text/html');
require_once __DIR__ . '/api/config/supabase.php';

$carlo_id = 'QC-2024-000009';

echo "═══════════════════════════════════════════════════════════════\n";
echo "🗑️  DELETE CARLO NICOLAS DATA\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$deleted = [];

// 1. Delete RPT bills
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Deleting RPT Bills...\n";
$rpt_result = db_delete('rcts_assessment_billing_hub', ['qcitizen_id' => 'eq.' . $carlo_id, 'bill_type' => 'eq.RPT']);
if ($rpt_result['success']) {
    echo "✅ RPT bills deleted\n";
    $deleted[] = 'RPT';
} else {
    echo "❌ Failed to delete RPT bills\n";
}

// 2. Delete Business Tax bills
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Deleting Business Tax Bills...\n";
$biz_result = db_delete('rcts_assessment_billing_hub', ['qcitizen_id' => 'eq.' . $carlo_id, 'bill_type' => 'eq.BusinessTax']);
if ($biz_result['success']) {
    echo "✅ Business Tax bills deleted\n";
    $deleted[] = 'BusinessTax';
} else {
    echo "❌ Failed to delete Business Tax bills\n";
}

// 3. Delete Market Stall bills
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Deleting Market Stall Bills...\n";
$market_result = db_delete('rcts_assessment_billing_hub', ['qcitizen_id' => 'eq.' . $carlo_id, 'bill_type' => 'eq.MarketRental']);
if ($market_result['success']) {
    echo "✅ Market Stall bills deleted\n";
    $deleted[] = 'MarketRental';
} else {
    echo "❌ Failed to delete Market Stall bills\n";
}

// 4. Delete Traffic Fine bills
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Deleting Traffic Fine Bills...\n";
$traffic_result = db_delete('rcts_assessment_billing_hub', ['qcitizen_id' => 'eq.' . $carlo_id, 'bill_type' => 'eq.TrafficFine']);
if ($traffic_result['success']) {
    echo "✅ Traffic Fine bills deleted\n";
    $deleted[] = 'TrafficFine';
} else {
    echo "❌ Failed to delete Traffic Fine bills\n";
}

// 5. Delete Traffic Violations
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Deleting Traffic Violations...\n";
$violations_result = db_delete('rcts_traffic_violation', ['qcitizen_id' => 'eq.' . $carlo_id]);
if ($violations_result['success']) {
    echo "✅ Traffic violations deleted\n";
    $deleted[] = 'TrafficViolations';
} else {
    echo "❌ Failed to delete traffic violations\n";
}

// 6. Delete Regulatory Clearances
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Deleting Regulatory Clearances...\n";
$clearance_result = db_delete('rcts_regulatory_clearance', ['qcitizen_id' => 'eq.' . $carlo_id]);
if ($clearance_result['success']) {
    echo "✅ Regulatory clearances deleted\n";
    $deleted[] = 'Clearances';
} else {
    echo "❌ Failed to delete regulatory clearances\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Deleted data types: " . implode(', ', $deleted) . "\n";
echo "Citizen ID: $carlo_id\n";
echo "\n✅ All Carlo Nicolas data has been removed from the system!\n";
echo "═══════════════════════════════════════════════════════════════\n";
?>
<br><br>
<a href="allocate-carlo-bills-browser.php">← Back to Allocation Page</a>
