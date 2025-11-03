<?php
// Simple test endpoint to send a test email using the EmailService
// Usage (GET): connections/test_smtp.php?to=you@example.com
// Or POST with form field 'to'

require_once __DIR__ . '/connections.php';
require_once __DIR__ . '/EmailService.php';

header('Content-Type: application/json');

$to = $_REQUEST['to'] ?? null;
if (!$to) {
    echo json_encode(['success' => false, 'message' => 'Missing `to` parameter']);
    exit;
}

try {
    $emailService = new EmailService();
    $subject = 'Test email from Paanakan - SMTP/SendGrid check';
    $text = 'This is a test email sent from the Paanakan test endpoint to verify SMTP/SendGrid transport.';
    $html = '<p>This is a <strong>test email</strong> sent from the Paanakan test endpoint to verify SMTP/SendGrid transport.</p>';

    // Before sending, report which env settings are detected to help debugging
    $envReport = [
        'EMAIL_USE_SENDGRID' => $_ENV['EMAIL_USE_SENDGRID'] ?? getenv('EMAIL_USE_SENDGRID'),
        'EMAIL_FALLBACK_SMTP' => $_ENV['EMAIL_FALLBACK_SMTP'] ?? getenv('EMAIL_FALLBACK_SMTP'),
        'SMTP_USER' => $_ENV['SMTP_USER'] ?? getenv('SMTP_USER') ? 'present' : 'missing',
        'SMTP_HOST' => $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?: 'default:smtp.gmail.com',
        'SMTP_PORT' => $_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?: 'default:587',
    ];

    $res = $emailService->sendEmail($to, $to, $subject, $text, $html);
    echo json_encode(['env' => $envReport, 'result' => $res]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>