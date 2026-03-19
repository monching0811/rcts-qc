<?php
// Simple test to check what's happening with the admin API

header('Content-Type: application/json');

echo "Starting...<br>";
flush();

ob_flush();

try {
    echo "Trying to include constants...<br>";
    flush();
    require_once __DIR__ . '/api/config/constants.php';
    echo "Constants included OK<br>";
    flush();
} catch (Exception $e) {
    echo "Error including constants: " . $e->getMessage() . "<br>";
}

try {
    echo "Trying to include db...<br>";
    flush();
    require_once __DIR__ . '/includes/db.php';
    echo "DB included OK<br>";
    flush();
} catch (Exception $e) {
    echo "Error including db: " . $e->getMessage() . "<br>";
}

echo json_encode(['success' => true, 'message' => 'Test complete']);
