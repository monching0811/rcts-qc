<?php
// Test subsystem 7 endpoint
$response = file_get_contents('http://localhost/rcts-qc/api/endpoints/subsystem7.php?action=get_property_by_citizen&qcitizen_id=b529bf30-50bf-43ab-a314-cc4c2f79c3f5');
$data = json_decode($response, true);

echo "========================================\n";
echo "SUBSYSTEM 7 ENDPOINT TEST\n";
echo "========================================\n\n";

if ($data['success']) {
    echo "✅ Response Status: SUCCESS\n";
    echo "Property Count: " . ($data['data']['property_count'] ?? 'N/A') . "\n\n";
    
    echo "Properties:\n";
    foreach ($data['data']['properties'] ?? [] as $p) {
        echo "  ✓ " . $p['tdn_number'] . "\n";
        echo "    Property: " . $p['owner_name'] . "\n";
        echo "    Class: " . $p['property_class'] . "\n";
        echo "    Address: " . $p['property_address'] . "\n";
        echo "    Market Value: ₱" . number_format($p['current_market_value'] ?? 0, 2) . "\n";
        echo "    Assessed Value: ₱" . number_format($p['assessed_value'] ?? 0, 2) . "\n";
        echo "    Annual Tax: ₱" . number_format($p['total_annual_tax'] ?? 0, 2) . "\n\n";
    }
    echo "========================================\n";
    echo "✅ These properties will now appear in:\n";
    echo "   Your Registered Properties (S7 section)\n";
    echo "========================================\n";
} else {
    echo "❌ Failed to get properties\n";
    echo "Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    echo "\nFull Response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
}
