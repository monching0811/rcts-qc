<?php
/**
 * MOCK: Subsystem 4 — Business Regulatory Clearances
 * mock-data/subsystem4/clearance-api.php
 *
 * Simulates QC Health Dept, Bureau of Fire Protection, and Sanitation
 * pushing clearance results to RCTS after inspection.
 *
 * RCTS inbound action: s4_clearance_passed / s4_clearance_failed
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'get_clearances';

// Load clearances from JSON file (like subsystem7 properties)
$clearances_file = __DIR__ . '/clearances.json';
$CLEARANCES = [];
if (file_exists($clearances_file)) {
    $clearances_data = json_decode(file_get_contents($clearances_file), true);
    $CLEARANCES = $clearances_data['clearances'] ?? [];
}

if ($action === 'get_clearances') {
    $bin = $_GET['bin_number'] ?? null;
    $qcitizen_id = $_GET['qcitizen_id'] ?? null;
    
    $out = $CLEARANCES;
    if ($bin) {
        $out = array_values(array_filter($CLEARANCES, fn($c) => $c['bin_number'] === $bin));
    } elseif ($qcitizen_id) {
        $out = array_values(array_filter($CLEARANCES, fn($c) => $c['qcitizen_id'] === $qcitizen_id));
    }
    
    echo json_encode(['success'=>true,'subsystem'=>'S4-Clearances','data'=>$out,'count'=>count($out)]);
    exit;
}

// Simulate pushing ONE clearance result to RCTS
if ($action === 'push_clearance') {
    $bin    = $_GET['bin_number']      ?? 'BIN-QC-2024-001';
    $type   = $_GET['clearance_type']  ?? 'Fire';
    $status = $_GET['status']          ?? 'Passed';
    $issuer = $_GET['issued_by']       ?? 'BFP-QC';

    $payload = json_encode([
        'source'            => 'S4-Clearances',
        'action'            => 's4_clearance_' . strtolower($status),
        'bin_number'        => $bin,
        'clearance_type'    => $type,
        'clearance_status'  => $status,
        'issued_by'         => $issuer,
        'valid_until'       => date('Y-12-31'),
        'inspection_date'   => date('c'),
        'inspector_name'    => 'Inspector Mock',
    ]);
    $ctx = stream_context_create(['http'=>[
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\nX-API-Key: DEV-BYPASS-KEY-LOCAL\r\n",
        'content' => $payload
    ]]);
    $url = 'http://localhost/rcts-qc/api/endpoints/inbound.php?action=s4_clearance_' . strtolower($status);
    $raw = @file_get_contents($url, false, $ctx);
    $res = $raw ? json_decode($raw, true) : ['success'=>false,'message'=>'No response from RCTS'];
    echo json_encode(['success'=>true,'signal_sent'=>$payload,'rcts_response'=>$res]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);