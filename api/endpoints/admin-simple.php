<?php
// Simple admin API with file-based audit logging
header('Content-Type: application/json');


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
        echo json_encode(['success' => true, 'users' => [
            ['qcitizen_id' => 'QC-ADMIN-0001', 'full_name' => 'Admin User', 'email' => 'admin@qc.gov.ph', 'role' => 'admin', 'status' => 'active'],
            ['qcitizen_id' => 'QC-2024-000001', 'full_name' => 'Juan Dela Cruz', 'email' => 'juan.delacruz@email.com', 'role' => 'citizen', 'status' => 'active'],
            ['qcitizen_id' => 'QC-2024-000002', 'full_name' => 'Cheryl Reyes-Macaraeg', 'email' => 'treasurer@qc.gov.ph', 'role' => 'treasurer', 'status' => 'active']
        ]]);
        break;
        
    case 'add_user':
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? 'citizen';
        
        addLog("Created new user: $email ($role)", 'admin');
        
        echo json_encode(['success' => true, 'message' => 'User created successfully']);
        break;
        
    case 'delete_user':
        $id = $_POST['qcitizen_id'] ?? '';
        if ($id) {
            addLog("Deleted user: $id", 'admin');
        }
        echo json_encode(['success' => true]);
        break;
        
    case 'reset_password':
        $id = $_POST['qcitizen_id'] ?? '';
        if ($id) {
            addLog("Password reset for user: $id", 'admin');
        }
        echo json_encode(['success' => true]);
        break;
        
    case 'update_user':
        $id = $_POST['qcitizen_id'] ?? '';
        $name = $_POST['full_name'] ?? '';
        $role = $_POST['role'] ?? '';
        $status = $_POST['status'] ?? 'active';
        
        if ($id) {
            $changes = [];
            if ($name) $changes[] = "name: $name";
            if ($role) $changes[] = "role: $role";
            if ($status) $changes[] = "status: $status";
            
            addLog("Updated user $id: " . implode(", ", $changes), 'admin');
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
        }
        break;
        
    case 'save_settings':
        addLog("System settings updated", 'admin');
        echo json_encode(['success' => true]);
        break;
        
    case 'list_settings':
        echo json_encode(['success' => true, 'settings' => [
            // Real Property Tax (RPT) Settings
            ['key' => 'RPT_BASIC_RATE', 'value' => '0.025', 'category' => 'RPT', 'description' => 'Real Property Tax Basic Rate (2.5% of Assessed Value)'],
            ['key' => 'RPT_SEF_RATE', 'value' => '0.01', 'category' => 'RPT', 'description' => 'Special Education Fund Rate (1% of Assessed Value)'],
            
            // Business Tax Settings
            ['key' => 'BIZ_TAX_RATE_RETAIL', 'value' => '0.012', 'category' => 'Business Tax', 'description' => 'Business Tax Rate for Retail (1.2%)'],
            ['key' => 'BIZ_TAX_RATE_WHOLESALE', 'value' => '0.015', 'category' => 'Business Tax', 'description' => 'Business Tax Rate for Wholesale (1.5%)'],
            ['key' => 'BIZ_TAX_RATE_MFR', 'value' => '0.02', 'category' => 'Business Tax', 'description' => 'Business Tax Rate for Manufacturers (2%)'],
            ['key' => 'BIZ_TAX_RATE_IMPORTER', 'value' => '0.025', 'category' => 'Business Tax', 'description' => 'Business Tax Rate for Importers (2.5%)'],
            
            // Penalty Settings
            ['key' => 'PENALTY_RATE_MONTHLY', 'value' => '0.02', 'category' => 'Penalty', 'description' => 'Monthly Penalty Rate for late payments (2% per month)'],
            ['key' => 'PENALTY_MAX_MONTHS', 'value' => '36', 'category' => 'Penalty', 'description' => 'Maximum months for penalty accumulation'],
            
            // Payment Due Dates
            ['key' => 'RPT_DUE_DATE_Q1', 'value' => '03-31', 'category' => 'Due Dates', 'description' => 'RPT Q1 Due Date (March 31)'],
            ['key' => 'RPT_DUE_DATE_Q2', 'value' => '06-30', 'category' => 'Due Dates', 'description' => 'RPT Q2 Due Date (June 30)'],
            ['key' => 'RPT_DUE_DATE_Q3', 'value' => '09-30', 'category' => 'Due Dates', 'description' => 'RPT Q3 Due Date (September 30)'],
            ['key' => 'RPT_DUE_DATE_Q4', 'value' => '12-31', 'category' => 'Due Dates', 'description' => 'RPT Q4 Due Date (December 31)'],
            ['key' => 'BIZ_TAX_DUE_DATE', 'value' => '01-20', 'category' => 'Due Dates', 'description' => 'Business Tax Annual Due Date (January 20)'],
            
            // Discount Settings
            ['key' => 'EARLY_PAYMENT_DISCOUNT_RATE', 'value' => '0.10', 'category' => 'Discount', 'description' => 'Early Payment Discount Rate (10% if paid within first month)'],
            ['key' => 'EARLY_PAYMENT_DISCOUNT_DEADLINE', 'value' => '01-31', 'category' => 'Discount', 'description' => 'Early Payment Discount Deadline (January 31)'],
            
            // Market Stall Settings
            ['key' => 'MARKET_STALL_DAILY_RATE', 'value' => '50.00', 'category' => 'Market', 'description' => 'Daily Market Stall Rate (PHP)'],
            ['key' => 'MARKET_STALL_MONTHLY_RATE', 'value' => '1200.00', 'category' => 'Market', 'description' => 'Monthly Market Stall Rate (PHP)'],
            
            // Traffic Fine Settings
            ['key' => 'TRAFFIC_VIOLATION_BASE_FINE', 'value' => '500.00', 'category' => 'Traffic', 'description' => 'Base Traffic Violation Fine (PHP)'],
            ['key' => 'TRAFFIC_VIOLATION_SURCHARGE', 'value' => '0.10', 'category' => 'Traffic', 'description' => 'Traffic Violation Surcharge (10% per month late)'],
        ]]);
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
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
}
