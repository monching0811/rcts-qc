<?php
/**
 * OTP SEND ENDPOINT
 * api/endpoints/otp-send.php
 *
 * Called after password is verified. Generates a 6-digit OTP,
 * stores it in rcts_otp_codes, and sends it via PHPMailer.
 *
 * POST body (JSON or form-data):
 *   email    string  - The staff email address
 *   purpose  string  - 'login' (default) or 'reset'
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../../includes/db.php';

// ── PHPMailer (manual install at project root /PHPMailer/src/) ────────────────
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$phpmailerBase = __DIR__ . '/../../PHPMailer/src/';
require_once $phpmailerBase . 'Exception.php';
require_once $phpmailerBase . 'PHPMailer.php';
require_once $phpmailerBase . 'SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// ── Parse input (supports both JSON and form-data) ────────────────────────────
$input   = json_decode(file_get_contents('php://input'), true);
$email   = strtolower(trim($input['email']   ?? $_POST['email']   ?? ''));
$purpose = trim($input['purpose'] ?? $_POST['purpose'] ?? 'login');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Valid email required']);
    exit;
}

// ── Rate limit: max 3 OTP requests per 10 minutes per email ──────────────────
$recentOtps = db('rcts_otp_codes', [
    'email'      => 'eq.' . $email,
    'purpose'    => 'eq.' . $purpose,
    'created_at' => 'gte.' . date('c', strtotime('-10 minutes')),
    'select'     => 'id',
]);
if ($recentOtps['success'] && count($recentOtps['data'] ?? []) >= 3) {
    echo json_encode([
        'success' => false,
        'message' => 'Too many OTP requests. Please wait 10 minutes before trying again.',
    ]);
    exit;
}

// ── Generate 6-digit OTP ──────────────────────────────────────────────────────
$otp     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = date('c', strtotime('+10 minutes'));

// ── Invalidate previous unused OTPs for this email+purpose ───────────────────
db_patch('rcts_otp_codes',
    ['email' => 'eq.' . $email, 'purpose' => 'eq.' . $purpose, 'used' => 'eq.false'],
    ['used' => true]
);

// ── Store new OTP ─────────────────────────────────────────────────────────────
$insert = db_create('rcts_otp_codes', [
    'email'      => $email,
    'otp_code'   => $otp,
    'purpose'    => $purpose,
    'used'       => false,
    'expires_at' => $expires,
]);

if (!$insert['success']) {
    echo json_encode(['success' => false, 'message' => 'Failed to generate OTP. Please try again.']);
    exit;
}

// ── Send OTP via PHPMailer ────────────────────────────────────────────────────
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'jimuelparagas@gmail.com';
    $mail->Password   = 'vgosmofjiivtmkui';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('jimuelparagas@gmail.com', 'RCTS-QC Treasury System');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'RCTS-QC Login Verification Code';
    $mail->Body    = buildOtpEmail($otp, $purpose);
    $mail->AltBody = "Your RCTS-QC verification code is: $otp\n\nThis code expires in 10 minutes.\nDo not share this code with anyone.";

    $mail->send();

    echo json_encode([
        'success' => true,
        'message' => 'OTP sent to ' . maskEmail($email),
    ]);

} catch (Exception $e) {
    error_log('[RCTS OTP] Mailer error for ' . $email . ': ' . $mail->ErrorInfo);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send OTP email. Please contact your system administrator.',
    ]);
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function maskEmail(string $email): string {
    [$user, $domain] = explode('@', $email, 2);
    $masked = substr($user, 0, 2) . str_repeat('*', max(0, strlen($user) - 2));
    return $masked . '@' . $domain;
}

function buildOtpEmail(string $otp, string $purpose): string {
    $label = $purpose === 'reset' ? 'Password Reset' : 'Login Verification';
    return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
    <body style="margin:0;padding:0;background:#f5f5f5;font-family:'Segoe UI',Arial,sans-serif;">
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:40px 0;">
        <tr><td align="center">
          <table width="480" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
            <!-- Header -->
            <tr>
              <td style="background:linear-gradient(135deg,#c9a84c,#e8c878);padding:28px 32px;text-align:center;">
                <div style="font-size:26px;font-weight:700;color:#fff;letter-spacing:0.05em;">RCTS-QC</div>
                <div style="font-size:12px;color:rgba(255,255,255,0.85);letter-spacing:0.1em;text-transform:uppercase;margin-top:4px;">
                  Revenue Collection &amp; Treasury System
                </div>
              </td>
            </tr>
            <!-- Body -->
            <tr>
              <td style="padding:36px 32px;">
                <p style="margin:0 0 8px;font-size:18px;font-weight:600;color:#1a1a1a;">{$label} Code</p>
                <p style="margin:0 0 28px;font-size:14px;color:#666;">
                  Use the code below to complete your sign-in. It expires in <strong>10 minutes</strong>.
                </p>
                <!-- OTP Box -->
                <div style="background:#f9f4ea;border:2px solid #e8c878;border-radius:10px;text-align:center;padding:24px 0;margin-bottom:28px;">
                  <span style="font-family:'Courier New',monospace;font-size:40px;font-weight:700;color:#8a6d3b;letter-spacing:0.25em;">{$otp}</span>
                </div>
                <p style="margin:0 0 8px;font-size:13px;color:#888;text-align:center;">
                  ⚠️ Never share this code with anyone. RCTS staff will <strong>never</strong> ask for your OTP.
                </p>
                <p style="margin:0;font-size:13px;color:#aaa;text-align:center;">
                  If you did not request this code, please ignore this email or contact IT support.
                </p>
              </td>
            </tr>
            <!-- Footer -->
            <tr>
              <td style="background:#f5f5f5;padding:18px 32px;border-top:1px solid #eee;text-align:center;">
                <p style="margin:0;font-size:11px;color:#aaa;">
                  Quezon City Treasury Department &bull; Department 8 &bull; This is an automated message.
                </p>
              </td>
            </tr>
          </table>
        </td></tr>
      </table>
    </body>
    </html>
    HTML;
}