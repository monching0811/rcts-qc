<?php
/**
 * MOCK: Subsystem 2 — Permits & Licensing (BPLO)
 * mock-data/subsystem2/permits-api.php
 *
 * Simulates the Quezon City Business Permit & Licensing Office (BPLO)
 * pushing business permit applications and approvals to RCTS.
 *
 * RCTS inbound action: s2_business_approved
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'get_permits';

// Load businesses from JSON file (like subsystem7 properties)
$businesses_file = __DIR__ . '/businesses.json';
$PERMITS = [];
if (file_exists($businesses_file)) {
    $businesses_data = json_decode(file_get_contents($businesses_file), true);
    $PERMITS = $businesses_data['businesses'] ?? [];
}

// Get all permits or filter by citizen
if ($action === 'get_permits') {
    $qcitizen = $_GET['qcitizen_id'] ?? null;
    $bin = $_GET['bin_number'] ?? null;
    
    $out = $PERMITS;
    if ($qcitizen) {
        $out = array_values(array_filter($PERMITS, fn($p) => $p['qcitizen_id'] === $qcitizen));
    } elseif ($bin) {
        $out = array_values(array_filter($PERMITS, fn($p) => $p['bin_number'] === $bin));
    }
    
    echo json_encode([
        'success' => true,
        'subsystem' => 'S2-Permits',
        'source' => 'BPLO - Business Permit & Licensing Office',
        'data' => $out,
        'count' => count($out)
    ]);
    exit;
}

// Get permit status for a specific BIN
if ($action === 'get_status') {
    $bin = $_GET['bin_number'] ?? null;
    
    if (!$bin) {
        echo json_encode(['success' => false, 'message' => 'bin_number required']);
        exit;
    }
    
    $permit = array_values(array_filter($PERMITS, fn($p) => $p['bin_number'] === $bin));
    
    if (empty($permit)) {
        echo json_encode(['success' => false, 'message' => 'Permit not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'subsystem' => 'S2-Permits',
        'data' => $permit[0]
    ]);
    exit;
}

// Simulate APPROVING a permit and pushing to RCTS
if ($action === 'approve_permit') {
    $bin = $_GET['bin_number'] ?? 'BIN-QC-2024-003';
    
    // Find the permit
    $permit = null;
    foreach ($PERMITS as $p) {
        if ($p['bin_number'] === $bin) {
            $permit = $p;
            break;
        }
    }
    
    if (!$permit) {
        echo json_encode(['success' => false, 'message' => 'Permit not found']);
        exit;
    }
    
    // Prepare payload for RCTS
    $payload = json_encode([
        'source' => 'S2-Permits',
        'action' => 's2_business_approved',
        'bin_number' => $permit['bin_number'],
        'qcitizen_id' => $permit['qcitizen_id'],
        'business_name' => $permit['business_name'],
        'nature_of_business' => $permit['nature_of_business'],
        'business_address' => $permit['business_address'],
        'gross_sales_declared' => $permit['gross_sales_declared'],
        'assessment_cycle' => $permit['assessment_cycle'],
        'permit_status' => 'Approved',
        'application_date' => $permit['application_date'],
        'approved_date' => date('Y-m-d'),
    ]);
    
    // Forward to RCTS inbound endpoint
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nX-API-Key: DEV-BYPASS-KEY-LOCAL\r\n",
            'content' => $payload
        ]
    ]);
    
    $url = 'http://localhost/rcts-qc/api/endpoints/inbound.php?action=s2_business_approved';
    $raw = @file_get_contents($url, false, $ctx);
    $res = $raw ? json_decode($raw, true) : ['success' => false, 'message' => 'No response from RCTS'];
    
    echo json_encode([
        'success' => true,
        'subsystem' => 'S2-Permits',
        'action' => 'approve_permit',
        'bin_number' => $bin,
        'signal_sent' => json_decode($payload),
        'rcts_response' => $res
    ]);
    exit;
}

// Push ALL permits to RCTS (for batch sync)
if ($action === 'sync_all') {
    $results = [];
    
    foreach ($PERMITS as $permit) {
        $payload = json_encode([
            'source' => 'S2-Permits',
            'action' => 's2_business_approved',
            'bin_number' => $permit['bin_number'],
            'qcitizen_id' => $permit['qcitizen_id'],
            'business_name' => $permit['business_name'],
            'nature_of_business' => $permit['nature_of_business'],
            'business_address' => $permit['business_address'],
            'gross_sales_declared' => $permit['gross_sales_declared'],
            'assessment_cycle' => $permit['assessment_cycle'],
            'permit_status' => $permit['permit_status'] === 'Active' ? 'Approved' : 'Pending',
        ]);
        
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nX-API-Key: DEV-BYPASS-KEY-LOCAL\r\n",
                'content' => $payload
            ]
        ]);
        
        $url = 'http://localhost/rcts-qc/api/endpoints/inbound.php?action=s2_business_approved';
        $raw = @file_get_contents($url, false, $ctx);
        $res = $raw ? json_decode($raw, true) : ['success' => false];
        
        $results[] = [
            'bin' => $permit['bin_number'],
            'result' => $res
        ];
    }
    
    echo json_encode([
        'success' => true,
        'subsystem' => 'S2-Permits',
        'synced' => count($results),
        'results' => $results
    ]);
    exit;
}

// Get mock clearance data for a business (to show on permit view)
if ($action === 'get_clearances') {
    $bin = $_GET['bin_number'] ?? null;
    
    // This would normally come from S4, but we mock it here for display
    $mockClearances = [
        'BIN-QC-2024-001' => [
            ['type' => 'Health', 'status' => 'Passed', 'issued_by' => 'QC Health Dept'],
            ['type' => 'Sanitary', 'status' => 'Passed', 'issued_by' => 'QC Sanitation'],
            ['type' => 'Fire', 'status' => 'Pending', 'issued_by' => 'BFP-QC']
        ],
        'BIN-QC-2024-002' => [
            ['type' => 'Health', 'status' => 'Passed', 'issued_by' => 'QC Health Dept'],
            ['type' => 'Sanitary', 'status' => 'Passed', 'issued_by' => 'QC Sanitation'],
            ['type' => 'Fire', 'status' => 'Passed', 'issued_by' => 'BFP-QC']
        ],
        'BIN-QC-2024-003' => [
            ['type' => 'Health', 'status' => 'Pending', 'issued_by' => 'QC Health Dept'],
            ['type' => 'Sanitary', 'status' => 'Pending', 'issued_by' => 'QC Sanitation'],
            ['type' => 'Fire', 'status' => 'Pending', 'issued_by' => 'BFP-QC']
        ],
    ];
    
    $out = $bin ? ($mockClearances[$bin] ?? []) : $mockClearances;
    
    echo json_encode([
        'success' => true,
        'subsystem' => 'S2-Permits',
        'bin_number' => $bin,
        'clearances' => $out
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action', 'available_actions' => [
    'get_permits',
    'get_status',
    'approve_permit',
    'sync_all',
    'get_clearances'
]]);
