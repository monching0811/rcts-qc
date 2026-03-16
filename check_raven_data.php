<?php
require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/includes/db.php';

echo "Checking TV-RAVEN-002:\n";

$v = db_select('rcts_traffic_violation', ['violation_ticket_id' => 'eq.TV-RAVEN-002']);
if (!empty($v['data'])) {
    echo "Violation exists:\n";
    print_r($v['data'][0]);
} else {
    echo "Violation not found\n";
}

echo "\n";

$b = db_select('rcts_assessment_billing_hub', ['asset_id' => 'eq.TV-RAVEN-002']);
if (!empty($b['data'])) {
    echo "Bill exists:\n";
    print_r($b['data'][0]);
} else {
    echo "Bill not found\n";
}
?>