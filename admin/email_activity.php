<?php
/**
 * Real-Time Email Activity Dashboard
 * Live updating dashboard to monitor email sends and events
 */

session_start();

// Simple auth check
if (!isset($_SESSION['email_logs_auth'])) {
    if (!isset($_GET['auth']) || $_GET['auth'] !== '1') {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Email Activity - Auth Required</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
            <style>
                body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
                .auth-card { background: white; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); padding: 40px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="auth-card mx-auto" style="max-width: 400px;">
                    <h3 class="text-center mb-4">🔐 Email Activity</h3>
                    <form method="GET">
                        <input type="hidden" name="auth" value="1">
                        <div class="mb-3">
                            <label class="form-label">Admin Password</label>
                            <input type="password" class="form-control" name="password" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Access</button>
                    </form>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    // Verify password
    $envFile = __DIR__ . '/../.env';
    $password = '';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Email Activity Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .dashboard-header {
            color: white;
            padding: 20px 0;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .metric-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
        }
        
        .metric-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .activity-feed {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            height: 600px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 10px;
            white-space: nowrap;
        }
        
        .badge-send { background: #d4e5f7; color: #2c5aa0; }
        .badge-delivered { background: #d4edda; color: #155724; }
        .badge-deferred { background: #fff3cd; color: #856404; }
        .badge-bounced { background: #f8d7da; color: #721c24; }
        .badge-ok { background: #d4edda; color: #155724; }
        .badge-fail { background: #f8d7da; color: #721c24; }
        
        .activity-content {
            flex: 1;
            min-width: 0;
        }
        
        .activity-email {
            color: #2c5aa0;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
        }
        
        .activity-time {
            color: #999;
            font-size: 0.8rem;
            margin-top: 4px;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 15px;
            animation: pulse 2s infinite;
        }
        
        .status-ok { background: #48bb78; }
        .status-fail { background: #f56565; }
        .status-deferred { background: #ed8936; }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .no-activity {
            text-align: center;
            color: #999;
            padding: 40px 20px;
        }
        
        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            border: none;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .refresh-btn:hover {
            transform: scale(1.1) rotate(180deg);
            background: #764ba2;
        }
        
        .alert-important {
            background: #fff5f5;
            border-left: 4px solid #f56565;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark sticky-top">
        <div class="container-fluid">
            <span class="navbar-brand">📊 Email Activity Dashboard</span>
            <small class="text-white">Live Updates • Auto-refresh every 5 seconds</small>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Metrics Row -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="metric-card">
                    <div class="metric-label">📤 Sent</div>
                    <div class="metric-number" id="stat-sent">0</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="metric-card" style="border-left-color: #48bb78;">
                    <div class="metric-label">📬 Delivered</div>
                    <div class="metric-number" id="stat-delivered" style="color: #48bb78;">0</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="metric-card" style="border-left-color: #ed8936;">
                    <div class="metric-label">⏸️ Deferred</div>
                    <div class="metric-number" id="stat-deferred" style="color: #ed8936;">0</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="metric-card" style="border-left-color: #f56565;">
                    <div class="metric-label">📪 Bounced</div>
                    <div class="metric-number" id="stat-bounced" style="color: #f56565;">0</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="metric-card" style="border-left-color: #f56565;">
                    <div class="metric-label">❌ Failed</div>
                    <div class="metric-number" id="stat-fails" style="color: #f56565;">0</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="metric-card" style="border-left-color: #4299e1;">
                    <div class="metric-label">📊 Events</div>
                    <div class="metric-number" id="stat-events" style="color: #4299e1;">0</div>
                </div>
            </div>
        </div>

        <!-- Activity Feed -->
        <div class="row">
            <div class="col-lg-12">
                <h4 class="mb-3">🔔 Recent Activity</h4>
                <div class="activity-feed" id="activity-feed">
                    <div class="no-activity">
                        <p>⏳ Loading activity...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Refresh Button -->
    <button class="refresh-btn" onclick="loadActivity()" title="Refresh">🔄</button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load activity data
        async function loadActivity() {
            try {
                const response = await fetch('<?php echo $_SERVER['REQUEST_URI'] ?>&api=1');
                const data = await response.json();
                
                // Update metrics
                document.getElementById('stat-sent').textContent = data.stats.total_sends;
                document.getElementById('stat-delivered').textContent = data.stats.delivered;
                document.getElementById('stat-deferred').textContent = data.stats.deferred;
                document.getElementById('stat-bounced').textContent = data.stats.bounced;
                document.getElementById('stat-fails').textContent = data.stats.total_fails;
                document.getElementById('stat-events').textContent = data.stats.total_events;
                
                // Update activity feed
                const feed = document.getElementById('activity-feed');
                if (data.entries.length === 0) {
                    feed.innerHTML = '<div class="no-activity"><p>📭 No activity yet</p></div>';
                    return;
                }
                
                let html = '';
                data.entries.slice(0, 30).forEach(entry => {
                    const badges = entry.badges.map(b => `<span class="activity-badge badge-${b.type}">${b.label}</span>`).join('');
                    const statusClass = entry.data.SUCCESS === 'OK' ? 'status-ok' : 
                                       entry.data.SUCCESS === 'FAIL' ? 'status-fail' : 'status-deferred';
                    
                    let email = entry.data.TO || entry.data.EMAIL || 'unknown';
                    let info = entry.data.SUBJECT || entry.data.EVENT || '';
                    
                    html += `
                        <div class="activity-item">
                            <div class="status-indicator ${statusClass}"></div>
                            <div class="activity-content">
                                <div>${badges}</div>
                                <div class="activity-email">${escapeHtml(email)}</div>
                                <div class="activity-time">${escapeHtml(entry.timestamp)} • ${escapeHtml(info.substring(0, 50))}</div>
                            </div>
                        </div>
                    `;
                });
                feed.innerHTML = html;
            } catch (error) {
                console.error('Error loading activity:', error);
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Load on page load
        loadActivity();

        // Auto-refresh every 5 seconds
        setInterval(loadActivity, 5000);
    </script>

    <?php
    // API endpoint handling
    if (isset($_GET['api']) && $_GET['api'] === '1') {
        header('Content-Type: application/json');
        
        $logsDir = __DIR__ . '/../logs';
        $sendLog = $logsDir . '/email_sends.log';
        $eventLog = $logsDir . '/sendgrid_events.log';

        function parseLogEntry($line) {
            if (empty(trim($line))) return null;
            if (!preg_match('/^\[(.*?)\](.*)$/', $line, $matches)) return null;
            
            $timestamp = $matches[1];
            $data = $matches[2];
            $entry = ['timestamp' => $timestamp, 'raw' => $line, 'data' => []];
            
            if (preg_match_all('/(\w+):\s*([^|]*?)(?:\s*\||$)/', $data, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $key = trim($match[1]);
                    $value = trim($match[2]);
                    $entry['data'][$key] = $value;
                }
            }
            return $entry;
        }

        function getLogEntries($filePath) {
            if (!file_exists($filePath)) return [];
            $entries = [];
            foreach (array_reverse(file($filePath, FILE_IGNORE_NEW_LINES)) as $line) {
                $entry = parseLogEntry($line);
                if ($entry) $entries[] = $entry;
            }
            return $entries;
        }

        $sendEntries = getLogEntries($sendLog);
        $eventEntries = getLogEntries($eventLog);

        $allEntries = [];
        foreach ($sendEntries as $e) { $e['source'] = 'send'; $allEntries[] = $e; }
        foreach ($eventEntries as $e) { $e['source'] = 'event'; $allEntries[] = $e; }

        usort($allEntries, fn($a, $b) => strtotime($b['timestamp']) - strtotime($a['timestamp']));

        $response = [
            'entries' => array_slice($allEntries, 0, 50),
            'stats' => [
                'total_sends' => count(array_filter($sendEntries, fn($e) => $e['data']['SUCCESS'] === 'OK')),
                'total_fails' => count(array_filter($sendEntries, fn($e) => $e['data']['SUCCESS'] === 'FAIL')),
                'total_events' => count($eventEntries),
                'delivered' => count(array_filter($eventEntries, fn($e) => stripos($e['raw'], 'DELIVERED') !== false)),
                'deferred' => count(array_filter($eventEntries, fn($e) => stripos($e['raw'], 'DEFERRED') !== false)),
                'bounced' => count(array_filter($eventEntries, fn($e) => stripos($e['raw'], 'BOUNCE') !== false)),
            ]
        ];

        echo json_encode($response);
        exit;
    }
    ?>
</body>
</html>
