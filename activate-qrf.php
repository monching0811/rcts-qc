<?php
// activate-qrf.php
// Simple script to unlock QRF for DRRM emergency disbursement

// Determine if we're running locally or on production
$host = $_SERVER['HTTP_HOST'] ?? '';
$is_local = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false);

// Use local URL if on localhost, otherwise use production URL
$base_url = $is_local 
    ? 'http://localhost/rcts-qc/api/endpoints/disbursement.php'
    : 'https://rcts-qc.great-site.net/api/endpoints/disbursement.php';

$url = $base_url . '?action=request_qrf_unlock';

$data = [
    'disaster_id'     => 'FLOOD-QC-2026',
    'calamity_signal' => 'Flood Relief Emergency',
    'amount_needed'   => 8000,
    'disbursement_ref_id' => 'DISB-DRRM-2025-007',
    'program_name'    => 'Flood Relief',
    'priority_flag'   => 'Emergency'
];

// Use cURL for more reliable HTTP requests
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Key: S6-DRRM-RCTS-KEY-2025'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FAILONERROR, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Helper function to render styled HTML response
function render_html($title, $message, $is_success = true, $extra = null, $http_code = 0, $curl_err = '', $raw_response = '') {
    $color = $is_success ? '#2ecc71' : '#e74c3c';
    $icon = $is_success ? '✓' : '✗';
    $extra_html = '';
    if ($extra) {
        $extra_html = '<div class="extra">' . htmlspecialchars($extra) . '</div>';
    }
    echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>QRF Unlock - $title</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .center {
            max-width: 500px;
            width: 100%;
            background: #fff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        .icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: $color;
            color: #fff;
            font-size: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-weight: bold;
        }
        h1 {
            color: #333;
            margin: 0 0 16px;
            font-size: 28px;
        }
        .message {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .extra {
            background: #f8f9fa;
            padding: 16px;
            border-radius: 8px;
            font-size: 14px;
            color: #555;
            text-align: left;
        }
        .btn {
            display: inline-block;
            background: $color;
            color: #fff;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 16px;
        }
        .debug {
            margin-top: 24px;
            padding: 16px;
            background: #fff3cd;
            border-radius: 8px;
            font-size: 12px;
            color: #856404;
            text-align: left;
            display: none;
        }
        .debug.show { display: block; }
    </style>
</head>
<body>
    <div class='center'>
        <div class='icon'>$icon</div>
        <h1>$title</h1>
        <div class='message'>" . htmlspecialchars($message) . "</div>
        $extra_html
        <a href='?' class='btn'>Try Again</a>
        <div class='debug' id='debug'></div>
    </div>
    <script>
        // Show debug info in case of error
        const debugInfo = document.getElementById('debug');
        if (" . ($is_success ? 'false' : 'true') . ") {
            debugInfo.classList.add('show');
            debugInfo.innerHTML = '<strong>Debug Information:</strong><br>' +
                'HTTP Code: $http_code<br>' +
                'cURL Error: ' + " . json_encode($curl_err) . " + '<br>' +
                'Response: ' + " . json_encode(substr($raw_response ?? '', 0, 500)) . ";
        }
    </script>
</body>
</html>";
}

// Check if cURL failed
if ($result === false || empty($result)) {
    render_html('Connection Failed', 'Could not connect to the API server. Please check your network connection.', false, '', 0, $curl_error, $result);
    exit;
}

$response = json_decode($result, true);

// Handle HTTP error codes
if ($http_code >= 400) {
    $error_msg = $response['message'] ?? $response['error'] ?? 'API request failed';
    render_html('Request Failed', $error_msg, false, 'HTTP Status: ' . $http_code, $http_code, $curl_error, $result);
    exit;
}

// Check if the API response indicates success
if ($response && isset($response['success']) && $response['success']) {
    $msg = $response['message'] ?? 'QRF Unlocked successfully!';
    $extra = '';
    if (isset($response['data'])) {
        $data_info = [];
        if (isset($response['data']['disaster_id'])) $data_info[] = 'Disaster: ' . $response['data']['disaster_id'];
        if (isset($response['data']['amount_unlocked'])) $data_info[] = 'Amount: ₱' . number_format($response['data']['amount_unlocked'], 2);
        if (isset($response['data']['qrf_balance_after'])) $data_info[] = 'Remaining QRF: ₱' . number_format($response['data']['qrf_balance_after'], 2);
        if (isset($response['data']['next_step'])) $data_info[] = 'Next: ' . $response['data']['next_step'];
        $extra = implode('<br>', $data_info);
    }
    render_html('Success!', $msg, true, $extra);
} else {
    // API returned but with error
    $error_msg = $response['message'] ?? $response['error'] ?? 'Unknown error occurred';
    render_html('Error', $error_msg, false, 'The API returned an error response.', $http_code, $curl_error, $result);
}
