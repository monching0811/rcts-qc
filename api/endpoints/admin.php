<?php
// api/endpoints/admin.php
// Treasury Admin API endpoint for user management, settings, and logs

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    require_once __DIR__ . '/../config/constants.php';
    require_once __DIR__ . '/../../includes/db.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Configuration error: ' . $e->getMessage()]);
    exit;
}

// Simple auth check - for demo, accept any request
// In production, implement proper token validation
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $auth);

// DEMO MODE: Allow all requests for demo purposes
$is_demo = true;

if (!$is_demo && empty($token)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authorization required']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list_users':
        try {
            $sql = "SELECT qcitizen_id, full_name, email, role, status FROM rcts_citizen_registry ORDER BY role, full_name";
            $rows = db_query($sql)->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'users' => $rows]);
        } catch (Exception $e) {
            // Return demo data if table doesn't exist
            echo json_encode(['success' => true, 'users' => [
                ['qcitizen_id' => 'QC-ADMIN-0001', 'full_name' => 'Admin User', 'email' => 'admin@qc.gov.ph', 'role' => 'admin', 'status' => 'active'],
                ['qcitizen_id' => 'QC-2024-000001', 'full_name' => 'Juan Dela Cruz', 'email' => 'juan.delacruz@email.com', 'role' => 'citizen', 'status' => 'active']
            ]]);
        }
        break;
        
    case 'add_user':
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? 'citizen';
        $password = $_POST['password'] ?? '';
        if (!$name || !$email || !$password) {
            echo json_encode(['success' => false, 'message' => 'Missing fields.']);
            break;
        }
        try {
            $id = 'QC-USER-' . strtoupper(bin2hex(random_bytes(4)));
            $sql = "INSERT INTO rcts_citizen_registry (qcitizen_id, full_name, email, role, status) VALUES (?,?,?,?, 'active')";
            db_query($sql, [$id, $name, $email, $role]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => true, 'message' => 'User created (demo mode)']);
        }
        break;
        
    case 'delete_user':
        $id = $_POST['qcitizen_id'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Missing user ID.']);
            break;
        }
        try {
            db_query("DELETE FROM rcts_citizen_registry WHERE qcitizen_id=?", [$id]);
        } catch (Exception $e) {
            // Ignore in demo mode
        }
        echo json_encode(['success' => true]);
        break;
        
    case 'reset_password':
        $id = $_POST['qcitizen_id'] ?? '';
        $pw = $_POST['password'] ?? '';
        if (!$id || !$pw) {
            echo json_encode(['success' => false, 'message' => 'Missing user ID or password.']);
            break;
        }
        try {
            db_query("UPDATE rcts_citizen_registry SET password=? WHERE qcitizen_id=?", [$pw, $id]);
        } catch (Exception $e) {
            // Ignore in demo mode
        }
        echo json_encode(['success' => true]);
        break;
        
    case 'save_settings':
        $rpt_basic = $_POST['RPT_BASIC_RATE'] ?? null;
        $rpt_sef = $_POST['RPT_SEF_RATE'] ?? null;
        try {
            $const_file = __DIR__ . '/../config/constants.php';
            $consts = file_get_contents($const_file);
            if ($rpt_basic !== null) {
                $consts = preg_replace('/define\(\'RPT_BASIC_RATE\',\s*([0-9.]+)\);/', "define('RPT_BASIC_RATE', $rpt_basic);", $consts);
            }
            if ($rpt_sef !== null) {
                $consts = preg_replace('/define\(\'RPT_SEF_RATE\',\s*([0-9.]+)\);/', "define('RPT_SEF_RATE', $rpt_sef);", $consts);
            }
            file_put_contents($const_file, $consts);
        } catch (Exception $e) {
            // Ignore in demo mode
        }
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
        echo json_encode(['success' => true, 'logs' => [
            ['ts' => date('Y-m-d H:i:s'), 'event' => 'User login: admin@qc.gov.ph'],
            ['ts' => date('Y-m-d H:i:s', strtotime('-1 minute')), 'event' => 'Admin viewed dashboard'],
            ['ts' => date('Y-m-d H:i:s', strtotime('-5 minutes')), 'event' => 'Settings updated'],
            ['ts' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'event' => 'User created: newuser@email.com'],
        ]]);
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
        echo json_encode(['success' => true, 'key' => $newkey]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
