# 📊 Email Logging UI Pages — Usage Guide

## Two UI Pages Created

### 1. **Email Logs Viewer** (`admin/email_logs.php`)
**Purpose**: Comprehensive log viewer with advanced filtering, search, and analytics

**Features**:
- ✅ View email_sends.log and sendgrid_events.log combined
- ✅ Real-time statistics (sent, failed, delivered, deferred, bounced)
- ✅ Advanced filtering (by type, status, deferral)
- ✅ Full-text search
- ✅ Correlation ID linking (click to search by CORR_ID)
- ✅ Pagination (20/50/100/200 entries)
- ✅ Auto-refresh mode
- ✅ Raw log view (for debugging)
- ✅ Color-coded badges and status indicators
- ✅ Responsive design (mobile-friendly)

**How to Access**:
```
http://yoursite.com/admin/email_logs.php
```

**First Time**:
1. Enter admin password (from `.env` as `EMAIL_LOG_PASSWORD`)
2. Session is remembered for current browser session

**What You'll See**:
```
┌─────────────────────────────────────────────────┐
│ 📧 Email Logs Viewer                            │
├─────────────────────────────────────────────────┤
│ Stats: 42 Sent | 2 Failed | 35 Delivered | ...  │
├─────────────────────────────────────────────────┤
│ Filter:  [All] [Sends] [Events] [Deferred] ...  │
│ Search:  [____________________] [Search]         │
│ Lines:   [Last 50 ▼]  Auto-refresh [ ]          │
├─────────────────────────────────────────────────┤
│ ✅ SEND  | TO: patient@ex | CORR_ID: abc123     │
│    SUBJECT: Appointment Scheduled...             │
│    TRANSPORT: sendgrid | STATUS: 202 | SUCCESS   │
│                                                   │
│ 📬 DELIVERED | EMAIL: patient@ex                │
│    STATUS: 250 | Timestamp                       │
│                                                   │
│ ⏸️  DEFERRED | ALERT: Connection timeout        │
│    EMAIL: patient@ex | REASON: timeout           │
└─────────────────────────────────────────────────┘
```

---

### 2. **Email Activity Dashboard** (`admin/email_activity.php`)
**Purpose**: Live real-time monitoring dashboard with auto-refresh

**Features**:
- ✅ Live updating metrics (refreshes every 5 seconds)
- ✅ Real-time activity feed
- ✅ 6 key metrics: Sent, Delivered, Deferred, Bounced, Failed, Total Events
- ✅ Animated activity entries
- ✅ Status indicators (green/red/orange)
- ✅ Manual refresh button
- ✅ Beautiful, minimal design
- ✅ Mobile-responsive

**How to Access**:
```
http://yoursite.com/admin/email_activity.php
```

**First Time**:
1. Enter admin password
2. Dashboard immediately starts auto-refreshing

**What You'll See**:
```
┌──────────────────────────────────────────────────┐
│ 📊 Email Activity Dashboard                      │
│ Live Updates • Auto-refresh every 5 seconds      │
├──────────────────────────────────────────────────┤
│                                                   │
│ 📤 Sent: 42  │ 📬 Delivered: 35 │ ⏸️ Deferred: 3 │
│ 📪 Bounced: 1 │ ❌ Failed: 2 │ 📊 Events: 50     │
│                                                   │
├──────────────────────────────────────────────────┤
│ 🔔 Recent Activity                               │
│                                                   │
│ ● [SEND] [✅ OK] [📧 SMTP]                       │
│   patient@example.com                            │
│   2025-11-03 16:41:16 • Appointment Scheduled   │
│                                                   │
│ ● [DELIVERED]                                    │
│   patient@example.com                            │
│   2025-11-03 16:41:18 • Status 250              │
│                                                   │
│ ● [⏸️ DEFERRED] [ALERT]                          │
│   patient@example.com                            │
│   2025-11-03 16:42:05 • Connection timeout      │
│                                                   │
│                         [Refresh 🔄] (floating button)
└──────────────────────────────────────────────────┘
```

---

## How to Choose Which to Use

| Situation | Use This |
|-----------|----------|
| Want to see all details & search | **Email Logs Viewer** |
| Want live monitoring (watch in real-time) | **Email Activity Dashboard** |
| Need to investigate a specific email | **Email Logs Viewer** (search) |
| Monitoring email system health | **Email Activity Dashboard** |
| Want to find correlations between sends & events | **Email Logs Viewer** (click CORR_ID) |
| Quick check how many emails delivered | **Email Activity Dashboard** |

---

## Feature Walkthrough

### Email Logs Viewer

**1. Statistics Bar**
```
✅ Sent: 42 | ❌ Failed: 2 | 📬 Delivered: 35 | ⏸️ Deferred: 3 | 📪 Bounced: 1 | 📊 Total Events: 50
```
Quick overview of current email status.

**2. Filter Buttons**
- `All` — Show everything
- `Sends` — Show only send attempts from your app
- `Events` — Show only SendGrid webhook events
- `Deferred` — Show only deferred emails (troubleshooting)
- `Failures` — Show only failed sends (troubleshooting)

**3. Search Box**
Search for:
- Email address: `patient@example.com`
- Subject: `Appointment`
- Error message: `SSL certificate`
- Correlation ID: `20251103-164051-abc123`

**4. Display Options**
- Last 20 / Last 50 / Last 100 / Last 200 entries
- Auto-refresh checkbox (auto-reloads page every 5 seconds)

**5. Log Entry View**
Each log entry shows:
- Type badge (SEND, PROCESSED, DELIVERED, DEFERRED, etc.)
- Status badge (✅ OK, ❌ FAIL, 📧 SMTP, etc.)
- Timestamp
- Correlation ID (clickable to search)
- Recipient email
- Subject
- Transport (sendgrid or smtp)
- Status code or result
- Error message (if any)
- Raw log (expandable for debugging)

**6. Click-to-Search**
Click on any Correlation ID to search for all related entries:
- This shows you both the send and all webhook events for that ID
- Perfect for tracing a single email's journey

---

### Email Activity Dashboard

**1. Metrics Cards**
Six large numbers showing current status:
- 📤 Sent (total successful sends)
- 📬 Delivered (emails accepted by recipient)
- ⏸️ Deferred (temporary failures)
- 📪 Bounced (hard failures)
- ❌ Failed (send errors)
- 📊 Events (total webhook events)

Numbers update every 5 seconds automatically.

**2. Activity Feed**
Real-time list of recent activities:
- Each entry shows: badges + email + timestamp + subject
- Entries animate in as they appear
- Status indicator (colored dot) shows health: 🟢 OK | 🔴 FAIL | 🟠 DEFERRED
- Up to 30 most recent entries visible

**3. Auto-Refresh**
- Automatically refreshes every 5 seconds
- Shows most recent activities
- Metrics update in real-time

**4. Manual Refresh**
- Floating blue circular button (bottom-right, 🔄 icon)
- Click to manually refresh immediately
- Button rotates on hover (nice animation)

---

## Security & Authentication

### Password Protection
Both pages require admin password from `.env`:
```env
EMAIL_LOG_PASSWORD=YourSecurePassword123
```

**Setup**:
1. Add to your `.env` file (if not already there)
2. Users must enter password on first access
3. Session remembered for current browser

**If you haven't set a password yet**:
Add this line to your `.env`:
```env
EMAIL_LOG_PASSWORD=admin123
```
(Change to something secure!)

### Recommendations
- ✅ Set a strong password in `.env`
- ✅ Use `.env` (not in code)
- ✅ Don't share password link publicly
- ✅ Consider restricting access to admin IPs only (optional enhancement)

---

## Common Tasks

### Find out if an email was delivered
1. Go to **Email Logs Viewer**
2. Search for recipient email: `patient@example.com`
3. Look for `DELIVERED` event (means sent and received)
4. If you see `DEFERRED`, check if `SMTP` fallback entry exists with `SUCCESS: OK`

### Check if deferral is happening
1. Go to **Email Activity Dashboard**
2. Watch the "⏸️ Deferred" number
3. If > 0, deferrals are occurring
4. Switch to **Email Logs Viewer**
5. Click filter: `[Deferred]`
6. See reason why in each entry

### Trace a single email's complete journey
1. Find the email in **Email Logs Viewer**
2. Note the `CORR_ID` (Correlation ID)
3. Click on it (or search for it)
4. See ALL related entries:
   - Initial send attempt
   - SMTP fallback (if occurred)
   - SendGrid events (processed, delivered, deferred, etc.)

### Investigate a failure
1. Go to **Email Logs Viewer**
2. Click filter: `[Failures]`
3. Check ERROR message
4. Common errors:
   - `SSL certificate verify failed` → Fix CA bundle
   - `Connection timeout` → Network issue
   - `Invalid email` → Check recipient email

### Monitor system health
1. Open **Email Activity Dashboard**
2. Watch metrics update every 5 seconds
3. Check for:
   - High failure count?
   - High deferral count?
   - Delivered count increasing normally?
4. If problems detected, switch to **Logs Viewer** for details

---

## Performance Notes

**Email Logs Viewer**:
- Fast to load (reads log files directly)
- Can handle 1000+ entries
- Search filters locally (fast)
- Best for detailed investigation

**Email Activity Dashboard**:
- Very fast (auto-refresh every 5 sec)
- Lightweight JSON API
- Best for monitoring over time
- Can leave open in browser tab

---

## Next Steps

1. **Add to Sidebar** (optional): 
   Add links to both pages in your admin sidebar menu

2. **Monitor Regularly**:
   - Daily: Quick glance at dashboard
   - Weekly: Review logs for patterns

3. **Set Up Alerts** (advanced):
   - If deferred count > 5, alert admin
   - If failed count > 2, alert admin
   - (Can implement in future)

4. **Rotate Logs** (recommended):
   - Delete logs older than 30 days
   - Or implement log archival

---

## Keyboard Shortcuts (Email Logs Viewer)

- `Ctrl+F` — Search within page
- Click Correlation ID → Search by that ID
- Click filter button → Instant filter
- Click entry `[Show Details]` → Expand raw log

---

## Testing the Pages

### Quick Test
1. Go to: `admin/email_logs.php`
2. Enter password
3. You should see your test log entry from earlier!
4. Try searching for your email address
5. Go to: `admin/email_activity.php`
6. Watch the dashboard update

### Send a Test Email
```php
// In your code:
EmailService->sendAppointmentScheduled($email, $name, $details);
```

Then:
1. Check **Email Logs Viewer** for send entry (appears immediately)
2. Check **Email Activity Dashboard** for live update (appears in 5 sec)
3. Check `logs/sendgrid_events.log` for webhook events (1-3 sec later)

---

## URLs Quick Reference

```
Email Logs Viewer:
http://localhost/admin/email_logs.php

Email Activity Dashboard:
http://localhost/admin/email_activity.php

API Endpoint (for integrations):
http://localhost/admin/api/email_logs.php?filter=all&limit=50&offset=0
```

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Pages not loading | Check if password is set in `.env` (EMAIL_LOG_PASSWORD) |
| No logs visible | Check if `logs/` directory exists and is writable |
| Password not working | Verify .env has correct password, restart browser |
| Dashboard not updating | Check auto-refresh is enabled, try manual refresh button |
| Search not working | Try exact email or simpler search term |

---

**Status**: ✅ Ready to use  
**Last Updated**: 2025-11-03  
**Test Status**: Verified working
