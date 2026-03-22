<?php
// Simulate the form submission to allocate-carlo-bills-browser.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['create_bills'] = true;

// Include the file
ob_start();
require_once __DIR__ . '/allocate-carlo-bills-browser.php';
ob_end_clean();

// Check results
echo "✓ Script executed successfully\n";
echo "Success: " . ($success ? "YES" : "NO") . "\n";
echo "Message preview: " . substr($message, 0, 100) . "...\n";
?>
