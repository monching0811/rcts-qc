<?php
// activate-qrf.php
// Simple script to unlock QRF for DRRM emergency disbursement

$url = 'https://rcts-qc.great-site.net/api/endpoints/disbursement.php?action=request_qrf_unlock';

$data = [
    'disaster_id'     => 'FLOOD-QC-2026',
    'calamity_signal' => 'Flood Relief Emergency',
    'amount_needed'   => 8000,
    'disbursement_ref_id' => 'DISB-DRRM-2025-007',
    'program_name'    => 'Flood Relief',
    'priority_flag'   => 'Emergency'
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

$response = $result ? json_decode($result, true) : null;

if ($response && isset($response['success']) && $response['success']) {
    // User-friendly HTML output
    $msg = htmlspecialchars($response['message'] ?? 'QRF Unlocked.');
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><title>QRF Unlock Success</title><style>body{font-family:sans-serif;background:#f8f8f8;margin:0;padding:0;} .center{max-width:480px;margin:60px auto;background:#fff;padding:32px 24px 24px 24px;border-radius:10px;box-shadow:0 2px 12px #0001;} h1{color:#2ecc71;} .details{margin-top:18px;font-size:15px;color:#444;} .next{margin-top:16px;font-size:14px;color:#888;}</style></head><body><div class='center'><h1>Success</h1><div class='details'>$msg</div>";
    if (isset($response['data']['next_step'])) {
        $next = htmlspecialchars($response['data']['next_step']);
        echo "<div class='next'><b>Next step:</b> $next</div>";
    }
    echo "</div></body></html>";
} else {
    // Fallback to JSON error
    header('Content-Type: application/json');
    echo $result ? $result : json_encode(['success'=>false,'message'=>'No response from API']);
}
