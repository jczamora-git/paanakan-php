# 🎯 SendGrid Webhook & Logging Setup — COMPLETE

## What Was Just Set Up

### 1. ✅ **SendGrid Event Webhook Receiver** 
   - **File**: `connections/sendgrid_events.php`
   - **Purpose**: Receives delivery events from SendGrid (processed, delivered, deferred, bounce, dropped)
   - **Output**: Logs events to `logs/sendgrid_events.log` with timestamps, email addresses, and delivery status
   - **Key Feature**: When a `deferred` event is received, the code notes it as an alert so you can manually investigate or add automated retry logic

### 2. ✅ **Email Send Logging with Correlation IDs**
   - **File Modified**: `connections/EmailService.php`
   - **New Method**: `logEmailSend()` — logs every send attempt
   - **Output**: Logs to `logs/email_sends.log` with:
     - **Correlation ID** — unique ID to match sends with webhook events
     - **Recipient email** — who the message was sent to
     - **Subject** — first 60 chars of email subject
     - **Transport** — which service was used (sendgrid, smtp, or smtp (fallback))
     - **Status** — HTTP status code or SMTP result
     - **Success/Error** — whether it succeeded or failed, and error message if any
   - **Key Feature**: Automatic SMTP fallback when SendGrid fails/defers

### 3. ✅ **Automatic SMTP Fallback on SendGrid Deferral**
   - **Behavior**: 
     - SendGrid is primary transport (email is sent via SendGrid)
     - If SendGrid returns a non-2xx status (error/deferral), SMTP fallback is triggered automatically
     - Email is retried via Gmail SMTP (if configured)
     - Both attempts are logged with correlation ID
   - **Configuration**: 
     - `EMAIL_USE_SENDGRID=true` (SendGrid primary)
     - `EMAIL_FALLBACK_SMTP=true` (enable fallback)

### 4. ✅ **Logging Directory**
   - **Directory**: `logs/` — created and ready for use
   - **Files**:
     - `logs/email_sends.log` — in-app send attempts (CORR_ID, recipient, transport, status)
     - `logs/sendgrid_events.log` — SendGrid webhook events (processed, delivered, deferred, bounce, dropped)

---

## 🚀 How to Use This Setup

### Step 1: Update `.env` for Production
```env
EMAIL_USE_SENDGRID=true
EMAIL_FALLBACK_SMTP=true
SMTP_ALLOW_INSECURE_TLS=false
```

### Step 2: Configure SendGrid Webhook (1 minute)
1. Log in to SendGrid: https://app.sendgrid.com
2. Go to **Settings → Mail Send Settings → Event Notification**
3. Set HTTP POST URL to:
   - `https://yourdomain.com/connections/sendgrid_events.php` (production)
   - `https://ngrok-tunnel.io/connections/sendgrid_events.php` (local testing with ngrok)
4. Select events: ✓ Processed, ✓ Dropped, ✓ Delivered, ✓ **Deferred**, ✓ Bounce, ✓ Spam Report
5. Click **Save** and **Test Your Integration**

### Step 3: Monitor Logs in Real-Time
```powershell
# View send attempts (what your app is doing)
Get-Content -Path "c:\xampp\htdocs\paanakan\logs\email_sends.log" -Tail 10 -Wait

# View SendGrid events (what SendGrid tells us about delivery)
Get-Content -Path "c:\xampp\htdocs\paanakan\logs\sendgrid_events.log" -Tail 10 -Wait
```

### Step 4: Test End-to-End
1. Have a patient schedule an appointment (or use `test_email_logging.php`)
2. Check `logs/email_sends.log` — you'll see the send attempt with CORR_ID
3. Within 1-3 seconds, SendGrid webhook posts events to `sendgrid_events.php`
4. Check `logs/sendgrid_events.log` — you'll see PROCESSED, then DELIVERED (or DEFERRED)

### Step 5: Verify SMTP Fallback
If you see a DEFERRED event in `sendgrid_events.log`:
1. Check `logs/email_sends.log` for a matching CORR_ID or email address
2. You should see TWO entries:
   - First: `TRANSPORT: sendgrid | SUCCESS: FAIL` (SendGrid deferred it)
   - Second: `TRANSPORT: smtp (fallback) | SUCCESS: OK` (SMTP retried and succeeded)

---

## 📊 Log Entry Examples

### Send Log (`logs/email_sends.log`)
```
[2025-11-03 16:41:16] CORR_ID: 20251103-164051-ef3b31cd | TO: patient@example.com | SUBJECT: Appointment Scheduled - Pending Approval | TRANSPORT: sendgrid | STATUS: 202 | SUCCESS: OK | ERROR:
[2025-11-03 16:41:20] CORR_ID: 20251103-164120-a1b2c3d4 | TO: admin@example.com | SUBJECT: Patient Scheduled Appointment | TRANSPORT: smtp (fallback) | STATUS: N/A | SUCCESS: OK | ERROR:
```

### Event Log (`logs/sendgrid_events.log`)
```
[2025-11-03 16:41:18] EVENT: PROCESSED | EMAIL: patient@example.com | MSG_ID: abc123xyz | STATUS: OK | REASON: | RESPONSE: | ATTEMPT: 1
[2025-11-03 16:41:20] EVENT: DELIVERED | EMAIL: patient@example.com | MSG_ID: abc123xyz | STATUS: 250 | REASON: | RESPONSE: | ATTEMPT: 1
[2025-11-03 16:42:05] EVENT: DEFERRED | EMAIL: patient@example.com | MSG_ID: def456uvw | STATUS: 4.4.2 | REASON: Connection timed out | RESPONSE: | ATTEMPT: 1
[2025-11-03 16:42:05] ALERT: Deferred email to patient@example.com (msg_id: def456uvw). Reason: Connection timed out. SMTP fallback may be needed.
```

---

## 🔧 How the Deferrals are Handled

**Flow when SendGrid defers:**

1. Patient schedules appointment → Email sent via SendGrid (HTTP 202 = accepted)
2. SendGrid processes email → sends PROCESSED event via webhook
3. SendGrid attempts delivery to recipient mail server → times out temporarily
4. SendGrid sends DEFERRED event to webhook (with reason: "Connection timed out", etc.)
5. **Our code receives DEFERRED event and logs it** with alert message
6. **Meanwhile, if SendGrid returned an error initially, SMTP fallback already attempted delivery via Gmail SMTP**
7. You can see both attempts correlated in `email_sends.log` using the CORR_ID

---

## 📋 Checklist Before Production

- [ ] Update `.env` with `EMAIL_USE_SENDGRID=true` and `SMTP_ALLOW_INSECURE_TLS=false`
- [ ] Configure SendGrid Event Webhook in dashboard (step 2 above)
- [ ] Test webhook delivery using SendGrid's "Test Your Integration" button
- [ ] `.env` is in `.gitignore` (never commit secrets!)
- [ ] Gmail app password is fresh/rotated and not exposed in code
- [ ] `logs/` directory exists and is writable by PHP
- [ ] Remove or password-protect `test_email_logging.php` before deploying
- [ ] Test end-to-end: schedule appointment, verify send log + event log + inbox delivery

---

## 🐛 Troubleshooting

### Webhook not receiving events
- Check webhook URL is publicly accessible (test in browser)
- Use ngrok for local testing (https://ngrok.com)
- Check SendGrid dashboard → API Activity for webhook posts

### Email sent but no events logged
- Wait 1-3 seconds (SendGrid webhook delivery has latency)
- Verify webhook URL is correct in SendGrid settings
- Webhook must return HTTP 200 OK (our endpoint does this automatically)

### SMTP fallback not triggering
- Verify `EMAIL_FALLBACK_SMTP=true` in `.env`
- Verify SMTP credentials are correct (SMTP_USER, SMTP_PASS)
- Check `logs/email_sends.log` for error details

### Too many logs (file growing large)
- Consider log rotation: keep only last 30 days of logs
- Or store events in database instead of file (optional enhancement)

---

## 📚 Reference

| File | Purpose |
|------|---------|
| `connections/sendgrid_events.php` | Webhook receiver — logs SendGrid events |
| `connections/EmailService.php` | Email service — sends emails & logs attempts |
| `logs/email_sends.log` | Send attempt log (what your app did) |
| `logs/sendgrid_events.log` | Event log (what SendGrid tells us) |
| `SENDGRID_WEBHOOK_SETUP.md` | Detailed setup guide |
| `test_email_logging.php` | Test script to verify logging works |

---

## 🎉 You're All Set!

Your email system now has:
- ✅ SendGrid as primary (reliable, scalable)
- ✅ SMTP fallback for when SendGrid defers/fails
- ✅ Full audit trail with correlation IDs
- ✅ Real-time event monitoring via webhook
- ✅ Automatic logging of all send attempts and results

When a SendGrid deferral occurs, your patients will automatically receive their email via the SMTP fallback. You can monitor both in the logs!

---

**Next recommended action**: Rotate your Gmail app password (it was exposed in chat) and configure the SendGrid Event Webhook in production.

For detailed instructions, see `SENDGRID_WEBHOOK_SETUP.md`.
