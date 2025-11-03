# Email Template Integration Checklist

## Summary

Professional HTML email templates have been created for Paanakan sa Calapan. Follow this checklist to integrate them into your project.

---

## ✅ Templates Created

- [x] **Welcome Email** - Sent after user registration
  - File: `connections/EmailTemplateEngine.php::getWelcomeEmailTemplate()`
  - Variables: name, case_id, email
  - Status: Ready to integrate

- [x] **Appointment Confirmation** - Sent after appointment scheduled
  - File: `connections/EmailTemplateEngine.php::getAppointmentConfirmationTemplate()`
  - Variables: date, time, type, location, case_id, doctor
  - Status: Ready to integrate

- [x] **Password Reset** - Sent when user requests reset
  - File: `connections/EmailTemplateEngine.php::getPasswordResetTemplate()`
  - Variables: name, reset_link, expiry_hours
  - Status: Ready to integrate

- [x] **Appointment Reminder** - Sent 24 hours before appointment
  - File: `connections/EmailTemplateEngine.php::getAppointmentReminderTemplate()`
  - Variables: date, time, type, location
  - Status: Ready to integrate (requires cron setup)

- [x] **Appointment Cancellation** - Sent when appointment is cancelled
  - File: `connections/EmailTemplateEngine.php::getAppointmentCancellationTemplate()`
  - Variables: date, type
  - Status: Ready to integrate

---

## 📋 Files Created/Modified

- [x] `connections/EmailTemplateEngine.php` - **NEW** - Template generator (650+ lines)
- [x] `connections/EmailService.php` - **MODIFIED** - Added template integration (5 methods)
- [x] `connections/test_email_templates.php` - **NEW** - Testing script
- [x] `EMAIL_TEMPLATE_INTEGRATION.md` - **NEW** - Integration documentation

---

## 🔧 Setup Tasks

### 1. Verify Prerequisites

- [ ] Verify `.env` has `SENDGRID_API_KEY` configured
- [ ] Verify `certs/cacert.pem` exists
- [ ] Verify `EmailService.php` loads without errors

Run test:
```bash
cd c:\xampp\htdocs\paanakan\connections
php test_email_templates.php
```

### 2. Update Registration Flow (`register.php`)

**Location:** After user account is created successfully

```php
require_once 'connections/EmailService.php';
$emailService = new EmailService();
$emailService->sendWelcomeEmail($user_email, $user_name, $case_id);
```

- [ ] Add email send code to `register.php` after account creation
- [ ] Test with a real registration
- [ ] Verify email arrives in inbox

### 3. Update Appointment Booking (`process_appointment.php`)

**Location:** After appointment is inserted into database

```php
require_once 'connections/EmailService.php';
$emailService = new EmailService();
$emailService->sendAppointmentConfirmation($patient_email, $patient_name, $appointment_data);
```

- [ ] Add email send code to `process_appointment.php` after INSERT
- [ ] Prepare appointment_details array with required fields
- [ ] Test by creating an appointment
- [ ] Verify confirmation email arrives

### 4. Update Password Reset (`forgot_password.php`)

**Location:** Replace current HTML link display

```php
// Replace this:
// echo "Click here to reset: <a href='$reset_link'>Reset</a>";

// With this:
require_once 'connections/EmailService.php';
$emailService = new EmailService();
$emailService->sendPasswordReset($user_email, $user_name, $reset_link);
echo "Password reset email has been sent to your email address.";
```

- [ ] Remove HTML link display from `forgot_password.php`
- [ ] Add email sending code
- [ ] Test forgot password flow
- [ ] Verify reset email arrives with working link

### 5. Setup Appointment Reminders (Optional)

**Create new file:** `admin/send_appointment_reminders.php`

See integration guide for full code example.

- [ ] Create the reminder script
- [ ] Test script manually (run in terminal)
- [ ] Setup Windows Task Scheduler to run daily at 9 AM
  - Task: `php C:\xampp\htdocs\paanakan\admin\send_appointment_reminders.php`
  - Schedule: Daily at 09:00

### 6. Update Appointment Cancellation Handler

**Location:** Where appointment status is changed to cancelled

```php
if ($new_status == 'Cancelled') {
    require_once 'connections/EmailService.php';
    $emailService = new EmailService();
    $emailService->sendAppointmentCancellation($patient_email, $patient_name, $appointment_data);
}
```

- [ ] Find cancellation handler code
- [ ] Add email sending logic
- [ ] Test by cancelling an appointment
- [ ] Verify cancellation email arrives

---

## 🧪 Testing Checklist

### Unit Testing

- [ ] Run `php connections/test_email_templates.php`
- [ ] All 5 templates show "success": true in output
- [ ] Status codes are 202

### Integration Testing

- [ ] Register new user → Welcome email arrives
- [ ] Create appointment → Confirmation email arrives
- [ ] Request password reset → Reset email arrives
- [ ] Cancel appointment → Cancellation email arrives

### Email Quality Checks

- [ ] Emails render correctly in Gmail
- [ ] Emails render correctly in Outlook
- [ ] Emails render correctly on mobile (check responsive)
- [ ] All links are clickable and working
- [ ] Images/colors display properly

### Data Accuracy Checks

- [ ] Patient name correct in email
- [ ] Appointment date correct
- [ ] Appointment time correct
- [ ] Case ID correct
- [ ] Reset link valid and expires properly

---

## 📊 Expected Results

### Welcome Email
- **Send Trigger:** Registration step 1 complete
- **Recipient:** User email from registration
- **Expected Content:**
  - ✓ User first name greeting
  - ✓ Case ID displayed prominently
  - ✓ Login link functional
  - ✓ Tips for using platform

### Appointment Confirmation
- **Send Trigger:** After appointment created
- **Recipient:** Patient email
- **Expected Content:**
  - ✓ Appointment date and time
  - ✓ Appointment type
  - ✓ Case ID
  - ✓ Instructions to arrive early
  - ✓ Contact info for changes

### Password Reset
- **Send Trigger:** User clicks "Forgot Password"
- **Recipient:** User email
- **Expected Content:**
  - ✓ Reset link (1-hour expiry)
  - ✓ Security warnings
  - ✓ No reply instructions
  - ✓ Contact support info

### Appointment Reminder
- **Send Trigger:** Daily cron job (24 hours before appointment)
- **Recipient:** Patient with appointment tomorrow
- **Expected Content:**
  - ✓ "Appointment is tomorrow" message
  - ✓ Time and location
  - ✓ Cancellation deadline (24 hours)

### Appointment Cancellation
- **Send Trigger:** Admin/Patient cancels appointment
- **Recipient:** Patient email
- **Expected Content:**
  - ✓ Cancellation confirmation
  - ✓ Original appointment details
  - ✓ Reschedule option
  - ✓ Refund/credit note

---

## 🐛 Troubleshooting

### Emails Not Sending

**Step 1:** Check `.env` configuration
```bash
# Verify the file exists and is readable
file c:\xampp\htdocs\paanakan\.env
```

**Step 2:** Run test script
```bash
php c:\xampp\htdocs\paanakan\connections\test_email_templates.php
```

**Step 3:** Check SendGrid dashboard
- Log in to SendGrid.com
- Check Activity Log for bounce/drop errors
- Verify API key is valid

**Step 4:** Enable PHP error logging
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Check error log for curl/SSL issues
```

### Template Not Rendering

```bash
# Check for syntax errors
php -l c:\xampp\htdocs\paanakan\connections\EmailTemplateEngine.php
```

Should output: "No syntax errors detected"

### Wrong Sender Email

Update `.env`:
```
SENDGRID_FROM_EMAIL=your-verified-email@paanakan.com
SENDGRID_FROM_NAME=Paanakan sa Calapan
```

### Links Not Working

Verify reset/appointment links point to correct domains:
```php
// Should be full, working URLs
'https://paanakan.com/reset-password?token=abc123'
'https://paanakan.com/appointments'
```

---

## 📞 Contact & Support

If you need to:
- **Customize colors/branding** → Edit `EmailTemplateEngine.php` class properties
- **Add new template** → Create new method in `EmailTemplateEngine.php`
- **Change from email** → Update `.env` `SENDGRID_FROM_EMAIL`
- **Troubleshoot issues** → See integration guide detailed debugging section

---

## 🎯 Timeline Estimate

- **Setup & Testing:** 30 minutes
- **Register.php integration:** 10 minutes
- **process_appointment.php integration:** 15 minutes
- **forgot_password.php integration:** 10 minutes
- **Full testing:** 30 minutes
- **Cron setup (optional):** 10 minutes

**Total: ~1.5 hours for full integration**

---

## ✅ Sign-off Checklist

- [ ] All templates tested with `test_email_templates.php`
- [ ] Registration sends welcome email
- [ ] Appointment creation sends confirmation email
- [ ] Password reset sends reset email
- [ ] Cancellation sends cancellation email
- [ ] All emails display correctly on mobile
- [ ] Documentation reviewed and understood
- [ ] Team members trained on new email system

**Integration Status:** ⏳ Ready to implement

---

*Last Updated: June 2025*
*Templates Version: 1.0*
*SendGrid Integration: Active*
