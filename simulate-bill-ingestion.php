<?php
// simulate-bill-ingestion.php
// Simulate end-to-end billing ingestion via inbound subsystem signals.

function call_api($url, $body, $apiKey) {
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\nX-API-Key: $apiKey\r\n",
            'method'  => 'POST',
            'content' => json_encode($body),
            'ignore_errors' => true
        ]
    ];
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    $decoded = $result ? json_decode($result, true) : null;
    return ['status' => $http_response_header[0] ?? '', 'raw' => $result, 'json' => $decoded];
}

function get_json($url) {
    $result = @file_get_contents($url);
    return $result ? json_decode($result, true) : null;
}

$citizenId = $_GET['citizen'] ?? 'QC-2024-000009';
$actionLog = [];

// Define test businesses for citizen Carlo
$businesses = [
    [
        'bin_number' => 'BIN-QC-2024-CARLO-001',
        'qcitizen_id' => $citizenId,
        'business_name' => "Carlo's Import & Export Trading",
        'nature_of_business' => 'Wholesale & Trading - Import/Export',
        'business_address' => '456 Eugenio Lopez Jr Ave., Barrio Fiesta, Quezon City',
        'gross_sales_declared' => 2500000,
        'assessment_cycle' => 'Annual',
        'permit_status' => 'Active'
    ],
    [
        'bin_number' => 'BIN-QC-2024-CARLO-002',
        'qcitizen_id' => $citizenId,
        'business_name' => "Carlo's Construction Materials Supply",
        'nature_of_business' => 'Wholesale & Retail - Construction Materials',
        'business_address' => '789 Anonas Street, San Juan, Quezon City',
        'gross_sales_declared' => 1800000,
        'assessment_cycle' => 'Annual',
        'permit_status' => 'Active'
    ]
];

$inboundBase = 'http://localhost/rcts-qc/api/endpoints/inbound.php';
$billBase = 'http://localhost/rcts-qc/api/endpoints/business-tax.php';
$s2Key = 'S2-PERMITS-RCTS-KEY-2025';
$s4Key = 'S4-HEALTH-RCTS-KEY-2025';
$s9Key = 'S9-TRANSPORT-RCTS-KEY-2025';

foreach ($businesses as $biz) {
    $payload = [
        'bin_number' => $biz['bin_number'],
        'qcitizen_id' => $biz['qcitizen_id'],
        'business_name' => $biz['business_name'],
        'gross_sales_declared' => $biz['gross_sales_declared'],
        'nature_of_business' => $biz['nature_of_business'],
        'business_address' => $biz['business_address'],
        'assessment_cycle' => $biz['assessment_cycle'],
        'permit_status' => $biz['permit_status']
    ];
    $resp = call_api("$inboundBase?action=s2_business_approved", $payload, $s2Key);
    $actionLog[] = ['step' => 's2_business_approved', 'bin' => $biz['bin_number'], 'result' => $resp];

    foreach (['Health', 'Sanitary', 'Fire'] as $type) {
        $clearance = [
            'clearance_ref_id' => 'CL-' . strtoupper(substr(uniqid(), -6)),
            'business_bin' => $biz['bin_number'],
            'qcitizen_id' => $biz['qcitizen_id'],
            'business_name' => $biz['business_name'],
            'clearance_type' => $type,
            'status_flag' => 'Passed',
            'inspection_date' => date('Y-m-d'),
            'inspector_name' => 'Mock Inspector'
        ];
        $resp = call_api("$inboundBase?action=s4_clearance_passed", $clearance, $s4Key);
        $actionLog[] = ['step' => 's4_clearance_passed', 'bin' => $biz['bin_number'], 'type' => $type, 'result' => $resp];
    }
}

// Optional: add a traffic violation for citizen (S9)
$traffic = [
    'violation_ticket_id' => 'TV-CARLO-' . date('ymd') . '-001',
    'qcitizen_id' => $citizenId,
    'vehicle_plate_no' => 'CAR-1234',
    'violation_type' => 'Illegal Parking',
    'fine_amount' => 1500.00,
    'apprehension_date' => date('Y-m-d'),
    'source_subsystem_id' => 9
];
$respTraffic = call_api("$inboundBase?action=s9_violation_issued", $traffic, $s9Key);
$actionLog[] = ['step' => 's9_violation_issued', 'ticket' => $traffic['violation_ticket_id'], 'result' => $respTraffic];

// Fetch resulting bills and clearances
$bizBills = get_json("$billBase?action=get_bills&qcitizen_id=$citizenId");
$allBills = get_json("$billBase?action=get_all_bills&qcitizen_id=$citizenId");
$rptBills = get_json("http://localhost/rcts-qc/api/endpoints/rpt.php?action=get_bills&qcitizen_id=$citizenId");
$marketBills = get_json("http://localhost/rcts-qc/api/endpoints/market-stall.php?action=get_vendor_bill&qcitizen_id=$citizenId");
$pending = get_json("http://localhost/rcts-qc/api/endpoints/payment.php?action=get_pending_bills&qcitizen_id=$citizenId");
$clearanceStatus = get_json("http://localhost/rcts-qc/api/endpoints/business-tax.php?action=clearance_status&qcitizen_id=$citizenId");

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Simulate Bill Ingestion</title>
    <style>body{font-family:Segoe UI,Arial,sans-serif;background:#f4f5f7;padding:20px;} .card{background:#fff;padding:18px;border-radius:10px;box-shadow:0 1px 12px rgba(0,0,0,.08);margin-bottom:15px;} h1{color:#2d7fb8;} pre{background:#20232a;color:#f4f4f4;padding:12px;border-radius:8px;overflow-x:auto;font-size:13px;line-height:1.4;} .success{color:#27ae60;font-weight:700;} .warn{color:#d35400;font-weight:700;}</style>
</head>
<body>
    <h1>Simulated Subsystem Bill Ingestion (Citizen: <?php echo htmlspecialchars($citizenId) ?>)</h1>
    <div class="card">
        <h3>Summary of actions</h3>
        <pre><?php echo htmlspecialchars(json_encode($actionLog, JSON_PRETTY_PRINT)); ?></pre>
    </div>
    <div class="card">
        <h3>Business Tax Bills (business-tax get_bills)</h3>
        <pre><?php echo htmlspecialchars(json_encode($bizBills, JSON_PRETTY_PRINT)); ?></pre>
    </div>
    <div class="card">
        <h3>All Bills (business-tax get_all_bills)</h3>
        <pre><?php echo htmlspecialchars(json_encode($allBills, JSON_PRETTY_PRINT)); ?></pre>
    </div>
    <div class="card">
        <h3>RPT Bills (rpt get_bills)</h3>
        <pre><?php echo htmlspecialchars(json_encode($rptBills, JSON_PRETTY_PRINT)); ?></pre>
    </div>
    <div class="card">
        <h3>Market Stall Bills (market-stall get_vendor_bill)</h3>
        <pre><?php echo htmlspecialchars(json_encode($marketBills, JSON_PRETTY_PRINT)); ?></pre>
    </div>
    <div class="card">
        <h3>Pending bills unified cart (payment get_pending_bills)</h3>
        <pre><?php echo htmlspecialchars(json_encode($pending, JSON_PRETTY_PRINT)); ?></pre>
    </div>
    <div class="card">
        <h3>Clearance status (business-tax clearance_status)</h3>
        <pre><?php echo htmlspecialchars(json_encode($clearanceStatus, JSON_PRETTY_PRINT)); ?></pre>
    </div>
</body>
</html>
