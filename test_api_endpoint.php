<?php
// Test the get_traffic_violations endpoint
$url = 'http://localhost/rcts-qc/api/endpoints/dashboard.php?action=get_traffic_violations&qcitizen_id=QC-2024-000001';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo 'Curl error: ' . curl_error($ch) . PHP_EOL;
} else {
    echo 'HTTP Code: ' . $httpCode . PHP_EOL;
    echo 'Response: ' . $response . PHP_EOL;
}

curl_close($ch);
?>