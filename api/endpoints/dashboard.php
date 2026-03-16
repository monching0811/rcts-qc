<?php
/**
 * ENDPOINT: Treasury Dashboard & Reports (Module 5)
 * api/endpoints/dashboard.php
 *
 * ROUTES:
 *   GET  ?action=live_summary        → real-time KPI cards
 *   GET  ?action=ledger_feed         → paginated ledger entries
 *   GET  ?action=liquidity_check     → stress test result
 *   GET  ?action=bill_status_overview → all bills with status across citizens
 *   GET  ?action=delinquency_report  → overdue bills
 *   GET  ?action=esre_data           → e-SRE report data
 *   POST ?action=refresh_snapshot    → re-compute and save dashboard snapshot
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

// Check if api_response function exists (should be in supabase.php or auth.php)
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

switch ($action) {

    // ── GET live KPI summary for the treasurer dashboard ─────────────────
    case 'live_summary':
        try {
            $month_start = date('Y-m-01');
            $month_end   = date('Y-m-t');

            // Total credits (revenue) this month
            $credits = db_select('rcts_treasury_ledger', [
                'entry_type'  => 'eq.Credit',
                'entry_timestamp' => 'gte.' . $month_start,
                'select'      => 'revenue_category,amount'
            ]);

            if (!$credits['success']) {
                api_response(false, 'Failed to fetch credits: ' . ($credits['error'] ?? 'Unknown error'), null, 500);
            }

            // Total debits (disbursements) this month
            $debits = db_select('rcts_treasury_ledger', [
                'entry_type'  => 'eq.Debit',
                'entry_timestamp' => 'gte.' . $month_start,
                'select'      => 'revenue_category,amount'
            ]);

            if (!$debits['success']) {
                api_response(false, 'Failed to fetch debits: ' . ($debits['error'] ?? 'Unknown error'), null, 500);
            }

            $total_collected = array_sum(array_column($credits['data'] ?? [], 'amount'));
            $total_disbursed = array_sum(array_column($debits['data'] ?? [], 'amount'));

            // Breakdown by category
            $by_category = [];
            foreach ($credits['data'] ?? [] as $row) {
                $cat = $row['revenue_category'];
                $by_category[$cat] = ($by_category[$cat] ?? 0) + (float)$row['amount'];
            }

            // Pending bills count
            $pending = db_select('rcts_assessment_billing_hub', ['status' => 'eq.Pending', 'select' => 'bill_type,total_amount_due']);
            $pending_total = array_sum(array_column($pending['data'] ?? [], 'total_amount_due'));

            // Delinquent count
            $delinquent = db_select('rcts_assessment_billing_hub', ['status' => 'eq.Delinquent', 'select' => 'bill_reference_no']);

            // Latest QRF balance
            $snap    = db_select('rcts_treasury_dashboard', ['order' => 'snapshot_timestamp.desc', 'limit' => '1']);
            $qrf_bal = (float)($snap['data'][0]['qrf_balance'] ?? 10000000);

            $summary = [
                'period'            => date('F Y'),
                'total_collected_mtd' => round($total_collected, 2),
                'total_disbursed_mtd' => round($total_disbursed, 2),
                'net_cash_position'   => round($total_collected - $total_disbursed, 2),
                'revenue_target'      => 5000000.00,
                'target_variance'     => round($total_collected - 5000000.00, 2),
                'collection_by_type'  => $by_category,
                'pending_bills_count' => count($pending['data'] ?? []),
                'pending_bills_total' => round($pending_total, 2),
                'delinquent_count'    => count($delinquent['data'] ?? []),
                'qrf_balance'         => $qrf_bal,
                'qrf_status'          => $snap['data'][0]['qrf_status'] ?? 'Locked',
                'as_of'               => date('Y-m-d H:i:s')
            ];

            api_response(true, 'Live treasury summary', $summary);
        } catch (Exception $e) {
            api_response(false, 'Dashboard error: ' . $e->getMessage(), null, 500);
        }
        break;

    // ── GET bill status overview (all bills across all citizens) ─────────
    case 'bill_status_overview':
        try {
            $limit = min((int)($_GET['limit'] ?? 50), 200);
            $status_filter = $_GET['status'] ?? null;
            $search_term = $_GET['search'] ?? null;

            // Build query - use service key to bypass RLS
            $filters = [
                'select' => 'bill_reference_no,qcitizen_id,bill_type,status,total_amount_due,due_date,created_at',
                'order' => 'created_at.desc',
                'limit' => 100
            ];

            // Get all bills using service key (bypasses RLS)
            $result = supabase_request('rcts_assessment_billing_hub', 'GET', $filters, [], true); // true = use service key

            if (!$result['success']) {
                api_response(false, 'Query failed: ' . ($result['error'] ?? 'Unknown'), [
                    'http_code' => $result['http_code'] ?? 0,
                    'bills' => [],
                    'debug' => 'Check server error log'
                ], 500);
                break;
            }

            $bills = $result['data'] ?? [];

            // Apply status filter in PHP
            if ($status_filter && $status_filter !== 'all') {
                $bills = array_values(array_filter($bills, function($b) use ($status_filter) {
                    return strtolower($b['status'] ?? '') === strtolower($status_filter);
                }));
            }

            // Get citizen IDs from bills
            $citizen_ids = [];
            foreach ($bills as $bill) {
                if (!empty($bill['qcitizen_id'])) {
                    $citizen_ids[] = $bill['qcitizen_id'];
                }
            }
            $citizen_ids = array_unique($citizen_ids);

            // Fetch citizens using service key
            $citizens = [];
            if (!empty($citizen_ids)) {
                $citizen_ids_csv = '"' . implode('","', $citizen_ids) . '"';
                $cit_result = supabase_request('rcts_citizen_registry', 'GET', [
                    'qcitizen_id' => 'in.(' . $citizen_ids_csv . ')',
                    'select' => 'qcitizen_id,full_name'
                ], [], true);
                
                if ($cit_result['success'] && !empty($cit_result['data'])) {
                    foreach ($cit_result['data'] as $c) {
                        $citizens[$c['qcitizen_id']] = $c['full_name'];
                    }
                }
            }

            // Add citizen names and format amounts
            foreach ($bills as &$bill) {
                $bill['citizen_name'] = $citizens[$bill['qcitizen_id']] ?? ($bill['qcitizen_id'] ?? 'Unknown');
                $bill['amount'] = $bill['total_amount_due'] ?? 0;
            }
            unset($bill);

            // Apply search filter if specified
            if ($search_term) {
                $search_lower = strtolower($search_term);
                $bills = array_values(array_filter($bills, function($b) use ($search_lower) {
                    return stripos($b['citizen_name'] ?? '', $search_lower) !== false ||
                           stripos($b['qcitizen_id'] ?? '', $search_lower) !== false;
                }));
            }

            api_response(true, 'Bill status overview retrieved', [
                'bills' => $bills,
                'count' => count($bills),
                'limit' => $limit,
                'filters' => [
                    'status' => $status_filter,
                    'search' => $search_term
                ]
            ]);
        } catch (Exception $e) {
            api_response(false, 'Bill status error: ' . $e->getMessage(), null, 500);
        }
        break;

    // ── GET paginated ledger feed ────────────────────────────────────────
    case 'ledger_feed':
        try {
            $limit  = min((int)($_GET['limit'] ?? 20), 100);
            $offset = (int)($_GET['offset'] ?? 0);

            $result = db_select('rcts_treasury_ledger', [
                'order'  => 'entry_timestamp.desc',
                'limit'  => $limit,
                'offset' => $offset
            ]);

            if (!$result['success']) {
                api_response(false, 'Failed to fetch ledger: ' . ($result['error'] ?? 'Unknown error'), null, 500);
            }

            api_response($result['success'], 'Ledger entries retrieved', [
                'entries' => $result['data'],
                'count'   => count($result['data'] ?? []),
                'limit'   => $limit,
                'offset'  => $offset
            ]);
        } catch (Exception $e) {
            api_response(false, 'Ledger error: ' . $e->getMessage(), null, 500);
        }
        break;

    // ── GET liquidity stress test ─────────────────────────────────────────
    case 'liquidity_check':
        try {
            $proposed_payout = (float)($_GET['proposed_payout'] ?? 0);

            // Get current cash position
            $credits = db_select('rcts_treasury_ledger', ['entry_type' => 'eq.Credit', 'select' => 'amount']);
            if (!$credits['success']) {
                api_response(false, 'Failed to fetch credits: ' . ($credits['error'] ?? 'Unknown error'), null, 500);
            }

            $debits  = db_select('rcts_treasury_ledger', ['entry_type' => 'eq.Debit',  'select' => 'amount']);
            if (!$debits['success']) {
                api_response(false, 'Failed to fetch debits: ' . ($debits['error'] ?? 'Unknown error'), null, 500);
            }

            $total_in  = array_sum(array_column($credits['data'] ?? [], 'amount'));
            $total_out = array_sum(array_column($debits['data']  ?? [], 'amount'));
            $cash_pos  = $total_in - $total_out;

            // Get total pending disbursements
            $pending_disb = db_select('rcts_aid_payout_registry', ['status' => 'eq.Scheduled', 'select' => 'approved_amount']);
            $total_pending = array_sum(array_column($pending_disb['data'] ?? [], 'approved_amount'));

            // Stress test: can we cover all pending + proposed without going below 20% reserve?
            $total_outflow  = $total_pending + $proposed_payout;
            $post_payout    = $cash_pos - $total_outflow;
            $reserve_floor  = $cash_pos * 0.20; // 20% minimum reserve
            $stress_result  = $post_payout >= $reserve_floor ? 'OK' : ($post_payout >= 0 ? 'Warning' : 'Critical');

            api_response(true, 'Liquidity stress test complete', [
                'current_cash_position' => round($cash_pos, 2),
                'total_pending_disburse'=> round($total_pending, 2),
                'proposed_payout'       => round($proposed_payout, 2),
                'total_outflow'         => round($total_outflow, 2),
                'cash_after_payout'     => round($post_payout, 2),
                'reserve_floor_20pct'   => round($reserve_floor, 2),
                'stress_result'         => $stress_result,
                'approved'              => $stress_result !== 'Critical',
                'recommendation'        => match($stress_result) {
                    'OK'       => 'Cash position is healthy. Payout approved.',
                    'Warning'  => 'Cash position is tight. Proceed with caution.',
                    'Critical' => 'Insufficient funds. Reduce payout batch or collect more revenue first.'
                }
            ]);
        } catch (Exception $e) {
            api_response(false, 'Stress test error: ' . $e->getMessage(), null, 500);
        }
        break;

    // ── GET delinquency report ────────────────────────────────────────────
    case 'delinquency_report':
        $result = db_select('rcts_assessment_billing_hub', [
            'status' => 'in.(Pending,Delinquent)',
            'select' => 'bill_reference_no,qcitizen_id,bill_type,total_amount_due,due_date,status',
            'order'  => 'due_date.asc'
        ]);

        // Flag as delinquent if past due date
        $today     = CURRENT_DATE;
        $overdue   = array_filter($result['data'] ?? [], fn($b) => $b['due_date'] < $today);
        $total_overdue = array_sum(array_column($overdue, 'total_amount_due'));

        api_response(true, 'Delinquency report', [
            'overdue_bills'  => array_values($overdue),
            'overdue_count'  => count($overdue),
            'total_overdue'  => round($total_overdue, 2),
            'generated_at'   => date('Y-m-d H:i:s')
        ]);
        break;

    // ── GET e-SRE data (Statement of Receipts & Expenditures) ────────────
    case 'esre_data':
        $year  = (int)($_GET['year']  ?? CURRENT_YEAR);
        $month = $_GET['month'] ?? null;

        $date_filter_start = $month ? "$year-$month-01" : "$year-01-01";
        $date_filter_end   = $month ? date("$year-$month-t") : "$year-12-31";

        $receipts = db_select('rcts_treasury_ledger', [
            'entry_type'       => 'eq.Credit',
            'entry_timestamp'  => 'gte.' . $date_filter_start,
            'select'           => 'revenue_category,fund_id,amount,entry_timestamp'
        ]);
        $expenditures = db_select('rcts_treasury_ledger', [
            'entry_type'       => 'eq.Debit',
            'entry_timestamp'  => 'gte.' . $date_filter_start,
            'select'           => 'revenue_category,fund_id,amount,entry_timestamp'
        ]);

        $total_receipts = array_sum(array_column($receipts['data'] ?? [], 'amount'));
        $total_expend   = array_sum(array_column($expenditures['data'] ?? [], 'amount'));

        api_response(true, 'e-SRE data for ' . ($month ? "Month $month/" : '') . $year, [
            'period'            => ($month ? "Month $month/" : '') . $year,
            'receipts'          => $receipts['data'],
            'total_receipts'    => round($total_receipts, 2),
            'expenditures'      => $expenditures['data'],
            'total_expenditures'=> round($total_expend, 2),
            'net_balance'       => round($total_receipts - $total_expend, 2),
            'generated_by'      => 'RCTS-QC Auto e-SRE',
            'generated_at'      => date('c'),
            'standard'          => 'BLGF/COA eSRE Format',
            'lgu'               => LGU_NAME
        ]);
        break;

    // ── POST refresh dashboard snapshot ──────────────────────────────────
    case 'refresh_snapshot':
        // Re-compute all KPIs and save a new snapshot row
        $month_start = date('Y-m-01');

        $all_credits  = db_select('rcts_treasury_ledger', ['entry_type' => 'eq.Credit', 'entry_timestamp' => 'gte.' . $month_start, 'select' => 'revenue_category,amount']);
        $all_debits   = db_select('rcts_treasury_ledger', ['entry_type' => 'eq.Debit',  'entry_timestamp' => 'gte.' . $month_start, 'select' => 'amount']);
        $pending_disb = db_select('rcts_aid_payout_registry', ['status' => 'eq.Scheduled', 'select' => 'approved_amount']);
        $delinquent   = db_select('rcts_assessment_billing_hub', ['status' => 'eq.Delinquent', 'select' => 'bill_reference_no']);

        $credits_by_type = [];
        foreach ($all_credits['data'] ?? [] as $row) {
            $credits_by_type[$row['revenue_category']] = ($credits_by_type[$row['revenue_category']] ?? 0) + $row['amount'];
        }

        $snapshot = [
            'total_collection_mtd'    => round(array_sum(array_column($all_credits['data'] ?? [], 'amount')), 2),
            'total_rpt_collected'     => round($credits_by_type['RPT']          ?? 0, 2),
            'total_business_tax'      => round($credits_by_type['BusinessTax']  ?? 0, 2),
            'total_market_rental'     => round($credits_by_type['MarketRental'] ?? 0, 2),
            'total_fines_collected'   => round($credits_by_type['TrafficFine']  ?? 0, 2),
            'total_disbursed_mtd'     => round(array_sum(array_column($all_debits['data'] ?? [], 'amount')), 2),
            'revenue_target'          => 5000000.00,
            'qrf_balance'             => 10000000.00,
            'qrf_status'              => 'Locked',
            'delinquency_count'       => count($delinquent['data'] ?? []),
            'pending_disbursements'   => round(array_sum(array_column($pending_disb['data'] ?? [], 'approved_amount')), 2),
            'liquidity_stress_result' => 'OK',
            'generated_by'            => 'system-auto-refresh'
        ];

        $insert = db_insert('rcts_treasury_dashboard', $snapshot);
        api_response($insert['success'], 'Dashboard snapshot refreshed', $snapshot);
        break;

    // ── GET traffic violations for a citizen ─────────────────────────────
    case 'get_traffic_violations':
        $qcitizen_id = $_GET['qcitizen_id'] ?? '';
        if (!$qcitizen_id) {
            api_response(false, 'qcitizen_id required', null, 400);
        }

        $result = db_select('rcts_traffic_violation', [
            'qcitizen_id' => 'eq.' . $qcitizen_id,
            'order'       => 'apprehension_date.desc'
        ]);
        api_response($result['success'], 'Traffic violations retrieved', $result['data']);
        break;

    default:
        api_response(false, 'Unknown action', [
            'available' => ['live_summary','ledger_feed','liquidity_check','delinquency_report','esre_data','refresh_snapshot','get_traffic_violations']
        ], 400);
}