<?php
/**
 * INTERNAL USER AUTH ENDPOINT  (updated — with login attempt tracking)
 * api/endpoints/internal-user-auth.php
 *
 * Step 1 of 2-step login:
 *   1. Validate email + password
 *   2. If valid, trigger OTP send (frontend calls otp-send.php)
 *
 * POST form-data: email, password
 *
 * Response JSON:
 *   { success: true,  otp_required: true,  message: "OTP sent..." }
 *   { success: false, message: "..." }
 *   { success: true,  otp_required: false, data: { ...staff } }  ← only if OTP disabled
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';

// ── Constants ─────────────────────────────────────────────────────────────────
const MAX_ATTEMPTS      = 5;    // max failed password attempts before lockout
const LOCKOUT_MINUTES   = 15;   // lockout duration in minutes
const ATTEMPT_WINDOW    = 15;   // window (minutes) to count attempts in
const OTP_ENABLED       = true; // set to false to disable OTP (dev/test only)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$email    = strtolower(trim($_POST['email']    ?? ''));
$password = $_POST['password'] ?? '';
$ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit;
}

// ── Check for lockout ─────────────────────────────────────────────────────────
$windowStart = date('c', strtotime('-' . ATTEMPT_WINDOW . ' minutes'));

$recentFails = db('rcts_login_attempts', [
    'email'        => 'eq.' . $email,
    'success'      => 'eq.false',
    'attempted_at' => 'gte.' . $windowStart,
    'select'       => 'id',
]);

$failCount = count($recentFails['data'] ?? []);

if ($failCount >= MAX_ATTEMPTS) {
    // Find the time of the most recent failure for a friendly message
    $lastFail = db('rcts_login_attempts', [
        'email'        => 'eq.' . $email,
        'success'      => 'eq.false',
        'attempted_at' => 'gte.' . $windowStart,
        'order'        => 'attempted_at.desc',
        'limit'        => '1',
    ]);
    $unlockTime = '';
    if (!empty($lastFail['data'][0]['attempted_at'])) {
        $unlockAt   = strtotime($lastFail['data'][0]['attempted_at']) + (LOCKOUT_MINUTES * 60);
        $unlockTime = ' Try again after ' . date('h:i A', $unlockAt) . '.';
    }
    echo json_encode([
        'success' => false,
        'message' => 'Account temporarily locked due to too many failed attempts.' . $unlockTime,
        'locked'  => true,
    ]);
    exit;
}

// ── Fetch user by email ───────────────────────────────────────────────────────
$user = db('rcts_internal_users', ['email' => 'eq.' . $email]);

if (!$user['success'] || empty($user['data'])) {
    // Log the failed attempt (don't reveal whether email exists)
    db_create('rcts_login_attempts', [
        'email'      => $email,
        'ip_address' => $ip,
        'success'    => false,
    ]);
    // Generic message — don't reveal "email not found"
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    exit;
}

$u = $user['data'][0];

// ── Role check ────────────────────────────────────────────────────────────────
$allowedRoles = ['treasurer', 'revenue_officer', 'auditor', 'admin'];
if (!in_array($u['role'], $allowedRoles)) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Not a staff account.']);
    exit;
}

// ── Status check ─────────────────────────────────────────────────────────────
if ($u['status'] !== 'active') {
    echo json_encode(['success' => false, 'message' => 'This account has been deactivated. Contact your administrator.']);
    exit;
}

// ── Verify password ───────────────────────────────────────────────────────────
if (!password_verify($password, $u['password_hash'])) {
    // Log the failed attempt
    db_create('rcts_login_attempts', [
        'email'      => $email,
        'ip_address' => $ip,
        'success'    => false,
    ]);

    $remaining = MAX_ATTEMPTS - ($failCount + 1);
    $msg = $remaining > 0
        ? "Invalid email or password. {$remaining} attempt(s) remaining before lockout."
        : 'Invalid email or password. Account is now locked for ' . LOCKOUT_MINUTES . ' minutes.';

    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// ── Password OK — clear old failed attempts for this email ───────────────────
// (best-effort, not critical)
// Note: We don't delete records for audit purposes — just note the success below.

// ── Build staff session object (used after OTP verification) ─────────────────
$staff = [
    'user_id'     => $u['user_id'],
    'full_name'   => $u['full_name'],
    'email'       => $u['email'],
    'role'        => $u['role'],
    'status'      => $u['status'],
    'login_token' => base64_encode($u['user_id'] . '|' . $u['email'] . '|' . time()),
];

// ── OTP flow ──────────────────────────────────────────────────────────────────
if (OTP_ENABLED) {
    // Log successful password check (OTP still pending)
    db_create('rcts_login_attempts', [
        'email'      => $email,
        'ip_address' => $ip,
        'success'    => true,   // password was correct; OTP pending
    ]);

    // Return signal to frontend — it must now call otp-send.php and show OTP input
    echo json_encode([
        'success'      => true,
        'otp_required' => true,
        'message'      => 'Password verified. An OTP has been sent to your email.',
        // Return staff data so the frontend can store it temporarily
        // (only persisted to session AFTER OTP is confirmed)
        'pending_staff' => $staff,
    ]);
} else {
    // OTP disabled (dev/test mode) — return session data directly
    db_create('rcts_login_attempts', [
        'email'      => $email,
        'ip_address' => $ip,
        'success'    => true,
    ]);
    echo json_encode([
        'success'      => true,
        'otp_required' => false,
        'data'         => $staff,
    ]);
}