<?php
/**
 * MOCK: Subsystem 9 — Traffic Management & Violations
 * mock-data/subsystem9/traffic-api.php
 *
 * Simulates the QCTO (Quezon City Traffic Operation) pushing
 * traffic violation fines to RCTS for billing.
 * RCTS inbound action: s9_violation_issued
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'get_violations';

// Load violations from JSON file
$violations_file = __DIR__ . '/traffic-violations.json';
$VIOLATIONS = [];
if (file_exists($violations_file)) {
    $violations_data = json_decode(file_get_contents($violations_file), true);
    $VIOLATIONS = $violations_data['violations'] ?? [];
}

if ($action === 'get_violations') {
    $qcitizen = $_GET['qcitizen_id'] ?? null;
    $out = $qcitizen ? array_values(array_filter($VIOLATIONS, fn($v)=>$v['qcitizen_id']===$qcitizen)) : $VIOLATIONS;
    echo json_encode(['success'=>true,'subsystem'=>'S9-Traffic','data'=>$out,'count'=>count($out),'total_fines'=>array_sum(array_column($out,'fine_amount'))]);
    exit;
}

// Push one or all violation tickets to RCTS
if ($action === 'push_violation') {
    $ticket = $_GET['ticket_number'] ?? null;
    $toSend = $ticket
        ? array_values(array_filter($VIOLATIONS, fn($v)=>$v['ticket_number']===$ticket))
        : $VIOLATIONS;

    $results = [];
    foreach ($toSend as $v) {
        $payload = json_encode([
            'source'         => 'S9-Traffic',
            'action'         => 's9_violation_issued',
            'ticket_number'  => $v['ticket_number'],
            'plate_number'   => $v['plate_number'],
            'qcitizen_id'    => $v['qcitizen_id'],
            'violation_type' => $v['violation_type'],
            'fine_amount'    => $v['fine_amount'],
            'issued_at'      => $v['issued_at'],
            'officer_badge'  => $v['officer_badge'],
            'location'       => $v['location'],
        ]);
        $ctx = stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/json\r\nX-API-Key: DEV-BYPASS-KEY-LOCAL\r\n",'content'=>$payload]]);
        $raw = @file_get_contents('http://localhost/rcts-qc/api/endpoints/inbound.php?action=s9_violation_issued', false, $ctx);
        $results[] = ['ticket'=>$v['ticket_number'],'result'=>$raw ? json_decode($raw,true) : ['success'=>false,'message'=>'No RCTS response']];
    }
    echo json_encode(['success'=>true,'pushed'=>count($results),'results'=>$results]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);