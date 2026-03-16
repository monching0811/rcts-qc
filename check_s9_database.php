<?php
// Check S9 database for violations
$s9_url = 'https://nhnynmdhamvspyujjpws.supabase.co';
$s9_key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im5obnlubWRoYW12c3B5dWpqcHdzIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzM0NjMxNzgsImV4cCI6MjA4OTAzOTE3OH0.f6mSOPkp1t0YOwxniE1v4Gl4cuKevYnrokZVzHBE0Lo';

function s9_request($endpoint, $method = 'GET', $data = null) {
    global $s9_url, $s9_key;
    $url = $s9_url . '/rest/v1/' . $endpoint;
    $headers = [
        'apikey: ' . $s9_key,
        'Authorization: Bearer ' . $s9_key,
        'Content-Type: application/json'
    ];

    $context = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers)
        ]
    ];

    if ($data) {
        $context['http']['content'] = json_encode($data);
    }

    $result = file_get_contents($url, false, stream_context_create($context));
    return json_decode($result, true);
}

// Query violations from S9 - try more table names
$table_names = [
    'traffic_violations', 'violations', 'traffic_fines', 'traffic_tickets', 'tickets',
    's9_traffic_violations', 'traffic_violation', 'violation_records', 'traffic_records',
    'enforcement_records', 'parking_tickets', 'traffic_citations'
];

$violations = null;
$found_table = null;
foreach ($table_names as $table) {
    $violations = s9_request($table);
    if ($violations && !isset($violations['message']) && !isset($violations['error'])) {
        $found_table = $table;
        echo "Found table: $table\n";
        break;
    }
}

if (!$violations || isset($violations['message']) || isset($violations['error'])) {
    echo "No violations table found in S9 database. Tried: " . implode(', ', $table_names) . "\n";
    echo "Last response: " . json_encode($violations) . "\n";

// Try very simple table names
$simple_names = ['traffic', 'violation', 'ticket', 'fine', 'citation', 'record', 'data'];

foreach ($simple_names as $table) {
    $violations = s9_request($table);
    if ($violations && !isset($violations['message']) && !isset($violations['error'])) {
        $found_table = $table;
        echo "Found table: $table\n";
        break;
    }
}

if (!$found_table) {
    echo "Still no table found. S9 database appears empty or tables have very different names.\n";
    echo "Possible issues:\n";
    echo "1. Data was deleted from S9's database\n";
    echo "2. Table name is completely different\n";
    echo "3. Credentials are for wrong Supabase project\n";
    echo "4. Data is stored in a different way (not REST API accessible)\n";
    exit;
}
}

echo "Citizens with violations in Subsystem 9 database:\n";
$citizens = [];
foreach ($violations as $v) {
    $citizen_id = $v['qcitizen_id'] ?? $v['citizen_id'] ?? null;
    if ($citizen_id) {
        $citizens[$citizen_id] = ($citizens[$citizen_id] ?? 0) + 1;
    }
}

foreach ($citizens as $id => $count) {
    echo "- $id ($count violation(s))\n";
}

echo "\nTotal violations in S9: " . count($violations) . "\n";
?>