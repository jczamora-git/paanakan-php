<?php
/**
 * Email Logs API Endpoint
 * 
 * Returns email logs as JSON for dynamic loading and real-time updates
 * Usage: /admin/api/email_logs.php?filter=all&search=&limit=50
 */

header('Content-Type: application/json');

// Simple auth check - in production, use proper session/auth
if (!isset($_SESSION)) {
    session_start();
}

// Check if authenticated
$isAuth = isset($_SESSION['email_logs_auth']) || (isset($_GET['token']) && $_GET['token'] === md5(date('Y-m-d')));

if (!$isAuth) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

// Get parameters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$limit = intval($_GET['limit'] ?? 50);
$offset = intval($_GET['offset'] ?? 0);

// Log file paths
$logsDir = __DIR__ . '/../logs';
$sendLog = $logsDir . '/email_sends.log';
$eventLog = $logsDir . '/sendgrid_events.log';

// Parse log entries
function parseLogEntry($line) {
    if (empty(trim($line))) return null;
    
    if (!preg_match('/^\[(.*?)\](.*)$/', $line, $matches)) {
        return null;
    }
    
    $timestamp = $matches[1];
    $data = $matches[2];
    
    $entry = [
        'timestamp' => $timestamp,
        'raw' => $line,
        'data' => []
    ];
    
    // Parse key-value pairs
    if (preg_match_all('/(\w+):\s*([^|]*?)(?:\s*\||$)/', $data, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $key = trim($match[1]);
            $value = trim($match[2]);
            $entry['data'][$key] = $value;
        }
    }
    
    return $entry;
}

// Get log entries
function getLogEntries($filePath) {
    if (!file_exists($filePath)) {
        return [];
    }
    
    $entries = [];
    $lines = array_reverse(file($filePath, FILE_IGNORE_NEW_LINES));
    
    foreach ($lines as $line) {
        $entry = parseLogEntry($line);
        if ($entry) {
            $entries[] = $entry;
        }
    }
    
    return $entries;
}

// Combine logs
$sendEntries = getLogEntries($sendLog);
$eventEntries = getLogEntries($eventLog);

$allEntries = [];
foreach ($sendEntries as $entry) {
    $entry['source'] = 'send';
    $entry['type'] = 'send';
    $allEntries[] = $entry;
}
foreach ($eventEntries as $entry) {
    $entry['source'] = 'event';
    $entry['type'] = strtolower($entry['data']['EVENT'] ?? 'unknown');
    $allEntries[] = $entry;
}

// Sort by timestamp
usort($allEntries, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Apply filters
$filtered = $allEntries;

if ($filter !== 'all') {
    $filtered = array_filter($filtered, function($entry) use ($filter) {
        if ($filter === 'sends') return $entry['source'] === 'send';
        if ($filter === 'events') return $entry['source'] === 'event';
        if ($filter === 'deferred') return stripos($entry['raw'], 'DEFERRED') !== false;
        if ($filter === 'fails') return stripos($entry['raw'], 'FAIL') !== false;
        if ($filter === 'alerts') return stripos($entry['raw'], 'ALERT') !== false;
        return true;
    });
}

// Apply search
if (!empty($search)) {
    $filtered = array_filter($filtered, function($entry) use ($search) {
        return stripos($entry['raw'], $search) !== false;
    });
}

// Count before pagination
$total = count($filtered);

// Apply pagination
$displayed = array_slice($filtered, $offset, $limit);

// Format response
$response = [
    'success' => true,
    'total' => $total,
    'offset' => $offset,
    'limit' => $limit,
    'count' => count($displayed),
    'entries' => [],
    'stats' => [
        'total_sends' => count(array_filter($sendEntries, fn($e) => $e['data']['SUCCESS'] === 'OK')),
        'total_fails' => count(array_filter($sendEntries, fn($e) => $e['data']['SUCCESS'] === 'FAIL')),
        'total_events' => count($eventEntries),
        'delivered' => count(array_filter($eventEntries, fn($e) => stripos($e['raw'], 'DELIVERED') !== false)),
        'deferred' => count(array_filter($eventEntries, fn($e) => stripos($e['raw'], 'DEFERRED') !== false)),
        'bounced' => count(array_filter($eventEntries, fn($e) => stripos($e['raw'], 'BOUNCE') !== false)),
    ]
];

// Format each entry
foreach ($displayed as $entry) {
    $formatted = [
        'timestamp' => $entry['timestamp'],
        'source' => $entry['source'],
        'type' => $entry['type'],
        'raw' => $entry['raw'],
        'data' => $entry['data'],
        'badges' => []
    ];
    
    // Add badges
    if ($entry['source'] === 'send') {
        $formatted['badges'][] = ['type' => 'send', 'label' => 'Send'];
        if ($entry['data']['SUCCESS'] === 'OK') {
            $formatted['badges'][] = ['type' => 'success', 'label' => '✅ OK'];
        } else {
            $formatted['badges'][] = ['type' => 'fail', 'label' => '❌ FAIL'];
        }
    } else {
        $formatted['badges'][] = ['type' => 'event', 'label' => strtoupper($entry['data']['EVENT'] ?? 'Event')];
    }
    
    if (stripos($entry['raw'], 'DEFERRED') !== false) {
        $formatted['badges'][] = ['type' => 'warning', 'label' => '⏸️ DEFERRED'];
    }
    
    if (stripos($entry['raw'], 'smtp') !== false) {
        $formatted['badges'][] = ['type' => 'smtp', 'label' => '📧 SMTP'];
    }
    
    $response['entries'][] = $formatted;
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
