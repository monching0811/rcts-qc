<?php
$citizen_id = 'a135da1e-6727-430e-9771-e15688e6f79e';

echo "Testing S10 API with curl (simulating browser fetch)...\n\n";

// Test with various headers 
$url = "http://localhost/rcts-qc/mock-data/subsystem10/public-assets-api.php?action=get_stalls_by_citizen&qcitizen_id=$citizen_id";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

// Split headers and body
$header_size = $info['header_size'];
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);

echo "=== Response Headers ===\n";
echo $headers . "\n";

echo "\n=== Response Body ===\n";
echo $body . "\n";

echo "\n=== Parsed Response ===\n";
$data = json_decode($body, true);
print_r($data);

echo "\n=== CORS Headers Present? ===\n";
$has_cors = stripos($headers, 'Access-Control') !== false;
echo "Has CORS headers: " . ($has_cors ? "YES" : "NO") . "\n";
?>
