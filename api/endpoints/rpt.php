<?php
/**
 * ENDPOINT: Real Property Tax (RPT) Collection
 * api/endpoints/rpt.php
 *
 * ROUTES:
 *   GET  ?action=get_bills&qcitizen_id=QC-2024-000001
 *   GET  ?action=compute&tdn=TDN-QC-2024-001&payment_date=2025-02-15
 *   POST ?action=generate_bill   (body: qcitizen_id)
 *   POST ?action=mark_paid       (body: bill_reference_no, transaction_id)
 */

require_once __DIR__ . '/../middleware/cors.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../config/constants.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {

    // ── GET all RPT bills for a citizen ─────────────────────────────────
    case 'get_bills':
        $id = $_GET['qcitizen_id'] ?? '';
        if (!$id) api_response(false, 'qcitizen_id required', null, 400);

        $result = db_select('rcts_assessment_billing_hub', [
            'qcitizen_id' => 'eq.' . $id,
            'bill_type'   => 'eq.RPT',
            'status'      => 'eq.Pending',
            'order'       => 'created_at.desc'
        ]);

        api_response($result['success'], 'RPT bills retrieved', $result['data']);
        break;

    // ── GET RPT computation for a specific TDN ──────────────────────────
    case 'compute':
        $tdn  = $_GET['tdn']          ?? '';
        $date = $_GET['payment_date'] ?? CURRENT_DATE;
        if (!$tdn) api_response(false, 'tdn required', null, 400);

        // Call Mock S7 API for live computation
        $url      = S7_API_URL . '?action=compute_rpt&tdn=' . urlencode($tdn) . '&payment_date=' . urlencode($date);
        $response = json_decode(file_get_contents($url), true);

        api_response(true, 'RPT computation from Assessor (S7)', $response['data'] ?? null);
        break;

    // ── POST generate bill for a citizen (called after S7 sync) ─────────
    case 'generate_bill':
        $id = $body['qcitizen_id'] ?? '';
        if (!$id) api_response(false, 'qcitizen_id required', null, 400);

        // Step 1: fetch citizen identity from S1
        $s1_url    = S1_API_URL . '?action=get_citizen&qcitizen_id=' . urlencode($id);
        $citizen   = json_decode(file_get_contents($s1_url), true);
        if (!$citizen['success']) api_response(false, 'Citizen not found in S1', null, 404);

        $citizen_data = $citizen['data'];
        $discount_info= $citizen_data['discount_info'] ?? null;

        // Step 2: fetch properties from S7
        $s7_url     = S7_API_URL . '?action=get_properties_by_citizen&qcitizen_id=' . urlencode($id);
        $properties = json_decode(file_get_contents($s7_url), true);
        if (!$properties['success'] || empty($properties['data']['properties'])) {
            api_response(false, 'No properties found for this citizen', null, 404);
        }

        $generated_bills = [];

        foreach ($properties['data']['properties'] as $prop) {
            $computation = $prop['rpt_computation'];

            // Check if bill already exists for this TDN this year
            $existing = db_select('rcts_assessment_billing_hub', [
                'asset_id'  => 'eq.' . $prop['tdn_number'],
                'bill_type' => 'eq.RPT',
                'tax_year'  => 'eq.' . CURRENT_YEAR,
                'status'    => 'eq.Pending'
            ]);
            if (!empty($existing['data'])) continue; // skip if already billed

            // Apply Senior/PWD discount if eligible
            $discount_rate = 0.0;
            if ($discount_info && in_array('RPT', $discount_info['applicable_to'] ?? [])) {
                $discount_rate = (float)$discount_info['discount_rate'];
            }

            // Apply early bird discount (whichever is higher — take early bird if both)
            if ($computation['is_early_bird']) {
                $discount_rate = max($discount_rate, RPT_EARLY_BIRD_RATE);
            }

            $bill_ref = 'RCTS-RPT-' . CURRENT_YEAR . '-' . strtoupper(substr(uniqid(), -6));

            // Calculate total amount due
            $total_discount = $computation['total_base_tax'] * $discount_rate;
            $total_penalty = $computation['is_late'] ? $computation['total_base_tax'] * (RPT_LATE_PENALTY_RATE * $computation['months_late']) : 0.0;
            $total_amount_due = $computation['total_base_tax'] - $total_discount + $total_penalty;

            $bill = [
                'bill_reference_no'   => $bill_ref,
                'qcitizen_id'         => $id,
                'bill_type'           => 'RPT',
                'originating_dept_id' => 7,
                'asset_id'            => $prop['tdn_number'],
                'tax_year'            => CURRENT_YEAR,
                'base_amount'         => $computation['total_base_tax'],
                'discount_rate'       => $discount_rate,
                'penalty_rate'        => $computation['is_late'] ? RPT_LATE_PENALTY_RATE * $computation['months_late'] : 0.0,
                'total_amount_due'    => $total_amount_due,
                'status'              => 'Pending',
                'due_date'            => CURRENT_YEAR . '-12-31'
            ];

            $insert = db_insert('rcts_assessment_billing_hub', $bill);
            if ($insert['success']) $generated_bills[] = $insert['data'];
        }

        api_response(true, count($generated_bills) . ' RPT bill(s) generated', $generated_bills);
        break;

    // ── POST mark bill as paid (called by payment.php after settlement) ──
    case 'mark_paid':
        $bill_ref = $body['bill_reference_no'] ?? '';
        if (!$bill_ref) api_response(false, 'bill_reference_no required', null, 400);

        $update = db_update(
            'rcts_assessment_billing_hub',
            ['bill_reference_no' => 'eq.' . $bill_ref],
            ['status' => 'Paid', 'updated_at' => date('c')]
        );

        // Notify S7 to update tax clearance
        $tdn = $body['tdn'] ?? '';
        if ($tdn) {
            $s7_notify_url = S7_API_URL . '?action=update_tax_clearance&tdn=' . urlencode($tdn) . '&status=Cleared';
            file_get_contents($s7_notify_url);
        }

        api_response($update['success'], 'RPT bill marked as Paid. Tax clearance updated in S7.', $update['data']);
        break;

    default:
        api_response(false, 'Unknown action', ['available' => ['get_bills','compute','generate_bill','mark_paid']], 400);
}