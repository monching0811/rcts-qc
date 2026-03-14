<?php
/**
 * MOCK: Subsystem 3 — Social Services & AICS
 * mock-data/subsystem3/social-aid-api.php
 *
 * Simulates the Social Services department pushing an Aid to
 * Individuals in Crisis Situations (AICS) payout request to RCTS.
 *
 * In production, S3 posts to: POST /api/endpoints/inbound.php?action=s3_aid_request
 * This mock can be called directly by the test page.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'get_payout_list';

$PAYOUT_LIST = [
    [
        'qcitizen_id'        => 'QC-2024-000001',
        'full_name'          => 'Juan Dela Cruz',
        'program_name'       => 'AICS Emergency Cash Assistance',
        'program_id'         => 'AICS-2025-001',
        'approved_amount'    => 3000.00,
        'disbursement_method'=> 'DigitalWallet',
        'scheduled_date'     => date('Y-m-d'),
        'priority_flag'      => 'Normal',
        'originating_dept_id'=> 3
    ],
    [
        'qcitizen_id'        => 'QC-2024-000002',
        'full_name'          => 'Maria Santos',
        'program_name'       => 'AICS Emergency Cash Assistance',
        'program_id'         => 'AICS-2025-001',
        'approved_amount'    => 3000.00,
        'disbursement_method'=> 'BankTransfer',
        'scheduled_date'     => date('Y-m-d'),
        'priority_flag'      => 'Normal',
        'originating_dept_id'=> 3
    ],
    [
        'qcitizen_id'        => 'QC-2024-000003',
        'full_name'          => 'Pedro Reyes',
        'program_name'       => 'Livelihood Assistance Grant',
        'program_id'         => 'LAG-2025-003',
        'approved_amount'    => 5000.00,
        'disbursement_method'=> 'DigitalWallet',
        'scheduled_date'     => date('Y-m-d', strtotime('+3 days')),
        'priority_flag'      => 'Normal',
        'originating_dept_id'=> 3
    ],
];

if ($action === 'get_payout_list') {
    echo json_encode([
        'success'    => true,
        'subsystem'  => 'S3-Social',
        'program'    => 'AICS & Livelihood',
        'data'       => $PAYOUT_LIST,
        'total'      => array_sum(array_column($PAYOUT_LIST, 'approved_amount')),
        'generated_at'=> date('c')
    ]);
    exit;
}

if ($action === 'push_to_rcts') {
    // Forward each beneficiary to RCTS inbound endpoint
    $base    = dirname(dirname(dirname(__FILE__))) . '/api/endpoints/inbound.php';
    $results = [];
    foreach ($PAYOUT_LIST as $recipient) {
        $payload = json_encode([
            'source'              => 'S3-Social',
            'action'              => 's3_aid_request',
            'qcitizen_id'         => $recipient['qcitizen_id'],
            'program_id'          => $recipient['program_id'],
            'program_name'        => $recipient['program_name'],
            'approved_amount'     => $recipient['approved_amount'],
            'disbursement_method' => $recipient['disbursement_method'],
            'scheduled_date'      => $recipient['scheduled_date'],
            'priority_flag'       => $recipient['priority_flag'],
            'originating_dept_id' => $recipient['originating_dept_id'],
        ]);
        // Internal PHP-to-PHP call
        $ctx = stream_context_create(['http'=>[
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nX-API-Key: DEV-BYPASS-KEY-LOCAL\r\n",
            'content' => $payload
        ]]);
        $url = 'http://localhost/rcts-qc/api/endpoints/inbound.php?action=s3_aid_request';
        $raw = @file_get_contents($url, false, $ctx);
        $results[] = ['qcitizen_id' => $recipient['qcitizen_id'], 'result' => $raw ? json_decode($raw, true) : ['success'=>false,'message'=>'No response']];
    }
    echo json_encode(['success'=>true,'pushed'=>count($results),'results'=>$results]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);