<?php
// INTERNAL USER AUTH ENDPOINT
// POST: email, password
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit;
}

// Fetch user by email
$user = db('rcts_internal_users', ['email' => 'eq.' . strtolower($email)]);
if (!$user['success'] || empty($user['data'])) {
    echo json_encode(['success' => false, 'message' => 'Account not found']);
    exit;
}
$u = $user['data'][0];

if (!in_array($u['role'], ['treasurer','revenue_officer','auditor','admin'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Not a staff account.']);
    exit;
}
if ($u['status'] !== 'active') {
    echo json_encode(['success' => false, 'message' => 'Account is not active.']);
    exit;
}

// Verify password
if (!password_verify($password, $u['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid password']);
    exit;
}

// Build session user object
$staff = [
    'user_id' => $u['user_id'],
    'full_name' => $u['full_name'],
    'email' => $u['email'],
    'role' => $u['role'],
    'status' => $u['status'],
    'login_token' => base64_encode($u['user_id'] . '|' . $u['email'] . '|' . time())
];

echo json_encode(['success' => true, 'data' => $staff]);
