# SendGrid Webhook Setup Guide

## Overview
This project now has:
1. **SendGrid Event Webhook Receiver** (`connections/sendgrid_events.php`) — receives delivery events (processed, delivered, deferred, bounce, dropped, etc.)
2. **Email Send Logging** (`logs/email_sends.log`) — logs every send attempt with correlation ID, recipient, transport, and result
3. **SMTP Fallback Trigger** — when SendGrid returns deferred/error, SMTP fallback is automatically triggered

This allows you to track email delivery status and automatically retry via SMTP when SendGrid defers.

---

## Step 1: Enable SendGrid (Production)

Update your `.env` file:

```env
EMAIL_USE_SENDGRID=true
EMAIL_FALLBACK_SMTP=true
SMTP_ALLOW_INSECURE_TLS=false
```

**Why?**
- `EMAIL_USE_SENDGRID=true` — SendGrid is primary transport
- `EMAIL_FALLBACK_SMTP=true` — If SendGrid returns an error (including deferred), SMTP fallback will attempt delivery
- `SMTP_ALLOW_INSECURE_TLS=false` — Production-grade SSL verification enabled

---

## Step 2: Configure SendGrid Event Webhook

1. Log into SendGrid Dashboard: https://app.sendgrid.com
2. Navigate to: **Settings** → **Mail Send Settings** → **Event Notification**
3. Click **Edit** on "Event Webhook"
4. Set **HTTP POST URL** to one of:
   - **Production**: `https://yourdomain.com/connections/sendgrid_events.php`
   - **Local Testing (ngrok)**: See Step 3 below

5. Select these events to monitor:
   - ✓ Processed
   - ✓ Dropped
   - ✓ Delivered
   - ✓ Deferred (IMPORTANT — this is when SMTP fallback should trigger)
   - ✓ Bounce
   - ✓ Spam Report

6. Click **Save** and **Test Your Integration**
   - SendGrid will send a test POST to verify the endpoint works
   - Check the response: you should see `{"success": true, ...}`

---

## Step 3: Local Testing with ngrok (Optional)

If testing locally before deployment:

1. **Install ngrok**: Download from https://ngrok.com/download
2. **Start ngrok**:
   ```powershell
   ngrok http 80
   ```
   Output will show a public URL like: `https://abc123.ngrok.io`

3. **Configure SendGrid Webhook** (from Step 2):
   - Set URL to: `https://abc123.ngrok.io/connections/sendgrid_events.php`
   - SendGrid will POST events to your local machine through the tunnel

4. **Monitor events in real-time**:
   ```powershell
   # In a new PowerShell window, tail the events log:
   Get-Content -Path "c:\xampp\htdocs\paanakan\logs\sendgrid_events.log" -Tail 10 -Wait
   ```

---

## Step 4: Monitor Email Delivery

### View Send Logs (in-app)
Every email sent is logged with a correlation ID to `logs/email_sends.log`:

```powershell
# Check recent sends
Get-Content "c:\xampp\htdocs\paanakan\logs\email_sends.log" -Tail 20

# Output format:
# [2025-11-03 10:45:32] CORR_ID: 20251103-104532-a1b2c3d4 | TO: patient@example.com | SUBJECT: Appointment Scheduled - Pending Approval - ... | TRANSPORT: sendgrid | STATUS: 202 | SUCCESS: OK | ERROR:
```

### View SendGrid Events (webhook)
SendGrid delivery events are logged to `logs/sendgrid_events.log`:

```powershell
# Check recent events
Get-Content "c:\xampp\htdocs\paanakan\logs\sendgrid_events.log" -Tail 20

# Output format:
# [2025-11-03 10:45:35] EVENT: PROCESSED | EMAIL: patient@example.com | MSG_ID: abc123xyz | STATUS: OK | REASON: | RESPONSE: | ATTEMPT: 1
# [2025-11-03 10:45:38] EVENT: DEFERRED | EMAIL: patient@example.com | MSG_ID: abc123xyz | STATUS: 4.4.2 | REASON: Connection timed out | RESPONSE: | ATTEMPT: 1
# [2025-11-03 10:45:40] ALERT: Deferred email to patient@example.com (msg_id: abc123xyz). Reason: Connection timed out. SMTP fallback may be needed.
```

### Correlate Sends with Events
Use the **correlation ID** or **email address** to match:
- Send log entry (from `email_sends.log`) with CORR_ID: `20251103-104532-a1b2c3d4`
- Event log entries (from `sendgrid_events.log`) with the same EMAIL and timestamp

Example flow:
1. Send logged: `CORR_ID: 20251103-104532-a1b2c3d4 | TO: patient@example.com | ... | TRANSPORT: sendgrid | STATUS: 202 | SUCCESS: OK`
   - Status 202 = SendGrid accepted the message
2. Event logged (1-3 sec later): `EVENT: PROCESSED | EMAIL: patient@example.com | ... | STATUS: OK`
   - SendGrid processed the message
3. Event logged (next): `EVENT: DELIVERED | EMAIL: patient@example.com | ... | STATUS: 250`
   - Recipient mail server delivered it

If DEFERRED event occurs:
- SendGrid received a temporary failure from recipient mail server
- Our code will automatically retry via SMTP (if SendGrid is deferred)
- Check SMTP fallback result in the send log

---

## Step 5: Test the Full Flow

### Test 1: Send Test Email (verify logs are written)
```powershell
# Use the test endpoint (with your email):
Invoke-WebRequest -Uri "http://localhost/connections/test_smtp.php?to=your-email@gmail.com" -Method Get
```

Check logs:
```powershell
# View send log (should have entry with TRANSPORT: sendgrid)
Get-Content "c:\xampp\htdocs\paanakan\logs\email_sends.log" -Tail 5

# View SendGrid events log (should have PROCESSED event after 1-3 seconds)
Get-Content "c:\xampp\htdocs\paanakan\logs\sendgrid_events.log" -Tail 5
```

### Test 2: Trigger SMTP Fallback (optional)
To test that SMTP fallback is triggered when SendGrid is down:

1. Temporarily set in `.env`:
   ```env
   EMAIL_USE_SENDGRID=false
   EMAIL_FALLBACK_SMTP=true
   ```

2. Send a test email — you should see `TRANSPORT: smtp` in send log

3. Re-enable SendGrid for normal operation

### Test 3: Send Real Appointment Email
1. In your app, have a patient schedule an appointment
2. Check send log for entry with appointment subject
3. Check SendGrid Activity dashboard or wait for webhook event
4. Verify email received in patient inbox (check spam folder too)

---

## Step 6: Production Deployment Checklist

- [ ] `.env` has `EMAIL_USE_SENDGRID=true` and `SMTP_ALLOW_INSECURE_TLS=false`
- [ ] `.env` is in `.gitignore` (never commit secrets!)
- [ ] SendGrid Event Webhook configured to POST to your production domain
- [ ] `logs/` directory exists and is writable by PHP/Apache
- [ ] SMTP credentials (Gmail app password) are fresh and not exposed in code
- [ ] Test endpoint (`test_smtp.php`) removed or password-protected
- [ ] Monitor email logs daily or set up log rotation for `email_sends.log` and `sendgrid_events.log`

---

## Troubleshooting

### Webhook not receiving events
- Verify the webhook URL is correct and publicly accessible
- Test with ngrok if local (check ngrok terminal for POST requests)
- Check SendGrid dashboard → Activity → Filter by webhook/event type
- Ensure `logs/` directory exists and is writable

### Email sent but never shows delivered
- Check `sendgrid_events.log` for DEFERRED event
- If DEFERRED: SMTP fallback should have triggered automatically
- Check `email_sends.log` for SMTP attempt (look for TRANSPORT: smtp (fallback))

### Too many logs
- Implement log rotation: set up PHP log rotation or a cron job to archive old logs
- Or store events in database instead (add DB logging method to EmailService)

### SMTP fallback not triggering
- Verify `EMAIL_FALLBACK_SMTP=true` in `.env`
- Verify SMTP credentials (SMTP_USER, SMTP_PASS) are correct
- Check `email_sends.log` for SMTP error details
- Test SMTP directly: run `php test_smtp.php?to=your-email@gmail.com` (temporary endpoint)

---

## Next Steps (Optional Enhancements)

- [ ] **Add database audit table** — store `email_logs` table for long-term auditing and admin UI reporting
- [ ] **Implement email retry queue** — automatically retry deferred messages after 5, 30, 60+ minutes
- [ ] **Add admin dashboard** — show email delivery stats, charts, and recent sends/events
- [ ] **Secure webhook endpoint** — add IP whitelisting or HMAC signature verification for `sendgrid_events.php`
- [ ] **Implement log rotation** — keep logs from growing unbounded (delete logs older than 30 days)

---

## Quick Reference: Log Files

| File | Purpose | Format |
|------|---------|--------|
| `logs/email_sends.log` | In-app email send attempts | `[timestamp] CORR_ID: ... \| TO: ... \| TRANSPORT: ... \| SUCCESS: ...` |
| `logs/sendgrid_events.log` | SendGrid delivery events (webhook) | `[timestamp] EVENT: PROCESSED/DEFERRED/DELIVERED/... \| EMAIL: ... \| STATUS: ...` |

---

## Questions?

- **SendGrid Events Documentation**: https://sendgrid.com/docs/for-developers/tracking-events/event/
- **SendGrid API Reference**: https://docs.sendgrid.com/api-reference/mail-send/mail-send
- **ngrok Documentation**: https://ngrok.com/docs

---

Generated: 2025-11-03 | Setup for Paanakan sa Calapan Healthcare Project
