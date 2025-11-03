<?php
/**
 * Email Template Test & Integration Guide
 * This script demonstrates how to use the new email templates
 */

// Load the services
require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/EmailTemplateEngine.php';

$emailService = new EmailService();
$templateEngine = new EmailTemplateEngine();

echo "=== Paanakan Email Template Testing Suite ===\n\n";

// Test 1: Welcome Email
echo "1. Testing Welcome Email Template\n";
echo "-----------------------------------\n";
$welcome_result = $emailService->sendWelcomeEmail(
    'paanakansacalapan090@gmail.com',
    'John Paul Baes',
    'C006'
);
echo json_encode($welcome_result, JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Appointment Confirmation
echo "2. Testing Appointment Confirmation Template\n";
echo "---------------------------------------------\n";
$appointment_data = [
    'scheduled_date' => 'June 15, 2025',
    'time' => '10:00 AM',
    'appointment_type' => 'Pre-Natal Checkup',
    'location' => 'Paanakan sa Calapan Clinic',
    'case_id' => 'C006',
    'doctor' => 'Dr. Maria Santos',
    'instructions' => 'Please arrive 10 minutes early and bring your medical records.'
];
$appointment_result = $emailService->sendAppointmentConfirmation(
    'paanakansacalapan090@gmail.com',
    'John Paul Baes',
    $appointment_data
);
echo json_encode($appointment_result, JSON_PRETTY_PRINT) . "\n\n";

// Test 3: Password Reset
echo "3. Testing Password Reset Template\n";
echo "-----------------------------------\n";
$reset_result = $emailService->sendPasswordReset(
    'paanakansacalapan090@gmail.com',
    'John Paul Baes',
    'https://paanakan.com/reset-password?token=abc123def456'
);
echo json_encode($reset_result, JSON_PRETTY_PRINT) . "\n\n";

// Test 4: Appointment Reminder
echo "4. Testing Appointment Reminder Template\n";
echo "----------------------------------------\n";
$reminder_result = $emailService->sendAppointmentReminder(
    'paanakansacalapan090@gmail.com',
    'John Paul Baes',
    [
        'scheduled_date' => 'June 15, 2025',
        'time' => '10:00 AM',
        'appointment_type' => 'Pre-Natal Checkup',
        'location' => 'Paanakan sa Calapan Clinic'
    ]
);
echo json_encode($reminder_result, JSON_PRETTY_PRINT) . "\n\n";

// Test 5: Appointment Cancellation
echo "5. Testing Appointment Cancellation Template\n";
echo "--------------------------------------------\n";
$cancellation_result = $emailService->sendAppointmentCancellation(
    'paanakansacalapan090@gmail.com',
    'John Paul Baes',
    [
        'scheduled_date' => 'June 15, 2025',
        'appointment_type' => 'Pre-Natal Checkup'
    ]
);
echo json_encode($cancellation_result, JSON_PRETTY_PRINT) . "\n\n";

echo "=== All Tests Complete ===\n";
?>
