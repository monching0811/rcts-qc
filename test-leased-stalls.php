<?php
/**
 * Test: Your Leased Stalls Integration
 * Verify S10 API returns stalls and they link to MarketRental bills
 */

$citizen_id = 'a135da1e-6727-430e-9771-e15688e6f79e';
echo "=== Testing Leased Stalls Section ===\n";
echo "Citizen ID: $citizen_id\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Step 1: Fetch stalls from S10 API
echo "STEP 1: Fetching stalls from Subsystem 10...\n";
$stalls_url = "http://localhost/rcts-qc/mock-data/subsystem10/public-assets-api.php?action=get_stalls_by_citizen&qcitizen_id=$citizen_id";
$stalls_response = json_decode(file_get_contents($stalls_url), true);

if ($stalls_response['success']) {
  $stalls = $stalls_response['data'];
  echo "✓ Found " . count($stalls) . " stalls for Brylle\n";
  foreach ($stalls as $s) {
    echo "  • {$s['stall_name']} ({$s['stall_asset_id']})\n";
    echo "    Rate: ₱{$s['monthly_rental_rate']}/month, " . $s['occupancy_status_flag'] . ", " . $s['occupancy_verification_method'] . "\n";
  }
} else {
  echo "✗ S10 API Error: " . ($stalls_response['message'] ?? 'Unknown error') . "\n";
  exit(1);
}

// Step 2: Fetch all bills for this citizen
echo "\nSTEP 2: Fetching pending bills...\n";
$bills_url = "http://localhost/rcts-qc/api/endpoints/payment.php?action=get_pending_bills&qcitizen_id=$citizen_id";
$bills_response = json_decode(file_get_contents($bills_url), true);

if ($bills_response['success']) {
  $all_bills = $bills_response['data']['bills'] ?? [];
  $market_bills = array_filter($all_bills, fn($b) => $b['bill_type'] === 'MarketRental');
  echo "✓ Found " . count($all_bills) . " total bills, " . count($market_bills) . " are MarketRental\n";
  
  // Create asset_id -> bill map
  $bills_by_asset = [];
  foreach ($market_bills as $bill) {
    $bills_by_asset[$bill['asset_id']] = $bill;
  }
  
  // Step 3: Link stalls to bills
  echo "\nSTEP 3: Linking stalls to bills...\n";
  $matched_count = 0;
  foreach ($stalls as $stall) {
    $stall_id = $stall['stall_asset_id'];
    if (isset($bills_by_asset[$stall_id])) {
      $bill = $bills_by_asset[$stall_id];
      echo "✓ {$stall['stall_name']}\n";
      echo "  → Bill Ref: {$bill['bill_reference_no']}\n";
      echo "  → Amount: ₱" . number_format($bill['total_amount_due'], 2) . "\n";
      echo "  → Due: {$bill['due_date']}\n";
      echo "  → Verification: {$stall['occupancy_verification_method']}\n";
      $matched_count++;
    } else {
      echo "⚠ {$stall['stall_name']} - No bill found\n";
    }
  }
  
  echo "\nSTEP 4: Summary\n";
  echo "Stalls with bills: $matched_count/" . count($stalls) . "\n";
  echo "Status: " . ($matched_count === count($stalls) ? "✅ ALL STALLS LINKED" : "⚠️ PARTIAL LINKING") . "\n";
  
} else {
  echo "✗ Payment API Error\n";
  exit(1);
}
?>
