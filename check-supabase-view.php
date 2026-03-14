<?php
require_once 'api/config/supabase.php';

$citizen_id = 'a135da1e-6727-430e-9771-e15688e6f79e';

// Query Supabase directly to see what's being returned
$url = SUPABASE_REST . '/v_citizen_pending_bills?qcitizen_id=eq.' . $citizen_id;
$headers = [
    'apikey: ' . SUPABASE_ANON_KEY,
    'Authorization: Bearer ' . SUPABASE_ANON_KEY,
    'Content-Type: application/json'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

echo "Direct Supabase Response:\n";
echo "Count: " . count($data) . "\n\n";

if (!empty($data)) {
  echo "First Bill Structure:\n";
  echo "Keys: " . implode(', ', array_keys($data[0])) . "\n\n";
  echo "Full First Bill:\n";
  print_r($data[0]);
}
?>
