<?php
/**
 * Test SendGrid Email Integration
 * 
 * This script tests the EmailService class with SendGrid API
 */

require_once __DIR__ . '/EmailService.php';

// Initialize EmailService
$emailService = new EmailService();

echo "=== SendGrid Email Test ===\n\n";

// Test 1: Simple email
echo "Test 1: Sending a simple test email...\n";
$result = $emailService->sendEmail(
    'paanakansacalapan090@gmail.com',  // Replace with your email for testing
    'Test User',
    'Test Email from Paanakan',
    'This is a test email sent via SendGrid API.',
    '<h1>Test Email</h1><p>This is a test email sent via SendGrid API.</p>'
);

if ($result['success']) {
    echo "✓ Email sent successfully!\n";
    echo "  Status Code: {$result['status_code']}\n";
} else {
    echo "✗ Failed to send email\n";
    echo "  Error: {$result['message']}\n";
}

echo "\n";

// Test 2: Appointment confirmation email
echo "Test 2: Sending appointment confirmation email...\n";
$appointment_details = [
    'date' => '2025-11-10',
    'time' => '10:00 AM',
    'type' => 'Prenatal Checkup'
];

$result = $emailService->sendAppointmentConfirmation(
    'test@example.com',  // Replace with your email for testing
    'Jane Doe',
    $appointment_details
);

if ($result['success']) {
    echo "✓ Appointment confirmation sent!\n";
} else {
    echo "✗ Failed to send appointment confirmation\n";
    echo "  Error: {$result['message']}\n";
}

echo "\n";

// Test 3: Welcome email
echo "Test 3: Sending welcome email...\n";
$result = $emailService->sendWelcomeEmail(
    'test@example.com',  // Replace with your email for testing
    'John Smith',
    'C001'
);

if ($result['success']) {
    echo "✓ Welcome email sent!\n";
} else {
    echo "✗ Failed to send welcome email\n";
    echo "  Error: {$result['message']}\n";
}

echo "\n=== Test Complete ===\n";
