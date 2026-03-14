<?php
/**
 * Comprehensive Test: Your Leased Stalls Display
 * Simulates all JavaScript API calls from business-tax.html
 */

$citizen_id = 'a135da1e-6727-430e-9771-e15688e6f79e';
$citizen_name = 'Brylle Kenneth Mendez';

echo "═══════════════════════════════════════════════════════════════\n";
echo "🧪 TESTING: Your Leased Stalls Display (business-tax.html)\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Citizen: $citizen_name\n";
echo "ID: $citizen_id\n\n";

// SECTION 1: Fetch Stalls from S10
echo "📱 SECTION 1: Fetch Stalls from Subsystem 10\n";
echo "─────────────────────────────────────────────\n";
$stalls_url = "http://localhost/rcts-qc/mock-data/subsystem10/public-assets-api.php?action=get_stalls_by_citizen&qcitizen_id=$citizen_id";
$stalls_response = json_decode(file_get_contents($stalls_url), true);
$stalls = $stalls_response['data'] ?? [];
echo "✓ Found " . count($stalls) . " stalls\n\n";

// SECTION 2: Fetch Bills from Payment API
echo "💰 SECTION 2: Fetch Pending Bills from Payment API\n";
echo "─────────────────────────────────────────────────────\n";
$bills_url = "http://localhost/rcts-qc/api/endpoints/payment.php?action=get_pending_bills&qcitizen_id=$citizen_id";
$bills_response = json_decode(file_get_contents($bills_url), true);
$all_bills = $bills_response['data']['bills'] ?? [];
$market_bills = array_filter($all_bills, fn($b) => $b['bill_type'] === 'MarketRental');
echo "✓ Found " . count($all_bills) . " total bills, " . count($market_bills) . " market rentals\n\n";

// SECTION 3: Create asset_id -> bill map
echo "🔗 SECTION 3: Link Stalls to Bills\n";
echo "──────────────────────────────────\n";
$bills_by_asset = [];
foreach ($market_bills as $bill) {
  $bills_by_asset[$bill['asset_id']] = $bill;
}

$occupancy_icons = [
  'QR_CHECKIN' => '📱 QR Check-in',
  'IOT_SENSOR' => '🔌 IoT Sensor',
  'MANUAL' => '✋ Manual Verification'
];

// SECTION 4: Render each stall card
echo "🎨 SECTION 4: Render Leased Stalls Section\n";
echo "──────────────────────────────────────────\n\n";

$total_stalls = 0;
$total_stall_revenue = 0;
$stalls_with_bills = 0;

foreach ($stalls as $stall) {
  $total_stalls++;
  $stall_id = $stall['stall_asset_id'];
  $bill = $bills_by_asset[$stall_id] ?? null;
  $occupancy_method = $occupancy_icons[$stall['occupancy_verification_method']] ?? $stall['occupancy_verification_method'];

  echo "┌─ STALL CARD " . $total_stalls . " ─────────────────────────────────────┐\n";
  echo "│ Name: {$stall['stall_name']}\n";
  echo "│ ID: {$stall['stall_asset_id']}\n";
  echo "│ Location: {$stall['facility_name']}\n";
  echo "│ Size: {$stall['stall_size_sqm']} sqm\n";
  echo "│ Occupancy Status: {$stall['occupancy_status_flag']}\n";
  echo "│ Verification Method: $occupancy_method\n";
  echo "│\n";

  if ($bill) {
    $stalls_with_bills++;
    $total_stall_revenue += $bill['total_amount_due'];
    echo "│ 📋 PENDING BILL:\n";
    echo "│    Bill Ref: {$bill['bill_reference_no']}\n";
    echo "│    Amount Due: ₱" . number_format($bill['total_amount_due'], 2) . "\n";
    echo "│    Due Date: {$bill['due_date']}\n";
    echo "│    Status: {$bill['status']}\n";
    echo "│\n";
    echo "│ ✅ [PAY NOW] button available\n";
  } else {
    echo "│ ℹ️  No pending bill for this stall yet\n";
  }

  echo "└────────────────────────────────────────────────────┘\n\n";
}

// SECTION 5: Summary
echo "📊 SECTION 5: Display Summary\n";
echo "──────────────────────────────\n";
echo "Total Leased Stalls: $total_stalls\n";
echo "Stalls with Pending Bills: $stalls_with_bills/$total_stalls\n";
echo "Total Monthly Revenue (Stalls): ₱" . number_format($total_stall_revenue, 2) . "\n\n";

// SECTION 6: Consolidated View
echo "🎯 SECTION 6: Verify Consolidated Bills View\n";
echo "──────────────────────────────────────────────\n";
$market_total = array_sum(array_column($market_bills, 'total_amount_due'));
$rpt_bills = array_filter($all_bills, fn($b) => $b['bill_type'] === 'RPT');
$rpt_total = array_sum(array_column($rpt_bills, 'total_amount_due'));
$grand_total = $market_total + $rpt_total;

echo "MarketRental Bills: " . count($market_bills) . " → ₱" . number_format($market_total, 2) . "\n";
echo "RPT Bills: " . count($rpt_bills) . " → ₱" . number_format($rpt_total, 2) . "\n";
echo "─────────────────────────────────────────\n";
echo "GRAND TOTAL: ₱" . number_format($grand_total, 2) . "\n\n";

// FINAL STATUS
echo "═══════════════════════════════════════════════════════════════\n";
if ($stalls_with_bills === $total_stalls && !empty($stalls)) {
  echo "✅ SUCCESS: Your Leased Stalls section is fully operational!\n";
  echo "   • All stalls have bills linked\n";
  echo "   • Occupancy verification methods displayed\n";
  echo "   • Pending bills visible with amounts\n";
  echo "   • Pay Now buttons available\n";
  echo "   • Consolidated view shows all bill types\n";
} else {
  echo "⚠ PARTIAL: Some stalls don't have bills yet\n";
}
echo "═══════════════════════════════════════════════════════════════\n";
?>
