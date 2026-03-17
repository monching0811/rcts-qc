<?php
/**
 * ENDPOINT: Inbound Webhook Receiver
 * api/endpoints/inbound.php
 *
 * The single entry point for ALL external subsystem signals.
 * Other teams call this URL to push data into RCTS.
 *
 * ROUTES (all POST):
 *   POST ?action=s2_business_approved    → Subsystem 2: business permit approved
 *   POST ?action=s4_clearance_passed     → Subsystem 4: health/sanitary passed
 *   POST ?action=s6_disaster_declared    → Subsystem 6: calamity trigger
 *   POST ?action=s9_violation_issued     → Subsystem 9: traffic ticket pushed
 *   POST ?action=s10_occupancy_update    → Subsystem 10: stall occupancy signal
 *   POST ?action=s3_aid_request         → Subsystem 3: social aid payout request
 *   POST ?action=s5_scholarship_request → Subsystem 5: scholarship payroll
 */

require_once __DIR__ . '/../middleware/cors.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../config/constants.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_response(false, 'This endpoint only accepts POST requests', null, 405);
}

$caller = require_subsystem_auth();
$action = $_GET['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// Log all inbound signals for audit trail
log_signal($caller, $action, $body);

switch ($action) {

    // ── S2: Business permit application approved ─────────────────────────
    // S2 pushes this when a business owner completes permit application
    case 's2_business_approved':
        $bin         = $body['bin_number']     ?? '';
        $qcitizen_id = $body['qcitizen_id']    ?? '';
        $gross_sales = (float)($body['gross_sales_declared'] ?? 0);
        $nature      = $body['nature_of_business'] ?? '';

        if (!$bin || !$qcitizen_id) {
            api_response(false, 'bin_number and qcitizen_id required', null, 400);
        }

        // Upsert business entity into RCTS DB
        $existing = db_select('rcts_business_entity', ['bin_number' => 'eq.' . $bin]);
        if (empty($existing['data'])) {
            db_insert('rcts_business_entity', [
                'bin_number'           => $bin,
                'qcitizen_id'          => $qcitizen_id,
                'business_name'        => $body['business_name']    ?? 'Unknown Business',
                'nature_of_business'   => $nature,
                'business_address'     => $body['business_address'] ?? '',
                'gross_sales_declared' => $gross_sales,
                'assessment_cycle'     => $body['assessment_cycle'] ?? 'Annual',
                'permit_status'        => 'Pending'
            ]);
        } else {
            db_update('rcts_business_entity',
                ['bin_number' => 'eq.' . $bin],
                ['gross_sales_declared' => $gross_sales, 'permit_status' => 'Pending', 'updated_at' => date('c')]
            );
        }

        api_response(true, 'S2 signal received. Business entity saved. Awaiting health/sanitary clearance from S4.', [
            'bin_number'  => $bin,
            'next_trigger'=> 'Waiting for S4 clearance signal to auto-generate Unified OP'
        ]);
        break;

    // ── S4: Health or sanitary clearance result pushed ───────────────────
    // This is the most important trigger — when PASSED, business tax bill is generated
    case 's4_clearance_passed':
        // Forward to business-tax endpoint's signal handler
        $fwd_url  = 'http://localhost/rcts-qc/api/endpoints/business-tax.php?action=receive_clearance_signal';
        $fwd_body = json_encode($body);
        $ctx      = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nX-API-Key: " . API_KEYS['S4'] . "\r\n",
            'content' => $fwd_body
        ]]);
        $result = json_decode(file_get_contents($fwd_url, false, $ctx), true);

        api_response(true, 'S4 clearance signal forwarded to Business Tax module', $result);
        break;

    // ── S6: Disaster/calamity declared — emergency QRF unlock ────────────
    case 's6_disaster_declared':
        $disaster_id   = $body['disaster_id']    ?? '';
        $amount_needed = (float)($body['amount_needed'] ?? 0);
        $calamity_sig  = $body['calamity_signal'] ?? '';

        if (!$disaster_id) api_response(false, 'disaster_id required', null, 400);

        // Forward to disbursement endpoint's QRF unlock handler
        $fwd_url  = 'http://localhost/rcts-qc/api/endpoints/disbursement.php?action=request_qrf_unlock';
        $fwd_body = json_encode($body);
        $ctx      = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nX-API-Key: " . API_KEYS['S6'] . "\r\n",
            'content' => $fwd_body
        ]]);
        $result = json_decode(file_get_contents($fwd_url, false, $ctx), true);

        api_response(true, 'S6 disaster signal received. QRF unlock request forwarded to Treasury Dashboard.', $result);
        break;

    // ── S9: Traffic violation ticket pushed ──────────────────────────────
    case 's9_violation_issued':
        $ticket_id      = $body['violation_ticket_id'] ?? '';
        $qcitizen_id    = $body['qcitizen_id']         ?? '';
        $plate_no       = $body['vehicle_plate_no']    ?? '';
        $violation_type = $body['violation_type']      ?? '';
        $fine_amount    = (float)($body['fine_amount'] ?? 0);
        $apprehension   = $body['apprehension_date']   ?? CURRENT_DATE;

        if (!$ticket_id || !$plate_no || $fine_amount <= 0) {
            api_response(false, 'violation_ticket_id, vehicle_plate_no, and fine_amount required', null, 400);
        }

        // Save violation record
        db_insert('rcts_traffic_violation', [
            'violation_ticket_id' => $ticket_id,
            'qcitizen_id'         => $qcitizen_id ?: null,
            'vehicle_plate_no'    => $plate_no,
            'violation_type'      => $violation_type,
            'fine_amount'         => $fine_amount,
            'total_amount_due'    => $fine_amount, // Initially same as fine_amount
            'apprehension_date'   => $apprehension,
            'payment_status'      => 'Unpaid',
            'source_subsystem_id' => 9
        ]);

        // Auto-generate a bill if citizen is identified
        if ($qcitizen_id) {
            $bill_ref = 'RCTS-TF-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
            $days_late = max(0, (strtotime(CURRENT_DATE) - strtotime($apprehension)) / 86400 - TRAFFIC_GRACE_PERIOD_DAYS);
            $penalty   = $days_late > 0 ? min(TRAFFIC_LATE_RATE * $days_late, 1.0) : 0.0; // cap at 100%

            db_insert('rcts_assessment_billing_hub', [
                'bill_reference_no'   => $bill_ref,
                'qcitizen_id'         => $qcitizen_id,
                'bill_type'           => 'TrafficFine',
                'originating_dept_id' => 9,
                'asset_id'            => $ticket_id,
                'tax_year'            => CURRENT_YEAR,
                'base_amount'         => $fine_amount,
                'discount_rate'       => 0.0,
                'penalty_rate'        => $penalty,
                'status'              => 'Pending',
                'due_date'            => date('Y-m-d', strtotime($apprehension . ' + ' . TRAFFIC_GRACE_PERIOD_DAYS . ' days'))
            ]);

            // Link ticket to bill
            db_update('rcts_traffic_violation',
                ['violation_ticket_id' => 'eq.' . $ticket_id],
                ['bill_reference_no' => $bill_ref]
            );
        }

        api_response(true, 'S9 traffic violation received. Bill generated' . ($qcitizen_id ? ' and linked to citizen.' : '. Citizen not identified yet.'), [
            'ticket_id'       => $ticket_id,
            'fine_amount'     => $fine_amount,
            'bill_generated'  => !empty($qcitizen_id)
        ]);
        break;

    // ── S10: Market stall occupancy verification signal ───────────────────
    case 's10_occupancy_update':
        // Forward to market-stall endpoint
        $fwd_url  = 'http://localhost/rcts-qc/api/endpoints/market-stall.php?action=receive_occupancy_signal';
        $fwd_body = json_encode($body);
        $ctx      = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nX-API-Key: " . API_KEYS['S10'] . "\r\n",
            'content' => $fwd_body
        ]]);
        $result = json_decode(file_get_contents($fwd_url, false, $ctx), true);

        api_response(true, 'S10 occupancy signal forwarded to Market Stall module', $result);
        break;

    // ── S3: Social aid payout request ────────────────────────────────────
    case 's3_aid_request':
        $fwd_url  = 'http://localhost/rcts-qc/api/endpoints/disbursement.php?action=submit_payout_list';
        $fwd_body = json_encode($body);
        $ctx      = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nX-API-Key: " . API_KEYS['S3'] . "\r\n",
            'content' => $fwd_body
        ]]);
        $result = json_decode(file_get_contents($fwd_url, false, $ctx), true);
        api_response(true, 'S3 aid payout request forwarded to Disbursement module', $result);
        break;

    // ── S5: Scholarship payroll submission ───────────────────────────────
    case 's5_scholarship_request':
        $fwd_url  = 'http://localhost/rcts-qc/api/endpoints/disbursement.php?action=submit_payout_list';
        $fwd_body = json_encode($body);
        $ctx      = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nX-API-Key: " . API_KEYS['S5'] . "\r\n",
            'content' => $fwd_body
        ]]);
        $result = json_decode(file_get_contents($fwd_url, false, $ctx), true);
        api_response(true, 'S5 scholarship payroll forwarded to Disbursement module', $result);
        break;

    default:
        api_response(false, 'Unknown action', [
            'available_actions' => [
                's2_business_approved',
                's4_clearance_passed',
                's6_disaster_declared',
                's9_violation_issued',
                's10_occupancy_update',
                's3_aid_request',
                's5_scholarship_request'
            ],
            'note' => 'All actions require POST method and X-API-Key header'
        ], 400);
}

// ── Audit log helper ─────────────────────────────────────────────────────────
function log_signal(string $caller, string $action, array $body): void {
    $log_dir  = __DIR__ . '/../../logs/';
    if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
    $log_file = $log_dir . 'inbound-signals-' . date('Y-m') . '.log';
    $entry    = date('c') . ' | FROM: ' . $caller . ' | ACTION: ' . $action . ' | PAYLOAD: ' . json_encode($body) . PHP_EOL;
    file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
}