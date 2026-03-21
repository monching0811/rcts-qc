<?php
// allocate-carlos-bills.php
// Allocate all bill types for Carlo Nicolas (QC-2024-000009) following PENDING-BILLS-ALLOCATION-PROCESS.md

function call_api($url, $body, $apiKey = 'DEV-BYPASS-KEY-LOCAL') {
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

$citizenId = 'QC-2024-000009'; // Carlo's ID
$actionLog = [];

// 1. Generate RPT bills (like Vince)
$respRPT = call_api("http://localhost/rcts-qc/api/endpoints/rpt.php?action=generate_bill", ['qcitizen_id' => $citizenId]);
$actionLog[] = ['step' => 'generate_rpt_bills', 'citizen' => $citizenId, 'result' => $respRPT];

// 2. Generate Business Tax bills (like Raven)
$respBiz = call_api("http://localhost/rcts-qc/api/endpoints/business-tax.php?action=generate_bill", ['qcitizen_id' => $citizenId]);
$actionLog[] = ['step' => 'generate_business_tax_bills', 'citizen' => $citizenId, 'result' => $respBiz];

// 3. Generate Market Stall bills (like Dave)
$respMarket = call_api("http://localhost/rcts-qc/api/endpoints/market-stall.php?action=generate_bill", ['qcitizen_id' => $citizenId]);
$actionLog[] = ['step' => 'generate_market_stall_bills', 'citizen' => $citizenId, 'result' => $respMarket];

// 4. Generate Traffic Fines (like Raven, via S9)
$trafficViolations = [
    [
        'violation_ticket_id' => 'TV-CARLO-001',
        'qcitizen_id' => $citizenId,
        'vehicle_plate_no' => 'CAR-1234',
        'violation_type' => 'Illegal Parking',
        'fine_amount' => 1500.00,
        'apprehension_date' => date('Y-m-d'),
        'source_subsystem_id' => 9
    ],
    [
        'violation_ticket_id' => 'TV-CARLO-002',
        'qcitizen_id' => $citizenId,
        'vehicle_plate_no' => 'CAR-5678',
        'violation_type' => 'Overtime Parking',
        'fine_amount' => 1000.00,
        'apprehension_date' => date('Y-m-d', strtotime('-1 day')),
        'source_subsystem_id' => 9
    ],
    [
        'violation_ticket_id' => 'TV-CARLO-003',
        'qcitizen_id' => $citizenId,
        'vehicle_plate_no' => 'CAR-9999',
        'violation_type' => 'No Parking Sign',
        'fine_amount' => 2000.00,
        'apprehension_date' => date('Y-m-d', strtotime('-2 days')),
        'source_subsystem_id' => 9
    ]
];

foreach ($trafficViolations as $traffic) {
    $respTraffic = call_api("http://localhost/rcts-qc/api/endpoints/inbound.php?action=s9_violation_issued", $traffic, 'S9-TRANSPORT-RCTS-KEY-2025');
    $actionLog[] = ['step' => 'generate_traffic_fine', 'ticket' => $traffic['violation_ticket_id'], 'result' => $respTraffic];
}

// Fetch final bill counts
$allBills = json_decode(file_get_contents("http://localhost/rcts-qc/api/endpoints/business-tax.php?action=get_all_bills&qcitizen_id=$citizenId"), true);

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Allocate Carlo's Bills</title>
    <style>body{font-family:Segoe UI,Arial,sans-serif;background:#f4f5f7;padding:20px;} .card{background:#fff;padding:18px;border-radius:10px;box-shadow:0 1px 12px rgba(0,0,0,.08);margin-bottom:15px;} h1{color:#2d7fb8;} pre{background:#20232a;color:#f4f4f4;padding:12px;border-radius:8px;overflow-x:auto;font-size:13px;line-height:1.4;}</style>
</head>
<body>
    <h1>Bill Allocation for Carlo Nicolas (QC-2024-000009)</h1>
    <div class="card">
        <h3>Allocation Results</h3>
        <pre><?php echo htmlspecialchars(json_encode($actionLog, JSON_PRETTY_PRINT)); ?></pre>
    </div>
    <div class="card">
        <h3>All Bills for Carlo</h3>
        <pre><?php echo htmlspecialchars(json_encode($allBills, JSON_PRETTY_PRINT)); ?></pre>
    </div>
</body>
</html>