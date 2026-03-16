<?php
require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/includes/db.php';

// Alter table to allow longer citizen IDs
$query = "ALTER TABLE rcts_traffic_violation ALTER COLUMN qcitizen_id TYPE VARCHAR(50)";

// Note: This is a direct SQL query since db_update doesn't support ALTER TABLE
// We'll use the supabase_request function directly

$result = supabase_request('rpc/exec_sql', 'POST', [
    'query' => $query
]);

if ($result && isset($result['success']) && $result['success']) {
    echo "✅ Successfully updated qcitizen_id column to VARCHAR(50)\n";
} else {
    echo "❌ Failed to update column: " . json_encode($result) . "\n";
}
?>