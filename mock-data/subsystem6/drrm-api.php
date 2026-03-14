<?php
/**
 * MOCK: Subsystem 6 — Disaster Risk Reduction & Management
 * mock-data/subsystem6/drrm-api.php
 *
 * Simulates DRRM declaring a disaster and requesting QRF unlock + payout.
 * RCTS inbound actions: s6_disaster_declared, s6_qrf_unlock_request
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'get_disaster_log';

$DISASTERS = [
    [
        'disaster_id'   => 'DRRM-2025-001',
        'type'          => 'Typhoon',
        'name'          => 'Typhoon Quezon (Local)',
        'declared_at'   => date('c', strtotime('-2 days')),
        'affected_barangays' => ['Cubao','Kamuning','Kamias'],
        'estimated_affected' => 1200,
        'qrf_requested' => 500000.00,
        'status'        => 'Active',
    ]
];

$QRF_RECIPIENTS = [
    ['qcitizen_id'=>'QC-2024-000001','full_name'=>'Juan Dela Cruz',  'approved_amount'=>5000.00,'program_id'=>'DRRM-2025-001','program_name'=>'Typhoon Quezon Relief','scheduled_date'=>date('Y-m-d'),'priority_flag'=>'Emergency','originating_dept_id'=>6,'disbursement_method'=>'DigitalWallet'],
    ['qcitizen_id'=>'QC-2024-000002','full_name'=>'Maria Santos',    'approved_amount'=>5000.00,'program_id'=>'DRRM-2025-001','program_name'=>'Typhoon Quezon Relief','scheduled_date'=>date('Y-m-d'),'priority_flag'=>'Emergency','originating_dept_id'=>6,'disbursement_method'=>'BankTransfer'],
    ['qcitizen_id'=>'QC-2024-000003','full_name'=>'Pedro Reyes',     'approved_amount'=>5000.00,'program_id'=>'DRRM-2025-001','program_name'=>'Typhoon Quezon Relief','scheduled_date'=>date('Y-m-d'),'priority_flag'=>'Emergency','originating_dept_id'=>6,'disbursement_method'=>'DigitalWallet'],
    ['qcitizen_id'=>'QC-2024-000006','full_name'=>'Cynthia Flores',  'approved_amount'=>5000.00,'program_id'=>'DRRM-2025-001','program_name'=>'Typhoon Quezon Relief','scheduled_date'=>date('Y-m-d'),'priority_flag'=>'Emergency','originating_dept_id'=>6,'disbursement_method'=>'DigitalWallet'],
];

if ($action === 'get_disaster_log') {
    echo json_encode(['success'=>true,'subsystem'=>'S6-DRRM','data'=>$DISASTERS,'recipients'=>$QRF_RECIPIENTS,'total_requested'=>array_sum(array_column($QRF_RECIPIENTS,'approved_amount'))]);
    exit;
}

// Push disaster declaration → triggers QRF unlock in RCTS
if ($action === 'declare_disaster') {
    $disaster = $DISASTERS[0];
    $payload  = json_encode([
        'source'        => 'S6-DRRM',
        'action'        => 's6_disaster_declared',
        'disaster_id'   => $disaster['disaster_id'],
        'disaster_type' => $disaster['type'],
        'disaster_name' => $disaster['name'],
        'qrf_amount_requested' => $disaster['qrf_requested'],
        'declared_at'   => $disaster['declared_at'],
    ]);
    $ctx = stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/json\r\nX-API-Key: DEV-BYPASS-KEY-LOCAL\r\n",'content'=>$payload]]);
    $raw = @file_get_contents('http://localhost/rcts-qc/api/endpoints/inbound.php?action=s6_disaster_declared', false, $ctx);
    echo json_encode(['success'=>true,'signal'=>'disaster_declared','rcts_response'=>$raw ? json_decode($raw,true) : ['success'=>false,'message'=>'No response']]);
    exit;
}

// Push QRF payout list
if ($action === 'push_qrf_payout') {
    $results = [];
    foreach ($QRF_RECIPIENTS as $r) {
        $payload = json_encode(array_merge($r, ['source'=>'S6-DRRM','action'=>'s6_qrf_payout_request']));
        $ctx = stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/json\r\nX-API-Key: DEV-BYPASS-KEY-LOCAL\r\n",'content'=>$payload]]);
        $raw = @file_get_contents('http://localhost/rcts-qc/api/endpoints/inbound.php?action=s3_aid_request', false, $ctx); // reuses disbursement logic
        $results[] = ['qcitizen_id'=>$r['qcitizen_id'],'result'=>$raw ? json_decode($raw,true) : ['success'=>false]];
    }
    echo json_encode(['success'=>true,'pushed'=>count($results),'results'=>$results]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);