# SendGrid Setup - Manual Installation Guide

## Current Status

✅ SendGrid API key added to `.env`  
✅ `composer.json` updated with SendGrid dependencies  
✅ `EmailService.php` created with ready-to-use email functions  
✅ Test script created  
✅ CA certificate downloaded to `certs/cacert.pem`  

❌ Composer packages need to be installed (SSL certificate issue)

## Fix SSL Certificate Issue

### Option 1: Update php.ini (Recommended)

1. Open `C:\xampp\php\php.ini` in a text editor **as Administrator**

2. Find and update (or add) these lines:
   ```ini
   curl.cainfo = "C:/xampp/htdocs/paanakan/certs/cacert.pem"
   openssl.cafile = "C:/xampp/htdocs/paanakan/certs/cacert.pem"
   ```

3. Save the file

4. Restart Apache if running

5. Run:
   ```bash
   composer install
   ```

### Option 2: Manual Package Download

If Option 1 doesn't work, manually download the packages:

1. Download SendGrid PHP library:
   - Go to: https://github.com/sendgrid/sendgrid-php/releases
   - Download the latest release ZIP
   - Extract to `vendor/sendgrid/sendgrid/`

2. Download phpdotenv:
   - Go to: https://github.com/vlucas/phpdotenv/releases
   - Download the latest release ZIP
   - Extract to `vendor/vlucas/phpdotenv/`

3. Create `vendor/autoload.php` manually (or use the simplified version below)

### Option 3: Simplified Version Without Composer

I can create a simplified EmailService that doesn't require Composer dependencies. Would you like me to do that?

## Quick Test (After Installation)

```bash
php connections/test_sendgrid.php
```

**Important:** Update the email addresses in `connections/test_sendgrid.php` before testing!

## Usage in Your Code

### Example 1: Send Welcome Email After Registration

In `register_info.php`, after successful registration:

```php
// After successful patient insert and commit
require_once __DIR__ . '/connections/EmailService.php';

try {
    $emailService = new EmailService();
    $emailService->sendWelcomeEmail(
        $email_to_store,
        $first_name . ' ' . $last_name,
        $case_id
    );
} catch (Exception $e) {
    // Log error but don't block registration
    error_log('Failed to send welcome email: ' . $e->getMessage());
}
```

### Example 2: Send Appointment Confirmation

```php
require_once __DIR__ . '/../connections/EmailService.php';

$emailService = new EmailService();

$appointment_details = [
    'date' => $appointment_date,
    'time' => $appointment_time,
    'type' => $appointment_type
];

$result = $emailService->sendAppointmentConfirmation(
    $patient_email,
    $patient_name,
    $appointment_details
);

if ($result['success']) {
    // Show toast: "Appointment confirmed and email sent!"
} else {
    // Show toast: "Appointment confirmed!"
    // Log error for admin review
    error_log('Email send failed: ' . $result['message']);
}
```

## Troubleshooting

### "Class 'SendGrid\Mail\Mail' not found"
- Composer packages are not installed
- Run `composer install` after fixing SSL certificate issue
- Or use the simplified version (Option 3 above)

### "SENDGRID_API_KEY not found"
- Make sure `.env` file exists in the project root
- Check that the API key is correctly set in `.env`

### Email not received
- Check spam folder
- Verify sender email in SendGrid dashboard
- Check SendGrid activity logs: https://app.sendgrid.com/email_activity

### Still having SSL issues?
Let me know and I'll create a version that doesn't require Composer!

## Next Steps

1. Fix the SSL certificate issue using Option 1 above
2. Run `composer install`
3. Test with `php connections/test_sendgrid.php`
4. Integrate into your registration and appointment flows
5. Monitor SendGrid dashboard for delivery stats

## Need Help?

If you're still having issues, I can:
- Create a Composer-free version of EmailService
- Help integrate it into specific pages
- Set up additional email templates
- Configure email notifications for other events

Just let me know what you need!
