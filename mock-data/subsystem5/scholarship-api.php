<?php
/**
 * MOCK: Subsystem 5 — QC Scholarship / Education
 * mock-data/subsystem5/scholarship-api.php
 *
 * Simulates the QC Scholarship Office pushing stipend payroll to RCTS.
 * RCTS inbound action: s5_scholarship_request
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'get_scholars';

$SCHOLARS = [
    ['qcitizen_id'=>'QC-2024-000001','full_name'=>'Juan Dela Cruz',   'school'=>'UP Diliman',          'course'=>'BS Computer Science',  'year_level'=>3,'approved_amount'=>6000.00,'program_id'=>'QCS-2025-BATCH1','program_name'=>'QC-Iskolar Stipend','scheduled_date'=>date('Y-m-d'),'priority_flag'=>'Normal','originating_dept_id'=>5,'disbursement_method'=>'DigitalWallet'],
    ['qcitizen_id'=>'QC-2024-000002','full_name'=>'Maria Santos',     'school'=>'Polytechnic Univ. PH','course'=>'BS Nursing',            'year_level'=>2,'approved_amount'=>6000.00,'program_id'=>'QCS-2025-BATCH1','program_name'=>'QC-Iskolar Stipend','scheduled_date'=>date('Y-m-d'),'priority_flag'=>'Normal','originating_dept_id'=>5,'disbursement_method'=>'BankTransfer'],
    ['qcitizen_id'=>'QC-2024-000003','full_name'=>'Pedro Reyes',      'school'=>'PLM Manila',          'course'=>'BS Engineering',        'year_level'=>1,'approved_amount'=>6000.00,'program_id'=>'QCS-2025-BATCH1','program_name'=>'QC-Iskolar Stipend','scheduled_date'=>date('Y-m-d'),'priority_flag'=>'Normal','originating_dept_id'=>5,'disbursement_method'=>'DigitalWallet'],
    ['qcitizen_id'=>'QC-2024-000006','full_name'=>'Cynthia Flores',   'school'=>'QC Polytechnic',     'course'=>'BS Accountancy',        'year_level'=>4,'approved_amount'=>6000.00,'program_id'=>'QCS-2025-BATCH1','program_name'=>'QC-Iskolar Stipend','scheduled_date'=>date('Y-m-d', strtotime('+7 days')),'priority_flag'=>'Normal','originating_dept_id'=>5,'disbursement_method'=>'BankTransfer'],
];

if ($action === 'get_scholars') {
    echo json_encode([
        'success'   => true,
        'subsystem' => 'S5-Education',
        'batch'     => 'QCS-2025-BATCH1',
        'data'      => $SCHOLARS,
        'total'     => array_sum(array_column($SCHOLARS, 'approved_amount')),
        'count'     => count($SCHOLARS),
    ]);
    exit;
}

if ($action === 'push_to_rcts') {
    $results = [];
    foreach ($SCHOLARS as $s) {
        $payload = json_encode([
            'source'              => 'S5-Education',
            'action'              => 's5_scholarship_request',
            'qcitizen_id'         => $s['qcitizen_id'],
            'program_id'          => $s['program_id'],
            'program_name'        => $s['program_name'],
            'approved_amount'     => $s['approved_amount'],
            'disbursement_method' => $s['disbursement_method'],
            'scheduled_date'      => $s['scheduled_date'],
            'priority_flag'       => $s['priority_flag'],
            'originating_dept_id' => $s['originating_dept_id'],
        ]);
        $ctx = stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/json\r\nX-API-Key: DEV-BYPASS-KEY-LOCAL\r\n",'content'=>$payload]]);
        $raw = @file_get_contents('http://localhost/rcts-qc/api/endpoints/inbound.php?action=s5_scholarship_request', false, $ctx);
        $results[] = ['qcitizen_id'=>$s['qcitizen_id'],'result'=>$raw ? json_decode($raw,true) : ['success'=>false,'message'=>'No response']];
    }
    echo json_encode(['success'=>true,'pushed'=>count($results),'results'=>$results]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);