<?php
$citizen_id = 'a135da1e-6727-430e-9771-e15688e6f79e';
$url = "http://localhost/rcts-qc/api/endpoints/payment.php?action=get_pending_bills&qcitizen_id=$citizen_id";

echo "Fetching: $url\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);
$stderr = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $stderr);

$response_raw = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Code: $http_code\n";
echo "Response Length: " . strlen($response_raw) . "\n";
echo "\nRaw Response:\n";
echo $response_raw . "\n";

if ($http_code === 200) {
  $parsed = json_decode($response_raw, true);
  if (json_last_error() === JSON_ERROR_NONE) {
    echo "\nJSON Parsed:\n";
    print_r($parsed);
  } else {
    echo "\nJSON Error: " . json_last_error_msg() . "\n";
  }
}
?>
