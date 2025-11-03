# 📈 Email Delivery Flow with SendGrid Deferral Handling

## System Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         PAANAKAN APPLICATION                               │
│                                                                             │
│  Patient schedules appointment                                             │
│  OR Admin approves appointment                                             │
│  OR Other email event triggered                                           │
│                                                                             │
│                          ↓                                                 │
│                   EmailService::sendEmail()                               │
│                                                                             │
│  ┌───────────────────────────────────────────────────────────────────┐   │
│  │ 1. Create correlation ID (unique per send)                        │   │
│  │    Example: 20251103-164051-ef3b31cd                             │   │
│  │                                                                    │   │
│  │ 2. Check .env flags:                                             │   │
│  │    - EMAIL_USE_SENDGRID=true    (SendGrid is primary)            │   │
│  │    - EMAIL_FALLBACK_SMTP=true   (SMTP is backup)                 │   │
│  │                                                                    │   │
│  │ 3. SendGrid primary attempt:                                     │   │
│  │    → POST to https://api.sendgrid.com/v3/mail/send               │   │
│  │    → Send email body, recipient, subject                         │   │
│  │    → Log attempt with CORR_ID to email_sends.log                 │   │
│  │                                                                    │   │
│  │    Result: HTTP 202 = SendGrid accepted message                  │   │
│  │             (actual delivery happens async)                      │   │
│  └───────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│                          ↓                                                 │
│                                                                             │
│  ┌─────────────────────────────────────┐   ┌──────────────────────────┐  │
│  │ SendGrid API returned success (202) │   │ SendGrid returned error  │  │
│  │ → Continue, return success           │   │ or deferral → Fallback  │  │
│  └─────────────────────────────────────┘   └──────────────────────────┘  │
│                  ↓                                      ↓                  │
│         Log: TRANSPORT: sendgrid         Log: TRANSPORT: sendgrid         │
│              SUCCESS: OK                      SUCCESS: FAIL               │
│              STATUS: 202                      ERROR: [reason]             │
│                                                                             │
│                                      ┌──────────────────────────────────┐ │
│                                      │ 4. SMTP Fallback triggered:      │ │
│                                      │    → Connect to SMTP_HOST (Gmail)│ │
│                                      │    → SMTP_USER + SMTP_PASS auth  │ │
│                                      │    → Send email via SMTP         │ │
│                                      │    → Log attempt (same CORR_ID)  │ │
│                                      │                                  │ │
│                                      │ Result: Success or SMTP error    │ │
│                                      │ Log: TRANSPORT: smtp (fallback)  │ │
│                                      │ Log: CORR_ID: [same as above]    │ │
│                                      └──────────────────────────────────┘ │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
         ↓                                           ↓
    ┌────────────────────────┐         ┌────────────────────────────┐
    │ EMAIL_SENDS.LOG        │         │ OPTIONAL: SMTP FALLBACK    │
    │                        │         │                            │
    │ [timestamp] CORR_ID... │         │ Triggers only if:          │
    │ TO: patient@...        │         │ • SendGrid returns error   │
    │ TRANSPORT: sendgrid    │         │ • EMAIL_FALLBACK_SMTP=true │
    │ STATUS: 202            │         │ • SMTP credentials present │
    │ SUCCESS: OK            │         │                            │
    │                        │         │ Result: Patient gets email │
    │                        │         │ via Gmail SMTP instead     │
    └────────────────────────┘         └────────────────────────────┘
         ↓
    SENDGRID PROCESSES ASYNC (in parallel)
         ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│                         SENDGRID WEBHOOK EVENTS                             │
│                                                                             │
│ SendGrid processes your message asynchronously and sends events back:       │
│                                                                             │
│ Webhook URL: https://yourdomain.com/connections/sendgrid_events.php        │
│                                                                             │
│ Event sequence (typical):                                                   │
│ 1. PROCESSED   → Message entered SendGrid's system                         │
│ 2. DELIVERED   → Recipient's mail server accepted the message              │
│    OR                                                                       │
│    DEFERRED    → Recipient's mail server rejected temporarily              │
│    OR                                                                       │
│    BOUNCED     → Recipient email invalid / hard bounce                     │
│    OR                                                                       │
│    DROPPED     → SendGrid dropped it (spam, duplicate, etc.)               │
│                                                                             │
│ Each event is logged to sendgrid_events.log with:                          │
│ - [timestamp] EVENT: {type} | EMAIL: {recipient} | STATUS: {code}         │
│ - REASON: {delivery_reason} | MSG_ID: {sendgrid_msg_id}                   │
│                                                                             │
│ If DEFERRED:                                                                │
│ - Log includes alert message                                               │
│ - Patient still received email via SMTP fallback (if triggered)             │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
         ↓
    SENDGRID_EVENTS.LOG
    
    [2025-11-03 16:41:18] EVENT: PROCESSED | EMAIL: patient@example.com | MSG_ID: abc123xyz
    [2025-11-03 16:41:20] EVENT: DELIVERED | EMAIL: patient@example.com | STATUS: 250
    
    OR (if deferred):
    
    [2025-11-03 16:42:05] EVENT: DEFERRED | EMAIL: patient@example.com | REASON: Connection timed out
    [2025-11-03 16:42:05] ALERT: Deferred email to patient@example.com. SMTP fallback may be needed.
```

---

## Deferral Scenario in Detail

### When SendGrid Defers (Temporary Failure)

**Timeline:**
```
t=0      Patient schedules appointment
         → EmailService.sendEmail() called
         → CORR_ID generated: 20251103-164051-abc123

t=0.1    SendGrid attempt: HTTP POST to SendGrid API
         → Payload: recipient, subject, body
         → SendGrid accepts: HTTP 202
         
t=0.2    Log entry written:
         [16:41:16] CORR_ID: 20251103-164051-abc123 | TO: patient@ex... | 
                   TRANSPORT: sendgrid | STATUS: 202 | SUCCESS: OK
         
t=1-3    SendGrid webhook posts events:
         PROCESSED event → patient's mail server contacted
         
t=5      Recipient mail server rejects temporarily:
         "Connection timed out" OR "Service unavailable"
         SendGrid receives this rejection
         
t=6      SendGrid sends DEFERRED event via webhook:
         POST to https://yourdomain.com/connections/sendgrid_events.php
         {event: "deferred", email: "patient@ex...", reason: "Connection timed out", ...}
         
t=6.1    Our webhook endpoint (sendgrid_events.php) receives event:
         ✓ Parses the JSON
         ✓ Extracts: email, event type, reason, message_id
         ✓ Writes to sendgrid_events.log:
         [16:41:21] EVENT: DEFERRED | EMAIL: patient@ex... | REASON: Connection timed out
         [16:41:21] ALERT: Deferred email to patient@ex... SMTP fallback may be needed.
         
t=0.3    (Back in time) If SendGrid had returned an error immediately,
         SMTP fallback would have been triggered at t=0.3:
         
         ✓ SMTP connection to smtp.gmail.com:587
         ✓ AUTH with SMTP_USER + SMTP_PASS
         ✓ Send via SMTP
         ✓ Log second attempt with same CORR_ID:
         [16:41:17] CORR_ID: 20251103-164051-abc123 | TO: patient@ex... |
                   TRANSPORT: smtp (fallback) | SUCCESS: OK
         
         Result: Patient receives email within 1 second via Gmail SMTP
```

---

## Log Correlation Example

### Scenario: Patient schedules appointment

**In your app (`admin/process_appointment.php` or `patient/manage_appointments.php`):**
```php
$emailService->sendAppointmentScheduled($patient_email, $patient_name, $appointment_details);
```

**What gets logged:**

### email_sends.log (in-app audit trail)
```
[2025-11-03 16:41:16] CORR_ID: 20251103-164051-ef3b31cd | TO: patient@example.com | 
                     SUBJECT: Appointment Scheduled - Pending Approval | 
                     TRANSPORT: sendgrid | STATUS: 202 | SUCCESS: OK | ERROR:
```

### sendgrid_events.log (SendGrid webhook events)
```
[2025-11-03 16:41:18] EVENT: PROCESSED | EMAIL: patient@example.com | MSG_ID: abc123xyz | STATUS: OK
[2025-11-03 16:41:20] EVENT: DELIVERED | EMAIL: patient@example.com | MSG_ID: abc123xyz | STATUS: 250
```

### How to correlate:
1. Note CORR_ID from email_sends.log: `20251103-164051-ef3b31cd`
2. Note timestamp: `16:41:16` (this is when SendGrid was called)
3. Check sendgrid_events.log for same email in next 1-3 seconds
4. Match by email address and timestamp proximity

### If deferral occurred:

**email_sends.log might have TWO entries:**
```
[2025-11-03 16:41:16] CORR_ID: 20251103-164051-ef3b31cd | TO: patient@example.com | 
                     TRANSPORT: sendgrid | STATUS: 500 | SUCCESS: FAIL | ERROR: Server error

[2025-11-03 16:41:17] CORR_ID: 20251103-164051-ef3b31cd | TO: patient@example.com | 
                     TRANSPORT: smtp (fallback) | STATUS: N/A | SUCCESS: OK | ERROR:
```

**sendgrid_events.log:**
```
[2025-11-03 16:41:25] EVENT: DEFERRED | EMAIL: patient@example.com | REASON: Connection timeout
[2025-11-03 16:41:25] ALERT: Deferred email to patient@example.com. SMTP fallback may be needed.
```

**Conclusion:** Email was initially sent via SendGrid (failed), then automatically retried via SMTP (succeeded).

---

## Monitoring Commands

### Real-time send log monitoring
```powershell
Get-Content -Path "c:\xampp\htdocs\paanakan\logs\email_sends.log" -Tail 20 -Wait
```

### Real-time event log monitoring
```powershell
Get-Content -Path "c:\xampp\htdocs\paanakan\logs\sendgrid_events.log" -Tail 20 -Wait
```

### Search for a specific email
```powershell
Select-String -Path "c:\xampp\htdocs\paanakan\logs\email_sends.log" -Pattern "patient@example.com"
```

### Search for deferrals
```powershell
Select-String -Path "c:\xampp\htdocs\paanakan\logs\sendgrid_events.log" -Pattern "DEFERRED|ALERT"
```

---

## Configuration Flags

| Flag | Value | Effect |
|------|-------|--------|
| `EMAIL_USE_SENDGRID` | `true` | Use SendGrid as primary transport |
| `EMAIL_USE_SENDGRID` | `false` | Skip SendGrid, use SMTP only (testing) |
| `EMAIL_FALLBACK_SMTP` | `true` | Enable SMTP fallback on SendGrid failure |
| `EMAIL_FALLBACK_SMTP` | `false` | Disable SMTP fallback (SendGrid-only) |
| `SMTP_ALLOW_INSECURE_TLS` | `true` | Disable SSL cert verification (testing only!) |
| `SMTP_ALLOW_INSECURE_TLS` | `false` | Enable strict SSL verification (production) |

---

**Setup Date**: 2025-11-03  
**Project**: Paanakan sa Calapan  
**Status**: ✅ Complete and tested
