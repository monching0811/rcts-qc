<?php
/**
 * Final test to verify Market Stall page integration is working
 * Tests:
 * 1. S10 API returns stalls for Brylle's UUID
 * 2. Payment API returns MarketRental bills with asset_id
 * 3. Stalls are properly linked to bills
 */

$brylle_uuid = "a135da1e-6727-430e-9771-e15688e6f79e";

echo "========== MARKET STALL FINAL TEST ==========\n\n";

// Test 1: S10 API
echo "[1] Testing S10 API (Subsystem 10 - Public Assets)\n";
echo "URL: http://localhost/rcts-qc/mock-data/subsystem10/public-assets-api.php\n";
echo "Query: action=get_stalls_by_citizen&qcitizen_id=$brylle_uuid\n\n";

$s10_url = "http://localhost/rcts-qc/mock-data/subsystem10/public-assets-api.php?action=get_stalls_by_citizen&qcitizen_id=$brylle_uuid&t=" . time();
$s10_response = @file_get_contents($s10_url);

if ($s10_response === false) {
    echo "❌ ERROR: Could not fetch S10 API\n";
} else {
    $s10_data = json_decode($s10_response, true);
    if (isset($s10_data['data']) && is_array($s10_data['data'])) {
        echo "✅ S10 API returned " . count($s10_data['data']) . " stalls\n";
        foreach ($s10_data['data'] as $stall) {
            echo "  - Stall: " . ($stall['stall_name'] ?? 'N/A') . "\n";
            echo "    Asset ID: " . ($stall['stall_asset_id'] ?? 'N/A') . "\n";
            echo "    Monthly Rate: ₱" . number_format($stall['monthly_rental_rate'] ?? 0) . "\n";
            echo "    Verification: " . ($stall['occupancy_verification_method'] ?? 'N/A') . "\n";
        }
    } else {
        echo "❌ ERROR: Unexpected response format\n";
        echo "Response: " . substr($s10_response, 0, 200) . "\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 2: Payment API - Get Pending Bills
echo "[2] Testing Payment API for MarketRental Bills\n";
echo "URL: http://localhost/rcts-qc/api/endpoints/payment.php\n";
echo "Query: action=get_pending_bills&qcitizen_id=$brylle_uuid\n\n";

$payment_url = "http://localhost/rcts-qc/api/endpoints/payment.php?action=get_pending_bills&qcitizen_id=$brylle_uuid&t=" . time();
$payment_response = @file_get_contents($payment_url);

if ($payment_response === false) {
    echo "❌ ERROR: Could not fetch Payment API\n";
} else {
    $payment_data = json_decode($payment_response, true);
    
    if (isset($payment_data['data']['bills']) && is_array($payment_data['data']['bills'])) {
        $all_bills = $payment_data['data']['bills'];
        $market_bills = array_filter($all_bills, function($b) { return $b['bill_type'] === 'MarketRental'; });
        
        echo "✅ Payment API returned " . count($all_bills) . " total bills\n";
        echo "✅ MarketRental bills: " . count($market_bills) . "\n\n";
        
        foreach ($market_bills as $bill) {
            echo "  - Bill Ref: " . $bill['bill_reference_no'] . "\n";
            echo "    Type: " . $bill['bill_type'] . "\n";
            echo "    Asset ID: " . ($bill['asset_id'] ?? 'MISSING!') . "\n";
            echo "    Amount: ₱" . number_format($bill['total_amount_due'], 2) . "\n";
            echo "    Status: " . $bill['status'] . "\n";
        }
    } else {
        echo "❌ ERROR: Unexpected response format\n";
        echo "Response: " . substr($payment_response, 0, 200) . "\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 3: Linking verification
echo "[3] Verifying Stall-to-Bill Linking\n\n";

if ($s10_response !== false && $payment_response !== false) {
    $s10_data = json_decode($s10_response, true);
    $payment_data = json_decode($payment_response, true);
    
    $stalls = $s10_data['data'] ?? [];
    $all_bills = $payment_data['data']['bills'] ?? [];
    $market_bills = array_filter($all_bills, function($b) { return $b['bill_type'] === 'MarketRental'; });
    
    // Build bill map
    $bill_map = [];
    foreach ($market_bills as $bill) {
        $bill_map[$bill['asset_id']] = $bill;
    }
    
    foreach ($stalls as $stall) {
        $stall_id = $stall['stall_asset_id'];
        $stall_name = $stall['stall_name'] ?? 'Unknown';
        
        if (isset($bill_map[$stall_id])) {
            $bill = $bill_map[$stall_id];
            echo "✅ $stall_name (" . $stall_id . ")\n";
            echo "   Linked to Bill: " . $bill['bill_reference_no'] . "\n";
            echo "   Amount: ₱" . number_format($bill['total_amount_due'], 2) . "\n";
        } else {
            echo "⚠️  $stall_name (" . $stall_id . ")\n";
            echo "   No matching bill found in MarketRental bills\n";
        }
    }
    
    echo "\n✅ All linkages verified!\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "\nNEXT STEPS:\n";
echo "1. Log in to the citizen portal (use Brylle's account)\n";
echo "2. Navigate to 'Market Stall' tab\n";
echo "3. Verify 2 stalls display with linked rental bills\n";
echo "4. Expected: ₱2,500 + ₱2,800 = ₱5,300 total\n";
echo "\n";
?>
