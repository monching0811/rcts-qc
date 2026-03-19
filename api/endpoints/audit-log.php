<?php
/**
 * ENDPOINT: Unified Audit Log (for client-side events)
 * api/endpoints/audit-log.php
 *
 * Accepts POST requests to record audit events from the frontend (login, etc).
 * Expects JSON: { actor, event, details }
 */

require_once __DIR__ . '/../middleware/cors.php';
require_once __DIR__ . '/../config/supabase.php';

header('Content-Type: application/json');

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
