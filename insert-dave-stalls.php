<?php
require_once 'api/config/supabase.php';

$stalls = [
    [
        'stall_asset_id' => 'STL-QC-2026-DAVE-001',
        'qcitizen_id' => 'eacd934b-0195-4640-b37c-aa0a8b40a9d2',
        'facility_location_id' => 'MKT-NOVALICHES-STALL-ROW-C',
        'facility_name' => 'Novaliches Public Market',
        'stall_number' => 'C-001',
        'stall_type' => 'Market',
        'area_sqm' => 20,
        'monthly_rental_rate' => 3500,
        'lease_start_date' => '2026-01-01',
        'lease_end_date' => '2026-12-31',
        'occupancy_status_flag' => 'Active',
        'occupancy_verification_method' => 'QR',
        'occupancy_last_verified' => '2026-03-12T09:15:00Z',
        'verification_source_subsystem' => 10
    ],
    [
        'stall_asset_id' => 'STL-QC-2026-DAVE-002',
        'qcitizen_id' => 'eacd934b-0195-4640-b37c-aa0a8b40a9d2',
        'facility_location_id' => 'MKT-NOVALICHES-STALL-ROW-D',
        'facility_name' => 'Novaliches Public Market',
        'stall_number' => 'D-001',
        'stall_type' => 'Market',
        'area_sqm' => 25,
        'monthly_rental_rate' => 4000,
        'lease_start_date' => '2026-02-15',
        'lease_end_date' => '2027-02-14',
        'occupancy_status_flag' => 'Active',
        'occupancy_verification_method' => 'IoT',
        'occupancy_last_verified' => '2026-03-12T10:30:00Z',
        'verification_source_subsystem' => 10
    ]
];

foreach ($stalls as $stall) {
    $result = db_insert('rcts_public_asset_stall', $stall);
    echo 'Inserted stall ' . $stall['stall_asset_id'] . ': ' . ($result['success'] ? 'OK' : 'FAILED - ' . json_encode($result)) . PHP_EOL;
}