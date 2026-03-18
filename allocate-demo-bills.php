<?php
// DEMO: Allocate 3 business tax, 3 market stall, and 3 traffic fine bills to Vince Nico
// Usage: php allocate-demo-bills.php

function post($url, $payload, $apiKey = null) {
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json" . ($apiKey ? "\r\nX-API-Key: $apiKey" : ""),
            'content' => json_encode($payload),
            'ignore_errors' => true
        ]
    ];
    $ctx = stream_context_create($opts);
    $result = @file_get_contents($url, false, $ctx);
    $http_response_header = $http_response_header ?? [];
    if ($result === false) {
        return [
            'error' => 'No response',
            'url' => $url,
            'payload' => $payload,
            'headers' => $http_response_header
        ];
    }
    $json = json_decode($result, true);
    if ($json === null) {
        return [
            'error' => 'Invalid JSON',
            'url' => $url,
            'payload' => $payload,
            'headers' => $http_response_header,
            'raw_body' => $result
        ];
    }
    return $json;
}

$vince_id = 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5';
$today = date('Y-m-d');
$now = date('c');

// 1. Business Tax Bills
for ($i = 1; $i <= 3; $i++) {
    $bin = "BIN-QC-2026-VINCE-00$i";
    $business = [
        'bin_number' => $bin,
        'qcitizen_id' => $vince_id,
        'business_name' => "Vince's Demo Business $i",
        'nature_of_business' => 'Retail',
        'business_address' => "123 Demo St $i, QC",
        'gross_sales_declared' => 100000 * $i,
        'assessment_cycle' => 'Annual'
    ];
    $s2 = post('https://rcts-qc.wuaze.com/api/endpoints/inbound.php?action=s2_business_approved', $business, 'DEV-BYPASS-KEY-LOCAL');
    echo "[S2] Business $i: ", json_encode($s2), "\n";

    foreach (['Health', 'Sanitary'] as $type) {
        $clearance = [
            'clearance_ref_id' => "CLR-2026-VINCE-00$i-" . substr($type,0,1),
            'business_bin' => $bin,
            'clearance_type' => $type,
            'status_flag' => 'Passed',
            'qcitizen_id' => $vince_id,
            'inspection_date' => $today,
            'inspector_name' => 'Demo Inspector'
        ];
        $s4 = post('https://rcts-qc.wuaze.com/api/endpoints/inbound.php?action=s4_clearance_passed', $clearance, 'DEV-BYPASS-KEY-LOCAL');
        echo "[S4] $type Clearance $i: ", json_encode($s4), "\n";
    }
}

// 2. Market Stall Bills
for ($i = 1; $i <= 3; $i++) {
    $stall = [
        'stall_asset_id' => "STL-QC-2026-VINCE-00$i",
        'qcitizen_id' => $vince_id,
        'occupancy_status_flag' => 'Active',
        'verification_method' => 'Manual',
        'verified_at' => $now
    ];
    $s10 = post('https://rcts-qc.wuaze.com/api/endpoints/inbound.php?action=s10_occupancy_update', $stall, 'DEV-BYPASS-KEY-LOCAL');
    echo "[S10] Stall $i: ", json_encode($s10), "\n";
}

// 3. Traffic Fine Bills
for ($i = 1; $i <= 3; $i++) {
    $ticket = [
        'violation_ticket_id' => "TKT-20260319-VINCE-00$i",
        'qcitizen_id' => $vince_id,
        'vehicle_plate_no' => "VNC-2026",
        'violation_type' => "Demo Violation $i",
        'fine_amount' => 1000 * $i,
        'apprehension_date' => $today
    ];
    $s9 = post('https://rcts-qc.wuaze.com/api/endpoints/inbound.php?action=s9_violation_issued', $ticket, 'DEV-BYPASS-KEY-LOCAL');
    echo "[S9] Violation $i: ", json_encode($s9), "\n";
}

echo "\nAll demo bills allocated to Vince Nico.\n";
