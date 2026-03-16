<?php
require_once __DIR__ . '/api/config/supabase.php';
require_once __DIR__ . '/includes/db.php';

// Query traffic violations
$result = db_select('rcts_traffic_violation', []);
echo "Violations:\n";
echo json_encode($result, JSON_PRETTY_PRINT);

// Query bills
$result2 = db_select('rcts_assessment_billing_hub', ['bill_type' => 'eq.TrafficFine']);
echo "\n\nBills:\n";
echo json_encode($result2, JSON_PRETTY_PRINT);
?>