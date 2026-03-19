<?php
// Simple admin API with file-based audit logging
header('Content-Type: application/json');

$LOG_FILE = __DIR__ . '/../../logs/audit.json';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Helper function to read logs
function readLogs() {
    global $LOG_FILE;
    if (file_exists($LOG_FILE)) {
        $content = file_get_contents($LOG_FILE);
        $logs = json_decode($content, true);
        return $logs ?: [];
    }
    return [];
}

// Helper function to write logs
function writeLogs($logs) {
    global $LOG_FILE;
    $dir = dirname($LOG_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($LOG_FILE, json_encode($logs, JSON_PRETTY_PRINT));
}

// Helper function to add log entry
function addLog($event, $user = 'system') {
    $logs = readLogs();
    $logs[] = [
        'ts' => date('Y-m-d H:i:s'),
        'event' => $event,
        'user' => $user
    ];
    // Keep only last 1000 entries
    if (count($logs) > 1000) {
        $logs = array_slice($logs, -1000);
    }
    writeLogs($logs);
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
        $logs = readLogs();
        // If no logs yet, add some demo entries
        if (empty($logs)) {
            $logs = [
                ['ts' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'event' => 'User login: admin@qc.gov.ph', 'user' => 'admin@qc.gov.ph'],
                ['ts' => date('Y-m-d H:i:s', strtotime('-30 minutes')), 'event' => 'Admin viewed dashboard', 'user' => 'admin@qc.gov.ph'],
                ['ts' => date('Y-m-d H:i:s', strtotime('-10 minutes')), 'event' => 'User login: treasurer@qc.gov.ph', 'user' => 'treasurer@qc.gov.ph'],
            ];
            writeLogs($logs);
        }
        echo json_encode(['success' => true, 'logs' => array_reverse($logs)]);
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
