<?php
// activate-qrf.php
// Simple script to unlock QRF for DRRM emergency disbursement

$url = 'https://rcts-qc.wuaze.com/api/endpoints/disbursement.php?action=request_qrf_unlock';

$data = [
    'disaster_id'     => 'EQ-MARIKINA-2026',
    'calamity_signal' => 'Earthquake Marikina Emergency',
    'amount_needed'   => 12000
];

$options = [
    'http' => [
        'header'  => [
            'Content-Type: application/json',
            'X-API-Key: S6-DRRM-RCTS-KEY-2025'
        ],
        'method'  => 'POST',
        'content' => json_encode($data),
        'ignore_errors' => true
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

header('Content-Type: application/json');
echo $result ? $result : json_encode(['success'=>false,'message'=>'No response from API']);
