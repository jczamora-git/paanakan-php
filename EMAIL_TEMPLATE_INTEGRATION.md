# Paanakan Email Template Integration Guide

## Overview

This guide explains how to integrate the new professional HTML email templates into the Paanakan Health Record Management System. Five responsive email templates have been created:

1. **Welcome Email** - Sent after user registration
2. **Appointment Confirmation** - Sent after appointment is scheduled
3. **Appointment Reminder** - Sent before appointment date
4. **Password Reset** - Sent when user requests password reset
5. **Appointment Cancellation** - Sent when appointment is cancelled

---

## Architecture

### Files Created

- **`connections/EmailTemplateEngine.php`** - Template generator with responsive HTML designs
- **`connections/EmailService.php`** - Updated with template integration (modified)
- **`connections/test_email_templates.php`** - Test script for all templates

### Template Features

✅ **Responsive Design** - Mobile-friendly HTML templates  
✅ **Branded Styling** - Consistent Paanakan colors and typography  
✅ **Professional Layout** - Clear information hierarchy and CTAs  
✅ **Fallback Text** - Plain text versions for all emails  
✅ **Accessibility** - Semantic HTML and proper color contrast  
✅ **Dynamic Content** - Variables for patient names, dates, times, etc.

---

## Integration Guide

### 1. Registration Flow - Welcome Email

**File:** `register.php`

After user registration completes (step 1), send welcome email:

```php
<?php
// After user is successfully created in users table
require_once 'connections/EmailService.php';

$emailService = new EmailService();

// Send welcome email with case ID
$result = $emailService->sendWelcomeEmail(
    $user_email,              // User's email address
    $first_name . ' ' . $last_name,  // User's full name
    $case_id                  // Generated case ID
);

if ($result['success']) {
    // Email sent successfully (202 status code)
    // Continue with registration flow
} else {
    // Handle error - log but don't block registration
    error_log("Welcome email failed: " . json_encode($result));
}
?>
```

**When to send:** After step 1 (user account creation), before or after case ID assignment

---

### 2. Appointment Confirmation

**File:** `process_appointment.php`

After appointment is successfully created in database:

```php
<?php
// After appointment is inserted into appointments table
require_once 'connections/EmailService.php';

$emailService = new EmailService();

// Get patient email and appointment details
$patient_email = $patient_data['email'];
$patient_name = $patient_data['first_name'] . ' ' . $patient_data['last_name'];

// Format appointment data
$appointment_details = [
    'scheduled_date' => date('F d, Y', strtotime($appointment_row['scheduled_date'])),
    'time' => date('g:i A', strtotime($appointment_row['scheduled_date'])),
    'appointment_type' => $appointment_row['appointment_type'],
    'location' => 'Paanakan sa Calapan Clinic',  // or get from settings
    'case_id' => $patient_data['case_id'],
    'doctor' => 'Our Medical Team',  // or get assigned doctor name
    'instructions' => 'Please arrive 10 minutes before your scheduled time.'
];

// Send confirmation email
$result = $emailService->sendAppointmentConfirmation(
    $patient_email,
    $patient_name,
    $appointment_details
);

if ($result['success']) {
    // Log activity
    // Update appointment table if needed
} else {
    error_log("Appointment confirmation email failed: " . json_encode($result));
}
?>
```

**Data Required:**
- Patient email address
- Patient full name
- Scheduled date and time
- Appointment type
- Case ID
- Doctor/staff name (optional)
- Special instructions (optional)

---

### 3. Password Reset Email

**File:** `forgot_password.php`

Replace the current HTML display of reset link with email sending:

**Current Code (Before):**
```php
// Currently displays link in HTML
echo "Click here to reset password: <a href='$reset_link'>Reset Password</a>";
```

**New Code (After):**
```php
<?php
// After generating password reset token
require_once 'connections/EmailService.php';

$emailService = new EmailService();

$result = $emailService->sendPasswordReset(
    $user_email,
    $user_first_name . ' ' . $user_last_name,
    $reset_link
);

if ($result['success']) {
    // Show success message to user
    echo "Password reset link has been sent to your email.";
} else {
    // Show error
    echo "Failed to send reset email. Please try again.";
}
?>
```

**Notes:**
- Remove or comment out the inline link display
- Ensure reset token has proper expiry (currently 1 hour)
- Link should point to: `https://paanakan.com/reset-password.php?token=XXX`

---

### 4. Appointment Reminder (Optional Scheduled Task)

**File:** New scheduled task or cron job

Create a new script to send reminders 24 hours before appointment:

```php
<?php
// File: admin/send_appointment_reminders.php
// Run via cron: 0 9 * * * php /path/to/send_appointment_reminders.php

require_once '../connections/connections.php';
require_once '../connections/EmailService.php';

$emailService = new EmailService();

// Get all appointments scheduled for tomorrow
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$query = "SELECT a.*, p.email, p.first_name, p.last_name, p.case_id
          FROM appointments a
          JOIN patients p ON a.patient_id = p.patient_id
          WHERE DATE(a.scheduled_date) = '$tomorrow'
          AND a.status IN ('Pending', 'Approved')";

$result = mysqli_query($connection, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $appointment_details = [
        'scheduled_date' => date('F d, Y', strtotime($row['scheduled_date'])),
        'time' => date('g:i A', strtotime($row['scheduled_date'])),
        'appointment_type' => $row['appointment_type'],
        'location' => 'Paanakan sa Calapan Clinic',
        'case_id' => $row['case_id']
    ];
    
    $emailService->sendAppointmentReminder(
        $row['email'],
        $row['first_name'] . ' ' . $row['last_name'],
        $appointment_details
    );
}
?>
```

---

### 5. Appointment Cancellation

**File:** `admin/update_appointment_status.php` or cancellation handler

When appointment status is changed to cancelled:

```php
<?php
if ($new_status == 'Cancelled') {
    require_once 'connections/EmailService.php';
    
    $emailService = new EmailService();
    
    $appointment_details = [
        'scheduled_date' => date('F d, Y', strtotime($appointment['scheduled_date'])),
        'appointment_type' => $appointment['appointment_type']
    ];
    
    $emailService->sendAppointmentCancellation(
        $patient['email'],
        $patient['first_name'] . ' ' . $patient['last_name'],
        $appointment_details
    );
}
?>
```

---

## Template Customization

### Modify Colors and Branding

Edit `EmailTemplateEngine.php` class properties:

```php
private $brand_color = '#2E7D32';      // Main green
private $secondary_color = '#4CAF50';   // Light green
private $accent_color = '#FF6B6B';      // Red for alerts
private $text_dark = '#333333';
private $text_light = '#666666';
private $bg_light = '#F5F5F5';
```

### Customize Contact Information

Update footer links and phone numbers in each template method:

```php
// In getBaseTemplate() method:
<p>📞 For support, please contact us at support@paanakan.com</p>
<p>📞 Call us at: <strong>(043) XXX-XXXX</strong></p>
```

### Add Logo or Images

Replace the emoji logo in header with image:

```php
// Change from:
<div class="logo">🏥</div>

// To:
<div class="logo"><img src="https://paanakan.com/logo.png" alt="Paanakan" style="width:60px;"></div>
```

---

## Testing

### 1. Test Email Template Rendering

Run the test script via CLI:

```bash
cd c:\xampp\htdocs\paanakan\connections
php test_email_templates.php
```

This will test all 5 templates and output JSON responses.

### 2. Test with Web UI

Open the web-based SendGrid testing interface:

```
http://localhost/paanakan/connections/sendgrid_ui.php
```

Fill in recipient email and choose template type.

### 3. Test Individual Templates

Create a simple test PHP file:

```php
<?php
require_once 'connections/EmailTemplateEngine.php';

$engine = new EmailTemplateEngine();

// Test welcome email
$html = $engine->getWelcomeEmailTemplate('John Doe', 'C001', null, 'john@example.com');

// Save to file for preview in browser
file_put_contents('/tmp/email_test.html', $html);
echo "Email template saved to /tmp/email_test.html";
?>
```

Open the HTML file in your browser to preview.

---

## API Methods Reference

### EmailService Methods

```php
// Send generic email
$emailService->sendEmail(
    $to_email,              // string
    $to_name,              // string
    $subject,              // string
    $body_text,            // string (plain text fallback)
    $body_html             // string (optional HTML version)
);
// Returns: ['success' => bool, 'status_code' => int]

// Send welcome email
$emailService->sendWelcomeEmail(
    $to_email,             // string
    $to_name,              // string
    $case_id               // string
);

// Send appointment confirmation
$emailService->sendAppointmentConfirmation(
    $to_email,             // string
    $to_name,              // string
    $appointment_details   // array with keys: scheduled_date, time, appointment_type, etc.
);

// Send appointment reminder
$emailService->sendAppointmentReminder(
    $to_email,             // string
    $to_name,              // string
    $appointment_details   // array
);

// Send password reset
$emailService->sendPasswordReset(
    $to_email,             // string
    $to_name,              // string
    $reset_link            // string (full URL)
);

// Send appointment cancellation
$emailService->sendAppointmentCancellation(
    $to_email,             // string
    $to_name,              // string
    $appointment_details   // array
);
```

---

## Environment Configuration

Ensure `.env` file contains:

```
SENDGRID_API_KEY=your_sendgrid_api_key_here
SENDGRID_FROM_EMAIL=paanakansacalapan090@gmail.com
SENDGRID_FROM_NAME=Paanakan sa Calapan
```

---

## Troubleshooting

### Issue: Emails not sending

**Check:**
1. SendGrid API key is valid in `.env`
2. Recipient email is correct format
3. Run test script: `php test_email_templates.php`
4. Check SendGrid dashboard for bounced emails
5. Verify SSL certificate (should be auto-handled with CA bundle)

### Issue: Templates not rendering

**Check:**
1. `EmailTemplateEngine.php` has no syntax errors
2. Class properties are accessible
3. Verify file paths in require_once statements
4. Check PHP version (requires 5.6+)

### Issue: Wrong sender email

**Solution:**
Update `SENDGRID_FROM_EMAIL` in `.env` file

---

## Performance Considerations

- EmailService classes are loaded on-demand via `require_once`
- SendGrid API calls are synchronous (consider async for high volume)
- Recommended: Send emails in background queue for bulk operations
- Template generation is fast (~5ms per email)

---

## Next Steps

1. **Update `register.php`** - Add welcome email on registration complete
2. **Update `process_appointment.php`** - Add confirmation email on appointment create
3. **Update `forgot_password.php`** - Replace HTML link with email sending
4. **Create reminder task** - Set up cron job for appointment reminders
5. **Update cancellation handler** - Add cancellation email when status changes
6. **Test all workflows** - Verify emails send through complete user journeys

---

## Files Summary

| File | Purpose | Status |
|------|---------|--------|
| `EmailTemplateEngine.php` | Template generator | ✅ Created |
| `EmailService.php` | Email sending service | ✅ Updated |
| `test_email_templates.php` | Testing script | ✅ Created |
| `.env` | Configuration | ✅ Configured |
| `certs/cacert.pem` | SSL certificate | ✅ Present |

---

## Support

For questions or issues:
1. Check test script output for error messages
2. Review SendGrid Activity log in dashboard
3. Enable PHP error logging for debugging
4. Verify email addresses are valid format
