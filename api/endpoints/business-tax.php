<?php
/**
 * ENDPOINT: Business Tax & Regulatory Fee Payment
 * api/endpoints/business-tax.php
 *
 * ROUTES:
 *   GET  ?action=get_bills&qcitizen_id=QC-2024-000001
 *   GET  ?action=clearance_status&bin=BIN-QC-2024-001
 *   POST ?action=receive_clearance_signal  (called by S4/S2 — the core TO-BE trigger)
 *   POST ?action=generate_unified_op       (body: bin_number)
 *   POST ?action=mark_paid                 (body: bill_reference_no)
 */

require_once __DIR__ . '/../middleware/cors.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../../includes/db.php';

require_once __DIR__ . '/../config/constants.php';

// Override constants with dynamic settings from database
$BIZ_TAX_RATE_RESTAURANT = get_setting('BIZ_TAX_RATE_RESTAURANT', BIZ_TAX_RATE_RESTAURANT);
$BIZ_TAX_RATE_RETAIL = get_setting('BIZ_TAX_RATE_RETAIL', BIZ_TAX_RATE_RETAIL);
$BIZ_TAX_RATE_SERVICE = get_setting('BIZ_TAX_RATE_SERVICE', BIZ_TAX_RATE_SERVICE);
$BIZ_TAX_RATE_MANUFACTURING = get_setting('BIZ_TAX_RATE_MFR', BIZ_TAX_RATE_MANUFACTURING);
$BIZ_TAX_RATE_DEFAULT = get_setting('BIZ_TAX_RATE_DEFAULT', BIZ_TAX_RATE_DEFAULT);

// Helper: Log audit event to rcts_audit_log
function audit_log($actor, $event, $details = null) {
    $entry = [
        'actor' => $actor,
        'event' => $event,
        'details' => is_array($details) ? json_encode($details) : $details,
    ];
    supabase_request('rcts_audit_log', 'POST', [], $entry, true);
}

$action = $_GET['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {

    // ── GET business tax bills for a citizen ────────────────────────────

    case 'get_bills':
        $id = $_GET['qcitizen_id'] ?? '';
        if (!$id) api_response(false, 'qcitizen_id required', null, 400);

        $result = db_select('rcts_assessment_billing_hub', [
            'qcitizen_id' => 'eq.' . $id,
            'bill_type'   => 'eq.BusinessTax',
            'order'       => 'created_at.desc'
        ]);
        audit_log($id, 'get_business_tax_bills', ['result_count' => count($result['data'] ?? [])]);
        api_response($result['success'], 'Business tax bills retrieved', $result['data']);
        break;

    // ── GET all bill types for a citizen (BusinessTax + TrafficFine + MarketRental + RPT etc.)
    case 'get_all_bills':
        $id = $_GET['qcitizen_id'] ?? '';
        if (!$id) api_response(false, 'qcitizen_id required', null, 400);

        $result = db_select('rcts_assessment_billing_hub', [
            'qcitizen_id' => 'eq.' . $id,
            'order'       => 'created_at.desc'
        ]);
        audit_log($id, 'get_all_bills', ['result_count' => count($result['data'] ?? [])]);
        api_response($result['success'], 'All bills retrieved for citizen', $result['data']);
        break;

    // ── POST generate business tax bills for a citizen (manual allocation) ──
    case 'generate_bill':
        $id = $body['qcitizen_id'] ?? '';
        if (!$id) api_response(false, 'qcitizen_id required', null, 400);

        // Fetch all businesses for the citizen
        $biz_result = db_select('rcts_business_entity', ['qcitizen_id' => 'eq.' . $id]);
        if (empty($biz_result['data'])) api_response(false, 'No businesses found for this citizen', null, 404);

        $generated_bills = [];
        foreach ($biz_result['data'] as $biz) {
            // Check if bill already exists
            $existing = db_select('rcts_assessment_billing_hub', [
                'asset_id'  => 'eq.' . $biz['bin_number'],
                'bill_type' => 'eq.BusinessTax',
                'tax_year'  => 'eq.' . CURRENT_YEAR,
                'status'    => 'eq.Pending'
            ]);
            if (!empty($existing['data'])) continue;

            // Compute business tax
            $gross_sales = (float)$biz['gross_sales_declared'];
            $rate        = get_biz_tax_rate($biz['nature_of_business']);
            $base_tax    = round($gross_sales * $rate, 2);
            $reg_fees    = FEE_SANITARY_PERMIT + FEE_GARBAGE_COLLECTION + FEE_FIRE_INSPECTION;
            $total_base  = $base_tax + $reg_fees;

            $bill_ref = 'RCTS-BT-' . CURRENT_YEAR . '-' . strtoupper(substr(uniqid(), -6));
            $bill = [
                'bill_reference_no'   => $bill_ref,
                'qcitizen_id'         => $id,
                'bill_type'           => 'BusinessTax',
                'originating_dept_id' => 2,
                'asset_id'            => $biz['bin_number'],
                'tax_year'            => CURRENT_YEAR,
                'base_amount'         => $total_base,
                'discount_rate'       => 0.0,
                'penalty_rate'        => 0.0,
                'total_amount_due'    => $total_base,
                'status'              => 'Pending',
                'due_date'            => CURRENT_YEAR . '-01-20'
            ];

            $insert = db_insert('rcts_assessment_billing_hub', $bill);
            if ($insert['success']) {
                $generated_bills[] = $insert['data'];
                audit_log($id, 'generate_business_tax_bill', $bill);
            }
        }
        api_response(true, count($generated_bills) . ' business tax bill(s) generated', $generated_bills);
        break;

    // ── GET current clearance status for a business ──────────────────────
    case 'clearance_status':
        $bin = $_GET['bin'] ?? '';
        $qcitizen_id = $_GET['qcitizen_id'] ?? '';
        
        // If bin provided, get specific business; otherwise get all for citizen
        if ($bin) {
            $result = db_select('v_business_clearance_status', [
                'bin_number' => 'eq.' . $bin
            ]);
        } elseif ($qcitizen_id) {
            // Get all clearances for this citizen
            $result = db_select('rcts_regulatory_clearance', [
                'qcitizen_id' => 'eq.' . $qcitizen_id
            ]);
        } else {
            // No params - return empty
            $result = ['success' => true, 'data' => []];
        }
        api_response($result['success'], 'Clearance status retrieved', $result['data']);
        break;

    // ── POST receive compliance signal from S4 (Health) or S2 (Permits) ─
    // THIS IS THE CORE TO-BE AUTOMATION — zero-touch billing trigger
    case 'receive_clearance_signal':
        $caller = require_subsystem_auth(); // must be S2 or S4

        $clearance_ref  = $body['clearance_ref_id'] ?? '';
        $bin            = $body['business_bin']     ?? '';
        $type           = $body['clearance_type']   ?? '';
        $status         = $body['status_flag']      ?? '';
        $qcitizen_id    = $body['qcitizen_id']      ?? '';
        $inspection_date= $body['inspection_date']  ?? CURRENT_DATE;
        $inspector      = $body['inspector_name']   ?? '';

        if (!$clearance_ref || !$bin || !$status) {
            audit_log($qcitizen_id ?: $caller, 'clearance_signal_failed', ['reason' => 'missing required fields']);
            api_response(false, 'clearance_ref_id, business_bin, and status_flag are required', null, 400);
        }

        // Save the clearance record into RCTS
        $clearance_data = [
            'clearance_ref_id'    => $clearance_ref,
            'qcitizen_id'         => $qcitizen_id,
            'business_bin'        => $bin,
            'clearance_type'      => $type,
            'inspection_date'     => $inspection_date,
            'valid_until'         => date('Y-12-31', strtotime($inspection_date)),
            'status_flag'         => $status,
            'inspector_name'      => $inspector,
            'source_subsystem_id' => (int)substr($caller, 1)
        ];
        db_insert('rcts_regulatory_clearance', $clearance_data);
        audit_log($qcitizen_id ?: $caller, 'clearance_signal_received', $clearance_data);

        // If PASSED — check if ALL required clearances are now complete
        if ($status === 'Passed') {
            $all_clearances = db_select('rcts_regulatory_clearance', [
                'business_bin' => 'eq.' . $bin,
                'status_flag'  => 'eq.Passed'
            ]);

            $passed_types = array_column($all_clearances['data'] ?? [], 'clearance_type');
            $required     = ['Health', 'Sanitary'];
            $all_passed   = count(array_intersect($required, $passed_types)) === count($required);

            if ($all_passed) {
                // AUTO-TRIGGER: Generate Unified Order of Payment
                $op_url  = 'http://localhost/rcts-qc/api/endpoints/business-tax.php?action=generate_unified_op';
                $op_body = json_encode(['bin_number' => $bin, 'auto_triggered' => true]);
                $ctx     = stream_context_create(['http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/json\r\nX-API-Key: DEV-BYPASS-KEY-LOCAL\r\n",
                    'content' => $op_body
                ]]);
                $op_response = json_decode(file_get_contents($op_url, false, $ctx), true);
                audit_log($qcitizen_id ?: $caller, 'unified_op_auto_generated', ['bin' => $bin, 'op_response' => $op_response]);
                api_response(true, 'Clearance signal received. All clearances PASSED. Unified OP auto-generated.', [
                    'clearance_saved'  => true,
                    'all_cleared'      => true,
                    'op_generated'     => $op_response['data'] ?? null
                ]);
            }

            api_response(true, 'Clearance signal received. Waiting for remaining clearances.', [
                'clearance_saved' => true,
                'all_cleared'     => false,
                'passed_so_far'   => $passed_types
            ]);
        }

        api_response(true, 'Clearance signal received. Status: ' . $status, ['clearance_saved' => true]);
        break;

    // ── POST generate unified order of payment ───────────────────────────
    case 'generate_unified_op':
        $bin          = $body['bin_number']     ?? '';
        $auto_trigger = $body['auto_triggered'] ?? false;
        if (!$bin) api_response(false, 'bin_number required', null, 400);

        // Fetch business entity
        $biz_result = db_select('rcts_business_entity', ['bin_number' => 'eq.' . $bin]);
        if (empty($biz_result['data'])) api_response(false, 'Business not found', null, 404);
        $biz = $biz_result['data'][0];

        // Fetch citizen for discount check
        $s1_url  = S1_API_URL . '?action=check_discount&qcitizen_id=' . urlencode($biz['qcitizen_id']);
        $disc    = json_decode(file_get_contents($s1_url), true);
        $disc_info = $disc['data']['discount_info'] ?? null;

        // Compute business tax based on gross sales and nature of business
        $gross_sales = (float)$biz['gross_sales_declared'];
        $rate        = get_biz_tax_rate($biz['nature_of_business']);
        $base_tax    = round($gross_sales * $rate, 2);

        // Add flat regulatory fees
        $reg_fees = FEE_SANITARY_PERMIT + FEE_GARBAGE_COLLECTION + FEE_FIRE_INSPECTION;
        if ($biz['is_puv_franchise']) $reg_fees += 1500.00; // franchise surcharge

        $total_base = $base_tax + $reg_fees;

        // Apply discount
        $discount_rate = 0.0;
        if ($disc_info && in_array('BusinessTax', $disc_info['applicable_to'] ?? [])) {
            $discount_rate = (float)$disc_info['discount_rate'];
        }

        // Check if bill already exists
        $existing = db_select('rcts_assessment_billing_hub', [
            'asset_id'  => 'eq.' . $bin,
            'bill_type' => 'eq.BusinessTax',
            'tax_year'  => 'eq.' . CURRENT_YEAR,
            'status'    => 'eq.Pending'
        ]);
        if (!empty($existing['data'])) {
            api_response(true, 'Unified OP already exists for this period', $existing['data'][0]);
        }

        $bill_ref = 'RCTS-BT-' . CURRENT_YEAR . '-' . strtoupper(substr(uniqid(), -6));

        // Calculate total amount due
        $total_discount = $total_base * $discount_rate;
        $total_penalty = 0.0; // No penalty for business tax
        $total_amount_due = $total_base - $total_discount + $total_penalty;

        $bill = [
            'bill_reference_no'   => $bill_ref,
            'qcitizen_id'         => $biz['qcitizen_id'],
            'bill_type'           => 'BusinessTax',
            'originating_dept_id' => 2,
            'asset_id'            => $bin,
            'tax_year'            => CURRENT_YEAR,
            'base_amount'         => $total_base,
            'discount_rate'       => $discount_rate,
            'penalty_rate'        => 0.0,
            'total_amount_due'    => $total_amount_due,
            'status'              => 'Pending',
            'due_date'            => CURRENT_YEAR . '-01-20'
        ];

        $insert = db_insert('rcts_assessment_billing_hub', $bill);
        audit_log($biz['qcitizen_id'], 'generate_unified_op', $bill);
        api_response($insert['success'], 'Unified Order of Payment generated' . ($auto_trigger ? ' (auto-triggered by clearance signals)' : ''), [
            'bill'         => $insert['data'],
            'breakdown'    => [
                'business_tax'     => $base_tax,
                'tax_rate_used'    => $rate,
                'sanitary_permit'  => FEE_SANITARY_PERMIT,
                'garbage_fee'      => FEE_GARBAGE_COLLECTION,
                'fire_inspection'  => FEE_FIRE_INSPECTION,
                'total_base'       => $total_base,
                'discount_applied' => $discount_rate * 100 . '%',
            ]
        ]);
        break;

    // ── POST mark paid + send success loop back to S2 ───────────────────
    case 'mark_paid':
        $bill_ref = $body['bill_reference_no'] ?? '';
        if (!$bill_ref) api_response(false, 'bill_reference_no required', null, 400);

        db_update('rcts_assessment_billing_hub',
            ['bill_reference_no' => 'eq.' . $bill_ref],
            ['status' => 'Paid', 'updated_at' => date('c')]
        );
        audit_log('system', 'business_tax_paid', ['bill_reference_no' => $bill_ref]);

        // Send "PAID" signal back to S2 so permit can be released
        // In production: POST to S2_API_URL with payment confirmation
        $s2_signal = [
            'signal_type'      => 'PAYMENT_CONFIRMED',
            'bill_reference_no'=> $bill_ref,
            'paid_at'          => date('c'),
            'message'          => 'Business tax settled. Permit may now be released.',
            'sent_to'          => 'Subsystem 2 - E-Permit Tracker'
        ];

        api_response(true, 'Business tax marked as Paid. Success loop sent to S2.', $s2_signal);
        break;

    // ── DEBUG: Test full Business Tax flow ───────────────────────────────
    case 'test_flow':
        $qcitizen_id = $_GET['qcitizen_id'] ?? 'QC-2024-000001';
        $bin = $_GET['bin'] ?? 'BIN-QC-2024-001';
        
        // Step 1: Get business entity
        $biz_result = db_select('rcts_business_entity', ['bin_number' => 'eq.' . $bin]);
        
        // Step 2: Get clearances
        $clear_result = db_select('rcts_regulatory_clearance', [
            'business_bin' => 'eq.' . $bin
        ]);
        
        // Step 3: Get bills
        $bill_result = db_select('rcts_assessment_billing_hub', [
            'asset_id' => 'eq.' . $bin,
            'bill_type' => 'eq.BusinessTax'
        ]);
        
        api_response(true, 'Business Tax Flow Debug Data', [
            'qcitizen_id' => $qcitizen_id,
            'bin' => $bin,
            'business_found' => !empty($biz_result['data']),
            'business_data' => $biz_result['data'][0] ?? null,
            'clearances' => $clear_result['data'] ?? [],
            'bills' => $bill_result['data'] ?? []
        ]);
        break;

    // ── DEBUG: Simulate S2 approval then S4 clearance PASS ──────────────────────
    case 'simulate_full_approval':
        $bin = $body['bin_number'] ?? 'BIN-QC-2024-001';
        $qcitizen_id = $body['qcitizen_id'] ?? 'QC-2024-000001';
        
        // First, simulate S2 business approved
        $s2_payload = [
            'source' => 'S2-Permits',
            'bin_number' => $bin,
            'qcitizen_id' => $qcitizen_id,
            'business_name' => 'Test Business',
            'nature_of_business' => 'Retail',
            'gross_sales_declared' => 200000
        ];
        
        // Insert business entity
        db_insert('rcts_business_entity', [
            'bin_number' => $bin,
            'qcitizen_id' => $qcitizen_id,
            'business_name' => 'Test Business',
            'nature_of_business' => 'Retail',
            'gross_sales_declared' => 200000,
            'assessment_cycle' => 'Annual'
        ]);
        
        // Now simulate S4 clearance PASSED
        $clearance_data = [
            'clearance_ref_id' => 'CLR-' . strtoupper(uniqid()),
            'business_bin' => $bin,
            'qcitizen_id' => $qcitizen_id,
            'clearance_type' => 'Health',
            'status_flag' => 'Passed',
            'inspection_date' => date('Y-m-d'),
            'source_subsystem_id' => 4
        ];
        db_insert('rcts_regulatory_clearance', $clearance_data);
        
        // Second clearance - Sanitary
        $clearance_data['clearance_ref_id'] = 'CLR-' . strtoupper(uniqid());
        $clearance_data['clearance_type'] = 'Sanitary';
        db_insert('rcts_regulatory_clearance', $clearance_data);
        
        // Now trigger the auto-generation
        $op_url = 'http://localhost/rcts-qc/api/endpoints/business-tax.php?action=generate_unified_op';
        $op_body = json_encode(['bin_number' => $bin]);
        $ctx = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nX-API-Key: DEV-BYPASS-KEY-LOCAL\r\n",
            'content' => $op_body
        ]]);
        $op_response = json_decode(file_get_contents($op_url, false, $ctx), true);
        
        api_response(true, 'Full approval flow simulated. Clearances PASSED → Unified OP Generated', [
            'bin' => $bin,
            'clearances_added' => ['Health', 'Sanitary'],
            'unified_op_result' => $op_response
        ]);
        break;

    default:
        api_response(false, 'Unknown action', ['available' => ['get_bills','clearance_status','receive_clearance_signal','generate_unified_op','mark_paid','test_flow','simulate_full_approval']], 400);
}

// ── Helper: business tax rate by nature of business ────────────────────────
function get_biz_tax_rate(string $nature): float {
    global $BIZ_TAX_RATE_RESTAURANT, $BIZ_TAX_RATE_RETAIL, $BIZ_TAX_RATE_SERVICE, $BIZ_TAX_RATE_MANUFACTURING, $BIZ_TAX_RATE_DEFAULT;
    $nature = strtolower($nature);
    if (str_contains($nature, 'food') || str_contains($nature, 'restaurant')) return floatval($BIZ_TAX_RATE_RESTAURANT);
    if (str_contains($nature, 'retail') || str_contains($nature, 'store'))    return floatval($BIZ_TAX_RATE_RETAIL);
    if (str_contains($nature, 'service'))                                      return floatval($BIZ_TAX_RATE_SERVICE);
    if (str_contains($nature, 'manufactur'))                                   return floatval($BIZ_TAX_RATE_MANUFACTURING);
    return floatval($BIZ_TAX_RATE_DEFAULT);
}