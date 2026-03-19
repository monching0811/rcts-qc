<?php
/**
 * ENDPOINT: User Logout Audit
 * api/endpoints/logout.php
 *
 * Records a logout event in the audit log for the current user.
 * Expects POST with session token or user info in session/cookie.
 */

require_once __DIR__ . '/../middleware/cors.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/supabase.php';

header('Content-Type: application/json');

session_start();

// Helper: Log audit event to rcts_audit_log
if (!function_exists('audit_log')) {
    function audit_log($actor, $event, $details = null) {
        $entry = [
            'actor' => $actor,
            'event' => $event,
            'details' => is_array($details) ? json_encode($details) : $details,
        ];
        supabase_request('rcts_audit_log', 'POST', [], $entry, true);
    }
}

// Try to get user info from session, POST, or token
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$actor = $_SESSION['user_id'] ?? $_SESSION['qcitizen_id'] ?? $body['qcitizen_id'] ?? 'unknown';
$details = [
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'time' => date('c'),
];

audit_log($actor, 'logout', $details);

echo json_encode([
    'success' => true,
    'message' => 'Logout audited',
    'actor' => $actor,
    'timestamp' => $details['time']
]);
