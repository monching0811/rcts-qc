<?php
require_once __DIR__ . '/api/config/supabase.php';

$carlo_id = 'QC-2024-000009';
$url = 'http://localhost/rcts-qc/mock-data/subsystem1/citizen-registry-api.php?action=verify_login&email=jackbobert24%40gmail.com&password=demo123';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

echo "═══════════════════════════════════════════════════════════════\n";
echo "VERIFICATION: Carlo Nicolas Login & Pending Bills\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Login Test:\n";
echo "──────────────────────────────────────────────────────────────\n";
echo "HTTP Status: " . $http_code . "\n";
echo "Success: " . ($data['success'] ? 'YES ✅' : 'NO ❌') . "\n\n";

if ($data['success']) {
    $citizen = $data['data'];
    echo "Citizen Profile:\n";
    echo "  Citizen ID: " . $citizen['qcitizen_id'] . "\n";
    echo "  Name: " . $citizen['full_name'] . "\n";
    echo "  Email: " . $citizen['email'] . "\n";
    echo "  Role: " . $citizen['role'] . "\n";
    echo "  Total Pending Bills: " . count($citizen['pending_bills'] ?? []) . "\n\n";
    
    if (!empty($citizen['pending_bills'])) {
        echo "Pending Bills Details:\n";
        echo "──────────────────────────────────────────────────────────────\n";
        
        $total = 0;
        foreach ($citizen['pending_bills'] as $idx => $bill) {
            echo ($idx + 1) . ". " . $bill['bill_reference_no'] . "\n";
            echo "   Type: " . $bill['bill_type'] . "\n";
            echo "   Amount: ₱" . number_format($bill['total_amount_due'], 2) . "\n";
            echo "   Status: " . $bill['status'] . "\n\n";
            $total += $bill['total_amount_due'];
        }
        
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "TOTAL PENDING AMOUNT DUE: ₱" . number_format($total, 2) . "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
    }
} else {
    echo "❌ Login failed!\n";
    echo "Message: " . ($data['message'] ?? 'Unknown error') . "\n";
}
?>
