<?php
/**
 * SIMULATION: Subsystem 7 - Urban Planning / Assessor
 * api/endpoints/subsystem7.php
 * 
 * This simulates the API that Subsystem 7 WOULD provide in production.
 * In a real implementation, this would be calls to the Assessor's GIS system.
 * 
 * For TO-BE demonstration, this returns mock property data.
 * 
 * TO-BE: GIS-Linked Auto-Lookup - System automatically fetches property data 
 * using QCitizen ID instead of manual TDN entry.
 */

require_once __DIR__ . '/../middleware/cors.php';
require_once __DIR__ . '/../config/supabase.php';

$action = $_GET['action'] ?? '';

// Mock property data (simulating what S7 Assessor would provide)
// TO-BE: Properties linked to QCitizen IDs from Subsystem 1
$mock_properties = [
    // Vince Nico Escala (b529bf30-50bf-43ab-a314-cc4c2f79c3f5) - Real user from S1
    'b529bf30-50bf-43ab-a314-cc4c2f79c3f5' => [
        [
            'tdn_number' => 'TDN-QC-2024-101',
            'property_index_number' => 'PIN-101-2024',
            'qcitizen_id' => 'b529bf30-50bf-43ab-a314-cc4c2f79c3f5',
            'property_owner' => 'Vince Nico Escala',
            'property_address' => '101 Manila Road, Quezon City',
            'property_type' => 'Residential',
            'lot_area' => 300.00,
            'floor_area' => 200.00,
            'market_value' => 3000000,
            'assessed_value' => 600000,
            'zoning_classification' => 'R1',
            'tax_year' => 2024,
            'tax_declaration_number' => 'TDN-2024-101',
            'gis_coordinate_lat' => 14.6760,
            'gis_coordinate_lng' => 121.0437,
            'status' => 'Active',
            'annual_rpt_due' => 12000,
            'annual_sef_due' => 6000,
            'total_annual_tax' => 18000,
            'tax_clearance_status' => 'Pending',
            'assessed_value_update_flag' => false
        ]
    ],
    // Raven Pogi (92be37af-7c34-4c9b-80cb-47cde7c3a9fd) - Real user from S1
    '92be37af-7c34-4c9b-80cb-47cde7c3a9fd' => [
        [
            'tdn_number' => 'TDN-QC-2024-102',
            'property_index_number' => 'PIN-102-2024',
            'qcitizen_id' => '92be37af-7c34-4c9b-80cb-47cde7c3a9fd',
            'property_owner' => 'Raven Pogi',
            'property_address' => '202 EDSA, Quezon City',
            'property_type' => 'Commercial',
            'lot_area' => 400.00,
            'floor_area' => 1000.00,
            'market_value' => 12000000,
            'assessed_value' => 2400000,
            'zoning_classification' => 'C1',
            'tax_year' => 2024,
            'tax_declaration_number' => 'TDN-2024-102',
            'gis_coordinate_lat' => 14.6780,
            'gis_coordinate_lng' => 121.0450,
            'status' => 'Active',
            'annual_rpt_due' => 48000,
            'annual_sef_due' => 24000,
            'total_annual_tax' => 72000,
            'tax_clearance_status' => 'Cleared',
            'assessed_value_update_flag' => false
        ]
    ],
    // vince o nico (bcb37eaa-4b68-48a5-9110-0439c7a3865e) - Real user from S1
    'bcb37eaa-4b68-48a5-9110-0439c7a3865e' => [
        [
            'tdn_number' => 'TDN-QC-2024-103',
            'property_index_number' => 'PIN-103-2024',
            'qcitizen_id' => 'bcb37eaa-4b68-48a5-9110-0439c7a3865e',
            'property_owner' => 'vince o nico',
            'property_address' => '303 Quezon Avenue, Quezon City',
            'property_type' => 'Residential',
            'lot_area' => 150.00,
            'floor_area' => 100.00,
            'market_value' => 1500000,
            'assessed_value' => 300000,
            'zoning_classification' => 'R1',
            'tax_year' => 2024,
            'tax_declaration_number' => 'TDN-2024-103',
            'gis_coordinate_lat' => 14.6790,
            'gis_coordinate_lng' => 121.0460,
            'status' => 'Active',
            'annual_rpt_due' => 6000,
            'annual_sef_due' => 3000,
            'total_annual_tax' => 9000,
            'tax_clearance_status' => 'Pending',
            'assessed_value_update_flag' => false
        ]
    ],
    // Brylle Kenneth Mendez (a135da1e-6727-430e-9771-e15688e6f79e) - Real user from S1
    'a135da1e-6727-430e-9771-e15688e6f79e' => [
        [
            'tdn_number' => 'TDN-QC-2024-104',
            'property_index_number' => 'PIN-104-2024',
            'qcitizen_id' => 'a135da1e-6727-430e-9771-e15688e6f79e',
            'property_owner' => 'Brylle Kenneth Mendez',
            'property_address' => '404 Timog Avenue, Quezon City',
            'property_type' => 'Residential',
            'lot_area' => 200.00,
            'floor_area' => 150.00,
            'market_value' => 2200000,
            'assessed_value' => 440000,
            'zoning_classification' => 'R2',
            'tax_year' => 2024,
            'tax_declaration_number' => 'TDN-2024-104',
            'gis_coordinate_lat' => 14.6750,
            'gis_coordinate_lng' => 121.0440,
            'status' => 'Active',
            'annual_rpt_due' => 8800,
            'annual_sef_due' => 4400,
            'total_annual_tax' => 13200,
            'tax_clearance_status' => 'Pending',
            'assessed_value_update_flag' => true
        ]
    ],
    // Dave Luna (eacd934b-0195-4640-b37c-aa0a8b40a9d2) - Real user from S1
    'eacd934b-0195-4640-b37c-aa0a8b40a9d2' => [
        [
            'tdn_number' => 'TDN-QC-2024-105',
            'property_index_number' => 'PIN-105-2024',
            'qcitizen_id' => 'eacd934b-0195-4640-b37c-aa0a8b40a9d2',
            'property_owner' => 'Dave Luna',
            'property_address' => '505 North Avenue, Quezon City',
            'property_type' => 'Commercial',
            'lot_area' => 600.00,
            'floor_area' => 1800.00,
            'market_value' => 20000000,
            'assessed_value' => 4000000,
            'zoning_classification' => 'C2',
            'tax_year' => 2024,
            'tax_declaration_number' => 'TDN-2024-105',
            'gis_coordinate_lat' => 14.6800,
            'gis_coordinate_lng' => 121.0470,
            'status' => 'Active',
            'annual_rpt_due' => 80000,
            'annual_sef_due' => 40000,
            'total_annual_tax' => 120000,
            'tax_clearance_status' => 'Delinquent',
            'assessed_value_update_flag' => false
        ]
    ]
];

// Mock zoning data
$mock_zoning = [
    'R1' => ['classification' => 'Residential Low Density', 'tax_rate' => 0.20],
    'R2' => ['classification' => 'Residential Medium Density', 'tax_rate' => 0.25],
    'R3' => ['classification' => 'Residential High Density', 'tax_rate' => 0.30],
    'C1' => ['classification' => 'Commercial Low Density', 'tax_rate' => 0.35],
    'C2' => ['classification' => 'Commercial Medium Density', 'tax_rate' => 0.40],
    'C3' => ['classification' => 'Commercial High Density', 'tax_rate' => 0.50],
    'I1' => ['classification' => 'Industrial Low Density', 'tax_rate' => 0.40],
    'I2' => ['classification' => 'Industrial Medium Density', 'tax_rate' => 0.45]
];

// Tax clearance records
$mock_clearance = [];

switch ($action) {
    
    // GIS-LINKED PROPERTY LOOKUP (alias for compatibility)
    case 'get_properties_by_citizen':
    case 'get_property_by_citizen':
        $qcitizen_id = $_GET['qcitizen_id'] ?? '';
        
        if (empty($qcitizen_id)) {
            api_response(false, 'qcitizen_id required', null, 400);
        }
        
        // Load properties from the mock data file
        $properties_file = __DIR__ . '/../../mock-data/subsystem7/properties.json';
        $properties = [];
        
        if (file_exists($properties_file)) {
            $properties_data = json_decode(file_get_contents($properties_file), true);
            $all_props = $properties_data['properties'] ?? [];
            
            // Filter properties by qcitizen_id
            $properties = array_filter($all_props, function($p) use ($qcitizen_id) {
                return $p['qcitizen_id'] === $qcitizen_id;
            });
            $properties = array_values($properties); // Re-index
        }
        
        // Add alias fields for RCTS UI compatibility
        $properties = array_map(function($prop) {
            // Ensure required field names for UI
            if (!isset($prop['property_class']) && isset($prop['property_class'])) {
                $prop['property_class'] = $prop['property_class'];
            }
            if (!isset($prop['current_market_value']) && isset($prop['current_market_value'])) {
                $prop['current_market_value'] = $prop['current_market_value'];
            }
            
            // Add rpt_computation for RPT API compatibility
            $assessed_value = floatval($prop['assessed_value'] ?? 0);
            $base_tax = $assessed_value * 0.02; // 2% Basic RPT
            $sef_tax = $assessed_value * 0.01; // 1% SEF
            $total_base_tax = $base_tax + $sef_tax;
            
            $month = (int)date('m');
            $is_early_bird = ($month >= 1 && $month <= 3);
            $is_late = ($month > 3);
            $months_late = $is_late ? ($month - 3) : 0;
            
            $prop['rpt_computation'] = [
                'is_early_bird' => $is_early_bird,
                'is_late' => $is_late,
                'months_late' => $months_late,
                'basic_rpt' => round($base_tax, 2),
                'sef_tax' => round($sef_tax, 2),
                'total_base_tax' => round($total_base_tax, 2),
                'discount_applied' => $is_early_bird ? '20% Early Bird Discount (Q1)' : 'None',
                'discount_amount' => $is_early_bird ? round($total_base_tax * 0.20, 2) : 0
            ];
            
            return $prop;
        }, $properties);
        
        api_response(true, 'Property records retrieved from Assessor GIS', [
            'qcitizen_id' => $qcitizen_id,
            'property_count' => count($properties),
            'properties' => $properties,
            'source' => 'Subsystem 7 - Urban Planning/Assessor (Simulated)',
            'integration_type' => 'GIS-Linked Auto-Lookup'
        ]);
        break;
        
    // GET PROPERTY BY TDN
    case 'get_property_by_tdn':
        $tdn = $_GET['tdn_number'] ?? '';
        
        if (empty($tdn)) {
            api_response(false, 'tdn_number required', null, 400);
        }
        
        $found = null;
        foreach ($mock_properties as $props) {
            foreach ($props as $p) {
                if ($p['tdn_number'] === $tdn) {
                    $found = $p;
                    break 2;
                }
            }
        }
        
        if ($found) {
            api_response(true, 'Property record found', [
                'property' => $found,
                'source' => 'Subsystem 7 - Urban Planning/Assessor'
            ]);
        } else {
            api_response(false, 'Property not found', ['tdn' => $tdn], 404);
        }
        break;
        
    // ZONING CLASSIFICATION LOOKUP
    case 'get_zoning_info':
        $classification = $_GET['zoning_class'] ?? '';
        
        if (empty($classification)) {
            api_response(false, 'zoning_class required', null, 400);
        }
        
        $info = $mock_zoning[$classification] ?? null;
        
        if ($info) {
            api_response(true, 'Zoning info retrieved', [
                'zoning_class' => $classification,
                'classification' => $info['classification'],
                'tax_rate' => $info['tax_rate'],
                'source' => 'Subsystem 7 - Zoning Division'
            ]);
        } else {
            api_response(false, 'Zoning classification not found', null, 404);
        }
        break;
        
    // CALCULATE RPT
    case 'calculate_rpt':
        $qcitizen_id = $_GET['qcitizen_id'] ?? '';
        $tax_year = $_GET['tax_year'] ?? date('Y');
        
        if (empty($qcitizen_id)) {
            api_response(false, 'qcitizen_id required', null, 400);
        }
        
        $properties = $mock_properties[$qcitizen_id] ?? [];
        
        if (empty($properties)) {
            api_response(false, 'No properties found for citizen', null, 404);
        }
        
        $calculations = [];
        $total_tax = 0;
        
        foreach ($properties as $prop) {
            $zoning = $mock_zoning[$prop['zoning_classification']] ?? ['tax_rate' => 0.20];
            $base_tax = $prop['assessed_value'] * ($zoning['tax_rate'] / 100);
            
            $month = (int)date('m');
            $discount = 0;
            $discount_label = 'None';
            
            if ($month >= 1 && $month <= 3) {
                $discount = $base_tax * 0.20;
                $discount_label = '20% Early Bird Discount (Q1)';
            }
            
            $net_tax = $base_tax - $discount;
            
            $calculations[] = [
                'tdn_number' => $prop['tdn_number'],
                'property_type' => $prop['property_type'],
                'assessed_value' => $prop['assessed_value'],
                'zoning_classification' => $prop['zoning_classification'],
                'tax_rate' => $zoning['tax_rate'],
                'base_tax' => round($base_tax, 2),
                'discount_applied' => $discount_label,
                'discount_amount' => round($discount, 2),
                'net_tax_due' => round($net_tax, 2),
                'tax_year' => $tax_year
            ];
            
            $total_tax += $net_tax;
        }
        
        api_response(true, 'RPT Calculation Complete', [
            'qcitizen_id' => $qcitizen_id,
            'tax_year' => $tax_year,
            'property_count' => count($properties),
            'calculations' => $calculations,
            'total_rpt_due' => round($total_tax, 2),
            'payment_deadline' => 'March 31, ' . $tax_year,
            'early_bird_deadline' => 'March 31, ' . $tax_year,
            'source' => 'Subsystem 7 - Assessor (Automated Calculation)'
        ]);
        break;
        
    // UPDATE TAX CLEARANCE (Called by RCTS after payment)
    case 'update_tax_clearance':
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $qcitizen_id = $body['qcitizen_id'] ?? '';
        $tax_status = $body['tax_status'] ?? '';
        $eor_number = $body['eor_number'] ?? '';
        $year = $body['year'] ?? date('Y');
        
        if (empty($qcitizen_id) || empty($tax_status)) {
            api_response(false, 'qcitizen_id and tax_status required', null, 400);
        }
        
        $mock_clearance[$qcitizen_id] = [
            'qcitizen_id' => $qcitizen_id,
            'tax_status' => $tax_status,
            'eor_number' => $eor_number,
            'year' => $year,
            'updated_at' => date('c'),
            'updated_by' => 'RCTS-System'
        ];
        
        api_response(true, 'Tax Clearance Status Updated in GIS', [
            'qcitizen_id' => $qcitizen_id,
            'tax_status' => $tax_status,
            'year' => $year,
            'eor_number' => $eor_number,
            'gis_updated' => true,
            'source' => 'RCTS → Subsystem 7 (Digital Handshake)'
        ]);
        break;
        
    // CHECK TAX CLEARANCE STATUS
    case 'check_tax_clearance':
        $qcitizen_id = $_GET['qcitizen_id'] ?? '';
        $year = $_GET['year'] ?? date('Y');
        
        if (empty($qcitizen_id)) {
            api_response(false, 'qcitizen_id required', null, 400);
        }
        
        $status = $mock_clearance[$qcitizen_id] ?? [
            'qcitizen_id' => $qcitizen_id,
            'tax_status' => 'Cleared',
            'year' => $year,
            'eor_number' => 'EOR-DEMO-' . $year,
            'updated_at' => date('c')
        ];
        
        api_response(true, 'Tax Clearance Status', [
            'clearance' => $status,
            'source' => 'Subsystem 7 - GIS Database'
        ]);
        break;
        
    // DEFAULT
    default:
        api_response(true, 'Subsystem 7 API (Simulated)', [
            'available_actions' => [
                'get_property_by_citizen&qcitizen_id=XXX - GIS-linked property lookup',
                'get_property_by_tdn&tdn_number=XXX - Lookup by TDN',
                'get_zoning_info&zoning_class=R1 - Get zoning tax rate',
                'calculate_rpt&qcitizen_id=XXX&tax_year=YYYY - Auto-calculate RPT',
                'check_tax_clearance&qcitizen_id=XXX - Verify tax clearance',
                'POST update_tax_clearance - Update clearance (called by RCTS after payment)'
            ],
            'description' => 'Simulated Urban Planning/Assessor API for TO-BE demonstration',
            'real_integration' => 'In production, this would connect to the Assessor\'s GIS system'
        ]);
}

function api_response($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c'),
        'system' => 'Subsystem 7 - Urban Planning (Simulated)'
    ]);
    exit;
}
