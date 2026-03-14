<?php
/**
 * AUTH MIDDLEWARE
 * ───────────────
 * Validates API keys from external subsystems before allowing access.
 * Include this in endpoint files that should only accept subsystem calls.
 *
 * USAGE:
 *   require_once __DIR__ . '/../middleware/auth.php';
 *   $caller = require_subsystem_auth(); // returns e.g. 'S2', 'S4', etc.
 */

require_once __DIR__ . '/../config/api-keys.php';

/**
 * Validate that the request comes from an authorized subsystem.
 * Returns the subsystem code on success, sends 401 and exits on failure.
 */
function require_subsystem_auth(): string {
    $key = $_SERVER['HTTP_X_API_KEY']
        ?? $_SERVER['HTTP_AUTHORIZATION']
        ?? $_GET['api_key']
        ?? '';

    // Strip "Bearer " prefix if sent as Authorization header
    $key = str_replace('Bearer ', '', $key);

    $subsystem = validate_api_key($key);

    if (!$subsystem) {
        http_response_code(401);
        echo json_encode([
            'success'   => false,
            'error'     => 'Unauthorized. Invalid or missing API key.',
            'hint'      => 'Send your API key in the X-API-Key header.',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    return $subsystem;
}

/**
 * For citizen-facing endpoints: validate a session token
 * (simple base64 token from mock S1 login).
 */
function require_citizen_auth(): array {
    $token = $_SERVER['HTTP_AUTHORIZATION'] ?? $_GET['token'] ?? '';
    $token = str_replace('Bearer ', '', $token);

    if (!$token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'No session token provided.']);
        exit;
    }

    $decoded = base64_decode($token);
    $parts   = explode(':', $decoded);

    if (count($parts) < 2) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid session token.']);
        exit;
    }

    return ['qcitizen_id' => $parts[0], 'timestamp' => $parts[1] ?? null];
}

/**
 * Standard JSON response helper (used across all endpoint files).
 */
function api_response(bool $success, string $message, $data = null, int $code = 200): void {
    http_response_code($code);
    echo json_encode([
        'success'   => $success,
        'message'   => $message,
        'data'      => $data,
        'timestamp' => date('Y-m-d H:i:s'),
        'system'    => 'RCTS-QC'
    ], JSON_PRETTY_PRINT);
    exit;
}
