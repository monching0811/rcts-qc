<?php
/**
 * MOCK: Subsystem 10 — Public Assets & Market Management
 * mock-data/subsystem10/public-assets-api.php
 *
 * Simulates occupancy verification signals from IoT sensors,
 * QR code scanners, and mobile app check-ins.
 * RCTS inbound action: s10_occupancy_update
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'get_stalls';

// Load stalls from JSON file
function load_stalls() {
    $json_file = __DIR__ . '/stalls.json';
    if (file_exists($json_file)) {
        $data = json_decode(file_get_contents($json_file), true);
        return $data['stalls'] ?? [];
    }
    // Fallback to legacy hardcoded data if JSON doesn't exist
    return [
        ['stall_asset_id'=>'STL-QC-2024-001','stall_number'=>'A-01','facility_name'=>'Novaliches Public Market', 'qcitizen_id'=>'QC-2024-000001','vendor_name'=>'Juan Dela Cruz','monthly_rental_rate'=>1500.00,'occupancy_status_flag'=>'Active',      'occupancy_verification_method'=>'IoT',       'occupancy_last_verified'=>date('c',strtotime('-2 hours'))],
        ['stall_asset_id'=>'STL-QC-2024-002','stall_number'=>'B-05','facility_name'=>'Novaliches Public Market', 'qcitizen_id'=>'QC-2024-000002','vendor_name'=>'Maria Santos',  'monthly_rental_rate'=>1800.00,'occupancy_status_flag'=>'Active',      'occupancy_verification_method'=>'QR',        'occupancy_last_verified'=>date('c',strtotime('-1 day'))],
        ['stall_asset_id'=>'STL-QC-2024-003','stall_number'=>'C-10','facility_name'=>'Fairview Market Center',  'qcitizen_id'=>'QC-2024-000003','vendor_name'=>'Pedro Reyes',   'monthly_rental_rate'=>2000.00,'occupancy_status_flag'=>'Active',      'occupancy_verification_method'=>'MobileApp', 'occupancy_last_verified'=>date('c',strtotime('-3 hours'))],
        ['stall_asset_id'=>'STL-QC-2024-004','stall_number'=>'D-02','facility_name'=>'Cubao Market',            'qcitizen_id'=>null,            'vendor_name'=>null,            'monthly_rental_rate'=>2500.00,'occupancy_status_flag'=>'Vacant',      'occupancy_verification_method'=>'IoT',       'occupancy_last_verified'=>date('c',strtotime('-6 hours'))],
        ['stall_asset_id'=>'STL-QC-2024-005','stall_number'=>'E-07','facility_name'=>'Cubao Market',            'qcitizen_id'=>'QC-2024-000006','vendor_name'=>'Cynthia Flores','monthly_rental_rate'=>2200.00,'occupancy_status_flag'=>'Active',      'occupancy_verification_method'=>'IoT',       'occupancy_last_verified'=>date('c',strtotime('-4 hours'))],
        ['stall_asset_id'=>'STL-QC-2024-006','stall_number'=>'F-03','facility_name'=>'Fairview Market Center',  'qcitizen_id'=>null,            'vendor_name'=>null,            'monthly_rental_rate'=>1900.00,'occupancy_status_flag'=>'UnderRepair', 'occupancy_verification_method'=>'Manual',    'occupancy_last_verified'=>date('c',strtotime('-1 day'))],
    ];
}

$STALLS = load_stalls();

if ($action === 'get_stalls') {
    $filter = $_GET['occupancy_status'] ?? null;
    $out    = $filter ? array_values(array_filter($STALLS, fn($s)=>$s['occupancy_status_flag']===$filter)) : $STALLS;
    echo json_encode(['success'=>true,'subsystem'=>'S10-PublicAssets','data'=>$out,'count'=>count($out)]);
    exit;
}

// Get stalls by citizen ID
if ($action === 'get_stalls_by_citizen') {
    $qcitizen_id = $_GET['qcitizen_id'] ?? null;
    if (!$qcitizen_id) {
        echo json_encode(['success'=>false,'message'=>'qcitizen_id required']);
        exit;
    }
    $filtered = array_values(array_filter($STALLS, fn($s)=>$s['qcitizen_id']===$qcitizen_id));
    echo json_encode(['success'=>true,'subsystem'=>'S10-PublicAssets','data'=>$filtered,'count'=>count($filtered)]);
    exit;
}

// Simulate IoT/QR pushing an occupancy update for ONE stall
if ($action === 'push_occupancy') {
    $stall_id = $_GET['stall_asset_id']          ?? 'STL-QC-2024-001';
    $status   = $_GET['occupancy_status_flag']   ?? 'Active';
    $method   = $_GET['verification_method']     ?? 'IoT';

    $stall = current(array_filter($STALLS, fn($s)=>$s['stall_asset_id']===$stall_id));
    if (!$stall) { echo json_encode(['success'=>false,'message'=>'Stall not found']); exit; }

    $payload = json_encode([
        'source'                      => 'S10-PublicAssets',
        'action'                      => 's10_occupancy_update',
        'stall_asset_id'              => $stall_id,
        'facility_name'               => $stall['facility_name'],
        'stall_number'                => $stall['stall_number'],
        'qcitizen_id'                 => $stall['qcitizen_id'],
        'vendor_name'                 => $stall['vendor_name'],
        'monthly_rental_rate'         => $stall['monthly_rental_rate'],
        'occupancy_status_flag'       => $status,
        'occupancy_verification_method'=> $method,
        'occupancy_last_verified'     => date('c'),
    ]);
    $ctx = stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/json\r\nX-API-Key: DEV-BYPASS-KEY-LOCAL\r\n",'content'=>$payload]]);
    $raw = @file_get_contents('http://localhost/rcts-qc/api/endpoints/inbound.php?action=s10_occupancy_update', false, $ctx);
    echo json_encode(['success'=>true,'signal_sent'=>json_decode($payload),'rcts_response'=>$raw ? json_decode($raw,true) : ['success'=>false,'message'=>'No RCTS response']]);
    exit;
}

// Push ALL active stalls at once — simulates daily IoT sweep
if ($action === 'push_all_active') {
    $results = [];
    foreach (array_filter($STALLS, fn($s)=>$s['occupancy_status_flag']==='Active') as $stall) {
        $payload = json_encode([
            'source'                       => 'S10-PublicAssets',
            'action'                       => 's10_occupancy_update',
            'stall_asset_id'               => $stall['stall_asset_id'],
            'facility_name'                => $stall['facility_name'],
            'stall_number'                 => $stall['stall_number'],
            'qcitizen_id'                  => $stall['qcitizen_id'],
            'vendor_name'                  => $stall['vendor_name'],
            'monthly_rental_rate'          => $stall['monthly_rental_rate'],
            'occupancy_status_flag'        => 'Active',
            'occupancy_verification_method'=> $stall['occupancy_verification_method'],
            'occupancy_last_verified'      => date('c'),
        ]);
        $ctx = stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/json\r\nX-API-Key: DEV-BYPASS-KEY-LOCAL\r\n",'content'=>$payload]]);
        $raw = @file_get_contents('http://localhost/rcts-qc/api/endpoints/inbound.php?action=s10_occupancy_update', false, $ctx);
        $results[] = ['stall'=>$stall['stall_asset_id'],'result'=>$raw ? json_decode($raw,true) : ['success'=>false]];
    }
    echo json_encode(['success'=>true,'pushed'=>count($results),'results'=>$results]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);