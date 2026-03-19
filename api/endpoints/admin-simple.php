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
        
    case 'save_settings':
        addLog("System settings updated", 'admin');
        echo json_encode(['success' => true]);
        break;
        
    case 'list_settings':
        echo json_encode(['success' => true, 'settings' => [
            ['key' => 'RPT_BASIC_RATE', 'value' => '0.025'],
            ['key' => 'RPT_SEF_RATE', 'value' => '0.01'],
            ['key' => 'BIZ_TAX_RATE_RETAIL', 'value' => '0.012'],
            ['key' => 'BIZ_TAX_RATE_WHOLESALE', 'value' => '0.015'],
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
