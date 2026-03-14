<?php
$citizen_id = 'a135da1e-6727-430e-9771-e15688e6f79e';
$url = "http://localhost/rcts-qc/api/endpoints/payment.php?action=get_pending_bills&qcitizen_id=$citizen_id";
$response = json_decode(file_get_contents($url), true);

if ($response['success']) {
  $bills = $response['data']['bills'] ?? [];
  echo "Total Bills: " . count($bills) . "\n";
  if (!empty($bills)) {
    echo "\nFirst Bill Structure:\n";
    echo "Keys: " . implode(', ', array_keys($bills[0])) . "\n\n";
    echo "Full First Bill:\n";
    print_r($bills[0]);
  }
} else {
  echo "Error: " . $response['message'] . "\n";
}
?>
