<?php
/**
 * SUBSYSTEM 1: CITIZEN REGISTRY API
 * ==================================
 * This API acts as the bridge between RCTS and Subsystem 1's Citizen Database.
 * 
 * INTEGRATION ARCHITECTURE:
 * - Fetches citizen data from S1's Supabase (profiles table)
 * - Uses RCTS's own Supabase for bills/payments
 * 
 * SUPABASE CREDENTIALS:
 * - S1 Supabase: https://tjcwwocpkpmhtdtlsiuc.supabase.co
 * - RCTS Supabase: https://ipjtrqcncyvmtzrbsjya.supabase.co
 */

require_once __DIR__ . '/../../api/config/supabase.php';

// ── S1 Supabase credentials (hardcoded for direct access) ───────────────────────
define('S1_URL', 'https://tjcwwocpkpmhtdtlsiuc.supabase.co');
define('S1_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRqY3d3b2Nwa3BtaHRkdGxzaXVjIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3MDYxOTczNiwiZXhwIjoyMDg2MTk1NzM2fQ.Yv7Q0pcs5tx-JBWcsPzIemj_RoYj1MF87ca_DbqKmMY');

// ── Fetch citizen from S1 Supabase ───────────────────────────────────────────
function fetch_s1_citizen(string $email): array {
    $url = S1_URL . '/rest/v1/profiles?email=eq.' . urlencode($email);
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . S1_KEY,
        'Authorization: Bearer ' . S1_KEY,
        'Prefer: return=representation'
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);
    return [
        'success' => ($http_code >= 200 && $http_code < 300),
        'data' => $data
    ];
}

// ── Fetch ALL citizens from S1 Supabase ──────────────────────────────────────
function fetch_all_s1_citizens(): array {
    $url = S1_URL . '/rest/v1/profiles?select=*';
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . S1_KEY,
        'Authorization: Bearer ' . S1_KEY,
        'Prefer: return=representation'
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);
    return [
        'success' => ($http_code >= 200 && $http_code < 300),
        'data' => $data
    ];
}

// ── Build Full Name ─────────────────────────────────────────────────────────────
function build_full_name(array $profile): string {
    $parts = [];
    if (!empty($profile['first_name'])) $parts[] = trim($profile['first_name']);
    if (!empty($profile['middle_name'])) $parts[] = trim($profile['middle_name']);
    if (!empty($profile['last_name'])) $parts[] = trim($profile['last_name']);
    if (!empty($profile['suffix'])) $parts[] = trim($profile['suffix']);
    return implode(' ', $parts);
}

// ── Action Router ───────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    // ── Verify Login ─────────────────────────────────────────────────────────
    case 'verify_login':
        $email = $_GET['email'] ?? '';
        $password = $_GET['password'] ?? '';

        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit;
        }

        // First, try to fetch from S1 Supabase (profiles table)
        $result = fetch_s1_citizen($email);

        if (!$result['success'] || empty($result['data'])) {
            // FALLBACK: Try local JSON mock data
            $local_data = load_local_citizens();
            $profile = null;
            
            foreach ($local_data as $citizen) {
                if (strtolower($citizen['email']) === strtolower($email)) {
                    $profile = $citizen;
                    break;
                }
            }
            
            if (!$profile) {
                echo json_encode(['success' => false, 'message' => 'Citizen not found']);
                exit;
            }
            
            // Build from local data
            $citizen = [
                'qcitizen_id' => $profile['qcitizen_id'],
                'full_name' => $profile['full_name'],
                'first_name' => explode(' ', $profile['full_name'])[0],
                'last_name' => end(explode(' ', $profile['full_name'])),
                'email' => $profile['email'],
                'phone' => $profile['mobile_no'] ?? '',
                'address' => $profile['address'] ?? '',
                'barangay' => $profile['barangay'] ?? '',
                'city' => 'Quezon City',
                'role' => $profile['role'] ?? 'citizen',
                'login_token' => $profile['qcitizen_id'],
                'is_pwd' => $profile['is_pwd'] ?? false,
                'is_senior' => $profile['is_senior_citizen'] ?? false,
                'discount_eligible' => $profile['discount_eligible'] ?? false
            ];
            
            // Check for Senior discount eligibility
            if (!empty($profile['date_of_birth'])) {
                $birth = new DateTime($profile['date_of_birth']);
                $today = new DateTime('today');
                $age = $birth->diff($today)->y;
                if ($age >= 60) {
                    $citizen['is_senior'] = true;
                    $citizen['discount_eligible'] = true;
                }
            }
            
            // Fetch pending bills from RCTS Supabase
            $citizen['pending_bills'] = get_rcts_bills($citizen['qcitizen_id']);
            
            echo json_encode(['success' => true, 'data' => $citizen, '_source' => 'local']);
            exit;
        }

        $profile = $result['data'][0];

        // For demo: accept any password
        if (!empty($password) || $profile['user_id']) {
            // Build citizen data for RCTS
            $citizen = [
                'qcitizen_id' => $profile['user_id'],
                'full_name' => build_full_name($profile),
                'first_name' => $profile['first_name'] ?? '',
                'last_name' => $profile['last_name'] ?? '',
                'email' => $profile['email'],
                'phone' => $profile['phone'] ?? '',
                'address' => $profile['address'] ?? '',
                'barangay' => $profile['barangay'] ?? '',
                'city' => $profile['city'] ?? 'Quezon City',
                'birth_date' => $profile['birth_date'] ?? null,
                'role' => determine_citizen_role($profile),
                'login_token' => $profile['user_id'],
                'is_pwd' => false,
                'is_senior' => false,
                'discount_eligible' => false
            ];

            // Check for Senior discount eligibility
            if (!empty($profile['birth_date'])) {
                $birth = new DateTime($profile['birth_date']);
                $today = new DateTime('today');
                $age = $birth->diff($today)->y;
                if ($age >= 60) {
                    $citizen['is_senior'] = true;
                    $citizen['discount_eligible'] = true;
                }
            }

            // Fetch pending bills from RCTS Supabase
            $citizen['pending_bills'] = get_rcts_bills($citizen['qcitizen_id']);

            echo json_encode(['success' => true, 'data' => $citizen, '_source' => 'supabase']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
        }
        break;

    // ── Get All Citizens ───────────────────────────────────────────────────────
    case 'get_all_citizens':
        $result = fetch_all_s1_citizens();

        if (!$result['success']) {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch citizens']);
            exit;
        }

        $citizens = array_map(function($profile) {
            return [
                'qcitizen_id' => $profile['user_id'],
                'full_name' => build_full_name($profile),
                'first_name' => $profile['first_name'] ?? '',
                'last_name' => $profile['last_name'] ?? '',
                'email' => $profile['email'],
                'phone' => $profile['phone'] ?? '',
                'role' => determine_citizen_role($profile),
                'created_at' => $profile['created_at']
            ];
        }, $result['data'] ?? []);

        echo json_encode(['success' => true, 'data' => $citizens]);
        break;

    // ── Get Citizen by ID ─────────────────────────────────────────────────────
    case 'get_citizen':
        // Support both 'id' and 'qcitizen_id' parameters
        $id = $_GET['id'] ?? $_GET['qcitizen_id'] ?? '';
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Citizen ID is required']);
            exit;
        }
        
        // Fetch from S1 Supabase by user_id
        $url = S1_URL . '/rest/v1/profiles?user_id=eq.' . urlencode($id);
        $headers = [
            'Content-Type: application/json',
            'apikey: ' . S1_KEY,
            'Authorization: Bearer ' . S1_KEY,
            'Prefer: return=representation'
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        
        if ($http_code >= 200 && $http_code < 300 && !empty($data)) {
            $profile = $data[0];
            $citizen = [
                'qcitizen_id' => $profile['user_id'],
                'full_name' => build_full_name($profile),
                'first_name' => $profile['first_name'] ?? '',
                'last_name' => $profile['last_name'] ?? '',
                'email' => $profile['email'],
                'phone' => $profile['phone'] ?? '',
                'address' => $profile['address'] ?? '',
                'barangay' => $profile['barangay'] ?? '',
                'city' => $profile['city'] ?? 'Quezon City',
                'birth_date' => $profile['birth_date'] ?? null
            ];
            echo json_encode(['success' => true, 'data' => $citizen]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Citizen not found']);
        }
        break;

    // ── Check Discount Eligibility ─────────────────────────────────────────────
    case 'check_discount':
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Citizen ID is required']);
            exit;
        }
        echo json_encode(['success' => true, 'data' => ['discount_eligible' => false]]);
        break;

    // ── Send Notification (Mock) ───────────────────────────────────────────────
    case 'send_notification':
        echo json_encode([
            'success' => true,
            'message' => 'Notification sent successfully',
            'data' => ['sent_at' => date('c')]
        ]);
        break;

    default:
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid action. Use: verify_login, get_all_citizens'
        ]);
}

// ── Helper: Determine Citizen Role ─────────────────────────────────────────────
function determine_citizen_role(array $profile): string {
    $email = strtolower($profile['email'] ?? '');
    if (strpos($email, 'treasurer') !== false || strpos($email, 'qc.gov.ph') !== false) return 'treasurer';
    if (strpos($email, 'auditor') !== false || strpos($email, 'coa') !== false) return 'auditor';
    return 'citizen';
}

// ── Load local citizens from JSON file (fallback) ───────────────────────────
function load_local_citizens(): array {
    $json_file = __DIR__ . '/citizens.json';
    if (!file_exists($json_file)) {
        return [];
    }
    $content = file_get_contents($json_file);
    $data = json_decode($content, true);
    return $data['citizens'] ?? [];
}

// ── Get RCTS Bills from RCTS Supabase ───────────────────────────────────────
function get_rcts_bills(string $qcitizen_id): array {
    // Use RCTS's Supabase to fetch bills
    $url = SUPABASE_URL . '/rest/v1/rcts_assessment_billing_hub?qcitizen_id=eq.' . urlencode($qcitizen_id) . '&status=eq.Pending&select=*';
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . SUPABASE_SERVICE_KEY,
        'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
        'Prefer: return=representation'
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);
    
    if ($http_code >= 200 && $http_code < 300 && !empty($data)) {
        return $data;
    }
    return [];
}
