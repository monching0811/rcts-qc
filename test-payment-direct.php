<?php
// Test payment.php directly
$_GET['action'] = 'get_pending_bills';
$_GET['qcitizen_id'] = 'a135da1e-6727-430e-9771-e15688e6f79e';

// Set headers
header('Content-Type: application/json');

// Check if db_select exists first
if (!function_exists('db_select')) {
  require_once __DIR__ . '/../config/supabase.php';
  require_once __DIR__ . '/../../includes/db.php';
}

echo "Testing payment.php endpoint...\n";
echo "include path ok\n";

// Now include payment
include 'payment.php';
?>
