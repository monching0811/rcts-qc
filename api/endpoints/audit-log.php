<?php
/**
 * ENDPOINT: Unified Audit Log
 * api/endpoints/audit-log.php
 *
 * Accepts POST requests to record audit events from the frontend (login, etc).
 * Accepts GET requests to retrieve audit logs.
 * 
 * POST: { actor, event, details }
 * GET: ?action=list - returns all logs
 */

require_once __DIR__ . '/../middleware/cors.php';
require_once __DIR__ . '/../config/supabase.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache');

// Handle GET request to list logs
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'list') {
    $result = supabase_request('rcts_audit_log', 'GET', ['order' => 'ts.desc', 'limit' => 100]);
    if (!$result['success']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to fetch logs',
            'error' => $result['error'] ?? 'Unknown error'
        ]);
        exit;
    }
    
    $logs = $result['data'] ?? [];
    echo json_encode(['success' => true, 'logs' => $logs]);
    exit;
}

// Handle POST request to add log
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$actor = $body['actor'] ?? 'unknown';
$event = $body['event'] ?? 'unknown';
$details = $body['details'] ?? null;

if (!$actor || !$event) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'actor and event required']);
    exit;
}

$entry = [
    'actor' => $actor,
    'event' => $event,
    'details' => is_array($details) ? json_encode($details) : $details,
];

$result = supabase_request('rcts_audit_log', 'POST', [], $entry, true);
if (!$result['success']) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to record audit log',
        'error' => $result['error'] ?? 'Unknown error',
        'data' => $result['data'] ?? null
    ]);
    exit;
}
echo json_encode([
    'success' => true,
    'message' => 'Audit log recorded',
    'data' => $result['data']
]);
