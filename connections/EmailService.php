<?php
/**
 * EmailService - minimal SendGrid integration without Composer dependencies
 * Uses direct HTTP calls to SendGrid API and a simple .env reader.
 */

class EmailService {
    private $apiKey;
    private $from_email;
    private $from_name;

    public function __construct() {
        // Ensure Composer autoload is available for PHPMailer (if installed)
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }
        // Load .env manually (simple parser)
        $envFile = __DIR__ . '/../.env';
        $parsed = [];
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || strpos($trimmed, '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                [$k, $v] = explode('=', $line, 2);
                $k = trim($k);
                // strip UTF-8 BOM if present (first key in file)
                $k = preg_replace('/^\xEF\xBB\xBF/', '', $k);
                // Strip surrounding whitespace and control chars (CR/LF)
                $v = trim($v);
                $v = str_replace(["\r", "\n"], '', $v);
                // Remove optional surrounding quotes
                $v = preg_replace('/^"|"$/', '', $v);
                $parsed[$k] = $v;
                // also populate $_ENV to make it available elsewhere
                $_ENV[$k] = $v;
            }
        }

        // Prefer parsed .env values, then getenv(), then existing $_ENV
        $this->apiKey = $parsed['SENDGRID_API_KEY'] ?? getenv('SENDGRID_API_KEY') ?: ($_ENV['SENDGRID_API_KEY'] ?? '');
        $this->from_email = $parsed['SENDGRID_FROM_EMAIL'] ?? getenv('SENDGRID_FROM_EMAIL') ?: ($_ENV['SENDGRID_FROM_EMAIL'] ?? 'noreply@paanakan.com');
        $this->from_name = $parsed['SENDGRID_FROM_NAME'] ?? getenv('SENDGRID_FROM_NAME') ?: ($_ENV['SENDGRID_FROM_NAME'] ?? 'Paanakan sa Calapan');

        if (empty($this->apiKey)) {
            throw new \Exception('SENDGRID_API_KEY not configured in .env');
        }
    }

    private function postJson($url, $data) {
        $ch = curl_init($url);
        $payload = json_encode($data);
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // Prefer an explicit local CA bundle if present; also set PHP ini at runtime
        $cacert = realpath(__DIR__ . '/../certs/cacert.pem');
        if ($cacert && file_exists($cacert)) {
            // Ensure PHP/cURL/OpenSSL use the bundle when verifying
            @ini_set('curl.cainfo', $cacert);
            @ini_set('openssl.cafile', $cacert);
            curl_setopt($ch, CURLOPT_CAINFO, $cacert);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            // No local CA bundle available — try default system bundle. As a last resort
            // we will let curl attempt the connection (this may fail on misconfigured PHP).
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);

        // If there's an SSL cert verification error, attempt a retry with verification disabled
        if ($err && stripos($err, 'SSL certificate problem') !== false) {
            // try once more with verification disabled (NOT recommended for production)
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $response2 = curl_exec($ch);
            $status2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err2 = curl_error($ch);
            curl_close($ch);
            return [
                'status' => $status2,
                'body' => $response2,
                'error' => $err2,
                'warning' => 'SSL verification failed; retried with verification disabled. Configure php.ini curl.cainfo/openssl.cafile to a valid CA bundle to fix.'
            ];
        }

        curl_close($ch);
        return ['status' => $status, 'body' => $response, 'error' => $err];
    }

    public function sendEmail($to_email, $to_name, $subject, $body_text, $body_html = null) {
        // Create a correlation ID for tracking this send through logs and webhooks
        $correlationId = date('Ymd-His') . '-' . substr(md5($to_email . time()), 0, 8);

        // Allow disabling SendGrid via .env for testing SMTP only
        $useSendgrid = ($_ENV['EMAIL_USE_SENDGRID'] ?? getenv('EMAIL_USE_SENDGRID') ?? 'true');
        $useSendgrid = in_array(strtolower($useSendgrid), ['1','true','yes'], true);

        if (!$useSendgrid) {
            // If SendGrid disabled, attempt SMTP directly (must have EMAIL_FALLBACK_SMTP enabled)
            $fallbackEnabled = ($_ENV['EMAIL_FALLBACK_SMTP'] ?? getenv('EMAIL_FALLBACK_SMTP') ?? 'true');
            $fallbackEnabled = in_array(strtolower($fallbackEnabled), ['1','true','yes'], true);
            if ($fallbackEnabled && class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
                $result = $this->sendViaSmtpFallback($to_email, $to_name, $subject, $body_text, $body_html);
                $this->logEmailSend($correlationId, $to_email, $subject, 'smtp', $result);
                return $result;
            }
            $result = ['success' => false, 'message' => 'SendGrid disabled and SMTP fallback not enabled/configured'];
            $this->logEmailSend($correlationId, $to_email, $subject, 'none', $result);
            return $result;
        }
        $payload = [
            'personalizations' => [[
                'to' => [[ 'email' => $to_email, 'name' => $to_name ]]
            ]],
            'from' => [ 'email' => $this->from_email, 'name' => $this->from_name ],
            'subject' => $subject,
            'content' => [[ 'type' => 'text/plain', 'value' => $body_text ]]
        ];
        if ($body_html) {
            $payload['content'][] = [ 'type' => 'text/html', 'value' => $body_html ];
        }

        $res = $this->postJson('https://api.sendgrid.com/v3/mail/send', $payload);
        // If curl error (network/SSL) or non-2xx status, consider SMTP fallback
        if (!empty($res['error']) || !($res['status'] >= 200 && $res['status'] < 300)) {
            $sendgrid_result = ['success' => false, 'status' => $res['status'] ?? null, 'error' => $res['error'] ?? null, 'body' => $res['body'] ?? null];

            // Log SendGrid attempt (even though it failed)
            $this->logEmailSend($correlationId, $to_email, $subject, 'sendgrid', $sendgrid_result);

            // Only attempt SMTP fallback if enabled in .env
            $fallbackEnabled = ($_ENV['EMAIL_FALLBACK_SMTP'] ?? getenv('EMAIL_FALLBACK_SMTP') ?? 'false');
            $fallbackEnabled = in_array(strtolower($fallbackEnabled), ['1','true','yes'], true);

            if ($fallbackEnabled && class_exists('\PHPMailer\\PHPMailer\\PHPMailer')) {
                $smtpRes = $this->sendViaSmtpFallback($to_email, $to_name, $subject, $body_text, $body_html);
                $this->logEmailSend($correlationId, $to_email, $subject, 'smtp (fallback)', $smtpRes);
                $sendgrid_result['smtp_fallback'] = $smtpRes;
                return $sendgrid_result;
            }

            return $sendgrid_result;
        }

        // Success via SendGrid
        $result = ['success' => true, 'status_code' => $res['status']];
        $this->logEmailSend($correlationId, $to_email, $subject, 'sendgrid', $result);
        return $result;
    }

    /**
     * Attempt to send mail via SMTP using PHPMailer as a fallback
     * Returns array with success boolean and diagnostics
     */
    public function sendViaSmtpFallback($to_email, $to_name, $subject, $body_text = '', $body_html = null) {
        // Load SMTP config from env
        $smtpHost = $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $smtpPort = $_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?: 587;
        $smtpUser = $_ENV['SMTP_USER'] ?? getenv('SMTP_USER') ?: '';
        $smtpPass = $_ENV['SMTP_PASS'] ?? getenv('SMTP_PASS') ?: '';
        $smtpSecure = $_ENV['SMTP_SECURE'] ?? getenv('SMTP_SECURE') ?: 'tls';

        if (empty($smtpUser) || empty($smtpPass)) {
            return ['success' => false, 'error' => 'SMTP credentials not configured'];
        }

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = $smtpSecure;
            $mail->Port = (int) $smtpPort;

            // Enable verbose debug output for troubleshooting (comment out in production)
            // $mail->SMTPDebug = 2;
            // $mail->Debugoutput = function($str, $level) { error_log("SMTP: $str"); };

            // Check if insecure TLS is explicitly allowed first
            $allowInsecure = ($_ENV['SMTP_ALLOW_INSECURE_TLS'] ?? getenv('SMTP_ALLOW_INSECURE_TLS') ?? 'false');
            $allowInsecure = in_array(strtolower($allowInsecure), ['1','true','yes'], true);

            if ($allowInsecure) {
                // Disable SSL certificate verification (use for testing only!)
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
            } else {
                // Try to use a CA bundle for proper verification
                $cacert = realpath(__DIR__ . '/../certs/cacert.pem');
                if (!($cacert && file_exists($cacert))) {
                    $iniCafile = ini_get('curl.cainfo') ?: ini_get('openssl.cafile');
                    if ($iniCafile && file_exists($iniCafile)) {
                        $cacert = realpath($iniCafile);
                    } else {
                        $cacert = false;
                    }
                }

                if ($cacert) {
                    $mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => true,
                            'verify_peer_name' => true,
                            'allow_self_signed' => false,
                            'cafile' => $cacert
                        ]
                    ];
                }
                // If no CA bundle and not allowing insecure, PHPMailer will use system defaults
            }

            $fromEmail = $this->from_email ?: $smtpUser;
            $fromName = $this->from_name ?: '';
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to_email, $to_name);
            $mail->Subject = $subject;
            $mail->isHTML(!empty($body_html));
            $mail->Body = $body_html ?: $body_text;
            $mail->AltBody = $body_text ?: strip_tags($body_html ?: '');

            $mail->send();
            return ['success' => true, 'transport' => 'smtp'];
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendAppointmentConfirmation($to_email, $to_name, $appointment_details) {
        require_once __DIR__ . '/EmailTemplateEngine.php';
        $subject = "Appointment Confirmation - Paanakan sa Calapan";
        $engine = new EmailTemplateEngine();
        $html = $engine->getAppointmentConfirmationTemplate($to_name, $appointment_details);
        $body = "Your appointment has been confirmed for " . ($appointment_details['date'] ?? 'TBD') . " at " . ($appointment_details['time'] ?? 'TBD');
        return $this->sendEmail($to_email, $to_name, $subject, $body, $html);
    }

    public function sendPasswordReset($to_email, $to_name, $reset_link) {
        require_once __DIR__ . '/EmailTemplateEngine.php';
        $subject = "Password Reset Request - Paanakan sa Calapan";
        $engine = new EmailTemplateEngine();
        $html = $engine->getPasswordResetTemplate($to_name, $reset_link);
        $body = "We received a request to reset your password. Click the link to reset it: " . $reset_link . " This link expires in 1 hour.";
        return $this->sendEmail($to_email, $to_name, $subject, $body, $html);
    }

    public function sendWelcomeEmail($to_email, $to_name, $case_id = null) {
        require_once __DIR__ . '/EmailTemplateEngine.php';
        $subject = "Welcome to Paanakan sa Calapan";
        $engine = new EmailTemplateEngine();
        $html = $engine->getWelcomeEmailTemplate($to_name, $case_id ?? 'TBD', null, $to_email);
        $body = "Welcome to Paanakan sa Calapan! Your account has been created with Case ID: " . ($case_id ?? 'TBD');
        return $this->sendEmail($to_email, $to_name, $subject, $body, $html);
    }

    public function sendAppointmentReminder($to_email, $to_name, $appointment_details) {
        require_once __DIR__ . '/EmailTemplateEngine.php';
        $subject = "Appointment Reminder - Paanakan sa Calapan";
        $engine = new EmailTemplateEngine();
        $html = $engine->getAppointmentReminderTemplate($to_name, $appointment_details);
        $body = "Reminder: You have an appointment scheduled for " . ($appointment_details['scheduled_date'] ?? 'TBD');
        return $this->sendEmail($to_email, $to_name, $subject, $body, $html);
    }

    public function sendAppointmentCancellation($to_email, $to_name, $appointment_details) {
        require_once __DIR__ . '/EmailTemplateEngine.php';
        $subject = "Appointment Cancelled - Paanakan sa Calapan";
        $engine = new EmailTemplateEngine();
        $html = $engine->getAppointmentCancellationTemplate($to_name, $appointment_details);
        $body = "Your appointment has been cancelled.";
        return $this->sendEmail($to_email, $to_name, $subject, $body, $html);
    }

    public function sendAppointmentScheduled($to_email, $to_name, $appointment_details) {
        require_once __DIR__ . '/EmailTemplateEngine.php';
        $subject = "Appointment Scheduled - Pending Approval - Paanakan sa Calapan";
        $engine = new EmailTemplateEngine();
        $html = $engine->getAppointmentScheduledTemplate($to_name, $appointment_details);
        $body = "Your appointment request for " . ($appointment_details['scheduled_date'] ?? 'TBD') . " at " . ($appointment_details['time'] ?? 'TBD') . " has been received and is pending approval.";
        return $this->sendEmail($to_email, $to_name, $subject, $body, $html);
    }

    /**
     * Log email send attempt for audit trail and troubleshooting
     * 
     * Writes to logs/email_sends.log with correlation ID, recipient, subject, transport, and result.
     * This helps correlate with SendGrid webhook events.
     */
    private function logEmailSend($correlationId, $to_email, $subject, $transport, $result) {
        // Ensure logs directory exists
        $logDir = realpath(__DIR__ . '/../logs');
        if (!$logDir || !is_dir($logDir)) {
            @mkdir(__DIR__ . '/../logs', 0755, true);
            $logDir = __DIR__ . '/../logs';
        }

        $logFile = $logDir . '/email_sends.log';

        // Build log entry with correlation ID
        $success = isset($result['success']) ? ($result['success'] ? 'OK' : 'FAIL') : 'UNKNOWN';
        $status = $result['status'] ?? $result['status_code'] ?? 'N/A';
        $error = $result['error'] ?? '';
        
        $logEntry = sprintf(
            "[%s] CORR_ID: %s | TO: %s | SUBJECT: %s | TRANSPORT: %s | STATUS: %s | SUCCESS: %s | ERROR: %s\n",
            date('Y-m-d H:i:s'),
            $correlationId,
            $to_email,
            substr($subject, 0, 60),
            $transport,
            $status,
            $success,
            $error
        );

        // Write to log file
        error_log($logEntry, 3, $logFile);
    }
}
