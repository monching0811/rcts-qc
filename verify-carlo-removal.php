<?php
$data = json_decode(file_get_contents('mock-data/subsystem7/properties.json'), true);
$properties = $data['properties'];
echo 'Total properties: ' . count($properties) . "\n";

$carlo_props = array_filter($properties, function($p) {
    return strpos($p['tdn_number'] ?? '', 'CARLO') !== false;
});

if (empty($carlo_props)) {
    echo "SUCCESS: All CARLO properties have been removed!\n";
} else {
    echo "REMAINING CARLO PROPERTIES:\n";
    foreach ($carlo_props as $p) {
        echo '  - ' . $p['tdn_number'] . "\n";
    }
}
?>
