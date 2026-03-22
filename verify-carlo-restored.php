<?php
$data = json_decode(file_get_contents('mock-data/subsystem7/properties.json'), true);
echo 'Total properties: ' . count($data['properties']) . "\n";

$carlo_props = array_filter($data['properties'], function($p) {
    return strpos($p['tdn_number'], 'CARLO') !== false;
});

echo 'CARLO properties restored: ' . count($carlo_props) . "\n";
foreach ($carlo_props as $p) {
    echo '  ✓ ' . $p['tdn_number'] . ' - ₱' . number_format($p['total_annual_tax'], 2) . "\n";
}
?>
