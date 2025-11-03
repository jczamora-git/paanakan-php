<?php
/**
 * Quick test to verify email logging is working
 * This sends a test email and shows logs
 */

require_once __DIR__ . '/connections/connections.php';
require_once __DIR__ . '/connections/EmailService.php';

// Test configuration
$testEmail = 'test@example.com';
$testName = 'Test User';
$testSubject = 'Test Email - Logging Verification';

echo "=== Email Send & Logging Test ===\n\n";

// Initialize EmailService
try {
    $emailService = new EmailService();
    echo "✓ EmailService initialized\n";
} catch (Exception $e) {
    echo "✗ EmailService error: " . $e->getMessage() . "\n";
    exit(1);
}

// Send test email
echo "\nSending test email to: $testEmail\n";
$result = $emailService->sendEmail(
    $testEmail,
    $testName,
    $testSubject,
    'This is a test email body.',
    '<p>This is a test email body in HTML.</p>'
);

echo "Send result:\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// Check if logs exist
$sendLogFile = __DIR__ . '/logs/email_sends.log';
$eventLogFile = __DIR__ . '/logs/sendgrid_events.log';

echo "=== Log Files ===\n";
if (file_exists($sendLogFile)) {
    echo "✓ Send log exists: $sendLogFile\n";
    echo "  Last 3 lines:\n";
    $lines = array_slice(file($sendLogFile), -3);
    foreach ($lines as $line) {
        echo "  " . trim($line) . "\n";
    }
} else {
    echo "✗ Send log not found\n";
}

echo "\n";
if (file_exists($eventLogFile)) {
    echo "✓ Event log exists: $eventLogFile\n";
    echo "  (Events will appear after SendGrid webhook posts)\n";
} else {
    echo "ℹ Event log not created yet (will be created when SendGrid sends events)\n";
}

echo "\n=== Setup Complete ===\n";
echo "Next steps:\n";
echo "1. Enable SendGrid Event Webhook in SendGrid dashboard\n";
echo "2. Point webhook to: https://yourdomain.com/connections/sendgrid_events.php\n";
echo "3. Monitor logs/email_sends.log and logs/sendgrid_events.log\n";
echo "4. Read SENDGRID_WEBHOOK_SETUP.md for detailed instructions\n";
?>
