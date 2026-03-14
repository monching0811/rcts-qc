<?php
$url = 'http://localhost/rcts-qc/mock-data/subsystem2/permits-api.php?action=get_permits&qcitizen_id=92be37af-7c34-4c9b-80cb-47cde7c3a9fd';
$response = @file_get_contents($url);
if ($response === false) {
    echo "Error fetching from S2 API\n";
} else {
    $data = json_decode($response, true);
    echo "S2 API Returns for Raven:\n\n";
    if (isset($data['data'])) {
        foreach ($data['data'] as $biz) {
            echo "• BIN: " . $biz['bin_number'] . "\n";
            echo "  Name: " . $biz['business_name'] . "\n\n";
        }
    } else {
        echo "No data in response\n";
        echo json_encode($data, JSON_PRETTY_PRINT);
    }
}
