<?php
/**
 * OTP VERIFY ENDPOINT
 * api/endpoints/otp-verify.php
 *
 * Verifies a submitted OTP code. Called from the login page's
 * second step (OTP entry screen).
 *
 * POST body (JSON or form-data):
 *   email    string  - Staff email address
 *   otp      string  - 6-digit code the user entered
 *   purpose  string  - 'login' (default) or 'reset'
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// ── Parse input ──────────────────────────────────────────────────────────────
$input   = json_decode(file_get_contents('php://input'), true);
$email   = strtolower(trim($input['email']   ?? $_POST['email']   ?? ''));
$otp     = trim($input['otp']     ?? $_POST['otp']     ?? '');
$purpose = trim($input['purpose'] ?? $_POST['purpose'] ?? 'login');

if (!$email || !$otp) {
    echo json_encode(['success' => false, 'message' => 'Email and OTP code are required']);
    exit;
}

if (!preg_match('/^\d{6}$/', $otp)) {
    echo json_encode(['success' => false, 'message' => 'OTP must be a 6-digit number']);
    exit;
}

// ── OTP attempt rate limit: max 5 verify attempts per 15 minutes ─────────────
// We reuse the login_attempts table with event type 'otp_verify'
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$recentAttempts = db('rcts_login_attempts', [
    'email'        => 'eq.' . $email,
    'ip_address'   => 'eq.' . $ip,
    'attempted_at' => 'gte.' . date('c', strtotime('-15 minutes')),
    'success'      => 'eq.false',
    'select'       => 'id',
]);
if ($recentAttempts['success'] && count($recentAttempts['data'] ?? []) >= 5) {
    echo json_encode([
        'success' => false,
        'message' => 'Too many failed attempts. Please wait 15 minutes or request a new code.',
    ]);
    exit;
}

// ── Fetch the most recent unused, unexpired OTP ───────────────────────────────
$result = db('rcts_otp_codes', [
    'email'      => 'eq.' . $email,
    'purpose'    => 'eq.' . $purpose,
    'used'       => 'eq.false',
    'expires_at' => 'gte.' . date('c'),
    'order'      => 'created_at.desc',
    'limit'      => '1',
]);

if (!$result['success'] || empty($result['data'])) {
    // Log failed attempt
    db_create('rcts_login_attempts', [
        'email'      => $email,
        'ip_address' => $ip,
        'success'    => false,
    ]);
    echo json_encode(['success' => false, 'message' => 'No valid OTP found. Please request a new code.']);
    exit;
}

$record = $result['data'][0];

// ── Constant-time comparison to prevent timing attacks ───────────────────────
if (!hash_equals((string)$record['otp_code'], (string)$otp)) {
    // Log failed attempt
    db_create('rcts_login_attempts', [
        'email'      => $email,
        'ip_address' => $ip,
        'success'    => false,
    ]);
    echo json_encode(['success' => false, 'message' => 'Invalid OTP code. Please check and try again.']);
    exit;
}

// ── Mark OTP as used ─────────────────────────────────────────────────────────
db_patch('rcts_otp_codes', ['id' => 'eq.' . $record['id']], ['used' => true]);

// ── Log successful verification ───────────────────────────────────────────────
db_create('rcts_login_attempts', [
    'email'      => $email,
    'ip_address' => $ip,
    'success'    => true,
]);

echo json_encode(['success' => true, 'message' => 'OTP verified successfully']);