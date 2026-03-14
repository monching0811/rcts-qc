<?php
require_once __DIR__ . '/api/config/supabase.php';

echo "Deleting all pending bills...\n";

$result = supabase_request('rcts_assessment_billing_hub', 'DELETE', ['status' => 'eq.Pending'], [], true);

if ($result['success']) {
    echo "Successfully deleted pending bills.\n";
    echo "Deleted count: " . count($result['data']) . "\n";
} else {
    echo "Error deleting bills: " . ($result['error'] ?? 'Unknown error') . "\n";
}
?>