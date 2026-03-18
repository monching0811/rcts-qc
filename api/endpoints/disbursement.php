<?php
/**
 * ENDPOINT: Outbound Disbursement (Money Out)
 * api/endpoints/disbursement.php
 *
 * Handles ALL outbound payouts:
 *   S3 → Social Aid (AICS, PWD, Senior Allowance)
 *   S5 → Scholarship payroll
 *   S6 → Emergency / Calamity QRF release
 *
 * ROUTES:
 *   GET  ?action=get_pending
 *   GET  ?action=get_by_dept&dept_id=3
 *   POST ?action=submit_payout_list    (S3/S5/S6 sends beneficiary list)
 *   POST ?action=execute_batch         (body: program_id — treasurer approves)
 *   POST ?action=request_qrf_unlock    (S6 triggers emergency fund)
 */

// Set up error handling before anything else
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Helper function to output errors as JSON
function send_error_response($message, $code = 500) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'data' => null,
        'timestamp' => date('Y-m-d H:i:s'),
        'system' => 'RCTS-QC'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Set error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    send_error_response("PHP Error [$errno]: $errstr in $errfile:$errline", 500);
});

try {
    require_once __DIR__ . '/../middleware/cors.php';
} catch (Exception $e) {
    send_error_response('CORS middleware error: ' . $e->getMessage(), 500);
}

try {
    require_once __DIR__ . '/../middleware/auth.php';
} catch (Exception $e) {
    send_error_response('Auth middleware error: ' . $e->getMessage(), 500);
}

try {
    require_once __DIR__ . '/../config/supabase.php';
} catch (Exception $e) {
    send_error_response('Supabase config error: ' . $e->getMessage(), 500);
}

try {
    require_once __DIR__ . '/../config/constants.php';
} catch (Exception $e) {
    send_error_response('Constants config error: ' . $e->getMessage(), 500);
}

try {
    require_once __DIR__ . '/../../includes/db.php';
} catch (Exception $e) {
    send_error_response('Database helpers error: ' . $e->getMessage(), 500);
}

// Check if api_response function exists (should be in auth.php)
if (!function_exists('api_response')) {
    // Fallback implementation
    function api_response(bool $success, string $message, $data = null, int $code = 200): void {
        http_response_code($code);
        echo json_encode([
            'success'   => $success,
            'message'   => $message,
            'data'      => $data,
            'timestamp' => date('Y-m-d H:i:s'),
            'system'    => 'RCTS-QC'
        ], JSON_PRETTY_PRINT);
        exit;
    }
}

// Check if db_select function exists
if (!function_exists('db_select')) {
    send_error_response('db_select function not found. Check supabase.php includes', 500);
}

$action = $_GET['action'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {

    // ── GET all pending disbursements (treasurer view) ───────────────────
    case 'get_pending':
        // Use direct query instead of view for debugging
        $result = db_select('rcts_aid_payout_registry', [
            'status' => 'eq.Scheduled',
            'order'  => 'priority_flag.desc,scheduled_date.asc'
        ]);
        // Join with citizen registry for full_name
        if ($result['success'] && !empty($result['data'])) {
            foreach ($result['data'] as &$row) {
                $citizen = db_select('rcts_citizen_registry', [
                    'qcitizen_id' => 'eq.' . $row['qcitizen_id'],
                    'select' => 'full_name,digital_wallet_link'
                ]);
                if (!empty($citizen['data'])) {
                    $row['full_name'] = $citizen['data'][0]['full_name'];
                    $row['digital_wallet_link'] = $citizen['data'][0]['digital_wallet_link'];
                }
            }
        }
        $total  = array_sum(array_column($result['data'] ?? [], 'approved_amount'));
        api_response($result['success'], 'Pending disbursements retrieved', [
            'disbursements'  => $result['data'],
            'count'          => count($result['data'] ?? []),
            'total_queued'   => round($total, 2)
        ]);
        break;

    // ── GET disbursements by originating department ──────────────────────
    case 'get_by_dept':
        $dept = $_GET['dept_id'] ?? '';
        if (!$dept) api_response(false, 'dept_id required', null, 400);

        $status = $_GET['status'] ?? 'Scheduled';
        $filters = [
            'originating_dept_id' => 'eq.' . $dept,
            'order'               => 'created_at.desc'
        ];
        if ($status !== 'all') {
            $filters['status'] = 'eq.' . $status;
        }
        $result = db_select('rcts_aid_payout_registry', $filters);
        api_response($result['success'], 'Disbursements for Dept ' . $dept . ' retrieved', $result['data']);
        break;

    // ── POST receive payout list from S3, S5, or S6 ──────────────────────
    case 'submit_payout_list':
        $caller       = require_subsystem_auth();
        $dept_id      = (int)substr($caller, 1); // 'S3' → 3

        if (!in_array($dept_id, [3, 5, 6])) {
            api_response(false, 'Only Subsystems 3, 5, and 6 can submit payout lists', null, 403);
        }

        $program_id   = $body['program_id']   ?? '';
        $program_name = $body['program_name'] ?? '';
        $recipients   = $body['recipients']   ?? [];
        $priority     = $body['priority']     ?? 'Normal';

        if (empty($recipients) || !$program_id) {
            api_response(false, 'program_id and recipients[] are required', null, 400);
        }

        // For S6 Emergency: auto-set priority to Emergency
        if ($dept_id === 6) $priority = 'Emergency';

        $saved    = [];
        $skipped  = 0;

        foreach ($recipients as $recipient) {
            $qcitizen_id = $recipient['qcitizen_id'] ?? '';
            $amount      = (float)($recipient['amount'] ?? 0);
            $method      = $recipient['disbursement_method'] ?? 'DigitalWallet';
            $wallet      = $recipient['recipient_wallet']    ?? '';

            if (!$qcitizen_id || $amount <= 0) { $skipped++; continue; }

            // Check if already submitted for same program
            $existing = db_select('rcts_aid_payout_registry', [
                'qcitizen_id' => 'eq.' . $qcitizen_id,
                'program_id'  => 'eq.' . $program_id,
                'status'      => 'in.(Scheduled,Released)'
            ]);
            if (!empty($existing['data'])) { $skipped++; continue; }

            $disb_ref = 'DISB-S' . $dept_id . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            $record = [
                'disbursement_ref_id'   => $disb_ref,
                'qcitizen_id'           => $qcitizen_id,
                'originating_dept_id'   => $dept_id,
                'program_id'            => $program_id,
                'program_name'          => $program_name,
                'approved_amount'       => $amount,
                'disbursement_method'   => $method,
                'recipient_wallet'      => $wallet,
                'priority_flag'         => $priority,
                'status'                => 'Scheduled',
                'scheduled_date'        => $body['scheduled_date'] ?? date('Y-m-d')
            ];

            $insert = db_insert('rcts_aid_payout_registry', $record);
            if ($insert['success']) $saved[] = $disb_ref;
        }

        $dept_names = [3 => 'Social Services', 5 => 'Education', 6 => 'DRRM'];
        api_response(true, count($saved) . ' payout records queued from ' . ($dept_names[$dept_id] ?? 'Unknown'), [
            'program_id'    => $program_id,
            'queued'        => count($saved),
            'skipped'       => $skipped,
            'priority'      => $priority,
            'note'          => $priority === 'Emergency'
                ? 'Emergency flag set. Will bypass standard liquidity queue.'
                : 'Pending treasurer approval via Liquidity Stress Test.'
        ]);
        break;

    // ── POST execute batch payout (treasurer approves + releases funds) ──
    case 'execute_batch':
        $program_id = $body['program_id'] ?? '';
        if (!$program_id) api_response(false, 'program_id required', null, 400);

        // Step 1: Get all Scheduled records for this program
        $records = db_select('rcts_aid_payout_registry', [
            'program_id' => 'eq.' . $program_id,
            'status'     => 'eq.Scheduled'
        ]);
        if (empty($records['data'])) {
            api_response(false, 'No scheduled disbursements found for this program', null, 404);
        }

        $batch    = $records['data'];
        $total    = array_sum(array_column($batch, 'approved_amount'));
        $priority = $batch[0]['priority_flag'] ?? 'Normal';

        // Step 2: Liquidity check (skip for Emergency priority — DRRM bypass)
        if ($priority !== 'Emergency') {
            $dashboard = db_select('rcts_treasury_dashboard', ['order' => 'snapshot_timestamp.desc', 'limit' => '1']);
            $snap      = $dashboard['data'][0] ?? null;

            if ($snap) {
                $available = (float)$snap['net_cash_position'];
                if ($total > $available * 0.30) { // max 30% of cash position per batch
                    api_response(false, 'Liquidity Stress Test FAILED. Insufficient cash position for this batch.', [
                        'batch_total'       => $total,
                        'available_30pct'   => round($available * 0.30, 2),
                        'net_cash_position' => $available,
                        'recommendation'    => 'Reduce batch size or wait for more revenue collection.'
                    ], 422);
                }
            }
        } else {
            // For Emergency: Verify QRF is unlocked
            $dashboard = db_select('rcts_treasury_dashboard', ['order' => 'snapshot_timestamp.desc', 'limit' => '1']);
            $snap      = $dashboard['data'][0] ?? null;
            if (!$snap || $snap['qrf_status'] !== 'Active') {
                api_response(false, 'Emergency disbursement DENIED. Quick Response Fund (QRF) must be unlocked first via DRRM request.', [
                    'qrf_status' => $snap['qrf_status'] ?? 'Unknown',
                    'required_action' => 'POST /api/endpoints/disbursement.php?action=request_qrf_unlock with disaster details'
                ], 403);
            }
        }

        // Step 3: Execute — mark each record Released and write to ledger
        $released    = [];
        $dept_names  = [3 => 'SocialAidDisbursement', 5 => 'ScholarshipDisbursement', 6 => 'DRRMDisbursement'];

        foreach ($batch as $record) {
            $disb_ref = $record['disbursement_ref_id'];
            $dept_id  = (int)$record['originating_dept_id'];

            // Write debit to ledger
            $ledger_data = [
                'disbursement_ref_id' => $disb_ref,
                'entry_type'          => 'Debit',
                'fund_id'             => $dept_id === 6 ? 'DRRM_QRF' : 'GeneralFund',
                'gl_account_code'     => $dept_id === 6 ? '5-06-001' : '5-0' . $dept_id . '-001',
                'revenue_category'    => $dept_names[$dept_id] ?? 'OtherRevenue',
                'amount'              => $record['approved_amount'],
                'remarks'             => $record['program_name'] . ' — ' . $record['qcitizen_id']
            ];
            $ledger_entry = db_insert('rcts_treasury_ledger', $ledger_data);
            $ledger_id    = $ledger_entry['data'][0]['ledger_entry_id'] ?? null;

            // Mark as Released
            db_update('rcts_aid_payout_registry',
                ['disbursement_ref_id' => 'eq.' . $disb_ref],
                [
                    'status'          => 'Released',
                    'released_at'     => date('c'),
                    'ledger_entry_id' => $ledger_id,
                    'updated_at'      => date('c')
                ]
            );

            $released[] = $disb_ref;
        }

        // Auto-lock QRF after DRRM disbursement batch is executed
        if ($priority === 'Emergency') {
            // Get latest QRF balance
            $snap_result = db_select('rcts_treasury_dashboard', ['order' => 'snapshot_timestamp.desc', 'limit' => '1']);
            $snap        = $snap_result['data'][0] ?? null;
            $qrf_balance = $snap ? (float)$snap['qrf_balance'] : 0;
            db_insert('rcts_treasury_dashboard', [
                'qrf_balance'             => $qrf_balance,
                'qrf_status'              => 'Locked',
                'liquidity_stress_result' => 'OK',
                'generated_by'            => 'DRRM-QRF-AUTOLOCK'
            ]);
        }

        // Step 4: Send completion report back to originating dept
        $completion = [
            'signal_type'      => 'DISBURSEMENT_COMPLETE',
            'program_id'       => $program_id,
            'total_released'   => count($released),
            'total_amount'     => round($total, 2),
            'released_at'      => date('c'),
            'ledger_debited'   => true,
            'sent_to'          => 'Subsystem ' . ($batch[0]['originating_dept_id'] ?? '?')
        ];

        api_response(true, count($released) . ' disbursements released. Ledger debited. Completion report sent.', $completion);
        break;

    // ── POST S6 requests QRF unlock (before sending victim list) ─────────
    case 'request_qrf_unlock':
        $caller       = require_subsystem_auth(); // must be S6
        $disaster_id  = $body['disaster_id']   ?? '';
        $calamity_sig = $body['calamity_signal']?? '';
        $amount_needed= (float)($body['amount_needed'] ?? 0);

        if (!$disaster_id || $amount_needed <= 0) {
            api_response(false, 'disaster_id and amount_needed are required', null, 400);
        }

        // Check current QRF balance from latest dashboard snapshot
        $snap_result = db_select('rcts_treasury_dashboard', ['order' => 'snapshot_timestamp.desc', 'limit' => '1']);
        $snap        = $snap_result['data'][0] ?? null;
        $qrf_balance = $snap ? (float)$snap['qrf_balance'] : 0;

        if ($qrf_balance < $amount_needed) {
            api_response(false, 'Insufficient QRF balance.', [
                'qrf_balance'   => $qrf_balance,
                'amount_needed' => $amount_needed,
                'shortfall'     => $amount_needed - $qrf_balance
            ], 422);
        }

        // Unlock QRF — update dashboard snapshot
        db_insert('rcts_treasury_dashboard', [
            'qrf_balance'             => $qrf_balance - $amount_needed,
            'qrf_status'              => 'Active',
            'liquidity_stress_result' => 'Warning',
            'generated_by'            => 'DRRM-QRF-UNLOCK-' . $disaster_id
        ]);

        api_response(true, 'QRF Unlocked. Funds reserved for disaster relief. You may now submit victim payout list.', [
            'disaster_id'       => $disaster_id,
            'amount_unlocked'   => $amount_needed,
            'qrf_balance_after' => $qrf_balance - $amount_needed,
            'qrf_status'        => 'Active',
            'next_step'         => 'POST disbursement.php?action=submit_payout_list with your victim list',
            'priority'          => 'Emergency — will bypass liquidity queue'
        ]);
        break;

    default:
        api_response(false, 'Unknown action', ['available' => ['get_pending','get_by_dept','submit_payout_list','execute_batch','request_qrf_unlock']], 400);
}