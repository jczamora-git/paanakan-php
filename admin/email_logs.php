<?php
/**
 * Email Logs Viewer - Admin Dashboard
 * 
 * Displays:
 * - Send attempts log (email_sends.log)
 * - SendGrid webhook events log (sendgrid_events.log)
 * - Filtering, search, real-time refresh
 * - Correlation ID highlighting and matching
 * - Statistics and analytics
 */

// Security: Check if user is admin (adjust based on your auth system)
session_start();

// For now, simple admin check - adjust to match your auth system
$isAdmin = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// If not admin and not already authenticated as admin, require password for now
if (!$isAdmin && !isset($_GET['auth'])) {
    // You should replace this with proper authentication
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Email Logs - Authentication Required</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <style>
            body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
            .auth-card { background: white; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); padding: 40px; max-width: 400px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="auth-card mx-auto">
                <h3 class="text-center mb-4">🔐 Email Logs Access</h3>
                <p class="text-muted text-center mb-3">This feature requires admin authentication.</p>
                <form method="GET">
                    <input type="hidden" name="auth" value="1">
                    <div class="mb-3">
                        <label class="form-label">Admin Password</label>
                        <input type="password" class="form-control" name="password" required>
                        <small class="text-muted">Set this in your .env as EMAIL_LOG_PASSWORD</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Access Logs</button>
                </form>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Check password if provided
if (isset($_GET['auth']) && isset($_GET['password'])) {
    require_once __DIR__ . '/../connections/EmailService.php';
    
    // Load .env to get password
    $envFile = __DIR__ . '/../.env';
    $password = '';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, 'EMAIL_LOG_PASSWORD=') === 0) {
                $password = trim(str_replace('EMAIL_LOG_PASSWORD=', '', $line));
                break;
            }
        }
    }
    
    if (empty($password) || $_GET['password'] !== $password) {
        die('<h3 class="text-danger text-center mt-5">Invalid password</h3>');
    }
    
    $_SESSION['email_logs_auth'] = true;
}

// Check if authenticated
if (!isset($_SESSION['email_logs_auth'])) {
    die('<h3 class="text-danger text-center mt-5">Not authenticated</h3>');
}

// Get filter/search parameters
$filterType = $_GET['filter'] ?? 'all'; // all, sends, events, deferred, fails
$searchQuery = $_GET['search'] ?? '';
$lines = $_GET['lines'] ?? 50;
$autoRefresh = $_GET['auto'] ?? 0;

// Read log files
$logsDir = __DIR__ . '/../logs';
$sendLog = $logsDir . '/email_sends.log';
$eventLog = $logsDir . '/sendgrid_events.log';

// Function to parse log entries
function parseLogEntries($filePath, $limit = 1000) {
    if (!file_exists($filePath)) {
        return [];
    }
    
    $entries = [];
    $lines = array_reverse(file($filePath, FILE_IGNORE_NEW_LINES));
    
    foreach ($lines as $line) {
        if (empty(trim($line))) continue;
        if (count($entries) >= $limit) break;
        
        // Try to parse as structured log
        if (preg_match('/^\[(.*?)\](.*)$/', $line, $matches)) {
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
            
            $entries[] = $entry;
        }
    }
    
    return $entries;
}

// Parse both logs
$sendEntries = parseLogEntries($sendLog, 500);
$eventEntries = parseLogEntries($eventLog, 500);

// Combine and sort by timestamp
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

// Sort by timestamp (reverse chronological)
usort($allEntries, function($a, $b) {
    $timeA = strtotime($a['timestamp']);
    $timeB = strtotime($b['timestamp']);
    return $timeB - $timeA;
});

// Apply filters
$filtered = $allEntries;

if ($filterType !== 'all') {
    $filtered = array_filter($filtered, function($entry) use ($filterType) {
        if ($filterType === 'sends') return $entry['source'] === 'send';
        if ($filterType === 'events') return $entry['source'] === 'event';
        if ($filterType === 'deferred') return strpos($entry['raw'], 'DEFERRED') !== false || strpos($entry['raw'], 'deferred') !== false;
        if ($filterType === 'fails') return strpos($entry['raw'], 'FAIL') !== false || strpos($entry['raw'], 'ERROR') !== false;
        return true;
    });
}

// Apply search
if (!empty($searchQuery)) {
    $filtered = array_filter($filtered, function($entry) use ($searchQuery) {
        return stripos($entry['raw'], $searchQuery) !== false;
    });
}

// Limit results
$displayed = array_slice($filtered, 0, $lines);

// Calculate stats
$stats = [
    'total_sends' => count(array_filter($sendEntries, fn($e) => $e['data']['SUCCESS'] === 'OK')),
    'total_fails' => count(array_filter($sendEntries, fn($e) => $e['data']['SUCCESS'] === 'FAIL')),
    'total_events' => count($eventEntries),
    'delivered' => count(array_filter($eventEntries, fn($e) => strpos($e['raw'], 'DELIVERED') !== false)),
    'deferred' => count(array_filter($eventEntries, fn($e) => strpos($e['raw'], 'DEFERRED') !== false)),
    'bounced' => count(array_filter($eventEntries, fn($e) => strpos($e['raw'], 'BOUNCE') !== false)),
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📧 Email Logs Viewer</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        :root {
            --primary: #667eea;
            --success: #48bb78;
            --warning: #f6ad55;
            --danger: #f56565;
            --info: #4299e1;
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 600;
            font-size: 1.3rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .stats-card.success { border-left-color: var(--success); }
        .stats-card.danger { border-left-color: var(--danger); }
        .stats-card.warning { border-left-color: var(--warning); }
        .stats-card.info { border-left-color: var(--info); }
        
        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0;
        }
        
        .stats-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .filter-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .btn-filter {
            margin: 5px;
        }
        
        .btn-filter.active {
            background: var(--primary);
            color: white;
        }
        
        .log-entry {
            background: white;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 3px solid #e0e0e0;
            transition: all 0.2s ease;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            overflow-x: auto;
        }
        
        .log-entry:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateX(4px);
        }
        
        .log-entry.send {
            border-left-color: var(--info);
        }
        
        .log-entry.event {
            border-left-color: var(--warning);
        }
        
        .log-entry.success {
            border-left-color: var(--success);
        }
        
        .log-entry.fail {
            border-left-color: var(--danger);
            background: #fff5f5;
        }
        
        .log-entry.deferred {
            border-left-color: #ed8936;
            background: #fffbf0;
        }
        
        .log-timestamp {
            color: #999;
            font-size: 0.8rem;
        }
        
        .log-type {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 8px;
        }
        
        .log-type.send {
            background: #d4e5f7;
            color: #2c5aa0;
        }
        
        .log-type.event {
            background: #fef5e7;
            color: #b89e1b;
        }
        
        .log-type.processed {
            background: #d4edda;
            color: #155724;
        }
        
        .log-type.delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .log-type.deferred {
            background: #fff3cd;
            color: #856404;
        }
        
        .log-type.bounced {
            background: #f8d7da;
            color: #721c24;
        }
        
        .corr-id {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .corr-id:hover {
            background: var(--primary);
            color: white;
        }
        
        .corr-id.highlighted {
            background: var(--primary);
            color: white;
        }
        
        .alert-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .badge-status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-ok { background: #d4edda; color: #155724; }
        .badge-fail { background: #f8d7da; color: #721c24; }
        .badge-smtp { background: #d4e5f7; color: #2c5aa0; }
        
        .no-data {
            background: white;
            padding: 40px;
            text-align: center;
            border-radius: 8px;
            color: #999;
        }
        
        .refresh-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: var(--success);
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .refresh-indicator.active {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand">📧 Email Logs Viewer</span>
            <span class="text-white"><small>Last updated: <span id="update-time">now</span></small></span>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="stats-card">
                    <div class="stats-label">✅ Sent</div>
                    <div class="stats-value"><?php echo $stats['total_sends']; ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card danger">
                    <div class="stats-label">❌ Failed</div>
                    <div class="stats-value"><?php echo $stats['total_fails']; ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card success">
                    <div class="stats-label">📬 Delivered</div>
                    <div class="stats-value"><?php echo $stats['delivered']; ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card warning">
                    <div class="stats-label">⏸️ Deferred</div>
                    <div class="stats-value"><?php echo $stats['deferred']; ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card danger">
                    <div class="stats-label">📪 Bounced</div>
                    <div class="stats-value"><?php echo $stats['bounced']; ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stats-card info">
                    <div class="stats-label">📊 Total Events</div>
                    <div class="stats-value"><?php echo $stats['total_events']; ?></div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h5 class="mb-3">🔍 Filter & Search</h5>
            <form method="GET" class="row g-3 align-items-end">
                <!-- Filter Buttons -->
                <div class="col-12">
                    <div>
                        <strong>Type:</strong>
                        <a href="?filter=all" class="btn btn-sm btn-outline-primary btn-filter <?php echo $filterType === 'all' ? 'active' : ''; ?>">All</a>
                        <a href="?filter=sends" class="btn btn-sm btn-outline-info btn-filter <?php echo $filterType === 'sends' ? 'active' : ''; ?>">Sends</a>
                        <a href="?filter=events" class="btn btn-sm btn-outline-warning btn-filter <?php echo $filterType === 'events' ? 'active' : ''; ?>">Events</a>
                        <a href="?filter=deferred" class="btn btn-sm btn-outline-warning btn-filter <?php echo $filterType === 'deferred' ? 'active' : ''; ?>">Deferred</a>
                        <a href="?filter=fails" class="btn btn-sm btn-outline-danger btn-filter <?php echo $filterType === 'fails' ? 'active' : ''; ?>">Failures</a>
                    </div>
                </div>

                <!-- Search & Settings -->
                <div class="col-md-6">
                    <input type="text" class="form-control" name="search" placeholder="Search email, subject, error..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="lines">
                        <option value="20" <?php echo $lines == 20 ? 'selected' : ''; ?>>Last 20</option>
                        <option value="50" <?php echo $lines == 50 ? 'selected' : ''; ?>>Last 50</option>
                        <option value="100" <?php echo $lines == 100 ? 'selected' : ''; ?>>Last 100</option>
                        <option value="200" <?php echo $lines == 200 ? 'selected' : ''; ?>>Last 200</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="auto" value="1" <?php echo $autoRefresh ? 'checked' : ''; ?> id="autoRefresh">
                        <label class="form-check-label" for="autoRefresh">
                            <span class="refresh-indicator <?php echo $autoRefresh ? 'active' : ''; ?>"></span>Auto-refresh
                        </label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">🔍 Search</button>
                </div>
            </form>
        </div>

        <!-- Results Info -->
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <strong>📊 Showing:</strong> <?php echo count($displayed); ?> of <?php echo count($filtered); ?> filtered entries
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <!-- Log Entries -->
        <?php if (empty($displayed)): ?>
            <div class="no-data">
                <h4>📭 No logs found</h4>
                <p>Check your filters or try a different search query</p>
            </div>
        <?php else: ?>
            <?php foreach ($displayed as $entry): 
                $isDeferred = strpos($entry['raw'], 'DEFERRED') !== false;
                $isFail = strpos($entry['raw'], 'FAIL') !== false;
                $isSmtp = strpos($entry['raw'], 'smtp') !== false;
                $isSend = $entry['source'] === 'send';
                $eventType = strtoupper($entry['data']['EVENT'] ?? '');
                ?>
                <div class="log-entry <?php echo $isDeferred ? 'deferred' : ($isFail ? 'fail' : ($isSend ? 'send' : 'event')); ?>">
                    <!-- Header with Type Badge -->
                    <div class="mb-2">
                        <?php if ($isSend): ?>
                            <span class="log-type send">📤 SEND</span>
                        <?php else: ?>
                            <span class="log-type event"><?php echo htmlspecialchars($eventType); ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($entry['data']['SUCCESS'])): ?>
                            <span class="badge-status badge-<?php echo $entry['data']['SUCCESS'] === 'OK' ? 'ok' : 'fail'; ?>">
                                <?php echo $entry['data']['SUCCESS'] === 'OK' ? '✅ OK' : '❌ FAIL'; ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if (strpos($entry['raw'], 'smtp') !== false): ?>
                            <span class="badge-status badge-smtp">📧 SMTP</span>
                        <?php endif; ?>
                        
                        <span class="log-timestamp"><?php echo htmlspecialchars($entry['timestamp']); ?></span>
                    </div>

                    <!-- Alert Badge for Deferred -->
                    <?php if ($isDeferred): ?>
                        <div class="alert-box mb-2">
                            <strong>⚠️ DEFERRED:</strong> <?php echo isset($entry['data']['REASON']) ? htmlspecialchars($entry['data']['REASON']) : 'Check logs for details'; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Log Details -->
                    <div>
                        <?php if (!empty($entry['data']['CORR_ID'])): ?>
                            <strong>CORR_ID:</strong> <span class="corr-id" onclick="searchCorrelation(this)"><?php echo htmlspecialchars($entry['data']['CORR_ID']); ?></span> | 
                        <?php endif; ?>
                        
                        <?php if (!empty($entry['data']['TO'])): ?>
                            <strong>TO:</strong> <code><?php echo htmlspecialchars($entry['data']['TO']); ?></code> | 
                        <?php endif; ?>
                        
                        <?php if (!empty($entry['data']['EMAIL'])): ?>
                            <strong>EMAIL:</strong> <code><?php echo htmlspecialchars($entry['data']['EMAIL']); ?></code> | 
                        <?php endif; ?>
                        
                        <?php if (!empty($entry['data']['SUBJECT'])): ?>
                            <strong>SUBJECT:</strong> <?php echo htmlspecialchars($entry['data']['SUBJECT']); ?> | 
                        <?php endif; ?>
                        
                        <?php if (!empty($entry['data']['TRANSPORT'])): ?>
                            <strong>TRANSPORT:</strong> <code><?php echo htmlspecialchars($entry['data']['TRANSPORT']); ?></code> | 
                        <?php endif; ?>
                        
                        <?php if (!empty($entry['data']['STATUS'])): ?>
                            <strong>STATUS:</strong> <code><?php echo htmlspecialchars($entry['data']['STATUS']); ?></code>
                        <?php endif; ?>
                        
                        <?php if (!empty($entry['data']['ERROR']) && trim($entry['data']['ERROR']) !== ''): ?>
                            <div class="mt-2"><strong>ERROR:</strong> <code><?php echo htmlspecialchars($entry['data']['ERROR']); ?></code></div>
                        <?php endif; ?>
                    </div>

                    <!-- Raw Log for debugging -->
                    <div class="mt-2" style="font-size: 0.75rem; color: #999; border-top: 1px solid #eee; padding-top: 8px;">
                        <details>
                            <summary>📋 Raw Entry</summary>
                            <code style="color: #666;"><?php echo htmlspecialchars($entry['raw']); ?></code>
                        </details>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Auto-refresh script -->
    <script>
        // Update time
        function updateTime() {
            const now = new Date();
            document.getElementById('update-time').textContent = now.toLocaleTimeString();
        }
        updateTime();
        setInterval(updateTime, 1000);

        // Auto-refresh functionality
        function checkAutoRefresh() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('auto') === '1') {
                setTimeout(() => location.reload(), 5000);
            }
        }
        checkAutoRefresh();

        // Search by correlation ID
        function searchCorrelation(element) {
            const corrId = element.textContent;
            window.location.href = '?search=' + encodeURIComponent(corrId);
        }

        // Highlight matching text
        function highlightMatches() {
            const urlParams = new URLSearchParams(window.location.search);
            const search = urlParams.get('search');
            if (!search) return;

            const entries = document.querySelectorAll('.log-entry');
            entries.forEach(entry => {
                const text = entry.textContent.toLowerCase();
                if (text.includes(search.toLowerCase())) {
                    entry.querySelectorAll('code, .corr-id').forEach(el => {
                        if (el.textContent.includes(search)) {
                            el.style.backgroundColor = '#ffd700';
                            el.style.borderRadius = '3px';
                        }
                    });
                }
            });
        }
        highlightMatches();
    </script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</body>
</html>
