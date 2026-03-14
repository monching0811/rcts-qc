<?php
require_once __DIR__ . '/api/config/supabase.php';

// Search for Raven in the citizen registry
$email = 'ravengutierrez2018@gmail.com';

$result = db_select('qcitizen_master_registry', [
    'email' => 'eq.' . $email
]);

if ($result['success'] && !empty($result['data'])) {
    $citizen = $result['data'][0];
    echo "Found citizen:\n";
    echo "QCitizen ID: " . $citizen['qcitizen_id'] . "\n";
    echo "Full Name: " . $citizen['full_name'] . "\n";
    echo "Email: " . $citizen['email'] . "\n";
    echo "Address: " . $citizen['address'] . "\n";
} else {
    echo "Citizen not found with email: " . $email . "\n";
    echo "Checking all citizens...\n";
    
    $all_result = db_select('qcitizen_master_registry', []);
    if ($all_result['success']) {
        echo "Total citizens in database: " . count($all_result['data']) . "\n";
        foreach ($all_result['data'] as $c) {
            echo "- " . $c['qcitizen_id'] . " | " . $c['full_name'] . " | " . $c['email'] . "\n";
        }
    }
}
?>
