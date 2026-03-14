<?php
$citizen_id = 'a135da1e-6727-430e-9771-e15688e6f79e';
$url = "http://localhost/rcts-qc/api/endpoints/payment.php?action=get_pending_bills&qcitizen_id=$citizen_id";
$response_raw = file_get_contents($url);
$response = json_decode($response_raw, true);

echo "=== Payment API Response ===\n";
echo "Status: " . ($response['success'] ?? 'N/A') . "\n";
echo "Message: " . ($response['message'] ?? 'N/A') . "\n\n";

if ($response['success']) {
  $bills = $response['data']['bills'] ?? [];
  echo "Total Bills: " . count($bills) . "\n";
  if (!empty($bills)) {
    echo "\nFirst Bill Keys: " . implode(', ', array_keys($bills[0])) . "\n";
    echo "\nFirst Bill (MarketRental):\n";
    $market = array_filter($bills, fn($b) => $b['bill_type'] === 'MarketRental')[0] ?? null;
    if ($market) {
      print_r($market);
    }
  }
} else {
  echo "Error Response:\n";
  print_r($response);
}
?>
