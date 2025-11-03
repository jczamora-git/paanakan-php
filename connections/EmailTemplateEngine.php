<?php
/**
 * EmailTemplateEngine - Professional HTML Email Template Generator
 * Provides responsive, branded email templates for Paanakan sa Calapan
 */

class EmailTemplateEngine {
    private $brand_color = '#2E7D32'; // Paanakan green
    private $secondary_color = '#4CAF50';
    private $accent_color = '#FF6B6B';
    private $text_dark = '#333333';
    private $text_light = '#666666';
    private $bg_light = '#F5F5F5';

    /**
     * Base email template wrapper with responsive design
     */
    private function getBaseTemplate($content, $title = 'Paanakan sa Calapan') {
        $year = date('Y');
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: {$this->text_dark};
            background-color: {$this->bg_light};
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, {$this->brand_color} 0%, {$this->secondary_color} 100%);
            color: #ffffff;
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        .logo {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px 30px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            font-size: 22px;
            color: {$this->brand_color};
            margin-bottom: 15px;
            border-bottom: 2px solid {$this->secondary_color};
            padding-bottom: 10px;
        }
        .section p {
            color: {$this->text_light};
            margin-bottom: 12px;
            line-height: 1.7;
        }
        .case-id-box, .details-box {
            background-color: {$this->bg_light};
            border-left: 4px solid {$this->brand_color};
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .case-id-box strong, .details-box strong {
            color: {$this->brand_color};
            font-size: 16px;
        }
        .case-id-box .value, .details-box .value {
            font-size: 18px;
            font-weight: bold;
            color: {$this->text_dark};
            margin-top: 5px;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, {$this->brand_color} 0%, {$this->secondary_color} 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 12px 40px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(46, 125, 50, 0.3);
        }
        .btn-secondary {
            background: #666666;
        }
        .warning-box {
            background-color: #FFF3CD;
            border: 1px solid #FFE69C;
            border-left: 4px solid #FFC107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            color: #856404;
        }
        .warning-box strong {
            color: #856404;
        }
        .appointment-details {
            background-color: {$this->bg_light};
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }
        .appointment-details-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }
        .appointment-details-row:last-child {
            border-bottom: none;
        }
        .appointment-details-label {
            font-weight: 600;
            color: {$this->brand_color};
            width: 40%;
        }
        .appointment-details-value {
            color: {$this->text_dark};
            font-weight: 500;
        }
        .footer {
            background-color: {$this->bg_light};
            padding: 25px 30px;
            text-align: center;
            border-top: 1px solid #ddd;
            font-size: 13px;
            color: {$this->text_light};
        }
        .footer p {
            margin-bottom: 10px;
        }
        .footer-links {
            margin: 15px 0;
        }
        .footer-links a {
            color: {$this->brand_color};
            text-decoration: none;
            margin: 0 10px;
            font-weight: 600;
        }
        .social-links {
            margin-top: 15px;
        }
        .social-links a {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            background-color: {$this->brand_color};
            color: white;
            border-radius: 50%;
            text-align: center;
            text-decoration: none;
            margin: 0 5px;
            font-size: 14px;
        }
        .highlight {
            color: {$this->brand_color};
            font-weight: 600;
        }
        .text-center {
            text-align: center;
        }
        .mt-20 {
            margin-top: 20px;
        }
        .mb-20 {
            margin-bottom: 20px;
        }
        @media (max-width: 600px) {
            .email-container { border-radius: 0; }
            .content { padding: 20px 15px; }
            .header { padding: 30px 15px; }
            .header h1 { font-size: 22px; }
            .section h2 { font-size: 18px; }
            .appointment-details-row { flex-direction: column; }
            .appointment-details-label { width: 100%; margin-bottom: 5px; }
            .btn { padding: 10px 30px; font-size: 14px; }
        }
    </style>
</head>
<body>
    <div class="email-container">
        {$content}
        <div class="footer">
            <p><strong>Paanakan sa Calapan</strong></p>
            <p>Health Record Management System</p>
            <p>📞 For support, please contact us at support@paanakan.com</p>
            <div class="footer-links">
                <a href="https://paanakan.com">Visit Website</a>
                <a href="https://paanakan.com/help">Help Center</a>
                <a href="https://paanakan.com/privacy">Privacy Policy</a>
            </div>
            <div class="social-links">
                <a href="#">f</a>
                <a href="#">tw</a>
                <a href="#">ig</a>
            </div>
            <p style="margin-top: 20px; font-size: 11px; color: #999;">
                © {$year} Paanakan sa Calapan. All rights reserved.<br/>
                This is an automated email. Please do not reply directly to this message.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Welcome Email Template - sent after user registration
     */
    public function getWelcomeEmailTemplate($user_name, $case_id, $login_url = null, $email = '') {
        $first_name = explode(' ', trim($user_name))[0];
        if (!$login_url) $login_url = 'https://paanakan.com/login';
        if (!$email) $email = 'Your email address';

        $content = <<<HTML
<div class="header">
    <div class="logo">🏥</div>
    <h1>Welcome to Paanakan</h1>
    <p>Your Health Record Management System</p>
</div>

<div class="content">
    <div class="section">
        <h2>Welcome, {$first_name}!</h2>
        <p>We're excited to have you join the Paanakan sa Calapan family. Your account has been successfully created and is ready to use.</p>
    </div>

    <div class="section">
        <h2>Your Case ID</h2>
        <div class="case-id-box">
            <strong>Your unique Case ID:</strong>
            <div class="value">{$case_id}</div>
            <p style="margin-top: 10px; font-size: 13px; color: {$this->text_light};">Please save this ID for your records. You'll use it to track your appointments and health records.</p>
        </div>
    </div>

    <div class="section">
        <h2>Getting Started</h2>
        <p>Here's what you can do with your Paanakan account:</p>
        <ul style="margin-left: 20px; color: {$this->text_light}; line-height: 1.8;">
            <li>Schedule and manage your appointments</li>
            <li>View your health records and test results</li>
            <li>Track your medical history</li>
            <li>Receive appointment reminders</li>
            <li>Manage your medical documents securely</li>
        </ul>
    </div>

    <div class="button-container">
        <a href="{$login_url}" class="btn">Log in to Your Account</a>
    </div>

    <div class="section">
        <h2>Account Details</h2>
        <div class="details-box">
            <p><strong>Name:</strong> {$user_name}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Status:</strong> <span class="highlight">Active</span></p>
        </div>
    </div>

    <div class="section" style="background-color: #E8F5E9; padding: 15px; border-radius: 6px; border-left: 4px solid #2E7D32;">
        <p><strong>💡 Tip:</strong> Keep your Case ID ({$case_id}) handy when you visit the clinic. It helps our staff quickly access your records.</p>
    </div>

    <div class="section">
        <p style="color: {$this->text_light}; font-size: 14px;">If you have any questions or need assistance, please don't hesitate to contact our support team. We're here to help!</p>
    </div>
</div>
HTML;

        return $this->getBaseTemplate($content, 'Welcome to Paanakan sa Calapan');
    }

    /**
     * Appointment Confirmation Email Template
     */
    public function getAppointmentConfirmationTemplate($patient_name, $appointment_data) {
        $first_name = explode(' ', trim($patient_name))[0];
        $date = $appointment_data['scheduled_date'] ?? 'TBD';
        $time = $appointment_data['time'] ?? 'TBD';
        $type = $appointment_data['appointment_type'] ?? 'General Checkup';
        $location = $appointment_data['location'] ?? 'Paanakan sa Calapan Clinic';
        $case_id = $appointment_data['case_id'] ?? '';
        $doctor = $appointment_data['doctor'] ?? 'Our Medical Team';
        $instructions = $appointment_data['instructions'] ?? 'Please arrive 10 minutes before your scheduled time.';

        $content = <<<HTML
<div class="header">
    <div class="logo">📅</div>
    <h1>Appointment Confirmed</h1>
    <p>Your appointment is scheduled</p>
</div>

<div class="content">
    <div class="section">
        <h2>Hello {$first_name}!</h2>
        <p>Your appointment with Paanakan sa Calapan has been confirmed. Please review the details below.</p>
    </div>

    <div class="section">
        <h2>Appointment Details</h2>
        <div class="appointment-details">
            <div class="appointment-details-row">
                <span class="appointment-details-label">📅 Date:</span>
                <span class="appointment-details-value">{$date}</span>
            </div>
            <div class="appointment-details-row">
                <span class="appointment-details-label">⏰ Time:</span>
                <span class="appointment-details-value">{$time}</span>
            </div>
            <div class="appointment-details-row">
                <span class="appointment-details-label">🏥 Type:</span>
                <span class="appointment-details-value">{$type}</span>
            </div>
            <div class="appointment-details-row">
                <span class="appointment-details-label">📍 Location:</span>
                <span class="appointment-details-value">{$location}</span>
            </div>
            <div class="appointment-details-row">
                <span class="appointment-details-label">👨‍⚕️ With:</span>
                <span class="appointment-details-value">{$doctor}</span>
            </div>
            <div class="appointment-details-row">
                <span class="appointment-details-label">📋 Case ID:</span>
                <span class="appointment-details-value"><span class="highlight">{$case_id}</span></span>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Important Instructions</h2>
        <div class="warning-box">
            <strong>⚠️ Please Note:</strong>
            <p style="margin-top: 10px;">{$instructions}</p>
        </div>
        <ul style="margin-left: 20px; color: {$this->text_light}; line-height: 1.8; margin-top: 15px;">
            <li>Bring your government-issued ID and insurance card (if applicable)</li>
            <li>List any current medications you're taking</li>
            <li>Prepare a list of questions or concerns to discuss</li>
            <li>Inform us of any allergies or medical conditions</li>
        </ul>
    </div>

    <div class="section">
        <h2>Need to Make Changes?</h2>
        <div class="details-box">
            <p style="margin-bottom: 10px;">If you need to reschedule or cancel your appointment:</p>
            <ul style="margin-left: 20px; color: {$this->text_light};">
                <li>Call us at: <strong>(043) XXX-XXXX</strong></li>
                <li>Email: <strong>appointments@paanakan.com</strong></li>
                <li>Message us through the patient portal</li>
            </ul>
            <p style="margin-top: 15px; font-size: 13px; color: #FF6B6B;"><strong>Note:</strong> Please provide at least 24 hours notice if you need to reschedule.</p>
        </div>
    </div>

    <div class="button-container">
        <a href="https://paanakan.com/appointments" class="btn">View My Appointments</a>
    </div>

    <div class="section" style="background-color: #E3F2FD; padding: 15px; border-radius: 6px; border-left: 4px solid #2196F3;">
        <p style="color: #1565C0;"><strong>💬 Reminder:</strong> We may send you a reminder message 24 hours before your appointment.</p>
    </div>
</div>
HTML;

        return $this->getBaseTemplate($content, 'Appointment Confirmed - Paanakan sa Calapan');
    }

    /**
     * Appointment Scheduled (Pending Approval) Email Template
     */
    public function getAppointmentScheduledTemplate($patient_name, $appointment_data) {
        // Reuse most of the confirmation template but adjust header and intro to indicate pending approval
        $first_name = explode(' ', trim($patient_name))[0];
        $date = $appointment_data['scheduled_date'] ?? 'TBD';
        $time = $appointment_data['time'] ?? 'TBD';
        $type = $appointment_data['appointment_type'] ?? 'General Checkup';
        $location = $appointment_data['location'] ?? 'Paanakan sa Calapan Clinic';
        $case_id = $appointment_data['case_id'] ?? '';
        $doctor = $appointment_data['doctor'] ?? 'Our Medical Team';
        $instructions = $appointment_data['instructions'] ?? 'Please arrive 10 minutes before your scheduled time.';

        $content = <<<HTML
<div class="header">
    <div class="logo">📅</div>
    <h1>Appointment Scheduled</h1>
    <p>Your appointment has been submitted</p>
</div>

<div class="content">
    <div class="section">
        <h2>Hello {$first_name}!</h2>
        <p>Thank you for using Paanakan sa Calapan. Your appointment request has been received and is currently <strong>pending approval</strong> by our clinic administrator. We'll notify you once it's approved.</p>
    </div>

    <div class="section">
        <h2>Appointment Details</h2>
        <div class="appointment-details">
            <div class="appointment-details-row">
                <span class="appointment-details-label">📅 Date:</span>
                <span class="appointment-details-value">{$date}</span>
            </div>
            <div class="appointment-details-row">
                <span class="appointment-details-label">⏰ Time:</span>
                <span class="appointment-details-value">{$time}</span>
            </div>
            <div class="appointment-details-row">
                <span class="appointment-details-label">🏥 Type:</span>
                <span class="appointment-details-value">{$type}</span>
            </div>
            <div class="appointment-details-row">
                <span class="appointment-details-label">📍 Location:</span>
                <span class="appointment-details-value">{$location}</span>
            </div>
            <div class="appointment-details-row">
                <span class="appointment-details-label">👨‍⚕️ With:</span>
                <span class="appointment-details-value">{$doctor}</span>
            </div>
            <div class="appointment-details-row">
                <span class="appointment-details-label">📋 Case ID:</span>
                <span class="appointment-details-value"><span class="highlight">{$case_id}</span></span>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>What Happens Next?</h2>
        <div class="details-box">
            <p>• Our staff will review your appointment request.</p>
            <p>• You will get an email notification once it is approved or if additional information is required.</p>
            <p>• If you need to change your request, please contact us.</p>
        </div>
    </div>

    <div class="button-container">
        <a href="https://paanakan.com/appointments" class="btn">View My Appointments</a>
    </div>

    <div class="section" style="background-color: #E3F2FD; padding: 15px; border-radius: 6px; border-left: 4px solid #2196F3;">
        <p style="color: #1565C0;"><strong>💬 Reminder:</strong> We will send a confirmation email when your appointment is approved.</p>
    </div>
</div>
HTML;

        return $this->getBaseTemplate($content, 'Appointment Scheduled - Paanakan sa Calapan');
    }

    /**
     * Password Reset Email Template
     */
    public function getPasswordResetTemplate($user_name, $reset_link, $expiry_hours = 1) {
        $first_name = explode(' ', trim($user_name))[0];

        $content = <<<HTML
<div class="header">
    <div class="logo">🔐</div>
    <h1>Password Reset Request</h1>
    <p>Secure your account</p>
</div>

<div class="content">
    <div class="section">
        <h2>Password Reset Request</h2>
        <p>Hello {$first_name},</p>
        <p>We received a request to reset the password for your Paanakan account. If you didn't make this request, please ignore this email or contact us immediately.</p>
    </div>

    <div class="button-container">
        <a href="{$reset_link}" class="btn">Reset Your Password</a>
    </div>

    <div class="section">
        <p style="text-align: center; color: {$this->text_light}; font-size: 13px;">Or copy this link and paste it into your browser:</p>
        <div style="background-color: {$this->bg_light}; padding: 12px; border-radius: 4px; margin-top: 10px; word-break: break-all;">
            <code style="color: {$this->text_dark}; font-size: 12px;">{$reset_link}</code>
        </div>
    </div>

    <div class="section">
        <h2>Link Expiration</h2>
        <div class="warning-box">
            <strong>⏰ Important:</strong>
            <p style="margin-top: 10px;">This password reset link will expire in <span class="highlight">{$expiry_hours} hour(s)</span>. After that, you'll need to request a new password reset link.</p>
        </div>
    </div>

    <div class="section">
        <h2>Security Tips</h2>
        <div class="details-box">
            <ul style="margin-left: 15px; color: {$this->text_light}; line-height: 1.8;">
                <li><strong>Use a strong password</strong> with uppercase, lowercase, numbers, and symbols</li>
                <li><strong>Never share your password</strong> with anyone, including Paanakan staff</li>
                <li><strong>Never reply to this email</strong> with your password</li>
                <li><strong>Use a unique password</strong> not used on other websites</li>
                <li><strong>Log out</strong> from all devices after changing your password</li>
            </ul>
        </div>
    </div>

    <div class="section">
        <h2>Didn't Request This?</h2>
        <p>If you didn't request a password reset, it's possible someone tried to access your account without permission. We recommend:</p>
        <ul style="margin-left: 20px; color: {$this->text_light}; line-height: 1.8;">
            <li><strong>Ignore this email</strong> — your password will remain unchanged</li>
            <li><strong>Check your account security</strong> settings</li>
            <li><strong>Enable two-factor authentication</strong> if available</li>
            <li><strong>Contact support immediately</strong> if you suspect unauthorized access</li>
        </ul>
    </div>

    <div class="section" style="background-color: #F3E5F5; padding: 15px; border-radius: 6px; border-left: 4px solid #9C27B0;">
        <p style="color: #6A1B9A;"><strong>🔒 Privacy Notice:</strong> Paanakan never asks for your password via email. If you receive suspicious emails claiming to be from Paanakan, please report them to abuse@paanakan.com</p>
    </div>

    <div class="section">
        <p style="text-align: center; color: {$this->text_light}; font-size: 13px; margin-top: 30px;">
            <strong>Questions?</strong> Contact our support team at support@paanakan.com or call (043) XXX-XXXX
        </p>
    </div>
</div>
HTML;

        return $this->getBaseTemplate($content, 'Password Reset - Paanakan sa Calapan');
    }

    /**
     * Appointment Reminder Email Template
     */
    public function getAppointmentReminderTemplate($patient_name, $appointment_data) {
        $first_name = explode(' ', trim($patient_name))[0];
        $date = $appointment_data['scheduled_date'] ?? 'Tomorrow';
        $time = $appointment_data['time'] ?? 'TBD';
        $type = $appointment_data['appointment_type'] ?? 'Your appointment';
        $location = $appointment_data['location'] ?? 'Paanakan sa Calapan';

        $content = <<<HTML
<div class="header">
    <div class="logo">🔔</div>
    <h1>Appointment Reminder</h1>
    <p>Your appointment is coming up</p>
</div>

<div class="content">
    <div class="section">
        <h2>Hello {$first_name}!</h2>
        <p>This is a friendly reminder about your upcoming appointment with us.</p>
    </div>

    <div class="section">
        <h2>Appointment Details</h2>
        <div class="appointment-details">
            <div class="appointment-details-row">
                <span class="appointment-details-label">📅 Date:</span>
                <span class="appointment-details-value">{$date}</span>
            </div>
            <div class="appointment-details-row">
                <span class="appointment-details-label">⏰ Time:</span>
                <span class="appointment-details-value">{$time}</span>
            </div>
            <div class="appointment-details-row">
                <span class="appointment-details-label">🏥 Type:</span>
                <span class="appointment-details-value">{$type}</span>
            </div>
            <div class="appointment-details-row">
                <span class="appointment-details-label">📍 Location:</span>
                <span class="appointment-details-value">{$location}</span>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Preparing for Your Visit</h2>
        <ul style="margin-left: 20px; color: {$this->text_light}; line-height: 1.8;">
            <li>Plan to arrive <strong>10 minutes early</strong></li>
            <li>Bring your <strong>government-issued ID</strong> and insurance card</li>
            <li>List any <strong>current symptoms or concerns</strong></li>
            <li>Note any <strong>medications</strong> you're currently taking</li>
            <li>Bring relevant <strong>medical documents</strong> if applicable</li>
        </ul>
    </div>

    <div class="button-container">
        <a href="https://paanakan.com/appointments" class="btn">View Appointment</a>
    </div>

    <div class="section">
        <h2>Need to Cancel or Reschedule?</h2>
        <p>If you need to cancel or reschedule your appointment, please contact us as soon as possible:</p>
        <div class="details-box">
            <p>📞 <strong>(043) XXX-XXXX</strong></p>
            <p>📧 <strong>appointments@paanakan.com</strong></p>
        </div>
        <p style="color: #FF6B6B; font-size: 13px; margin-top: 10px;"><strong>⚠️ Important:</strong> Please provide at least 24 hours notice to avoid cancellation fees.</p>
    </div>

    <div class="section" style="background-color: #E8F5E9; padding: 15px; border-radius: 6px;">
        <p style="color: {$this->text_dark};"><strong>✓ We're looking forward to seeing you!</strong></p>
        <p style="color: {$this->text_light}; margin-top: 10px; font-size: 14px;">If you have any questions, don't hesitate to reach out. Your health and well-being are our top priority.</p>
    </div>
</div>
HTML;

        return $this->getBaseTemplate($content, 'Appointment Reminder - Paanakan sa Calapan');
    }

    /**
     * Appointment Cancellation Template
     */
    public function getAppointmentCancellationTemplate($patient_name, $appointment_data) {
        $first_name = explode(' ', trim($patient_name))[0];
        $date = $appointment_data['scheduled_date'] ?? 'TBD';
        $type = $appointment_data['appointment_type'] ?? 'Your appointment';

        $content = <<<HTML
<div class="header" style="background: linear-gradient(135deg, #D32F2F 0%, #F57C00 100%);">
    <div class="logo">❌</div>
    <h1>Appointment Cancelled</h1>
    <p>Your appointment has been cancelled</p>
</div>

<div class="content">
    <div class="section">
        <h2>Appointment Cancelled</h2>
        <p>Hello {$first_name},</p>
        <p>We want to confirm that your appointment has been cancelled as requested.</p>
    </div>

    <div class="section">
        <h2>Cancelled Appointment Details</h2>
        <div class="appointment-details">
            <div class="appointment-details-row">
                <span class="appointment-details-label">📅 Date:</span>
                <span class="appointment-details-value">{$date}</span>
            </div>
            <div class="appointment-details-row">
                <span class="appointment-details-label">🏥 Type:</span>
                <span class="appointment-details-value">{$type}</span>
            </div>
            <div class="appointment-details-row">
                <span class="appointment-details-label">Status:</span>
                <span class="appointment-details-value" style="color: #D32F2F;"><strong>Cancelled</strong></span>
            </div>
        </div>
    </div>

    <div class="button-container">
        <a href="https://paanakan.com/appointments" class="btn">Schedule New Appointment</a>
    </div>

    <div class="section">
        <h2>Need Help?</h2>
        <p>If you'd like to reschedule your appointment or have questions, please contact us:</p>
        <div class="details-box">
            <p>📞 <strong>(043) XXX-XXXX</strong></p>
            <p>📧 <strong>support@paanakan.com</strong></p>
        </div>
    </div>

    <div class="section" style="background-color: #FFF3CD; padding: 15px; border-radius: 6px; border-left: 4px solid #FFC107;">
        <p style="color: #856404;"><strong>💡 Note:</strong> Your cancellation may have a refund or credit associated with it. Please check your account or contact us for details.</p>
    </div>
</div>
HTML;

        return $this->getBaseTemplate($content, 'Appointment Cancelled - Paanakan sa Calapan');
    }
}
?>
