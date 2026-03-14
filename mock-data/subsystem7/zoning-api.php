<?php
/**
 * MOCK SUBSYSTEM 7 API — Urban Planning, Zoning & Housing
 * --------------------------------------------------------
 * Simulates the Zoning Clearance System API from the City Assessor.
 * RCTS Module 1 (RPT) calls this to auto-fetch property values
 * using a citizen's QCitizen_ID — no manual TDN entry needed.
 *
 * USAGE by RCTS modules:
 *   GET  ?action=get_properties_by_citizen&qcitizen_id=QC-2024-000001
 *   GET  ?action=get_property_by_tdn&tdn=TDN-QC-2024-001
 *   GET  ?action=get_all_properties
 *   GET  ?action=get_rezoned_properties
 *   GET  ?action=compute_rpt&tdn=TDN-QC-2024-001&payment_date=2025-02-15
 *   POST ?action=update_tax_clearance  (body: tdn, status)
 *
 * PLACE THIS FILE IN: htdocs/rcts-qc/mock-data/subsystem7/
 * ACCESS VIA: http://localhost/rcts-qc/mock-data/subsystem7/zoning-api.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// ── Load mock data ──────────────────────────────────────────────────────────
$properties_file = __DIR__ . '/properties.json';
$gis_file        = __DIR__ . '/gis-data.json';

$properties_data = json_decode(file_get_contents($properties_file), true);
$gis_data        = json_decode(file_get_contents($gis_file), true);

$properties      = $properties_data['properties'];
$assessment_rates= $properties_data['assessment_rates'];
$early_bird      = $properties_data['early_bird_discount'];
$late_penalty    = $properties_data['late_penalty'];
$gis_zones       = $gis_data['gis_zones'];
$rezoned         = $gis_data['recently_rezoned'];

// ── Route the request ───────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

switch ($action) {

    // ── 1. Get all properties owned by a citizen (THE CORE S7 CALL) ──────
    // This is what replaces manual TDN entry in your TO-BE plan
    case 'get_properties_by_citizen':
        $id = $_GET['qcitizen_id'] ?? '';
        if (!$id) respond(false, 'qcitizen_id is required', null, 400);

        $owned = array_values(
            array_filter($properties, fn($p) => $p['qcitizen_id'] === $id)
        );

        if (empty($owned)) {
            respond(false, 'No properties found for this citizen', ['properties' => []], 200);
        }

        // Attach computed RPT for current year to each property
        foreach ($owned as &$prop) {
            $prop['rpt_computation'] = compute_rpt($prop, date('Y-m-d'));
        }

        respond(true, count($owned) . ' property/properties found', [
            'qcitizen_id' => $id,
            'count'       => count($owned),
            'properties'  => $owned
        ]);
        break;

    // ── 2. Get single property by TDN ────────────────────────────────────
    case 'get_property_by_tdn':
        $tdn = $_GET['tdn'] ?? '';
        if (!$tdn) respond(false, 'tdn is required', null, 400);

        $found = array_values(
            array_filter($properties, fn($p) => $p['tdn_number'] === $tdn)
        );

        if (empty($found)) respond(false, 'Property not found', null, 404);

        $prop = $found[0];
        $prop['rpt_computation'] = compute_rpt($prop, date('Y-m-d'));

        respond(true, 'Property found', $prop);
        break;

    // ── 3. Get ALL properties (for treasurer view) ───────────────────────
    case 'get_all_properties':
        foreach ($properties as &$prop) {
            $prop['rpt_computation'] = compute_rpt($prop, date('Y-m-d'));
        }
        respond(true, count($properties) . ' properties found', $properties);
        break;

    // ── 4. Get recently rezoned properties (triggers RPT re-assessment) ──
    case 'get_rezoned_properties':
        respond(true, count($rezoned) . ' rezoned properties', [
            'rezoned_properties' => $rezoned,
            'note'               => 'These properties have assessed_value_update_flag = true'
        ]);
        break;

    // ── 5. Compute exact RPT amount for a given payment date ─────────────
    // This is the core automation: system passes payment_date, gets exact bill
    case 'compute_rpt':
        $tdn          = $_GET['tdn']          ?? '';
        $payment_date = $_GET['payment_date'] ?? date('Y-m-d');

        if (!$tdn) respond(false, 'tdn is required', null, 400);

        $found = array_values(
            array_filter($properties, fn($p) => $p['tdn_number'] === $tdn)
        );

        if (empty($found)) respond(false, 'Property not found', null, 404);

        $prop   = $found[0];
        $result = compute_rpt($prop, $payment_date);

        respond(true, 'RPT computation complete', $result);
        break;

    // ── 6. Update tax clearance status (called by RCTS after payment) ────
    case 'update_tax_clearance':
        $body   = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $tdn    = $body['tdn']    ?? $_GET['tdn']    ?? '';
        $status = $body['status'] ?? $_GET['status'] ?? '';

        if (!$tdn || !$status) {
            respond(false, 'tdn and status are required', null, 400);
        }

        $valid_statuses = ['Cleared', 'Pending', 'Delinquent'];
        if (!in_array($status, $valid_statuses)) {
            respond(false, 'Invalid status. Use: Cleared, Pending, or Delinquent', null, 400);
        }

        // In mock: just confirm the update was received
        respond(true, 'Tax clearance updated in Assessor database', [
            'tdn'                => $tdn,
            'new_status'         => $status,
            'updated_at'         => date('Y-m-d H:i:s'),
            'updated_by_system'  => 'RCTS-QC',
            'note'               => 'GIS database synced. Property map shows ' . $status
        ]);
        break;

    // ── 7. Get GIS zone info by coordinate ───────────────────────────────
    case 'get_gis_zone':
        $coord = $_GET['coordinate'] ?? '';
        if (!$coord) respond(false, 'coordinate is required', null, 400);

        $found = array_values(
            array_filter($gis_zones, fn($z) => $z['coordinate_id'] === $coord)
        );

        if (empty($found)) respond(false, 'Coordinate not found in GIS database', null, 404);

        respond(true, 'GIS zone found', $found[0]);
        break;

    // ── Default: API docs ─────────────────────────────────────────────────
    default:
        respond(true, 'Mock Subsystem 7 API is running', [
            'subsystem'         => 'Subsystem 7 - Urban Planning, Zoning & Housing',
            'version'           => '1.0-mock',
            'status'            => 'online',
            'total_properties'  => count($properties),
            'total_gis_zones'   => count($gis_zones),
            'available_actions' => [
                'get_properties_by_citizen' => 'GET ?action=get_properties_by_citizen&qcitizen_id=QC-2024-000001',
                'get_property_by_tdn'       => 'GET ?action=get_property_by_tdn&tdn=TDN-QC-2024-001',
                'get_all_properties'        => 'GET ?action=get_all_properties',
                'get_rezoned_properties'    => 'GET ?action=get_rezoned_properties',
                'compute_rpt'               => 'GET ?action=compute_rpt&tdn=TDN-QC-2024-001&payment_date=2025-02-15',
                'update_tax_clearance'      => 'POST ?action=update_tax_clearance (body: tdn, status)',
                'get_gis_zone'              => 'GET ?action=get_gis_zone&coordinate=14.6180,121.0550'
            ]
        ]);
        break;
}

// ── Helper: Compute RPT with discount/penalty logic ─────────────────────────
function compute_rpt(array $prop, string $payment_date): array {
    global $early_bird, $late_penalty;

    $base_tax     = (float) $prop['annual_rpt_due'];
    $sef_tax      = $base_tax * 0.5;   // SEF = 50% of basic RPT
    $total_base   = $base_tax + $sef_tax;

    $year         = (int) date('Y', strtotime($payment_date));
    $deadline     = $year . '-12-31';

    // ── Early Bird check ─────────────────────────────────────────────────
    $pd_month     = (int) date('m', strtotime($payment_date));
    $pd_day       = (int) date('d', strtotime($payment_date));
    $eb_start_m   = $early_bird['window_start_month'];
    $eb_end_m     = $early_bird['window_end_month'];
    $eb_end_d     = $early_bird['window_end_day'];

    $is_early_bird = ($pd_month >= $eb_start_m && $pd_month <= $eb_end_m &&
                      !($pd_month === $eb_end_m && $pd_day > $eb_end_d));

    $discount_rate   = $is_early_bird ? (float)$early_bird['rate'] : 0.0;
    $discount_amount = round($total_base * $discount_rate, 2);

    // ── Late penalty check ───────────────────────────────────────────────
    $is_late     = strtotime($payment_date) > strtotime($deadline);
    $months_late = 0;
    $penalty_amount = 0.0;

    if ($is_late) {
        $deadline_ts = strtotime($deadline);
        $payment_ts  = strtotime($payment_date);
        $months_late = (int) floor(($payment_ts - $deadline_ts) / (30 * 86400));
        $penalty_amount = round($total_base * (float)$late_penalty['rate_per_month'] * $months_late, 2);
    }

    $net_amount = round($total_base - $discount_amount + $penalty_amount, 2);

    return [
        'tdn_number'       => $prop['tdn_number'],
        'property_class'   => $prop['property_class'],
        'assessed_value'   => $prop['assessed_value'],
        'basic_rpt'        => $base_tax,
        'sef_tax'          => $sef_tax,
        'total_base_tax'   => $total_base,
        'payment_date'     => $payment_date,
        'is_early_bird'    => $is_early_bird,
        'discount_rate'    => $discount_rate,
        'discount_amount'  => $discount_amount,
        'is_late'          => $is_late,
        'months_late'      => $months_late,
        'penalty_rate'     => $late_penalty['rate_per_month'],
        'penalty_amount'   => $penalty_amount,
        'net_amount_due'   => $net_amount,
        'computation_note' => $is_early_bird
            ? '20% Early Bird Discount Applied (Jan-Mar)'
            : ($is_late ? "{$months_late} month(s) late — 2%/month penalty applied" : 'Regular rate — no discount or penalty')
    ];
}

function respond(bool $success, string $message, $data = null, int $code = 200): void {
    http_response_code($code);
    echo json_encode([
        'success'   => $success,
        'message'   => $message,
        'data'      => $data,
        'timestamp' => date('Y-m-d H:i:s'),
        'source'    => 'Subsystem 7 Mock API'
    ], JSON_PRETTY_PRINT);
    exit;
}
