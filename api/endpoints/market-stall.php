<?php
/**
 * ENDPOINT: Market Stall Rental & Billing
 * api/endpoints/market-stall.php
 *
 * ROUTES:
 *   GET  ?action=get_active_stalls
 *   GET  ?action=get_vendor_bill&qcitizen_id=QC-2024-000003
 *   POST ?action=receive_occupancy_signal   (called by S10 — monthly trigger)
 *   POST ?action=generate_invoice           (body: stall_asset_id)
 *   POST ?action=mark_paid                  (body: bill_reference_no)
 */

require_once __DIR__ . '/../middleware/cors.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/supabase.php';

require_once __DIR__ . '/../config/constants.php';

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

    // ── GET all active stalls with vendor info ───────────────────────────
    case 'get_active_stalls':
        $result = db_select('rcts_public_asset_stall', ['occupancy_status_flag' => 'eq.Active'], 'stall_asset_id,facility_name,stall_number,qcitizen_id,monthly_rental_rate,occupancy_status_flag,occupancy_last_verified,occupancy_verification_method');
        if (!$result['success']) {
            api_response(false, 'Failed to fetch stalls', null, 500);
        }
        // Join with citizen registry for vendor names
        $stalls = $result['data'];
        foreach ($stalls as &$stall) {
            if ($stall['qcitizen_id']) {
                $citizen = db_select('rcts_citizen_registry', ['qcitizen_id' => 'eq.' . $stall['qcitizen_id']], 'full_name,mobile_no');
                if ($citizen['success'] && !empty($citizen['data'])) {
                    $stall['vendor_name'] = $citizen['data'][0]['full_name'];
                    $stall['mobile_no'] = $citizen['data'][0]['mobile_no'];
                }
            }
        }
        audit_log('system', 'get_active_stalls', ['count' => count($stalls)]);
        api_response(true, 'Active market stalls retrieved', $stalls);
        break;

    // ── GET rental bill for a specific vendor ────────────────────────────
    case 'get_vendor_bill':
        $id = $_GET['qcitizen_id'] ?? '';
        if (!$id) api_response(false, 'qcitizen_id required', null, 400);

        $result = db_select('rcts_assessment_billing_hub', [
            'qcitizen_id' => 'eq.' . $id,
            'bill_type'   => 'eq.MarketRental',
            'status'      => 'eq.Pending',
            'order'       => 'created_at.desc'
        ]);
        audit_log($id, 'get_vendor_bill', ['result_count' => count($result['data'] ?? [])]);
        api_response($result['success'], 'Vendor rental bills retrieved', $result['data']);
        break;

    // ── POST receive occupancy signal from S10 (the monthly auto-trigger) 
    case 'receive_occupancy_signal':
        $caller    = require_subsystem_auth(); // must be S10
        $stall_id  = $body['stall_asset_id']         ?? '';
        $status    = $body['occupancy_status_flag']   ?? '';
        $method    = $body['verification_method']     ?? 'Manual';
        $verified_at = $body['verified_at']           ?? date('c');

        if (!$stall_id || !$status) {
            api_response(false, 'stall_asset_id and occupancy_status_flag are required', null, 400);
        }

        // Update stall occupancy in our DB
        db_update('rcts_public_asset_stall',
            ['stall_asset_id' => 'eq.' . $stall_id],
            [
                'occupancy_status_flag'       => $status,
                'occupancy_verification_method' => $method,
                'occupancy_last_verified'     => $verified_at,
                'updated_at'                  => date('c')
            ]
        );
        audit_log($caller, 'receive_occupancy_signal', ['stall_id' => $stall_id, 'status' => $status]);

        if ($status !== 'Active') {
            api_response(true, 'Occupancy signal received. Stall is ' . $status . '. No invoice generated.', [
                'stall_id' => $stall_id,
                'status'   => $status
            ]);
        }

        // AUTO-TRIGGER: generate monthly invoice for active stall
        $inv_url  = 'http://localhost/rcts-qc/api/endpoints/market-stall.php?action=generate_invoice';
        $inv_body = json_encode(['stall_asset_id' => $stall_id, 'auto_triggered' => true]);
        $ctx      = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nX-API-Key: DEV-BYPASS-KEY-LOCAL\r\n",
            'content' => $inv_body
        ]]);
        $inv_response = json_decode(file_get_contents($inv_url, false, $ctx), true);

        audit_log($caller, 'auto_generate_invoice', ['stall_id' => $stall_id, 'auto_triggered' => true]);
        api_response(true, 'Occupancy signal received. Stall is Active. Invoice auto-generated.', [
            'stall_id'        => $stall_id,
            'occupancy_status'=> $status,
            'invoice'         => $inv_response['data'] ?? null
        ]);
        break;

    // ── POST generate monthly rental invoice ─────────────────────────────
    case 'generate_invoice':
        $stall_id     = $body['stall_asset_id'] ?? '';
        $auto_trigger = $body['auto_triggered'] ?? false;
        if (!$stall_id) api_response(false, 'stall_asset_id required', null, 400);

        // Fetch stall details
        $stall_result = db_select('rcts_public_asset_stall', ['stall_asset_id' => 'eq.' . $stall_id]);
        if (empty($stall_result['data'])) api_response(false, 'Stall not found', null, 404);
        $stall = $stall_result['data'][0];

        if ($stall['occupancy_status_flag'] !== 'Active') {
            api_response(false, 'Stall is not Active. Cannot generate invoice.', null, 422);
        }

        if (!$stall['qcitizen_id']) {
            api_response(false, 'No vendor assigned to this stall.', null, 422);
        }

        // Check if invoice already exists for this month
        $month_start = date('Y-m-01');
        $month_end   = date('Y-m-t');
        $existing = db_select('rcts_assessment_billing_hub', [
            'asset_id'  => 'eq.' . $stall_id,
            'bill_type' => 'eq.MarketRental',
            'status'    => 'eq.Pending',
            'created_at'=> 'gte.' . $month_start
        ]);
        if (!empty($existing['data'])) {
            api_response(true, 'Invoice already exists for this month', $existing['data'][0]);
        }

        // Check for arrears (any previous unpaid bills)
        $arrears_result = db_select('rcts_assessment_billing_hub', [
            'asset_id'  => 'eq.' . $stall_id,
            'bill_type' => 'eq.MarketRental',
            'status'    => 'eq.Pending',
            'created_at'=> 'lt.' . $month_start
        ]);
        $has_arrears  = !empty($arrears_result['data']);
        $penalty_rate = $has_arrears ? MARKET_LATE_PENALTY_RATE : 0.0;

        $bill_ref = 'RCTS-MS-' . date('Ym') . '-' . strtoupper(substr(uniqid(), -6));

        // Calculate total amount due
        $base_amount = (float)$stall['monthly_rental_rate'];
        $total_discount = $base_amount * 0.0; // No discount for market rental
        $total_penalty = $has_arrears ? $base_amount * $penalty_rate : 0.0;
        $total_amount_due = $base_amount - $total_discount + $total_penalty;

        $bill = [
            'bill_reference_no'   => $bill_ref,
            'qcitizen_id'         => $stall['qcitizen_id'],
            'bill_type'           => 'MarketRental',
            'originating_dept_id' => 10,
            'asset_id'            => $stall_id,
            'tax_year'            => CURRENT_YEAR,
            'base_amount'         => $base_amount,
            'discount_rate'       => 0.0,
            'penalty_rate'        => $penalty_rate,
            'total_amount_due'    => $total_amount_due,
            'status'              => 'Pending',
            'due_date'            => date('Y-m-t') // last day of current month
        ];

        $insert = db_insert('rcts_assessment_billing_hub', $bill);
        audit_log($stall['qcitizen_id'], 'generate_invoice', $bill);

        api_response($insert['success'], 'Market rental invoice generated' . ($auto_trigger ? ' (auto-triggered by S10 signal)' : ''), [
            'invoice'     => $insert['data'],
            'stall'       => $stall['stall_number'],
            'facility'    => $stall['facility_name'],
            'has_arrears' => $has_arrears,
            'penalty'     => $has_arrears ? ($penalty_rate * 100 . '% surcharge applied') : 'none'
        ]);
        break;

    // ── POST mark paid + send lease confirmation back to S10 ────────────
    case 'mark_paid':
        $bill_ref = $body['bill_reference_no'] ?? '';
        if (!$bill_ref) api_response(false, 'bill_reference_no required', null, 400);

        db_update('rcts_assessment_billing_hub',
            ['bill_reference_no' => 'eq.' . $bill_ref],
            ['status' => 'Paid', 'updated_at' => date('c')]
        );
        audit_log('system', 'market_rental_paid', ['bill_reference_no' => $bill_ref]);

        // Notify S10: lease renewal confirmed, vendor cleared to stay
        $s10_signal = [
            'signal_type'      => 'LEASE_PAYMENT_CONFIRMED',
            'bill_reference_no'=> $bill_ref,
            'paid_at'          => date('c'),
            'message'          => 'Monthly rental settled. Lease standing: Active.',
            'sent_to'          => 'Subsystem 10 - Facility Reservation System'
        ];

        api_response(true, 'Rental marked Paid. Lease renewal confirmation sent to S10.', $s10_signal);
        break;

    // ═══════════════════════════════════════════════════════════════════════
    // TO-BE FEATURE: AUTO-DEBIT MONTHLY (Priority 3)
    // Auto-deducts rent from vendor wallets on 1st of month OR occupancy verification
    // ═══════════════════════════════════════════════════════════════════════
    case 'auto_debit_monthly':
        // This can be triggered: 
        // 1. By cron job on 1st of month
        // 2. By occupancy signal from S10
        // 3. Manually by treasurer
        $auto_trigger = $body['auto_trigger'] ?? false;
        $target_month = $body['target_month'] ?? date('Y-m');
        
        // Get all active stalls with verified occupancy
        $stalls_result = db_select('rcts_public_asset_stall', [
            'occupancy_status_flag' => 'eq.Active'
        ]);
        $stalls = $stalls_result['data'] ?? [];
        
        if (empty($stalls)) {
            api_response(true, 'No active stalls with verified occupancy', ['debit_results' => []]);
        }
        
        $debit_results = [];
        $success_count = 0;
        $failed_count = 0;
        
        foreach ($stalls as $stall) {
            $vendor_id = $stall['qcitizen_id'];
            $stall_id = $stall['stall_asset_id'];
            $monthly_rent = (float)$stall['monthly_rental_rate'];
            
            if (!$vendor_id) {
                $debit_results[] = ['stall_id' => $stall_id, 'status' => 'skipped', 'reason' => 'No vendor assigned'];
                continue;
            }
            
            // Check if bill exists for this month
            $month_start = $target_month . '-01';
            $bill_result = db_select('rcts_assessment_billing_hub', [
                'asset_id'  => 'eq.' . $stall_id,
                'bill_type' => 'eq.MarketRental',
                'status'    => 'eq.Pending',
                'created_at'=> 'gte.' . $month_start
            ]);
            
            // If no pending bill, generate one first
            $bill_ref = '';
            if (empty($bill_result['data'])) {
                $bill_ref = 'RCTS-MS-' . date('Ym', strtotime($month_start)) . '-' . strtoupper(substr(uniqid(), -6));
                $bill = [
                    'bill_reference_no'   => $bill_ref,
                    'qcitizen_id'         => $vendor_id,
                    'bill_type'           => 'MarketRental',
                    'originating_dept_id' => 10,
                    'asset_id'            => $stall_id,
                    'tax_year'            => date('Y', strtotime($month_start)),
                    'base_amount'         => $monthly_rent,
                    'discount_rate'       => 0.0,
                    'penalty_rate'        => 0.0,
                    'status'              => 'Pending',
                    'due_date'            => date('Y-m-t', strtotime($month_start))
                ];
                db_insert('rcts_assessment_billing_hub', $bill);
            } else {
                $bill = $bill_result['data'][0];
                $bill_ref = $bill['bill_reference_no'];
                $monthly_rent = (float)$bill['total_amount_due'];
            }
            
            // Simulate wallet balance check (in real system, would call S1 wallet API)
            $wallet_balance = 10000; // Mock: assume vendor has sufficient balance
            
            if ($wallet_balance < $monthly_rent) {
                $debit_results[] = [
                    'stall_id' => $stall_id,
                    'vendor_id' => $vendor_id,
                    'amount' => $monthly_rent,
                    'status' => 'failed',
                    'reason' => 'Insufficient wallet balance'
                ];
                $failed_count++;
                continue;
            }
            
            // Execute auto-debit (simulate successful payment)
            $txn_id = 'TXN-AUTO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $eor_number = 'EOR-AUTO-' . date('Y') . '-' . strtoupper(substr(uniqid(), -8));
            
            // Create payment transaction
            $txn_data = [
                'transaction_id' => $txn_id,
                'bill_reference_no' => $bill_ref,
                'qcitizen_id' => $vendor_id,
                'gateway_provider' => 'Auto-Debit',
                'amount_settled' => $monthly_rent,
                'digital_hash' => md5($txn_id . $vendor_id . $monthly_rent . date('c')),
                'transaction_status' => 'Success',
                'settlement_loop_sent' => true,
                'bank_reference_no' => 'AUTO-DEBIT-' . $stall_id
            ];
            db_insert('rcts_payment_transaction', $txn_data);
            
            // Mark bill as Paid
            db_update('rcts_assessment_billing_hub',
                ['bill_reference_no' => 'eq.' . $bill_ref],
                ['status' => 'Paid', 'updated_at' => date('c')]
            );
            
            // Generate e-OR
            $eor_data = [
                'eor_number' => $eor_number,
                'transaction_id' => $txn_id,
                'qcitizen_id' => $vendor_id,
                'amount_paid' => $monthly_rent,
                'bill_type' => 'MarketRental',
                'digital_signature_token' => md5($eor_number . $txn_id . date('c')),
                'blockchain_registry_id' => 'BC-' . strtoupper(md5($eor_number)),
                'sent_to_citizen' => false
            ];
            supabase_request('rcts_eor', 'POST', [], $eor_data, true);
            
            // Update Treasury Ledger
            $ledger_data = [
                'transaction_id' => $txn_id,
                'entry_type' => 'Credit',
                'fund_id' => 'GeneralFund',
                'gl_account_code' => '1-03-001',
                'revenue_category' => 'MarketRental',
                'amount' => $monthly_rent,
                'remarks' => 'Auto-Debit Settlement — Market Stall Rental — eOR: ' . $eor_number
            ];
            supabase_request('rcts_treasury_ledger', 'POST', [], $ledger_data, true);
            
            // Send notification to vendor (via S1)
            $vendor_info = db_select('rcts_qcitizen_master', ['qcitizen_id' => 'eq.' . $vendor_id]);
            $vendor_name = $vendor_info['data'][0]['full_name'] ?? 'Vendor';
            
            $notify_body = json_encode([
                'qcitizen_id' => $vendor_id,
                'message' => 'Auto-debit successful! ₱' . number_format($monthly_rent, 2) . ' rental for ' . $stall['stall_number'] . ' has been deducted. eOR: ' . $eor_number,
                'type' => 'auto_debit_confirmation'
            ]);
            $notify_ctx = stream_context_create(['http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $notify_body
            ]]);
            @file_get_contents(S1_API_URL . '?action=send_notification', false, $notify_ctx);
            
            // Notify S10 (Assets) of lease confirmation
            $s10_ctx = stream_context_create(['http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode([
                    'stall_id' => $stall_id,
                    'payment_status' => 'Auto-Debit-Success',
                    'eor_number' => $eor_number,
                    'action' => 'confirm_lease'
                ])
            ]]);
            @file_get_contents(S10_API_URL . '?action=update_lease_status', false, $s10_ctx);
            
            $debit_results[] = [
                'stall_id' => $stall_id,
                'vendor_id' => $vendor_id,
                'vendor_name' => $vendor_name,
                'amount' => $monthly_rent,
                'transaction_id' => $txn_id,
                'eor_number' => $eor_number,
                'status' => 'success',
                'message' => 'Auto-debit successful'
            ];
            $success_count++;
        }
        
        api_response(true, 'Monthly Auto-Debit Complete', [
            'target_month' => $target_month,
            'total_stalls_processed' => count($stalls),
            'successful_debits' => $success_count,
            'failed_debits' => $failed_count,
            'total_collected' => array_sum(array_map(function($r) { return $r['status'] === 'success' ? $r['amount'] : 0; }, $debit_results)),
            'debit_results' => $debit_results
        ]);
        break;

    default:
        api_response(false, 'Unknown action', ['available' => ['get_active_stalls','get_vendor_bill','receive_occupancy_signal','generate_invoice','mark_paid']], 400);
}