<?php
/**
 * SendGrid Event Webhook Receiver
 * 
 * This endpoint receives delivery events from SendGrid's Event Webhook.
 * It logs events to a file and DB (optional) for debugging and monitoring.
 * 
 * Setup Instructions:
 * 1. Go to SendGrid Dashboard → Settings → Mail Send Settings → Event Notification
 * 2. Enable "Event Notification" and set the HTTP POST URL to:
 *    - For production: https://yourdomain.com/connections/sendgrid_events.php
 *    - For local testing: use ngrok to create a public tunnel to your local server
 *      Example: ngrok http 80 (then use https://xxxxx.ngrok.io/connections/sendgrid_events.php)
 * 3. Select events to receive: processed, dropped, delivered, deferred, bounce, spam_report, open, click
 * 4. Test by sending an email and watching this log file or the SendGrid dashboard
 * 
 * Log File: {PROJECT_ROOT}/logs/sendgrid_events.log
 */

// Verify the request is a POST and has data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Read the raw POST body
$input = file_get_contents('php://input');
if (empty($input)) {
    http_response_code(400);
    die('No data');
}

// Optional: Verify SendGrid webhook signature for added security
// This requires the Webhook Signing Secret from SendGrid dashboard
// For now, we'll log all events without verification (you can add this later)

// Decode the webhook payload (SendGrid sends an array of events)
$events = json_decode($input, true);
if (!is_array($events)) {
    // Single event may not be wrapped in array
    $events = [$events];
}

// Ensure logs directory exists
$logDir = realpath(__DIR__ . '/../logs');
if (!$logDir || !is_dir($logDir)) {
    mkdir(__DIR__ . '/../logs', 0755, true);
    $logDir = __DIR__ . '/../logs';
}

$logFile = $logDir . '/sendgrid_events.log';

// Process each event
foreach ($events as $event) {
    if (!is_array($event)) {
        continue;
    }

    // Extract key fields
    $eventType = $event['event'] ?? 'unknown';
    $email = $event['email'] ?? 'unknown';
    $timestamp = $event['timestamp'] ?? time();
    $messageId = $event['sg_message_id'] ?? 'unknown';
    $response = $event['response'] ?? '';
    $reason = $event['reason'] ?? '';
    $status = $event['status'] ?? '';
    $attempt = $event['attempt'] ?? '';

    // Build log entry
    $logEntry = sprintf(
        "[%s] EVENT: %s | EMAIL: %s | MSG_ID: %s | STATUS: %s | REASON: %s | RESPONSE: %s | ATTEMPT: %s\n",
        date('Y-m-d H:i:s', $timestamp),
        strtoupper($eventType),
        $email,
        $messageId,
        $status,
        $reason,
        $response,
        $attempt
    );

    // Write to log file
    error_log($logEntry, 3, $logFile);

    // Optional: If event is "deferred", you could trigger a retry or alert
    if ($eventType === 'deferred') {
        $alertEntry = sprintf(
            "[%s] ALERT: Deferred email to %s (msg_id: %s). Reason: %s. SMTP fallback may be needed.\n",
            date('Y-m-d H:i:s', $timestamp),
            $email,
            $messageId,
            $reason
        );
        error_log($alertEntry, 3, $logFile);
    }

    // Optional: Store in database for long-term audit trail (implement as needed)
    // Placeholder for DB logging:
    // storeEventInDb($event);
}

// Return 200 OK to SendGrid (important: confirm receipt)
http_response_code(200);
echo json_encode(['success' => true, 'events_received' => count($events)]);
?>
