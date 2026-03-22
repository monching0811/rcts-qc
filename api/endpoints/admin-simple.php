<?php
// Simple admin API with file-based audit logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


require_once __DIR__ . '/../config/supabase.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';


// Helper function to add log entry to rcts_audit_log (Supabase)
function addLog($event, $user = 'system') {
    $entry = [
        'actor' => $user,
        'event' => $event,
        'details' => null,
    ];
    supabase_request('rcts_audit_log', 'POST', [], $entry, true);
}

switch ($action) {
    case 'list_users':
        require_once __DIR__ . '/../../includes/db.php';
        // Fetch all internal users (staff)
        $staff = db('rcts_internal_users');
        $users = [];
        if ($staff['success'] && !empty($staff['data'])) {
            foreach ($staff['data'] as $u) {
                $users[] = [
                    'user_id' => $u['user_id'],
                    'qcitizen_id' => $u['user_id'],
                    'full_name' => $u['full_name'],
                    'email' => $u['email'],
                    'role' => $u['role'],
                    'status' => $u['status'],
                    'created_at' => $u['created_at'],
                    'updated_at' => $u['updated_at']
                ];
            }
        }
        // Optionally, fetch citizens if needed (not included in dashboard count)
        echo json_encode(['success' => true, 'users' => $users]);
        break;
        
    case 'add_user':
        require_once __DIR__ . '/../../includes/db.php';
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? 'citizen';
        $password = $_POST['password'] ?? '';

        if (!$name || !$email || !$role || !$password) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            break;
        }

        // Only add to internal_users if staff
        $staffRoles = ['treasurer','revenue_officer','auditor','admin'];
        if (in_array($role, $staffRoles)) {
            // Check if email already exists
            $exists = db('rcts_internal_users', ['email' => 'eq.' . strtolower($email)]);
            if (!empty($exists['data'])) {
                echo json_encode(['success' => false, 'message' => 'Email already exists in staff accounts']);
                break;
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = db_create('rcts_internal_users', [
                'full_name' => $name,
                'email' => strtolower($email),
                'password_hash' => $hash,
                'role' => $role,
                'status' => 'active'
            ]);
            if (!$ins['success']) {
                echo json_encode(['success' => false, 'message' => 'Failed to create staff account']);
                break;
            }
        }

        addLog("Created new user: $email ($role)", 'admin');
        echo json_encode(['success' => true, 'message' => 'User created successfully']);
        break;
        
    case 'delete_user':
        require_once __DIR__ . '/../../includes/db.php';
        $id = $_POST['qcitizen_id'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            break;
        }
        $result = db_delete('rcts_internal_users', ['user_id' => 'eq.' . $id]);
        if (!$result['success']) {
            echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
            break;
        }
        addLog("Deleted user: $id", 'admin');
        echo json_encode(['success' => true]);
        break;
        
    case 'reset_password':
        require_once __DIR__ . '/../../includes/db.php';
        $id = $_POST['qcitizen_id'] ?? '';
        $password = $_POST['password'] ?? '';
        if (!$id || !$password) {
            echo json_encode(['success' => false, 'message' => 'User ID and password required']);
            break;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $result = db_patch('rcts_internal_users', ['user_id' => 'eq.' . $id], ['password_hash' => $hash]);
        if (!$result['success']) {
            echo json_encode(['success' => false, 'message' => 'Failed to reset password']);
            break;
        }
        addLog("Password reset for user: $id", 'admin');
        echo json_encode(['success' => true]);
        break;
        
    case 'update_user':
        require_once __DIR__ . '/../../includes/db.php';
        $id = $_POST['qcitizen_id'] ?? '';
        $name = $_POST['full_name'] ?? '';
        $role = $_POST['role'] ?? '';
        $status = $_POST['status'] ?? '';

        // Debug: Log received data
        error_log('update_user called with id=' . $id . ', name=' . $name . ', role=' . $role . ', status=' . $status);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            break;
        }

        // First, check if user exists
        $userCheck = db_select('rcts_internal_users', ['user_id' => 'eq.' . $id]);
        error_log('User check result: ' . json_encode($userCheck));
        
        if (!$userCheck['success']) {
            echo json_encode(['success' => false, 'message' => 'Database error checking user', 'debug' => $userCheck]);
            break;
        }
        
        if (empty($userCheck['data'])) {
            echo json_encode(['success' => false, 'message' => 'User not found in database', 'debug' => ['searched_id' => $id]]);
            break;
        }

        $patchData = [];
        $changes = [];
        if ($name) {
            $patchData['full_name'] = $name;
            $changes[] = "full_name: $name";
        }
        if ($role) {
            $patchData['role'] = $role;
            $changes[] = "role: $role";
        }
        if ($status) {
            $patchData['status'] = $status;
            $changes[] = "status: $status";
        }

        if (!empty($patchData)) {
            $result = db_patch('rcts_internal_users', ['user_id' => 'eq.' . $id], $patchData);
            // Log result for debugging
            error_log('update_user db_patch result: ' . json_encode($result));
            if (!$result['success']) {
                echo json_encode(['success' => false, 'message' => 'Failed to update user', 'debug' => $result]);
                break;
            }
        }

        addLog("Updated user $id: " . implode(", ", $changes), 'admin');
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        break;
        
    case 'save_settings':
        require_once __DIR__ . '/../../includes/db.php';
        
        // Get all POST data
        $updates = $_POST;
        unset($updates['action']);
        
        if (empty($updates)) {
            echo json_encode(['success' => false, 'message' => 'No settings provided']);
            break;
        }
        
        // Check if table exists
        $tableCheck = supabase_request('rcts_system_settings', 'GET', ['select' => 'setting_key', 'limit' => 1], [], true);
        
        if (!$tableCheck['success']) {
            // Table doesn't exist - create it
            $createResult = supabase_request('rcts_system_settings', 'POST', [], [
                'setting_key' => '_init_',
                'setting_value' => 'init',
                'category' => 'System',
                'description' => 'Table initialization'
            ], true);
            
            // Delete the init row
            if ($createResult['success']) {
                supabase_request('rcts_system_settings', 'DELETE', ['setting_key' => 'eq._init_'], [], true);
            }
        }
        
        // Update each setting
        $saved = [];
        $errors = [];
        
        foreach ($updates as $key => $value) {
            // Check if setting exists
            $check = supabase_request('rcts_system_settings', 'GET', [
                'setting_key' => 'eq.' . $key
            ], [], true);
            
            if ($check['success'] && !empty($check['data'])) {
                // Update existing
                $result = supabase_request('rcts_system_settings', 'PATCH', [
                    'setting_key' => 'eq.' . $key
                ], [
                    'setting_value' => $value,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => 'admin'
                ], true);
            } else {
                // Insert new
                $result = supabase_request('rcts_system_settings', 'POST', [], [
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'category' => 'Custom',
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => 'admin'
                ], true);
            }
            
            if ($result['success']) {
                $saved[] = $key;
            } else {
                $errors[] = $key;
            }
        }
        
        addLog("System settings updated: " . implode(', ', $saved), 'admin');
        
        if (empty($errors)) {
            echo json_encode(['success' => true, 'message' => 'Settings saved successfully', 'saved' => $saved]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Some settings failed to save', 'saved' => $saved, 'errors' => $errors]);
        }
        break;
        
    case 'list_settings':
        require_once __DIR__ . '/../../includes/db.php';
        
        // First, try to create the settings table if it doesn't exist
        $tableCheck = supabase_request('rcts_system_settings', 'GET', ['select' => 'setting_key', 'limit' => 1], [], true);
        
        if (!$tableCheck['success']) {
            // Table doesn't exist, use hardcoded defaults
            echo json_encode(['success' => true, 'settings' => [
                ['key' => 'RPT_BASIC_RATE', 'value' => '0.025', 'category' => 'RPT', 'description' => 'Real Property Tax Basic Rate (2.5% of Assessed Value)'],
                ['key' => 'RPT_SEF_RATE', 'value' => '0.01', 'category' => 'RPT', 'description' => 'Special Education Fund Rate (1% of Assessed Value)'],
                ['key' => 'BIZ_TAX_RATE_RETAIL', 'value' => '0.012', 'category' => 'Business Tax', 'description' => 'Business Tax Rate for Retail (1.2%)'],
                ['key' => 'BIZ_TAX_RATE_WHOLESALE', 'value' => '0.015', 'category' => 'Business Tax', 'description' => 'Business Tax Rate for Wholesale (1.5%)'],
                ['key' => 'BIZ_TAX_RATE_MFR', 'value' => '0.02', 'category' => 'Business Tax', 'description' => 'Business Tax Rate for Manufacturers (2%)'],
                ['key' => 'BIZ_TAX_RATE_IMPORTER', 'value' => '0.025', 'category' => 'Business Tax', 'description' => 'Business Tax Rate for Importers (2.5%)'],
                ['key' => 'PENALTY_RATE_MONTHLY', 'value' => '0.02', 'category' => 'Penalty', 'description' => 'Monthly Penalty Rate for late payments (2% per month)'],
                ['key' => 'PENALTY_MAX_MONTHS', 'value' => '36', 'category' => 'Penalty', 'description' => 'Maximum months for penalty accumulation'],
                ['key' => 'RPT_DUE_DATE_Q1', 'value' => '03-31', 'category' => 'Due Dates', 'description' => 'RPT Q1 Due Date (March 31)'],
                ['key' => 'RPT_DUE_DATE_Q2', 'value' => '06-30', 'category' => 'Due Dates', 'description' => 'RPT Q2 Due Date (June 30)'],
                ['key' => 'RPT_DUE_DATE_Q3', 'value' => '09-30', 'category' => 'Due Dates', 'description' => 'RPT Q3 Due Date (September 30)'],
                ['key' => 'RPT_DUE_DATE_Q4', 'value' => '12-31', 'category' => 'Due Dates', 'description' => 'RPT Q4 Due Date (December 31)'],
                ['key' => 'BIZ_TAX_DUE_DATE', 'value' => '01-20', 'category' => 'Due Dates', 'description' => 'Business Tax Annual Due Date (January 20)'],
                ['key' => 'EARLY_PAYMENT_DISCOUNT_RATE', 'value' => '0.10', 'category' => 'Discount', 'description' => 'Early Payment Discount Rate (10% if paid within first month)'],
                ['key' => 'EARLY_PAYMENT_DISCOUNT_DEADLINE', 'value' => '01-31', 'category' => 'Discount', 'description' => 'Early Payment Discount Deadline (January 31)'],
                ['key' => 'MARKET_STALL_DAILY_RATE', 'value' => '50.00', 'category' => 'Market', 'description' => 'Daily Market Stall Rate (PHP)'],
                ['key' => 'MARKET_STALL_MONTHLY_RATE', 'value' => '1200.00', 'category' => 'Market', 'description' => 'Monthly Market Stall Rate (PHP)'],
                ['key' => 'TRAFFIC_VIOLATION_BASE_FINE', 'value' => '500.00', 'category' => 'Traffic', 'description' => 'Base Traffic Violation Fine (PHP)'],
                ['key' => 'TRAFFIC_VIOLATION_SURCHARGE', 'value' => '0.10', 'category' => 'Traffic', 'description' => 'Traffic Violation Surcharge (10% per month late)'],
            ], 'source' => 'defaults']);
            break;
        }
        
        // Table exists, read from database
        $result = supabase_request('rcts_system_settings', 'GET', [
            'select' => 'setting_key,setting_value,category,description,updated_at',
            'order' => 'category,setting_key'
        ], [], true);
        
        if ($result['success'] && !empty($result['data'])) {
            $settings = [];
            foreach ($result['data'] as $row) {
                $settings[] = [
                    'key' => $row['setting_key'],
                    'value' => $row['setting_value'],
                    'category' => $row['category'],
                    'description' => $row['description']
                ];
            }
            echo json_encode(['success' => true, 'settings' => $settings, 'source' => 'database']);
        } else {
            // Database query failed, use defaults
            echo json_encode(['success' => true, 'settings' => [], 'source' => 'defaults']);
        }
        break;
        
    case 'list_logs':
        // Fetch from rcts_audit_log (last 1000 entries, admin only)
        $result = supabase_request('rcts_audit_log', 'GET', [
            'select' => 'created_at,actor,event,details',
            'order' => 'created_at.desc',
            'limit' => 1000
        ], [], true);
        $logs = [];
        if ($result['success'] && !empty($result['data'])) {
            foreach ($result['data'] as $row) {
                $logs[] = [
                    'ts' => $row['created_at'],
                    'event' => $row['event'],
                    'user' => $row['actor'],
                    'details' => $row['details']
                ];
            }
        }
        echo json_encode(['success' => true, 'logs' => $logs]);
        break;
        
    case 'add_log':
        // Allow other pages to add log entries
        $event = $_POST['event'] ?? '';
        $user = $_POST['user'] ?? 'system';
        if ($event) {
            addLog($event, $user);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Event required']);
        }
        break;
        
    case 'rule_log':
        echo json_encode(['success' => true, 'log' => [
            ['ts' => date('Y-m-d H:i:s', strtotime('-2 days')), 'user' => 'admin@qc.gov.ph', 'change' => 'Changed RPT_BASIC_RATE from 0.02 to 0.025'],
            ['ts' => date('Y-m-d H:i:s', strtotime('-1 days')), 'user' => 'treasurer@qc.gov.ph', 'change' => 'Changed BIZ_TAX_RATE_RETAIL from 0.01 to 0.012'],
        ]]);
        break;
        
    case 'list_apikeys':
        echo json_encode(['success' => true, 'keys' => [
            ['subsystem' => 'S1', 'key' => 'S1-RCTS-KEY-2026'],
            ['subsystem' => 'S2', 'key' => 'S2-RCTS-KEY-2026'],
            ['subsystem' => 'S3', 'key' => 'S3-RCTS-KEY-2026'],
            ['subsystem' => 'S4', 'key' => 'S4-RCTS-KEY-2026'],
            ['subsystem' => 'S5', 'key' => 'S5-RCTS-KEY-2026'],
            ['subsystem' => 'S6', 'key' => 'S6-RCTS-KEY-2026'],
            ['subsystem' => 'S7', 'key' => 'S7-RCTS-KEY-2026'],
            ['subsystem' => 'S8', 'key' => 'S8-RCTS-KEY-2026'],
            ['subsystem' => 'S9', 'key' => 'S9-RCTS-KEY-2026'],
            ['subsystem' => 'S10', 'key' => 'S10-RCTS-KEY-2026'],
        ]]);
        break;
        
    case 'regen_apikey':
        $subsystem = $_POST['subsystem'] ?? '';
        if (!$subsystem) {
            echo json_encode(['success' => false, 'message' => 'Invalid subsystem.']);
            break;
        }
        $newkey = strtoupper($subsystem) . '-RCTS-KEY-' . rand(1000,9999) . '-' . date('Y');
        addLog("Regenerated API key for subsystem: $subsystem", 'admin');
        echo json_encode(['success' => true, 'key' => $newkey]);
        break;
        
    case 'analytics':
        // Fetch real analytics data from Supabase
        $period = $_GET['period'] ?? 30;
        $all_time = ($period === 'all' || $period === '0' || intval($period) > 1000);
        if ($all_time) {
            // No date filter, get all transactions
            $paymentsResult = supabase_request('rcts_payment_transaction', 'GET', [
                'select' => 'amount_settled,transaction_timestamp,transaction_status,bill_reference_no',
                'transaction_status' => 'eq.Success'
            ], [], true);
            $startDate = '1970-01-01';
        } else {
            $startDate = date('Y-m-d', strtotime("-{$period} days"));
            $paymentsResult = supabase_request('rcts_payment_transaction', 'GET', [
                'select' => 'amount_settled,transaction_timestamp,transaction_status,bill_reference_no',
                'transaction_status' => 'eq.Success',
                'transaction_timestamp' => 'gte.' . $startDate
            ], [], true);
        }
        
        $totalRevenue = 0;
        $totalTransactions = 0;
        $dailyRevenue = [];
        $byModule = [
            'Real Property Tax' => 0,
            'Business Tax' => 0,
            'Market Stall' => 0,
            'Traffic Fines' => 0,
            'Other' => 0
        ];
        
        if ($paymentsResult['success'] && !empty($paymentsResult['data'])) {
            $totalTransactions = count($paymentsResult['data']);
            // Gather all bill_reference_no from payments
            $billRefs = [];
            foreach ($paymentsResult['data'] as $p) {
                $amount = floatval($p['amount_settled'] ?? 0);
                $totalRevenue += $amount;
                // Track daily revenue
                $day = date('Y-m-d', strtotime($p['transaction_timestamp']));
                if (!isset($dailyRevenue[$day])) {
                    $dailyRevenue[$day] = 0;
                }
                $dailyRevenue[$day] += $amount;
                if (!empty($p['bill_reference_no'])) {
                    $billRefs[] = $p['bill_reference_no'];
                }
            }
            // Fetch all bill types in one query
            $billTypeMap = [];
            if (!empty($billRefs)) {
                // Chunk billRefs to avoid URL length issues (Supabase/PostgREST limit)
                $chunks = array_chunk($billRefs, 100);
                foreach ($chunks as $chunk) {
                    $csv = '"' . implode('","', $chunk) . '"';
                    $billResult = supabase_request('rcts_assessment_billing_hub', 'GET', [
                        'bill_reference_no' => 'in.(' . $csv . ')',
                        'select' => 'bill_reference_no,bill_type'
                    ], [], true);
                    if ($billResult['success'] && !empty($billResult['data'])) {
                        foreach ($billResult['data'] as $row) {
                            $billTypeMap[$row['bill_reference_no']] = $row['bill_type'] ?? 'Other';
                        }
                    }
                }
            }
            // Aggregate by module using the map
            foreach ($paymentsResult['data'] as $p) {
                $billRef = $p['bill_reference_no'] ?? '';
                $amount = floatval($p['amount_settled'] ?? 0);
                $billType = $billTypeMap[$billRef] ?? 'Other';
                switch ($billType) {
                    case 'RPT':
                        $byModule['Real Property Tax'] += $amount;
                        break;
                    case 'BusinessTax':
                        $byModule['Business Tax'] += $amount;
                        break;
                    case 'MarketRental':
                        $byModule['Market Stall'] += $amount;
                        break;
                    case 'TrafficFine':
                        $byModule['Traffic Fines'] += $amount;
                        break;
                    default:
                        $byModule['Other'] += $amount;
                }
            }
        }
        
        // Get pending bills count
        $pendingResult = supabase_request('rcts_assessment_billing_hub', 'GET', [
            'select' => 'bill_reference_no',
            'status' => 'eq.Pending'
        ], [], true);
        $pendingPayments = $pendingResult['success'] ? count($pendingResult['data']) : 0;
        
        // Get pending disbursements
        $disbursementResult = supabase_request('rcts_aid_payout_registry', 'GET', [
            'select' => 'disbursement_ref_id',
            'status' => 'eq.Scheduled'
        ], [], true);
        $pendingDisbursements = $disbursementResult['success'] ? count($disbursementResult['data']) : 0;
        
        // Format daily revenue for chart
        $dailyData = [];
        $current = strtotime($startDate);
        $end = strtotime(date('Y-m-d'));
        while ($current <= $end) {
            $day = date('Y-m-d', $current);
            $dailyData[] = [
                'date' => $day,
                'amount' => isset($dailyRevenue[$day]) ? $dailyRevenue[$day] : 0
            ];
            $current = strtotime('+1 day', $current);
        }
        
        $avgTransaction = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;
        
        echo json_encode([
            'success' => true,
            'totalRevenue' => $totalRevenue,
            'totalTransactions' => $totalTransactions,
            'avgTransaction' => $avgTransaction,
            'pendingPayments' => $pendingPayments,
            'pendingDisbursements' => $pendingDisbursements,
            'byModule' => array_filter($byModule),
            'dailyRevenue' => $dailyData,
            'debug' => [
                'paymentsResult' => $paymentsResult,
                'pendingResult' => $pendingResult,
                'disbursementResult' => $disbursementResult,
                'period' => $period,
                'startDate' => $startDate
            ]
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
}
