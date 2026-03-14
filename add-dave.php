<?php
require_once 'api/config/supabase.php';
$dave = [
    'qcitizen_id' => 'eacd934b-0195-4640-b37c-aa0a8b40a9d2',
    'full_name' => 'Dave Mercado',
    'date_of_birth' => '1985-05-15',
    'address' => '123 Novaliches Street, Quezon City',
    'email' => 'dave.mercado@email.com',
    'mobile_no' => '+639123456789',
    'is_senior_citizen' => false,
    'is_pwd' => false,
    'is_solo_parent' => false,
    'role' => 'citizen',
    'status' => 'active'
];
$result = db_insert('rcts_citizen_registry', $dave);
echo 'Insert result: ' . json_encode($result) . PHP_EOL;
?>