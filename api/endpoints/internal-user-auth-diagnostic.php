<?php
// Simple diagnostic for endpoint availability
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'internal-user-auth.php is reachable and running.',
    'time' => date('c'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'path' => __FILE__,
    'server' => $_SERVER['SERVER_NAME'] ?? '',
    'php_version' => PHP_VERSION
]);
