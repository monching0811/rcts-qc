<?php
// api/endpoints/admin.php
// Treasury Admin API endpoint for user management, settings, and logs

header('Content-Type: application/json');

// Error handling - ensure we always output JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
    exit;
});

try {
    require_once __DIR__ . '/../config/constants.php';
    require_once __DIR__ . '/../../includes/db.php';
} catch (Exception $e) {
    error_log("Include error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Configuration error']);
    exit;
}

session_start();

function is_admin() {
    // Check Authorization header for token-based auth
    // Use fallback for servers without pecl_http
    $auth = '';
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    } else {
        // Fallback: parse from $_SERVER
        foreach ($_SERVER as $key => $value) {
            if (strtolower(substr($key, 0, 5)) === 'http_') {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', substr($key, 5))));
                if (strtolower($header) === 'authorization') {
                    $auth = $value;
                    break;
                }
            }
        }
    }
    
    if (strpos($auth, 'Bearer ') === 0) {
        $token = substr($auth, 7);
        
        // Check for demo tokens - case insensitive
        $token_upper = strtoupper($token);
        if (strpos($token_upper, 'QC-ADMIN') === 0 || $token_upper === 'QC-STAFF-00001') {
            return true;
        }
        
        // Try to find user in database by qcitizen_id with admin role
        try {
            $db = db();
            $stmt = $db->prepare("SELECT role FROM rcts_citizen_registry WHERE qcitizen_id = ? AND role = 'admin'");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && $user['role'] === 'admin') {
                return true;
            }
            
            // Try by email
            $stmt2 = $db->prepare("SELECT role FROM rcts_citizen_registry WHERE email = ? AND role = 'admin'");
            $stmt2->execute([$token]);
            $user2 = $stmt2->fetch(PDO::FETCH_ASSOC);
            if ($user2 && $user2['role'] === 'admin') {
                return true;
            }
        } catch (Exception $e) {
            // Database error - ignore
        }
    }
    return false;
}

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Admins only.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list_users':
        // List all users (treasury staff, admin, auditor, citizen)
        $sql = "SELECT qcitizen_id, full_name, email, role, status FROM rcts_citizen_registry ORDER BY role, full_name";
        $rows = db_query($sql)->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'users' => $rows]);
        break;
    case 'add_user':
        // Add a new user (basic demo, no password hash for now)
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? 'citizen';
        $password = $_POST['password'] ?? '';
        if (!$name || !$email || !$password) {
            echo json_encode(['success' => false, 'message' => 'Missing fields.']);
            break;
        }
        $id = 'QC-USER-' . strtoupper(bin2hex(random_bytes(4)));
        $sql = "INSERT INTO rcts_citizen_registry (qcitizen_id, full_name, email, role, status) VALUES (?,?,?,?, 'active')";
        db_query($sql, [$id, $name, $email, $role]);
        echo json_encode(['success' => true]);
        break;
    case 'delete_user':
        $id = $_POST['qcitizen_id'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Missing user ID.']);
            break;
        }
        db_query("DELETE FROM rcts_citizen_registry WHERE qcitizen_id=?", [$id]);
        echo json_encode(['success' => true]);
        break;
    case 'reset_password':
        $id = $_POST['qcitizen_id'] ?? '';
        $pw = $_POST['password'] ?? '';
        if (!$id || !$pw) {
            echo json_encode(['success' => false, 'message' => 'Missing user ID or password.']);
            break;
        }
        // For demo: store password in a separate table or as a field (not secure, just for demo)
        db_query("UPDATE rcts_citizen_registry SET password=? WHERE qcitizen_id=?", [$pw, $id]);
        echo json_encode(['success' => true]);
        break;
    case 'save_settings':
        // For demo: update constants.php file directly (not secure, just for demo)
        $rpt_basic = $_POST['RPT_BASIC_RATE'] ?? null;
        $rpt_sef = $_POST['RPT_SEF_RATE'] ?? null;
        $const_file = __DIR__ . '/../config/constants.php';
        $consts = file_get_contents($const_file);
        if ($rpt_basic !== null) {
            $consts = preg_replace('/define\(\'RPT_BASIC_RATE\',\s*([0-9.]+)\);/', "define('RPT_BASIC_RATE', $rpt_basic);", $consts);
        }
        if ($rpt_sef !== null) {
            $consts = preg_replace('/define\(\'RPT_SEF_RATE\',\s*([0-9.]+)\);/', "define('RPT_SEF_RATE', $rpt_sef);", $consts);
        }
        file_put_contents($const_file, $consts);
        echo json_encode(['success' => true]);
        break;
    case 'list_settings':
        // Demo: just return a static setting
        echo json_encode(['success' => true, 'settings' => [
            ['key' => 'RPT_BASIC_RATE', 'value' => RPT_BASIC_RATE],
            ['key' => 'RPT_SEF_RATE', 'value' => RPT_SEF_RATE],
        ]]);
        break;
    case 'list_logs':
        // Demo: return a static log
        echo json_encode(['success' => true, 'logs' => [
            ['ts' => date('Y-m-d H:i:s'), 'event' => 'User login: treasurer@qc.gov.ph'],
            ['ts' => date('Y-m-d H:i:s'), 'event' => 'Admin viewed logs'],
        ]]);
        break;
    case 'rule_log':
        // Demo: return a static business rule change log
        echo json_encode(['success' => true, 'log' => [
            ['ts' => date('Y-m-d H:i:s', strtotime('-2 days')), 'user' => 'admin@qc.gov.ph', 'change' => 'Changed RPT_BASIC_RATE from 0.02 to 0.025'],
            ['ts' => date('Y-m-d H:i:s', strtotime('-1 days')), 'user' => 'treasurer@qc.gov.ph', 'change' => 'Changed BIZ_TAX_RATE_RETAIL from 0.01 to 0.012'],
        ]]);
        break;
    case 'list_apikeys':
        // List API keys for inbound subsystems
        require_once __DIR__ . '/../config/api-keys.php';
        $keys = [];
        foreach (API_KEYS as $subsystem => $key) {
            $keys[] = ['subsystem' => $subsystem, 'key' => $key];
        }
        echo json_encode(['success' => true, 'keys' => $keys]);
        break;
    case 'regen_apikey':
        // Regenerate API key for a subsystem (demo: just randomize, not secure)
        require_once __DIR__ . '/../config/api-keys.php';
        $subsystem = $_POST['subsystem'] ?? '';
        if (!$subsystem || !isset(API_KEYS[$subsystem])) {
            echo json_encode(['success' => false, 'message' => 'Invalid subsystem.']);
            break;
        }
        $newkey = strtoupper($subsystem) . '-RCTS-KEY-' . rand(1000,9999) . '-' . date('Y');
        // Update the API_KEYS array in api-keys.php (simple regex replace)
        $file = __DIR__ . '/../config/api-keys.php';
        $src = file_get_contents($file);
        $src = preg_replace(
            "/'" . preg_quote($subsystem, '/') . "'\s*=>\s*'[^']*'/",
            "'" . $subsystem . "'   => '" . $newkey . "'",
            $src
        );
        file_put_contents($file, $src);
        echo json_encode(['success' => true, 'key' => $newkey]);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
