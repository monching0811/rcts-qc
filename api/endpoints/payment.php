<?php
/**
 * ENDPOINT: Digital Payment Integration (QC-PAY)
 * api/endpoints/payment.php
 *
 * The clearinghouse — handles ALL money movement in and out.
 *
 * ROUTES:
 *   GET  ?action=get_pending_bills&qcitizen_id=QC-2024-000001
 *   GET  ?action=get_transaction_history&qcitizen_id=QC-2024-000001
 *   GET  ?action=get_receipt&transaction_id=TXN-xxx
 *   POST ?action=checkout        (body: qcitizen_id, bill_reference_nos[], gateway_provider)
 *   POST ?action=execute         (body: transaction_id — simulates bank confirmation)
 */

require_once __DIR__ . '/../middleware/cors.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../config/constants.php';

require_once __DIR__ . '/../config/payment_gateways.php';
require_once __DIR__ . '/../lib/PaymentGateway.php';

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
$rawBody = file_get_contents('php://input');
$body   = json_decode($rawBody, true) ?? [];

/**
 * Helper: Complete a transaction by marking bills paid, issuing receipt, and
 * updating the ledger. This is called by both the mock execute path and real
 * gateway webhooks.
 */
function complete_payment_transaction(string $txn_id, string $bank_ref = null, array $provider_data = []): array {
    // Fetch transaction (use service key to bypass RLS)
    $txn_result = supabase_request('rcts_payment_transaction', 'GET', [
        'transaction_id' => 'eq.' . $txn_id
    ], [], true);

    if (empty($txn_result['data'])) {
        return ['success' => false, 'message' => 'Transaction not found', 'code' => 404];
    }

    $txn = $txn_result['data'][0];

    // Interpret bank_reference_no: may be clean text "BILL_REF PROVIDER_REF", JSON object, or legacy JSON array
    $bankRefRaw = $txn['bank_reference_no'] ?? null;
    $bankRefParsed = json_decode($bankRefRaw ?? '', true);

    $bill_refs_to_update = [];
    $provider_ref = null;

    if (is_array($bankRefParsed)) {
        // JSON format
        if (isset($bankRefParsed['bills']) && is_array($bankRefParsed['bills'])) {
            $bill_refs_to_update = $bankRefParsed['bills'];
            $provider_ref = $bankRefParsed['provider_reference'] ?? null;
        } elseif (array_values($bankRefParsed) === $bankRefParsed) {
            // Numeric array (legacy storage)
            $bill_refs_to_update = $bankRefParsed;
        }
    } elseif (is_string($bankRefRaw) && strpos($bankRefRaw, ' ') !== false) {
        // Clean text format: "BILL_REF PROVIDER_REF"
        $parts = explode(' ', $bankRefRaw, 2);
        $bill_refs_to_update = [$parts[0]];
        $provider_ref = $parts[1] ?? null;
    }

    if (empty($bill_refs_to_update)) {
        $bill_refs_to_update = [$txn['bill_reference_no']];
    }

    // Prefer provider reference passed in (from webhook or polling)
    if (!empty($bank_ref)) {
        $provider_ref = $bank_ref;
    }

    // Persist structured bank_reference_no (bills + provider_reference)
    // For Stripe and mock gateways, use clean text format. For others, use JSON.
    if ($txn['gateway_provider'] === 'Stripe' || $txn['gateway_provider'] === 'Mock' || $txn['gateway_provider'] === 'GCash' || $txn['gateway_provider'] === 'Maya' || $txn['gateway_provider'] === 'Bank') {
        // Clean text format: "BILL_REF PROVIDER_REF"
        $billRef = is_array($bill_refs_to_update) ? $bill_refs_to_update[0] : $bill_refs_to_update;
        $updatedBankRef = $billRef . ' ' . $provider_ref;
    } else {
        // JSON format for complex gateways like PayMongo
        $updatedBankRef = json_encode([
            'bills' => $bill_refs_to_update,
            'provider_reference' => $provider_ref,
        ]);
    }

    // Mark transaction Success
    $updated = supabase_request('rcts_payment_transaction', 'PATCH', [
        'transaction_id' => 'eq.' . $txn_id
    ], ['transaction_status' => 'Success', 'bank_reference_no' => $updatedBankRef], true);

    if (!($updated['success'] ?? false)) {
        return ['success' => false, 'message' => 'Failed to update transaction status', 'details' => $updated];
    }

    foreach ($bill_refs_to_update as $bill_ref) {
        supabase_request('rcts_assessment_billing_hub', 'PATCH', [
            'bill_reference_no' => 'eq.' . $bill_ref
        ], ['status' => 'Paid', 'updated_at' => date('c')], true);
    }

    // Create e-OR and ledger entries (reuse existing logic from execute)
    // For brevity, we re-use the existing execute logic by re-invoking the execute flow
    // via an internal call that mimics the 'execute' endpoint.
    // Note: we avoid recursion by isolating the core post-payment logic.

    // (Re)use the same code path as execute to create eOR & ledger entries
    // by calling the internal function used in the execute case.

    return process_post_payment($txn_id);
}

/**
 * Internal helper used by execute + webhook to create eOR + ledger entries.
 */
function process_post_payment(string $txn_id): array {
    // Fetch transaction details
    $txn = supabase_request('rcts_payment_transaction', 'GET', [
        'transaction_id' => 'eq.' . $txn_id
    ], [], true);
    if (empty($txn['data'])) {
        return ['success' => false, 'message' => 'Transaction not found for post-processing'];
    }
    $txn = $txn['data'][0];

    // Build e-OR and ledger exactly as execute currently does
    // (This duplicates code for clarity; consider refactoring further if needed)

    // Determine bill types for eOR and ledger
    $bankRefRaw = $txn['bank_reference_no'] ?? null;
    $bankRefParsed = json_decode($bankRefRaw ?? '', true);

    $bill_refs_to_update = [];
    if (is_array($bankRefParsed)) {
        if (isset($bankRefParsed['bills']) && is_array($bankRefParsed['bills'])) {
            $bill_refs_to_update = $bankRefParsed['bills'];
        } elseif (array_values($bankRefParsed) === $bankRefParsed) {
            $bill_refs_to_update = $bankRefParsed;
        }
    }

    if (empty($bill_refs_to_update)) {
        $bill_refs_to_update = [$txn['bill_reference_no']];
    }

    $bill_types = [];
    foreach ($bill_refs_to_update as $bill_ref) {
        $bill_info = db_select('rcts_assessment_billing_hub', ['bill_reference_no' => 'eq.' . $bill_ref]);
        if (!empty($bill_info['data'])) {
            $bill_types[] = $bill_info['data'][0]['bill_type'];
        }
    }
    $bill_type = count(array_unique($bill_types)) === 1 ? $bill_types[0] : 'Multiple';

    // Create e-OR
    $eor_number = 'EOR-' . date('Y') . '-' . strtoupper(substr(uniqid(), -8));
    $eor_data = [
        'eor_number' => $eor_number,
        'transaction_id' => $txn_id,
        'qcitizen_id' => $txn['qcitizen_id'],
        'amount_paid' => $txn['amount_settled'],
        'bill_type' => $bill_type,
        'digital_signature_token' => md5($eor_number . $txn_id . date('c')),
        'blockchain_registry_id' => 'BC-' . strtoupper(md5($eor_number)),
        'sent_to_citizen' => false
    ];
    supabase_request('rcts_eor', 'POST', [], $eor_data, true);

    // Ledger entries by bill type
    $gl_map = [
        'RPT' => ['code' => '1-01-001', 'fund' => 'GeneralFund'],
        'BusinessTax' => ['code' => '1-02-001', 'fund' => 'GeneralFund'],
        'MarketRental' => ['code' => '1-03-001', 'fund' => 'GeneralFund'],
        'TrafficFine' => ['code' => '1-04-001', 'fund' => 'GeneralFund'],
        'FacilityFee' => ['code' => '1-05-001', 'fund' => 'GeneralFund'],
    ];

    $bills_by_type = [];
    foreach ($bill_refs_to_update as $bill_ref) {
        $bill_info = db_select('rcts_assessment_billing_hub', ['bill_reference_no' => 'eq.' . $bill_ref]);
        if (!empty($bill_info['data'])) {
            $bill = $bill_info['data'][0];
            $type = $bill['bill_type'];
            if (!isset($bills_by_type[$type])) {
                $bills_by_type[$type] = 0;
            }
            $bills_by_type[$type] += (float)$bill['total_amount_due'];
        }
    }

    foreach ($bills_by_type as $type => $amount) {
        $gl = $gl_map[$type] ?? ['code' => '1-09-001', 'fund' => 'GeneralFund'];
        $ledger_data = [
            'transaction_id' => $txn_id,
            'entry_type' => 'Credit',
            'fund_id' => $gl['fund'],
            'gl_account_code' => $gl['code'],
            'revenue_category' => $type,
            'amount' => $amount,
            'remarks' => 'QC-PAY Settlement — ' . $type . ' — eOR: ' . $eor_number
        ];
        supabase_request('rcts_treasury_ledger', 'POST', [], $ledger_data, true);
    }

    // Notify subsystems for specific bills
    foreach ($bill_refs_to_update as $bill_ref) {
        $bill = db_select('rcts_assessment_billing_hub', ['bill_reference_no' => 'eq.' . $bill_ref]);
        if (!empty($bill['data'])) {
            $bill = $bill['data'][0];
            if ($bill['originating_dept_id'] == 9) {
                // Traffic fine paid, resolve in S9
                $ticket_id = $bill['asset_id'];
                s9_supabase_request('traffic_violations', 'PATCH', [
                    'ticket_id' => 'eq.' . $ticket_id
                ], ['status' => 'Resolved']);
            }
        }
    }

    // Notify citizen (S1)
    $notify_body = json_encode([
        'qcitizen_id' => $txn['qcitizen_id'],
        'message' => 'Payment confirmed! Your payment of ₱' . number_format($txn['amount_settled'], 2) . ' has been settled. eOR No.: ' . $eor_number,
        'type' => 'payment_confirmation'
    ]);
    $notify_ctx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $notify_body
    ]]);
    @file_get_contents(S1_API_URL . '?action=send_notification', false, $notify_ctx);

    // Outbound digital handshakes (send payment confirmation to other subsystems)
    $outbound_results = [];

    // Determine bills_by_type if not already present
    if (empty($bills_by_type)) {
        $bills_by_type = [];
        foreach ($bill_refs_to_update as $bill_ref) {
            $bill_info = db_select('rcts_assessment_billing_hub', ['bill_reference_no' => 'eq.' . $bill_ref]);
            if (!empty($bill_info['data'])) {
                $bill = $bill_info['data'][0];
                $type = $bill['bill_type'];
                if (!isset($bills_by_type[$type])) {
                    $bills_by_type[$type] = 0;
                }
                $bills_by_type[$type] += (float)$bill['total_amount_due'];
            }
        }
    }

    foreach ($bills_by_type as $bill_type_key => $amount) {
        switch ($bill_type_key) {
            case 'BusinessTax':
                $s2_payload = json_encode([
                    'qcitizen_id' => $txn['qcitizen_id'],
                    'payment_status' => 'Paid',
                    'eor_number' => $eor_number,
                    'transaction_id' => $txn_id,
                    'action' => 'release_permit'
                ]);
                $s2_ctx = stream_context_create(['http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => $s2_payload
                ]]);
                $outbound_results['S2_Permits'] = @file_get_contents(S2_API_URL . '?action=payment_settlement', false, $s2_ctx) !== false ? 'sent' : 'failed';

                $s4_payload = json_encode([
                    'qcitizen_id' => $txn['qcitizen_id'],
                    'clearance_type' => 'Sanitary',
                    'payment_status' => 'Paid',
                    'eor_number' => $eor_number
                ]);
                $s4_ctx = stream_context_create(['http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => $s4_payload
                ]]);
                $outbound_results['S4_Health'] = @file_get_contents(S4_API_URL . '?action=update_clearance_status', false, $s4_ctx) !== false ? 'sent' : 'failed';
                break;

            case 'RPT':
                $s7_payload = json_encode([
                    'qcitizen_id' => $txn['qcitizen_id'],
                    'tax_status' => 'Cleared',
                    'year' => date('Y'),
                    'eor_number' => $eor_number
                ]);
                $s7_ctx = stream_context_create(['http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => $s7_payload
                ]]);
                $outbound_results['S7_Zoning'] = @file_get_contents(S7_API_URL . '?action=update_tax_clearance', false, $s7_ctx) !== false ? 'sent' : 'failed';
                break;

            case 'TrafficFine':
                $s9_payload = json_encode([
                    'qcitizen_id' => $txn['qcitizen_id'],
                    'violation_status' => 'Settled',
                    'eor_number' => $eor_number,
                    'settlement_date' => date('Y-m-d')
                ]);
                $s9_ctx = stream_context_create(['http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => $s9_payload
                ]]);
                $outbound_results['S9_Transport'] = @file_get_contents(S9_API_URL . '?action=resolve_violation', false, $s9_ctx) !== false ? 'sent' : 'failed';
                break;

            case 'MarketRental':
                $s10_payload = json_encode([
                    'qcitizen_id' => $txn['qcitizen_id'],
                    'payment_status' => 'Paid',
                    'eor_number' => $eor_number,
                    'action' => 'confirm_lease'
                ]);
                $s10_ctx = stream_context_create(['http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => $s10_payload
                ]]);
                $outbound_results['S10_Assets'] = @file_get_contents(S10_API_URL . '?action=update_lease_status', false, $s10_ctx) !== false ? 'sent' : 'failed';
                break;

            case 'FacilityFee':
                $s10_payload = json_encode([
                    'qcitizen_id' => $txn['qcitizen_id'],
                    'payment_status' => 'Paid',
                    'eor_number' => $eor_number,
                    'action' => 'confirm_reservation'
                ]);
                $s10_ctx = stream_context_create(['http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => $s10_payload
                ]]);
                $outbound_results['S10_Facility'] = @file_get_contents(S10_API_URL . '?action=update_reservation_status', false, $s10_ctx) !== false ? 'sent' : 'failed';
                break;
        }
    }

    // Update transaction to mark outbound settlement loop sent
    db_update('rcts_payment_transaction', ['transaction_id' => 'eq.' . $txn_id], ['settlement_loop_sent' => true]);

    // Trigger webhook event records
    $webhook_payload = [
        'transaction_id' => $txn_id,
        'eor_number' => $eor_number,
        'qcitizen_id' => $txn['qcitizen_id'],
        'amount_paid' => $txn['amount_settled'],
        'bill_types' => array_keys($bills_by_type),
        'timestamp' => date('c')
    ];

    supabase_request('webhook_events', 'POST', [], [
        'event_type' => 'payment.completed',
        'event_data' => $webhook_payload
    ], true);

    supabase_request('webhook_events', 'POST', [], [
        'event_type' => 'receipt.issued',
        'event_data' => array_merge($webhook_payload, ['eor_data' => $eor_data])
    ], true);

    supabase_request('webhook_events', 'POST', [], [
        'event_type' => 'ledger.updated',
        'event_data' => [
            'transaction_id' => $txn_id,
            'entry_type' => 'Credit',
            'amount' => $txn['amount_settled'],
            'categories' => array_keys($bills_by_type),
            'timestamp' => date('c')
        ]
    ], true);

    return [
        'success' => true,
        'transaction_id' => $txn_id,
        'eor_number' => $eor_number,
        'amount_paid' => $txn['amount_settled'],
        'bill_type' => $bill_type,
        'provider' => $txn['gateway_provider'],
        'provider_data' => $provider_data,
        'bills_updated' => $bill_refs_to_update,
        'digital_handshakes' => $outbound_results,
    ];
}

switch ($action) {

    // ═══════════════════════════════════════════════════════════════════════
    // TEST ENDPOINT - Test PATCH/UPDATE operation
    // USE: ?action=test_patch&bill_ref=YOUR-BILL-REF
    // ═══════════════════════════════════════════════════════════════════════
    case 'test_patch':
        $bill_ref = $_GET['bill_ref'] ?? $_GET['bill_reference_no'] ?? '';
        if (!$bill_ref) {
            api_response(false, 'bill_ref required', null, 400);
        }
        
        error_log("=== TEST PATCH START ===");
        error_log("Attempting to PATCH bill: $bill_ref");
        
        // Fetch bill BEFORE patch
        error_log("Step 1: Fetching bill before patch...");
        $before = supabase_request('rcts_assessment_billing_hub', 'GET', 
            ['bill_reference_no' => 'eq.' . $bill_ref], [], true);
        
        error_log("Before PATCH - Full Result: " . json_encode($before));
        $before_status = $before['data'][0]['status'] ?? 'NOT FOUND';
        error_log("Before PATCH - Status: $before_status");
        
        // Attempt to PATCH to "Paid"
        error_log("Step 2: Executing PATCH...");
        $patch_result = supabase_request('rcts_assessment_billing_hub', 'PATCH', 
            ['bill_reference_no' => 'eq.' . $bill_ref],
            ['status' => 'Paid', 'updated_at' => date('c')], 
            true);  // Use service key
        
        error_log("PATCH Result: " . json_encode($patch_result));
        error_log("PATCH Success: " . ($patch_result['success'] ? 'TRUE' : 'FALSE'));
        error_log("PATCH HTTP Code: " . ($patch_result['http_code'] ?? 'UNKNOWN'));
        
        // Fetch bill AFTER patch
        error_log("Step 3: Fetching bill after patch...");
        $after = supabase_request('rcts_assessment_billing_hub', 'GET', 
            ['bill_reference_no' => 'eq.' . $bill_ref], [], true);
        
        error_log("After PATCH - Full Result: " . json_encode($after));
        $after_status = $after['data'][0]['status'] ?? 'NOT FOUND';
        error_log("After PATCH - Status: $after_status");
        error_log("=== TEST PATCH END ===");
        
        api_response(true, 'PATCH test completed - check server logs', [
            'bill_reference_no' => $bill_ref,
            'before_status' => $before_status,
            'after_status' => $after_status,
            'patch_success' => $patch_result['success'] ?? false,
            'patch_http_code' => $patch_result['http_code'] ?? 'UNKNOWN',
            'status_changed' => $before_status !== $after_status
        ]);
        break;

    // ═══════════════════════════════════════════════════════════════════════
    // DEBUG ENDPOINT - Check bill status directly in billing hub
    // ═══════════════════════════════════════════════════════════════════════
    case 'debug_bill_status':
        $bill_ref = $_GET['bill_reference_no'] ?? '';
        if (!$bill_ref) {
            $id = $_GET['qcitizen_id'] ?? '';
            if (!$id) api_response(false, 'bill_reference_no or qcitizen_id required', null, 400);
            
            // Get ALL bills for citizen (not just pending)
            $result = supabase_request('rcts_assessment_billing_hub', 'GET', 
                ['qcitizen_id' => 'eq.' . $id, 'order' => 'created_at.desc', 'limit' => '20'], [], true);
            
            api_response(true, 'All bills for citizen', [
                'bills' => $result['data'],
                'count' => count($result['data'] ?? [])
            ]);
        }
        
        // Get specific bill
        $result = supabase_request('rcts_assessment_billing_hub', 'GET', 
            ['bill_reference_no' => 'eq.' . $bill_ref], [], true);
        
        api_response(true, 'Bill status', [
            'bill' => $result['data'][0] ?? null,
            'http_code' => $result['http_code'] ?? 0
        ]);
        break;

    // ═══════════════════════════════════════════════════════════════════════
    // TEST ENDPOINT - Debug S7 integration
    // ═══════════════════════════════════════════════════════════════════════
    // TEST ENDPOINT - Debug S7 integration
    // USE: ?action=test_bill&cid=YOUR_ID&tdn=TEST-123&amt=5000&type=RPT&dept=8
    case 'test_bill':
        $cid = $_GET['cid'] ?? '';
        $tdn = $_GET['tdn'] ?? 'TDN-DEBUG-' . time();
        $amt = floatval($_GET['amt'] ?? 1000);
        $type = $_GET['type'] ?? 'RPT';
        $dept = intval($_GET['dept'] ?? 8);
        
        $r = supabase_request('rcts_assessment_billing_hub', 'POST', [], [
            'bill_reference_no' => $tdn,
            'qcitizen_id' => $cid,
            'bill_type' => $type,
            'originating_dept_id' => $dept,
            'asset_id' => $tdn,
            'base_amount' => $amt * 0.67,
            'discount_rate' => 0,
            'penalty_rate' => 0,
            'total_amount_due' => $amt,
            'status' => 'Pending',
            'tax_year' => date('Y'),
            'verification_ref_id' => 'DEBUG'
        ], true);
        
        api_response(true, 'Bill created', ['result' => $r]);
        break;

    case 'debug_s7_flow':
        $qcitizen_id = $_GET['qcitizen_id'] ?? '';
        
        error_log("DEBUG S7 FLOW START - qcitizen_id: $qcitizen_id");
        
        // Step 1: Check what's in billing hub for this citizen
        $existing_bills = supabase_request('rcts_assessment_billing_hub', 'GET', 
            ['qcitizen_id' => 'eq.' . $qcitizen_id], [], true);
        
        $rpt_bills = [];
        foreach ($existing_bills['data'] ?? [] as $bill) {
            if ($bill['bill_type'] === 'RPT') {
                $rpt_bills[] = $bill;
            }
        }
        
        api_response(true, 'Debug S7 Flow', [
            'qcitizen_id' => $qcitizen_id,
            'total_bills_in_hub' => count($existing_bills['data'] ?? []),
            'rpt_bills' => $rpt_bills,
            'rpt_count' => count($rpt_bills),
            'supabase_success' => $existing_bills['success'] ?? false
        ]);
        break;

    // TEST ENDPOINT - Check database tables
    case 'debug_tables':
        // Check payment_transaction table
        $payments = supabase_request('rcts_payment_transaction', 'GET', [], [], true);
        // Check eor table  
        $eors = supabase_request('rcts_eor', 'GET', [], [], true);
        // Check billing hub
        $bills = supabase_request('rcts_assessment_billing_hub', 'GET', ['limit' => 5], [], true);
        
        api_response(true, 'Database debug info', [
            'payment_transaction_count' => count($payments['data'] ?? []),
            'payment_transaction_sample' => $payments['data'][0] ?? null,
            'eor_count' => count($eors['data'] ?? []),
            'eor_sample' => $eors['data'][0] ?? null,
            'billing_hub_count' => count($bills['data'] ?? []),
            'billing_hub_sample' => $bills['data'][0] ?? null
        ]);
        break;

    // ═══════════════════════════════════════════════════════════════════════
    // TEST ENDPOINT - Remove in production
    // ═══════════════════════════════════════════════════════════════════════
    case 'test':
        api_response(true, 'Payment API is working', [
            'timestamp' => date('c'),
            'transaction_id' => 'TXN-TEST-123'
        ]);
        break;

    // ── GET all pending bills for a citizen (the unified cart) ───────────
    case 'get_pending_bills':
        $id = $_GET['qcitizen_id'] ?? '';
        if (!$id) api_response(false, 'qcitizen_id required', null, 400);

        // Query the billing table directly to get asset_id
        $result = db_select('rcts_assessment_billing_hub', ['qcitizen_id' => 'eq.' . $id]);
        $all_bills = $result['data'] ?? [];
        
        // Filter to only pending and add citizen name
        $bills = [];
        $citizen_name = '';
        
        foreach ($all_bills as $bill) {
            if ($bill['status'] === 'Pending') {
                // Get citizen name on first pass
                if (!$citizen_name && empty($bill['full_name'])) {
                    $citizen_result = db_select('rcts_citizen_registry', ['qcitizen_id' => 'eq.' . $id]);
                    $citizen = $citizen_result['data'][0] ?? [];
                    $citizen_name = $citizen['full_name'] ?? $id;
                }
                
                // Add citizen name
                $bill['full_name'] = $citizen_name ?: $id;
                $bills[] = $bill;
            }
        }

        // Compute grand total
        $total = array_sum(array_column($bills, 'total_amount_due'));

        api_response($result['success'], 'Pending bills retrieved', [
            'bills'       => $bills,
            'bill_count'  => count($bills),
            'grand_total' => round($total, 2)
        ]);
        break;

    // ═══════════════════════════════════════════════════════════════════════
    // TO-BE FEATURE: CONSOLIDATED BILL VIEW
    // Returns all pending bills grouped by type with one grand total
    // ═══════════════════════════════════════════════════════════════════════
    case 'get_consolidated_bill':
        $id = $_GET['qcitizen_id'] ?? '';
        if (!$id) api_response(false, 'qcitizen_id required', null, 400);

        $result = db_select('v_citizen_pending_bills', ['qcitizen_id' => 'eq.' . $id]);
        $bills = $result['data'] ?? [];

        // Group bills by type
        $bills_by_type = [];
        $bill_refs = [];
        foreach ($bills as $bill) {
            $type = $bill['bill_type'];
            if (!isset($bills_by_type[$type])) {
                $bills_by_type[$type] = [
                    'bill_type' => $type,
                    'count' => 0,
                    'subtotal' => 0,
                    'items' => []
                ];
            }
            $bills_by_type[$type]['count']++;
            $bills_by_type[$type]['subtotal'] += (float)$bill['total_amount_due'];
            $bills_by_type[$type]['items'][] = $bill;
            $bill_refs[] = $bill['bill_reference_no'];
        }

        // Calculate grand total
        $grand_total = array_sum(array_column($bills, 'total_amount_due'));

        // Get citizen info from S1
        $s1_url = S1_API_URL . '?action=get_citizen&qcitizen_id=' . urlencode($id);
        $citizen = json_decode(file_get_contents($s1_url), true);

        api_response(true, 'Consolidated Bill retrieved', [
            'consolidated_bill' => [
                'qcitizen_id' => $id,
                'citizen_name' => $citizen['data']['full_name'] ?? 'Unknown',
                'bill_count' => count($bills),
                'grouped_bills' => array_values($bills_by_type),
                'grand_total' => round($grand_total, 2),
                'bill_reference_nos' => $bill_refs,
                'generated_at' => date('c')
            ]
        ]);
        break;

    // ═══════════════════════════════════════════════════════════════════════
    // TO-BE FEATURE: PAY ALL BILLS (One-Click Checkout)
    // Pays all pending bills in a single transaction with instant settlement
    // ═══════════════════════════════════════════════════════════════════════
    case 'pay_all':
        $qcitizen_id = $body['qcitizen_id'] ?? '';
        $gateway = $body['gateway_provider'] ?? 'GCash';

        if (!$qcitizen_id) api_response(false, 'qcitizen_id required', null, 400);

        // Get all pending bills
        $result = db_select('v_citizen_pending_bills', ['qcitizen_id' => 'eq.' . $qcitizen_id]);
        $bills = $result['data'] ?? [];

        if (empty($bills)) {
            api_response(false, 'No pending bills found', null, 404);
        }

        // Get all bill reference numbers
        $bill_refs = array_column($bills, 'bill_reference_no');

        // Verify identity using RCTS local registry
        $citizen_check = db_select('rcts_citizen_registry', ['qcitizen_id' => 'eq.' . $qcitizen_id]);
        if (!$citizen_check['success'] || empty($citizen_check['data'])) {
            api_response(false, 'Citizen not found in RCTS registry', null, 401);
        }
        $citizen = ['data' => ['full_name' => $citizen_check['data'][0]['full_name'] ?? 'Unknown']];

        // Calculate total
        $total = array_sum(array_column($bills, 'total_amount_due'));

        // Create transaction
        $txn_id = 'TXN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

        $txn_data = [
            'transaction_id' => $txn_id,
            'bill_reference_no' => $bill_refs[0],
            'qcitizen_id' => $qcitizen_id,
            'gateway_provider' => $gateway,
            'amount_settled' => round($total, 2),
            'digital_hash' => md5($txn_id . $qcitizen_id . $total . date('c')),
            'transaction_status' => 'Success',
            'settlement_loop_sent' => false,
            'bank_reference_no' => json_encode($bill_refs)
        ];

        $insert = db_insert('rcts_payment_transaction', $txn_data);

        // Mark all bills as Paid
        foreach ($bill_refs as $bill_ref) {
            error_log("PAY_ALL: PATCH bill $bill_ref to 'Paid'");
            $patch_result = supabase_request('rcts_assessment_billing_hub', 'PATCH', 
                ['bill_reference_no' => 'eq.' . $bill_ref], 
                ['status' => 'Paid', 'updated_at' => date('c')], true);
            error_log("PAY_ALL: PATCH result: " . json_encode($patch_result));
        }

        // Issue e-OR
        $eor_number = 'EOR-' . date('Y') . '-' . strtoupper(substr(uniqid(), -8));
        $bill_types = array_unique(array_column($bills, 'bill_type'));
        $bill_type = count($bill_types) === 1 ? $bill_types[0] : 'Multiple';

        $eor_data = [
            'eor_number' => $eor_number,
            'transaction_id' => $txn_id,
            'qcitizen_id' => $qcitizen_id,
            'amount_paid' => round($total, 2),
            'bill_type' => $bill_type,
            'digital_signature_token' => md5($eor_number . $txn_id . date('c')),
            'blockchain_registry_id' => 'BC-' . strtoupper(md5($eor_number)),
            'sent_to_citizen' => false
        ];
        supabase_request('rcts_eor', 'POST', [], $eor_data, true);

        // Write to Treasury Ledger - group by type
        $gl_map = [
            'RPT' => ['code' => '1-01-001', 'fund' => 'GeneralFund'],
            'BusinessTax' => ['code' => '1-02-001', 'fund' => 'GeneralFund'],
            'MarketRental' => ['code' => '1-03-001', 'fund' => 'GeneralFund'],
            'TrafficFine' => ['code' => '1-04-001', 'fund' => 'GeneralFund'],
            'FacilityFee' => ['code' => '1-05-001', 'fund' => 'GeneralFund'],
        ];

        $bills_by_type = [];
        foreach ($bills as $bill) {
            $type = $bill['bill_type'];
            if (!isset($bills_by_type[$type])) $bills_by_type[$type] = 0;
            $bills_by_type[$type] += (float)$bill['total_amount_due'];
        }

        foreach ($bills_by_type as $type => $amount) {
            $gl = $gl_map[$type] ?? ['code' => '1-09-001', 'fund' => 'GeneralFund'];
            $ledger_data = [
                'transaction_id' => $txn_id,
                'entry_type' => 'Credit',
                'fund_id' => $gl['fund'],
                'gl_account_code' => $gl['code'],
                'revenue_category' => $type,
                'amount' => $amount,
                'remarks' => 'QC-PAY Consolidated Settlement — ' . $type . ' — eOR: ' . $eor_number
            ];
            supabase_request('rcts_treasury_ledger', 'POST', [], $ledger_data, true);
        }

        // Notify S1 (Citizen)
        $notify_body = json_encode([
            'qcitizen_id' => $qcitizen_id,
            'message' => 'Payment confirmed! Your consolidated payment of ₱' . number_format($total, 2) . ' has been settled. eOR No.: ' . $eor_number,
            'type' => 'payment_confirmation'
        ]);
        $notify_ctx = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $notify_body
        ]]);
        @file_get_contents(S1_API_URL . '?action=send_notification', false, $notify_ctx);

        // Trigger digital handshakes
        $outbound_results = [];
        foreach ($bills_by_type as $type => $amount) {
            switch ($type) {
                case 'BusinessTax':
                    $s2_ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => json_encode(['qcitizen_id' => $qcitizen_id, 'payment_status' => 'Paid', 'eor_number' => $eor_number, 'action' => 'release_permit'])]]);
                    @file_get_contents(S2_API_URL . '?action=payment_settlement', false, $s2_ctx);
                    $outbound_results['S2_Permits'] = 'sent';
                    
                    $s4_ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => json_encode(['qcitizen_id' => $qcitizen_id, 'clearance_type' => 'Sanitary', 'payment_status' => 'Paid', 'eor_number' => $eor_number])]]);
                    @file_get_contents(S4_API_URL . '?action=update_clearance_status', false, $s4_ctx);
                    $outbound_results['S4_Health'] = 'sent';
                    break;
                case 'RPT':
                    $s7_ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => json_encode(['qcitizen_id' => $qcitizen_id, 'tax_status' => 'Cleared', 'year' => date('Y'), 'eor_number' => $eor_number])]]);
                    @file_get_contents(S7_API_URL . '?action=update_tax_clearance', false, $s7_ctx);
                    $outbound_results['S7_Zoning'] = 'sent';
                    break;
                case 'TrafficFine':
                    $s9_ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => json_encode(['qcitizen_id' => $qcitizen_id, 'violation_status' => 'Settled', 'eor_number' => $eor_number, 'settlement_date' => date('Y-m-d')])]]);
                    @file_get_contents(S9_API_URL . '?action=resolve_violation', false, $s9_ctx);
                    $outbound_results['S9_Transport'] = 'sent';
                    break;
                case 'MarketRental':
                    $s10_ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => json_encode(['qcitizen_id' => $qcitizen_id, 'payment_status' => 'Paid', 'eor_number' => $eor_number, 'action' => 'confirm_lease'])]]);
                    @file_get_contents(S10_API_URL . '?action=update_lease_status', false, $s10_ctx);
                    $outbound_results['S10_Assets'] = 'sent';
                    break;
            }
        }

        api_response(true, 'CONSOLIDATED PAYMENT SUCCESSFUL! All bills paid in one transaction.', [
            'transaction_id' => $txn_id,
            'eor_number' => $eor_number,
            'amount_paid' => round($total, 2),
            'bills_paid' => count($bills),
            'bill_types' => array_keys($bills_by_type),
            'gateway' => $gateway,
            'citizen_name' => $citizen['data']['full_name'],
            'ledger_updated' => true,
            'notification_sent' => true,
            'digital_handshakes' => $outbound_results,
            'message' => 'All outstanding bills (RPT, Business Tax, Market Rental, Traffic Fines) have been settled in a single transaction!'
        ]);
        
        // ═══════════════════════════════════════════════════════════════════════
        // TO-BE: TRIGGER WEBHOOK EVENTS FOR CONSOLIDATED PAYMENT
        // ═══════════════════════════════════════════════════════════════════════
        $webhook_payload = [
            'transaction_id' => $txn_id,
            'eor_number' => $eor_number,
            'qcitizen_id' => $qcitizen_id,
            'amount_paid' => round($total, 2),
            'bill_types' => array_keys($bills_by_type),
            'timestamp' => date('c')
        ];
        
        // Store webhook events directly to database
        supabase_request('webhook_events', 'POST', [], [
            'event_type' => 'payment.completed',
            'event_data' => $webhook_payload
        ], true);
        
        supabase_request('webhook_events', 'POST', [], [
            'event_type' => 'receipt.issued',
            'event_data' => $webhook_payload
        ], true);
        break;

    // ── GET transaction status by transaction ID (for polling) ─────────────────
    case 'get_transaction_status':
        $txn_id = $_GET['transaction_id'] ?? '';
        if (!$txn_id) api_response(false, 'transaction_id required', null, 400);

        $result = supabase_request('rcts_payment_transaction', 'GET', [
            'transaction_id' => 'eq.' . $txn_id
        ], [], true);

        if (empty($result['data'])) {
            api_response(false, 'Transaction not found', null, 404);
        }

        $txn = $result['data'][0];

        // If this is a PayMongo transaction and still pending, try to fetch the
        // latest status from PayMongo (allows polling without relying on webhooks).
        if (
            $txn['transaction_status'] === 'Pending' &&
            ($txn['gateway_provider'] ?? '') === 'PayMongo' &&
            !empty($txn['bank_reference_no'])
        ) {
            $bankRefRaw = $txn['bank_reference_no'];
            $bankRefParsed = json_decode($bankRefRaw, true);

            $linkId = null;
            if (is_array($bankRefParsed)) {
                if (!empty($bankRefParsed['provider_reference'])) {
                    $linkId = $bankRefParsed['provider_reference'];
                }
            } else {
                $linkId = $bankRefRaw;
            }

            // If we don't have a link ID yet, bail out (must rely on webhook)
            if (empty($linkId)) {
                // Nothing to poll
            } else {
                $apiKey = PAYMENT_GATEWAYS['PayMongo']['api_key'] ?? getenv('PAYMONGO_API_KEY');

                if ($apiKey) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "https://api.paymongo.com/v1/links/" . urlencode($linkId));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Basic ' . base64_encode($apiKey . ':'),
                        'Content-Type: application/json',
                    ]);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    $resp = curl_exec($ch);
                    curl_close($ch);

                    $data = json_decode($resp, true);
                    $linkStatus = $data['data']['attributes']['status'] ?? null;

                    if (in_array(strtolower($linkStatus), ['paid', 'succeeded'], true)) {
                        // Mark the transaction complete (same as webhook)
                        $complete = complete_payment_transaction($txn_id, $linkId, $data);
                        if ($complete['success']) {
                            // Refresh txn info
                            $result = supabase_request('rcts_payment_transaction', 'GET', [
                                'transaction_id' => 'eq.' . $txn_id
                            ], [], true);
                            $txn = $result['data'][0] ?? $txn;
                        }
                    }
                }
            }
        }

        api_response($result['success'], 'Transaction status retrieved', [
            'transaction' => $txn
        ]);
        break;

    // ── POLL PayMongo payment status (for webhook-free verification) ──────
    case 'poll_payment_status':
        $txn_id = $_GET['transaction_id'] ?? '';
        if (!$txn_id) api_response(false, 'transaction_id required', null, 400);

        // Get transaction details
        $txn_result = supabase_request('rcts_payment_transaction', 'GET', [
            'transaction_id' => 'eq.' . $txn_id
        ], [], true);

        if (empty($txn_result['data'])) {
            api_response(false, 'Transaction not found', null, 404);
        }

        $txn = $txn_result['data'][0];

        // Only poll if it's a PayMongo transaction and still pending
        if ($txn['gateway_provider'] !== 'PayMongo' || $txn['transaction_status'] !== 'Pending') {
            api_response(true, 'Transaction status', [
                'status' => $txn['transaction_status'],
                'transaction_id' => $txn_id
            ]);
        }

        // Extract provider reference from bank_reference_no
        $bankRefRaw = $txn['bank_reference_no'] ?? '';
        $bankRefParsed = json_decode($bankRefRaw, true);
        $providerRef = null;

        if (is_array($bankRefParsed) && isset($bankRefParsed['provider_reference'])) {
            $providerRef = $bankRefParsed['provider_reference'];
        } elseif (is_string($bankRefRaw) && !empty($bankRefRaw)) {
            $providerRef = $bankRefRaw;
        }

        if (!$providerRef) {
            api_response(false, 'No provider reference found for polling', null, 400);
        }

        // Initialize PayMongo gateway and poll
        require_once __DIR__ . '/../lib/gateways/PayMongoGateway.php';
        $gateway = new PayMongoGateway(PAYMENT_GATEWAYS['PayMongo'] ?? []);

        $pollResult = $gateway->pollPaymentStatus($providerRef);

        if (!$pollResult['status'] || $pollResult['status'] === 'Failed') {
            api_response(false, $pollResult['error'] ?? 'Polling failed', null, 500);
        }

        // If payment succeeded, complete the transaction
        if ($pollResult['status'] === 'Success') {
            $complete = complete_payment_transaction($txn_id, $providerRef, $pollResult['metadata'] ?? []);
            if (!$complete['success']) {
                api_response(false, 'Failed to complete transaction: ' . ($complete['message'] ?? 'Unknown error'), null, 500);
            }
        }

        api_response(true, 'Payment status polled', [
            'status' => $pollResult['status'],
            'transaction_id' => $txn_id,
            'provider_reference' => $providerRef,
            'raw_status' => $pollResult['raw_status'] ?? null
        ]);
        break;

    // ── GET transaction history for a citizen ────────────────────────────
    case 'get_transaction_history':
        $id = $_GET['qcitizen_id'] ?? '';
        if (!$id) api_response(false, 'qcitizen_id required', null, 400);

        $result = supabase_request('rcts_payment_transaction', 'GET', [
            'qcitizen_id' => 'eq.' . $id,
            'transaction_status' => 'eq.Success',
            'order' => 'transaction_timestamp.desc'
        ], [], true);

        api_response($result['success'], 'Transaction history retrieved', [
            'transactions' => $result['data'],
            'count'        => count($result['data'] ?? [])
        ]);
        break;

    // ── GET receipt/eOR for a transaction ───────────────────────────────
    case 'get_receipt':
        $txn_id = $_GET['transaction_id'] ?? '';
        if (!$txn_id) api_response(false, 'transaction_id required', null, 400);

        // Simple query: get eOR by transaction_id
        $eor = supabase_request('rcts_eor', 'GET', 
            ['transaction_id' => 'eq.' . $txn_id], 
            [], true);
        
        // If eOR doesn't exist, check if transaction exists and create eOR
        if (empty($eor['data'])) {
            $txn = supabase_request('rcts_payment_transaction', 'GET', 
                ['transaction_id' => 'eq.' . $txn_id], 
                [], true);
            
            if (!empty($txn['data'])) {
                // Transaction exists but eOR missing - create eOR now
                $t = $txn['data'][0];
                $eor_number = 'EOR-' . date('Y') . '-' . strtoupper(substr(uniqid(), -8));
                
                $new_eor = [
                    'eor_number'              => $eor_number,
                    'transaction_id'          => $txn_id,
                    'qcitizen_id'             => $t['qcitizen_id'],
                    'amount_paid'             => $t['amount_settled'],
                    'bill_type'               => 'RPT',
                    'digital_signature_token' => md5($eor_number . $txn_id . date('c')),
                    'blockchain_registry_id'  => 'BC-' . strtoupper(md5($eor_number)),
                    'sent_to_citizen'         => false
                ];
                
                $eor_insert = supabase_request('rcts_eor', 'POST', [], $new_eor, true);
                error_log("eOR insert result: " . json_encode($eor_insert));
                
                // Check if insert succeeded
                if ($eor_insert['success']) {
                    // Re-fetch the created eOR
                    $eor = supabase_request('rcts_eor', 'GET', 
                        ['transaction_id' => 'eq.' . $txn_id], 
                        [], true);
                } else {
                    // Return error with details
                    api_response(false, 'Failed to create e-OR. RLS may be blocking insert.', 
                        ['txn_id' => $txn_id, 'eor_insert_error' => $eor_insert], 500);
                }
            }
        }
        
        if (empty($eor['data'])) {
            api_response(false, 'Receipt not found for transaction: ' . $txn_id, 
                ['txn_id' => $txn_id, 'eor_result' => $eor], 404);
        }

        // Get the transaction details
        $txn = supabase_request('rcts_payment_transaction', 'GET', 
            ['transaction_id' => 'eq.' . $txn_id], 
            [], true);

        api_response(true, 'Receipt retrieved', [
            'eor'         => $eor['data'][0],
            'transaction' => $txn['data'][0] ?? null
        ]);
        break;

    // ── POST initiate checkout (creates pending transaction) ─────────────
    case 'checkout':
        $qcitizen_id  = $body['qcitizen_id']        ?? '';
        $bill_refs    = $body['bill_reference_nos']  ?? [];
        $gateway      = $body['gateway_provider']    ?? 'GCash';
        $s7_properties = $body['s7_properties']      ?? []; // TO-BE: S7 properties from GIS

        error_log("=== CHECKOUT START ===");
        error_log("qcitizen_id: $qcitizen_id");
        error_log("bill_refs received: " . json_encode($bill_refs));
        error_log("s7_properties count: " . count($s7_properties));
        error_log("s7_properties detail: " . json_encode($s7_properties));
        error_log("===================");

        if (!$qcitizen_id || empty($bill_refs)) {
            audit_log($qcitizen_id ?: 'unknown', 'checkout_failed', ['reason' => 'missing qcitizen_id or bill_refs']);
            api_response(false, 'qcitizen_id and bill_reference_nos[] required', null, 400);
        }

        // Step 1: Verify identity (use RCTS local registry, not S1)
        $citizen_check = db_select('rcts_citizen_registry', ['qcitizen_id' => 'eq.' . $qcitizen_id]);
        if (!$citizen_check['success'] || empty($citizen_check['data'])) {
            audit_log($qcitizen_id, 'checkout_failed', ['reason' => 'citizen not found in registry']);
            api_response(false, 'Citizen not found in RCTS registry', null, 401);
        }
        $citizen = ['data' => ['full_name' => $citizen_check['data'][0]['full_name'] ?? 'Unknown']];

        // TO-BE: Insert S7 properties into billing hub if provided
        // Note: We now handle this more robustly below in the bill creation loop
        if (!empty($s7_properties)) {
            error_log("S7 properties received in checkout: " . count($s7_properties) . " items");
        } else {
            error_log("No S7 properties provided to checkout");
        }

        // Step 2: Compute total from selected bills
        // TO-BE FIX: Query the billing hub directly instead of using view
        // because view might not include newly inserted S7 bills
        $total = 0;
        $bill_details = [];
        
        // First, ensure ALL selected bill references are in the billing hub
        // This is critical for S7 properties which may not be in DB yet
        foreach ($bill_refs as $ref) {
            $existing = supabase_request('rcts_assessment_billing_hub', 'GET', 
                ['bill_reference_no' => 'eq.' . $ref], [], true);
            
            if (empty($existing['data'])) {
                // Bill doesn't exist - try to find in S7 properties and create it
                error_log("Bill $ref not found in hub, checking S7 properties...");
                
                // Look for this ref in the s7_properties array
                $s7_prop = null;
                foreach ($s7_properties as $prop) {
                    if (($prop['tdn_number'] ?? '') === $ref) {
                        $s7_prop = $prop;
                        break;
                    }
                }
                
                if ($s7_prop) {
                    // Create the bill from S7 property
                    $total_rpt = floatval($s7_prop['total_annual_tax'] ?? ($s7_prop['annual_rpt_due'] ?? 0) + ($s7_prop['annual_sef_due'] ?? 0));
                    $annual_rpt = floatval($s7_prop['annual_rpt_due'] ?? 0);
                    
                    $bill_data = [
                        'bill_reference_no' => $ref,
                        'qcitizen_id' => $qcitizen_id,
                        'bill_type' => 'RPT',
                        'originating_dept_id' => '8',
                        'asset_id' => $ref,
                        'base_amount' => $annual_rpt,
                        'discount_rate' => 0,
                        'penalty_rate' => 0,
                        'total_amount_due' => $total_rpt,
                        'status' => 'Pending',
                        'tax_year' => $s7_prop['tax_year'] ?? date('Y'),
                        'verification_ref_id' => 'S7-GIS-' . $ref
                    ];
                    
                    $insert_result = supabase_request('rcts_assessment_billing_hub', 'POST', [], $bill_data, true);
                    error_log("Created S7 bill for $ref: " . json_encode($insert_result));
                }
            }
        }
        
        // Now fetch all pending bills for this citizen
        $all_bills = supabase_request('rcts_assessment_billing_hub', 'GET', [
            'qcitizen_id' => 'eq.' . $qcitizen_id,
            'status' => 'eq.Pending'
        ], [], true);
        
        if (!($all_bills['success'] ?? false)) {
            // Fallback to db_select
            $all_bills = db_select('rcts_assessment_billing_hub', [
                'qcitizen_id' => 'eq.' . $qcitizen_id,
                'status' => 'eq.Pending'
            ]);
        }
        
        if (!($all_bills['success'] ?? false)) {
            api_response(false, 'Failed to retrieve bills for citizen', null, 500);
        }
        
        // Create a lookup map for faster searching
        $bill_lookup = [];
        foreach ($all_bills['data'] ?? [] as $bill) {
            $bill_lookup[$bill['bill_reference_no']] = $bill;
        }
        
        error_log("All pending bills in hub: " . json_encode(array_keys($bill_lookup)));
        error_log("Looking for refs: " . json_encode($bill_refs));
        
        foreach ($bill_refs as $ref) {
            if (isset($bill_lookup[$ref])) {
                $bill_data = $bill_lookup[$ref];
                $total += (float)$bill_data['total_amount_due'];
                $bill_details[] = $bill_data;
                error_log("Checkout: Added bill $ref to checkout, total now: $total");
            } else {
                error_log("Bill $ref not found in citizen's pending bills");
            }
        }

        if (empty($bill_details)) {
            error_log("Checkout: No valid bills found. Debug info:");
            error_log("Requested refs: " . json_encode($bill_refs));
            error_log("Citizen ID: $qcitizen_id");
            audit_log($qcitizen_id, 'checkout_failed', ['reason' => 'no valid bills found', 'requested_refs' => $bill_refs]);
            // LAST RESORT: If S7 properties were passed but bills still not found,
            // force-create them now
            if (!empty($s7_properties)) {
                error_log("FORCE CREATING S7 BILLS - LAST RESORT");
                foreach ($s7_properties as $prop) {
                    $bill_ref = $prop['tdn_number'] ?? '';
                    if (empty($bill_ref)) continue;
                    
                    $total_rpt = floatval($prop['total_annual_tax'] ?? ($prop['annual_rpt_due'] ?? 0) + ($prop['annual_sef_due'] ?? 0));
                    $annual_rpt = floatval($prop['annual_rpt_due'] ?? 0);
                    
                    $bill_data = [
                        'bill_reference_no' => $bill_ref,
                        'qcitizen_id' => $qcitizen_id,
                        'bill_type' => 'RPT',
                        'originating_dept_id' => '8',
                        'asset_id' => $bill_ref,
                        'base_amount' => $annual_rpt,
                        'discount_rate' => 0,
                        'penalty_rate' => 0,
                        'total_amount_due' => $total_rpt,
                        'status' => 'Pending',
                        'tax_year' => $prop['tax_year'] ?? date('Y'),
                        'verification_ref_id' => 'S7-GIS-' . $bill_ref
                    ];
                    
                    $insert_result = supabase_request('rcts_assessment_billing_hub', 'POST', [], $bill_data, true);
                    error_log("FORCE CREATE S7 bill $bill_ref: " . json_encode($insert_result));
                }
                
                // Re-fetch all pending bills
                $all_bills = supabase_request('rcts_assessment_billing_hub', 'GET', [
                    'qcitizen_id' => 'eq.' . $qcitizen_id,
                    'status' => 'eq.Pending'
                ], [], true);
                
                $bill_lookup = [];
                foreach ($all_bills['data'] ?? [] as $bill) {
                    $bill_lookup[$bill['bill_reference_no']] = $bill;
                }
                
                // Try again to find bills
                foreach ($bill_refs as $ref) {
                    if (isset($bill_lookup[$ref])) {
                        $bill_data = $bill_lookup[$ref];
                        $total += (float)$bill_data['total_amount_due'];
                        $bill_details[] = $bill_data;
                        error_log("After force create: Found bill $ref, total now: $total");
                    }
                }
            }
        }
        
        // Final check
        if (empty($bill_details)) {
            api_response(false, 'No valid pending bills found for these reference numbers', null, 404);
        }

        // Step 3: Create a Pending transaction record
        $txn_id = 'TXN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

        // For multi-bill checkout we use the first bill ref as anchor
        $primary_bill_ref = $bill_refs[0];

        $txn_data = [
            'transaction_id'      => $txn_id,
            'bill_reference_no'   => $primary_bill_ref,
            'qcitizen_id'         => $qcitizen_id,
            'gateway_provider'    => $gateway,
            'amount_settled'      => round($total, 2),
            'digital_hash'        => md5($txn_id . $qcitizen_id . $total . date('c')),
            'transaction_status'  => 'Pending',
            'settlement_loop_sent'=> false,
            'bank_reference_no'   => json_encode($bill_refs)  // Temporarily store bill references here
        ];

        $insert = db_insert('rcts_payment_transaction', $txn_data);
        
        // Debug: Log the insert result
        error_log("Checkout insert result: " . json_encode($insert));
        
        if (!$insert['success']) {
            // Return detailed error for debugging
            api_response(false, 'Checkout failed: ' . json_encode($insert), $insert, 500);
        }

        // Normalize provider name (case-insensitive) and store canonical name
        $gatewayProvider = PaymentGatewayFactory::normalizeProvider($gateway);
        $txn_data['gateway_provider'] = $gatewayProvider;

        // Create payment session with the selected gateway
        $paymentGateway = PaymentGatewayFactory::create($gatewayProvider);
        $gatewaySession = $paymentGateway->createPayment($txn_data);

        // If the gateway provides a reference (e.g., PayMongo link ID), store it for polling.
        if (!empty($gatewaySession['provider_reference'])) {
            // Preserve bill list in bank_reference_no while also keeping provider reference
            $bankRefs = json_encode(['bills' => $bill_refs, 'provider_reference' => $gatewaySession['provider_reference']]);
            db_update('rcts_payment_transaction', ['transaction_id' => 'eq.' . $txn_id], ['bank_reference_no' => $bankRefs]);
        }

        audit_log($qcitizen_id, 'checkout', ['bill_refs' => $bill_refs, 'total' => $total]);
        api_response(true, 'Checkout initiated. Proceed to payment confirmation.', [
            'transaction_id' => $txn_id,
            'amount_due'     => round($total, 2),
            'gateway'        => $gatewayProvider,
            'bills_included' => count($bill_details),
            'citizen_name'   => $citizen['data']['full_name'],
            'gateway_session' => $gatewaySession,
            'next_step'      => 'POST ?action=execute with transaction_id to simulate bank confirmation (or use webhook for real gateway)',
        ]);
        break;

    // ── POST execute payment (simulates bank/wallet confirmation) ────────
    case 'execute':
        $txn_id   = $body['transaction_id'] ?? $_GET['transaction_id'] ?? '';
        $bank_ref = $body['bank_reference_no'] ?? 'MOCK-BANK-' . strtoupper(uniqid());
        if (!$txn_id) api_response(false, 'transaction_id required', null, 400);

        $result = complete_payment_transaction($txn_id, $bank_ref, $body);
        if (!($result['success'] ?? false)) {
            audit_log('system', 'execute_failed', ['txn_id' => $txn_id, 'reason' => $result['message'] ?? 'Payment processing failed']);
            api_response(false, $result['message'] ?? 'Payment processing failed', $result, 500);
        }
        audit_log($result['transaction_id'] ?? $txn_id, 'execute', $result);

        // If accessed via GET (e.g., mock gateway redirect), redirect to receipt page
        if (empty($rawBody)) {
            header('Location: ../../pages/citizen/receipt.html?txn=' . urlencode($txn_id));
            exit;
        }

        api_response(true, 'Payment executed', $result);
        break;

        break;

    case 'webhook':
        // Webhook callback from real payment providers (GCash/Maya/Bank)
        // Example: provider posts callback to /api/endpoints/payment.php?action=webhook
        // with JSON payload containing transaction_id and status.
        $provider = $body['gateway_provider'] ?? $_GET['gateway_provider'] ?? PAYMENT_GATEWAY_DEFAULT;

        $gatewayProvider = PaymentGatewayFactory::normalizeProvider($provider);
        $gateway = PaymentGatewayFactory::create($gatewayProvider);
        $webhookResult = $gateway->handleWebhook($body, getallheaders(), $rawBody);

        if (empty($webhookResult['transaction_id'])) {
            api_response(false, 'Webhook missing transaction_id', $webhookResult, 400);
        }

        // If the gateway tells us the payment succeeded, finalize the payment.
        if (($webhookResult['status'] ?? '') === 'Success') {
            $result = complete_payment_transaction($webhookResult['transaction_id'], $webhookResult['provider_reference'] ?? null, $webhookResult);
            if (!($result['success'] ?? false)) {
                api_response(false, 'Webhook processing failed', $result, 500);
            }
            api_response(true, 'Webhook handled, payment completed', $result);
        }

        api_response(true, 'Webhook received', $webhookResult);
        break;

    default:
        api_response(false, 'Unknown action', ['available' => ['get_pending_bills','get_transaction_history','get_receipt','checkout','execute','webhook']], 400);
}