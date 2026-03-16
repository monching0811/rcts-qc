<?php
require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/includes/db.php';

// Get citizens with violations
$violations = db_select('rcts_traffic_violation', ['qcitizen_id' => 'not.is.null']);
$citizen_ids = array_unique(array_column($violations['data'] ?? [], 'qcitizen_id'));

echo "Citizens with traffic violations:\n";
foreach ($citizen_ids as $id) {
    // Try to get name from citizen registry
    $citizen = db_select('rcts_citizen_registry', ['qcitizen_id' => 'eq.' . $id]);
    $name = ($citizen['data'][0]['full_name'] ?? 'Unknown Citizen');
    echo "- $id: $name\n";
}
?>