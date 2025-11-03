# System Architecture Diagram — Final Visual

## Complete Email Delivery Flow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                      YOUR APPLICATION (Paanakan)                           │
│                                                                             │
│  Patient schedules appointment / Admin approves appointment                │
│                     ↓                                                      │
│         EmailService::sendEmail()                                         │
│                     ↓                                                      │
│  ┌────────────────────────────────────────────────────────────────────┐  │
│  │ 1. Generate unique Correlation ID (CORR_ID)                       │  │
│  │    Example: 20251103-164051-abc12345                              │  │
│  │                                                                    │  │
│  │ 2. Attempt SendGrid (primary)                                     │  │
│  │    POST → api.sendgrid.com/v3/mail/send                           │  │
│  │    Response: HTTP 202 (accepted)                                  │  │
│  │                                                                    │  │
│  │ 3. Log send attempt with CORR_ID                                 │  │
│  │    → email_sends.log                                              │  │
│  │    → TRANSPORT: sendgrid                                          │  │
│  │                                                                    │  │
│  │ 4. If error/deferral → SMTP fallback                             │  │
│  │    SMTP connect: smtp.gmail.com:587                               │  │
│  │    Auth + send via Gmail SMTP                                     │  │
│  │    Log second attempt (same CORR_ID)                              │  │
│  │    → TRANSPORT: smtp (fallback)                                   │  │
│  └────────────────────────────────────────────────────────────────────┘  │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
              ↓ (SendGrid processes async)              ↓ (SMTP sends sync)
    ┌──────────────────────────┐            ┌─────────────────────────┐
    │   SENDGRID PROCESSING    │            │  EMAIL REACHES INBOX   │
    │                          │            │  (via Gmail SMTP)       │
    │ • Processes message      │            │                        │
    │ • Sends webhook events   │            │ Patient receives       │
    │                          │            │ email within 1-2 sec   │
    │ Event sequence:          │            └─────────────────────────┘
    │ 1. PROCESSED             │
    │ 2. DELIVERED or DEFERRED │
    │    or BOUNCED            │
    │                          │
    └──────────────────────────┘
              ↓
    ┌──────────────────────────────────────┐
    │  WEBHOOK EVENT POSTED TO YOUR SERVER │
    │  POST → connections/sendgrid_events.php
    │  Payload: {event, email, reason, ... }
    └──────────────────────────────────────┘
              ↓
    ┌──────────────────────────────────────┐
    │  EVENT LOGGED                        │
    │  File: sendgrid_events.log          │
    │  [timestamp] EVENT: DELIVERED       │
    │  EMAIL: patient@ex  STATUS: 250     │
    │                                      │
    │  OR if deferred:                     │
    │  [timestamp] EVENT: DEFERRED         │
    │  EMAIL: patient@ex  REASON: timeout  │
    │  [timestamp] ALERT: Deferred...      │
    └──────────────────────────────────────┘
```

---

## Data Flow: From Click to Delivery

```
PATIENT ACTION
    ↓
schedule_appointment.php / process_appointment.php
    ↓
EmailService::sendAppointmentScheduled($email, $name, $details)
    ↓
EmailService::sendEmail($to, $name, $subject, $body_text, $body_html)
    ├─ Generate CORR_ID: 20251103-164051-abc12345
    ├─ Try SendGrid:
    │   ├─ HTTP 202? → SUCCESS, log "TRANSPORT: sendgrid"
    │   └─ HTTP 4xx/5xx or error? → Try SMTP fallback
    │
    ├─ SMTP Fallback (if enabled & SendGrid failed):
    │   ├─ Connect smtp.gmail.com:587
    │   ├─ AUTH (SMTP_USER / SMTP_PASS)
    │   ├─ Send email
    │   └─ Log "TRANSPORT: smtp (fallback)"
    │
    └─ Return result to caller
            ↓
          app/logs/email_sends.log
          [timestamp] CORR_ID: ... | TO: ... | SUBJECT: ... | TRANSPORT: ... | SUCCESS: ...

SENDGRID WEBHOOK (async)
    ├─ SendGrid posts event: PROCESSED
    ├─ SendGrid posts event: DELIVERED (or DEFERRED, BOUNCED, etc.)
    │
    └─ POST → sendgrid_events.php
              ↓
            Parse event
              ↓
            logs/sendgrid_events.log
            [timestamp] EVENT: DELIVERED | EMAIL: ... | STATUS: 250
```

---

## Log Correlation Example

### Scenario: Patient schedules appointment, SendGrid defers, SMTP sends it

**Timeline:**
```
t=0     Patient clicks "Schedule Appointment"
        ↓
t=0.1   EmailService.sendEmail() called
        ├─ CORR_ID generated: 20251103-164051-abc123
        ├─ SendGrid POST: HTTP 202 accepted
        ├─ Log to email_sends.log: TRANSPORT: sendgrid
        ├─ SendGrid will process async
        └─ Return success response
        
t=0.2   App displays "Appointment pending approval"
        Modal shown, Toast notification
        
t=0.3   (Same execution) If SendGrid had error:
        ├─ SMTP fallback triggered
        ├─ SMTP connect → send → success
        ├─ Log to email_sends.log: TRANSPORT: smtp (fallback), same CORR_ID
        └─ Patient receives email via SMTP
        
t=1-3   SendGrid webhook posts events:
        ├─ PROCESSED event
        ├─ POST → sendgrid_events.php
        └─ Log to sendgrid_events.log: EVENT: PROCESSED
        
t=5     SendGrid attempts recipient delivery
        Recipient mail server rejects: "Connection timeout"
        
t=6     SendGrid sends DEFERRED event:
        ├─ POST → sendgrid_events.php
        │   {event: "deferred", email: "patient@ex", reason: "Connection timed out", ...}
        │
        └─ sendgrid_events.php receives & logs:
           └─ sendgrid_events.log: EVENT: DEFERRED, EMAIL: patient@ex, REASON: Connection timed out
           └─ ALERT: Deferred email to patient@ex... SMTP fallback may be needed.
        
RESULT:
├─ email_sends.log shows 2 entries with CORR_ID: 20251103-164051-abc123
│  ├─ TRANSPORT: sendgrid (returned 202)
│  └─ TRANSPORT: smtp (fallback) (if SendGrid error triggered fallback)
│
├─ sendgrid_events.log shows DEFERRED event
│
└─ Patient received email via SMTP (if fallback triggered) ✓
```

---

## How to Read the Logs

### email_sends.log (What Your App Did)
```
[2025-11-03 16:41:16] CORR_ID: 20251103-164051-ef3b31cd | TO: patient@example.com | SUBJECT: Appointment Scheduled - Pend... | TRANSPORT: sendgrid | STATUS: 202 | SUCCESS: OK | ERROR:
[2025-11-03 16:41:17] CORR_ID: 20251103-164051-ef3b31cd | TO: patient@example.com | SUBJECT: Appointment Scheduled - Pend... | TRANSPORT: smtp (fallback) | STATUS: N/A | SUCCESS: OK | ERROR:
                      └─────────────────── SAME ID ──────────┘                                    └─ First attempt  ┘  └─ Second attempt ┘
```

**Reading tips:**
- `CORR_ID` = unique per send, same ID if multiple attempts
- `TRANSPORT: sendgrid` = tried SendGrid first
- `TRANSPORT: smtp (fallback)` = SMTP was attempted (either because SendGrid failed, or because SendGrid disabled)
- Both have `SUCCESS: OK` = both succeeded (fallback wasn't needed)
- If first is `SUCCESS: FAIL` and second is `SUCCESS: OK` = SendGrid failed, SMTP saved it!

### sendgrid_events.log (What SendGrid Told Us)
```
[2025-11-03 16:41:18] EVENT: PROCESSED | EMAIL: patient@example.com | MSG_ID: abc123xyz | STATUS: OK | REASON: | RESPONSE: | ATTEMPT: 1
[2025-11-03 16:41:20] EVENT: DELIVERED | EMAIL: patient@example.com | MSG_ID: abc123xyz | STATUS: 250 | REASON: | RESPONSE: | ATTEMPT: 1

OR (if deferred):

[2025-11-03 16:42:05] EVENT: DEFERRED | EMAIL: patient@example.com | MSG_ID: def456uvw | STATUS: 4.4.2 | REASON: Connection timed out | RESPONSE: | ATTEMPT: 1
[2025-11-03 16:42:05] ALERT: Deferred email to patient@example.com (msg_id: def456uvw). Reason: Connection timed out. SMTP fallback may be needed.
```

**Reading tips:**
- `EVENT: PROCESSED` = SendGrid got the message
- `EVENT: DELIVERED` = recipient's mail server accepted it (✓ success)
- `EVENT: DEFERRED` = recipient's mail server rejected temporarily (reason: Connection timed out, etc.)
- `ATTEMPT: 1` = first attempt; if `ATTEMPT: 2+` = SendGrid retried
- `ALERT:` line = our code detected deferral and logged it

---

## Debugging Guide

### "I don't see my email logs"
```
1. Check that logs/ directory exists:
   Test-Path c:\xampp\htdocs\paanakan\logs
   
2. Check that sendgrid_events.php is in place:
   Test-Path c:\xampp\htdocs\paanakan\connections\sendgrid_events.php
   
3. Manually verify logging works:
   php c:\xampp\htdocs\paanakan\test_email_logging.php
```

### "Webhook events not appearing"
```
1. Verify webhook URL in SendGrid dashboard:
   Settings → Event Notification → HTTP POST URL
   
2. Check if SendGrid even posted events:
   SendGrid Dashboard → Activity → look for your recipient email
   
3. If no events in Activity, deferral may not have occurred (email delivered immediately)
   
4. Use ngrok for local testing:
   ngrok http 80
   Update webhook URL to: https://{ngrok_id}.ngrok.io/connections/sendgrid_events.php
```

### "SMTP fallback not triggered"
```
1. Verify settings in .env:
   EMAIL_FALLBACK_SMTP=true
   SMTP_USER and SMTP_PASS are set
   
2. Check email_sends.log for SMTP error:
   Select-String logs\email_sends.log -Pattern "FAIL"
   
3. Verify SMTP_HOST, SMTP_PORT, SMTP_SECURE are correct:
   echo $env:SMTP_HOST
   echo $env:SMTP_PORT
```

---

## Performance Expectations

| Step | Typical Time | Notes |
|------|---|---|
| SendGrid API POST | <100ms | Fast, just HTTP request |
| SendGrid webhook post | 1-3 sec | Asynchronous, may have delay |
| SMTP send | 1-2 sec | Depends on Gmail servers |
| Patient receives email | 2-10 sec | SMTP is faster, webhook is async |
| Webhook event logged | 1-3 sec | After SendGrid posts |

---

## Success Criteria

✅ **You'll know it's working when:**
1. You send a test appointment
2. You check `email_sends.log` and see entry with CORR_ID (within seconds)
3. You check `sendgrid_events.log` and see webhook events (within 3 seconds)
4. Patient receives email in inbox (within 10 seconds)

✅ **Deferral handling is working when:**
1. You see DEFERRED event in `sendgrid_events.log`
2. You check `email_sends.log` and see TWO entries with same CORR_ID
3. Second entry shows TRANSPORT: smtp (fallback) with SUCCESS: OK
4. Patient still received email (via SMTP)

---

## Maintenance Tips

- **Daily**: Spot-check logs for any FAIL entries or DEFERRED events
- **Weekly**: Review email_sends.log for volume and patterns
- **Monthly**: Archive old logs (optional, but keeps disk clean)
- **As-needed**: Search logs for specific emails: `Select-String logs\email_sends.log -Pattern "patient@email.com"`

---

**Architecture Version**: 1.0  
**Last Updated**: 2025-11-03  
**Status**: Production Ready ✅
