<?php
// Test generate_bill for Vince
$url = 'http://localhost/rcts-qc/api/endpoints/rpt.php?action=generate_bill';
$data = ['qcitizen_id' => 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5'];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
    ],
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo 'Response: ' . $result . PHP_EOL;