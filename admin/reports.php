<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require '../connections/connections.php';
$pdo = connection();

$con = connection();
$con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Patients Widget: Get new patients from the past month and compare it to the previous month
$current_month = date('Y-m-01');
$last_month = date('Y-m-01', strtotime('-1 month'));

$queryNewPatients = $pdo->prepare("
    SELECT COUNT(*) as total FROM patients 
    WHERE created_at >= :current_month
");
$queryNewPatients->execute(['current_month' => $current_month]);
$newPatients = $queryNewPatients->fetch(PDO::FETCH_ASSOC)['total'];

$queryLastMonthPatients = $pdo->prepare("
    SELECT COUNT(*) as total FROM patients 
    WHERE created_at >= :last_month AND created_at < :current_month
");
$queryLastMonthPatients->execute(['last_month' => $last_month, 'current_month' => $current_month]);
$lastMonthPatients = $queryLastMonthPatients->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate percentage increase
$patientIncrease = ($lastMonthPatients > 0) ? (($newPatients - $lastMonthPatients) / $lastMonthPatients) * 100 : 0;

// Appointments Widget: Get today's appointments and compare to the same day last week
$today = date('Y-m-d');
$lastWeek = date('Y-m-d', strtotime('-7 days'));

$queryAppointmentsToday = $pdo->prepare("
    SELECT COUNT(*) as total FROM appointments 
    WHERE DATE(scheduled_date) = :today
");
$queryAppointmentsToday->execute(['today' => $today]);
$appointmentsToday = $queryAppointmentsToday->fetch(PDO::FETCH_ASSOC)['total'];

$queryAppointmentsLastWeek = $pdo->prepare("
    SELECT COUNT(*) as total FROM appointments 
    WHERE DATE(scheduled_date) = :last_week
");
$queryAppointmentsLastWeek->execute(['last_week' => $lastWeek]);
$appointmentsLastWeek = $queryAppointmentsLastWeek->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate percentage change
$appointmentsChange = ($appointmentsLastWeek > 0) ? (($appointmentsToday - $appointmentsLastWeek) / $appointmentsLastWeek) * 100 : 0;

// Rooms Widget: Get available and occupied rooms
$queryRooms = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'Available' THEN 1 END) AS available_rooms,
        COUNT(CASE WHEN status = 'Occupied' THEN 1 END) AS occupied_rooms
    FROM rooms
");
$queryRooms->execute();
$roomData = $queryRooms->fetch(PDO::FETCH_ASSOC);
$availableRooms = $roomData['available_rooms'];
$occupiedRooms = $roomData['occupied_rooms'];

// Get the first day of the current and last month
$current_month = date('Y-m-01');
$last_month = date('Y-m-01', strtotime('-1 month'));

// Function to fetch total sales (billing_header + billing_items)
function getTotalSales($pdo, $startDate, $endDate = null) {
    $query = "
        SELECT 
            SUM(bh.service_amount + COALESCE(bi.total_item_amount, 0)) AS total_sales
        FROM billing_header bh
        LEFT JOIN (
            SELECT billing_id, SUM(item_amount) AS total_item_amount 
            FROM billing_items 
            GROUP BY billing_id
        ) bi ON bh.billing_id = bi.billing_id
        WHERE bh.billing_date >= :start_date" . ($endDate ? " AND bh.billing_date < :end_date" : "");

    $stmt = $pdo->prepare($query);

    // Bind parameters based on whether an end date is required
    if ($endDate) {
        $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
    } else {
        $stmt->execute(['start_date' => $startDate]);
    }

    return $stmt->fetch(PDO::FETCH_ASSOC)['total_sales'] ?? 0;
}

// Fetch total sales for this month and last month
$salesThisMonth = getTotalSales($pdo, $current_month);
$salesLastMonth = getTotalSales($pdo, $last_month, $current_month);

// Calculate percentage change in sales
$salesChange = ($salesLastMonth > 0) ? (($salesThisMonth - $salesLastMonth) / $salesLastMonth) * 100 : 0;

// Get daily appointment counts for the last 7 days
$queryAppointments = $pdo->prepare("
    SELECT DATE(scheduled_date) AS appointment_date, COUNT(*) AS total
    FROM appointments
    WHERE scheduled_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(scheduled_date)
    ORDER BY appointment_date ASC
");
$queryAppointments->execute();
$appointmentsData = $queryAppointments->fetchAll(PDO::FETCH_ASSOC);

// Get filter values from GET
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role = isset($_GET['role']) ? trim($_GET['role']) : '';
$startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where = [];
$params = [];
if ($search !== '') {
    $where[] = 'al.action LIKE :search';
    $params['search'] = "%$search%";
}
if ($role !== '') {
    $where[] = 'u.role = :role';
    $params['role'] = $role;
}
if ($startDate !== '') {
    $where[] = 'DATE(al.timestamp) >= :start_date';
    $params['start_date'] = $startDate;
}
if ($endDate !== '') {
    $where[] = 'DATE(al.timestamp) <= :end_date';
    $params['end_date'] = $endDate;
}
$whereSQL = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Fetch filtered logs with pagination
$recentLogsQuery = "
    SELECT al.*, u.role 
    FROM activity_log al
    JOIN users u ON al.user_id = u.user_id
    $whereSQL
    ORDER BY al.timestamp DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($recentLogsQuery);
foreach ($params as $key => $val) {
    $stmt->bindValue(":$key", $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch total count for pagination
$totalQuery = "SELECT COUNT(*) FROM activity_log al JOIN users u ON al.user_id = u.user_id $whereSQL";
$totalStmt = $pdo->prepare($totalQuery);
foreach ($params as $key => $val) {
    $totalStmt->bindValue(":$key", $val);
}
$totalStmt->execute();
$totalCount = $totalStmt->fetchColumn();
$totalPages = ceil($totalCount / $limit);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css"><!-- Sidebar styles -->
    <link rel="stylesheet" href="../css/components.css"><!-- Table styles -->
    <link rel="stylesheet" href="../css/widgets.css"><!-- Widgets styles -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2E8B57;
            --primary-light: #3CB371;
            --secondary-color: #f8f9fa;
            --text-color: #333;
            --border-color: #eee;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
        }
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        .dashboard-main-content {
            flex-grow: 1;
            padding: 20px;
            margin-left: 270px;
            transition: all 0.4s ease;
            background-color: #f8f9fa;
        }
        .sidebar.collapsed ~ .dashboard-main-content {
            margin-left: 85px;
        }
        @media (max-width: 768px) {
            .dashboard-main-content {
                margin-left: 0;
                padding: 20px;
            }
            .sidebar.collapsed ~ .dashboard-main-content {
                margin-left: 0;
                padding-left: 85px;
            }
        }
        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .widgets-row {
            margin-bottom: 25px;
        }
        .widget {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 22px 18px;
            display: flex;
            align-items: center;
            gap: 18px;
            min-height: 120px;
        }
        .widget .icon-container {
            font-size: 2.2rem;
            color: white;
            background: #eafaf1;
            border-radius: 50%;
            width: 54px;
            height: 54px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .widget .text-container h5 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.2rem;
            color: var(--primary-color);
        }
        .widget .text-container .h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
            color: #333;
        }
        .widget .text-container p {
            margin-bottom: 0;
            font-size: 0.95rem;
            color: #888;
        }
        .table-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table thead th {
            background-color: var(--secondary-color);
            color: var(--text-color);
            font-weight: 600;
            border-bottom: 2px solid var(--primary-color);
        }
        .table tbody tr:hover {
            background-color: rgba(46, 139, 87, 0.05);
        }
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        /* Additional styles for reports page */
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stats-card h6 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .stats-card .value {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .stats-card .trend {
            font-size: 0.9rem;
            color: #666;
        }
        
        .trend.up {
            color: #28a745;
        }
        
        .trend.down {
            color: #dc3545;
        }
        
        .search-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .search-container .form-control,
        .search-container .form-select {
            min-width: 120px;
            height: 38px;
            font-size: 1rem;
        }
        .search-container .input-group .form-control {
            min-width: 220px;
        }
        .search-container .btn,
        .search-container .form-select {
            height: 38px;
            font-size: 1rem;
        }
        .search-container form.row {
            flex-wrap: nowrap !important;
            gap: 0.5rem;
        }
        @media (max-width: 991.98px) {
            .search-container form.row {
                flex-wrap: wrap !important;
            }
        }
        
        .pagination {
            margin-top: 20px;
        }
        
        .page-link {
            color: var(--primary-color);
            border-color: var(--border-color);
        }
        
        .page-link:hover {
            color: var(--primary-light);
            background-color: var(--secondary-color);
        }
        
        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
    <?php include '../sidebar.php'; ?>

        <!-- Main Content -->
        <main class="dashboard-main-content">
            <?php include '../admin/breadcrumb.php'; ?>
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h2 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </h2>
                    </div>
                </div>
            </div>

            <!-- Widgets Section -->
            <div class="row g-3 widgets-row">
                <!-- New Patients Widget -->
                <div class="col-md-3 new-patients">
                    <div class="widget">
                        <div class="icon-container">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="text-container">
                            <h5>New Patients</h5>
                            <div class="h2">+<?php echo number_format($newPatients); ?></div>
                            <p><?php echo number_format($patientIncrease, 2); ?>% from last month</p>
                        </div>
                    </div>
                </div>
                <!-- Today's Appointment Widget -->
                <div class="col-md-3 todays-appointment">
                    <div class="widget">
                        <div class="icon-container">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="text-container">
                            <h5>Today's Appointment</h5>
                            <div class="h2"><?php echo number_format($appointmentsToday); ?></div>
                            <p><?php echo number_format($appointmentsChange, 2); ?>% from last week</p>
                        </div>
                    </div>
                </div>
                <!-- Available Rooms Widget -->
                <div class="col-md-3 available-rooms">
                    <div class="widget">
                        <div class="icon-container">
                            <i class="fas fa-bed"></i>
                        </div>
                        <div class="text-container">
                            <h5>Available Rooms</h5>
                            <div class="h2"><?php echo number_format($availableRooms); ?></div>
                            <p><?php echo number_format($occupiedRooms); ?> room is occupied</p>
                        </div>
                    </div>
                </div>
                <!-- Sales Widget -->
                <div class="col-md-3 sales">
                    <div class="widget">
                        <div class="icon-container">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="text-container">
                            <h5>Sales</h5>
                            <div class="h2">₱<?php echo number_format($salesThisMonth, 2); ?></div>
                            <p><?php echo number_format($salesChange, 2); ?>% from last month</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="search-container">
                <form id="searchForm" class="row g-2 align-items-center flex-nowrap" style="flex-wrap:nowrap;" method="get">
                    <div class="col-auto flex-grow-1">
                        <div class="input-group">
                            <input type="text" id="searchInput" name="search" class="form-control" placeholder="Search logs by action..." value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-outline-success" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-auto">
                        <input type="date" id="startDate" name="start_date" class="form-control" placeholder="Start Date" value="<?= htmlspecialchars($startDate) ?>">
                    </div>
                    <div class="col-auto px-0" style="width:auto;">
                        <span>to</span>
                    </div>
                    <div class="col-auto">
                        <input type="date" id="endDate" name="end_date" class="form-control" placeholder="End Date" value="<?= htmlspecialchars($endDate) ?>">
                    </div>
                    <div class="col-auto">
                        <select id="roleFilter" name="role" class="form-select">
                            <option value="">All Roles</option>
                            <option value="Admin" <?= $role === 'Admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="Staff" <?= $role === 'Staff' ? 'selected' : '' ?>>Staff</option>
                            <option value="Doctor" <?= $role === 'Doctor' ? 'selected' : '' ?>>Doctor</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <a href="reports.php" class="btn btn-secondary">
                            <i class="fas fa-redo-alt me-2"></i>Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Reports Table -->
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">Activity Logs</h4>
                    <span class="text-muted">Total Records: <?= $totalCount ?></span>
                </div>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User ID</th>
                            <th>Role</th>
                            <th>Action</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentLogs)): ?>
                            <?php foreach ($recentLogs as $index => $log): ?>
                                <tr>
                                    <td><?= (($page - 1) * $limit) + $index + 1 ?></td>
                                    <td><?= htmlspecialchars($log['user_id']) ?></td>
                                    <td><?= htmlspecialchars($log['role']) ?></td>
                                    <td><?= htmlspecialchars($log['action']) ?></td>
                                    <td><?= (new DateTime($log['timestamp']))->format('F j, Y g:i a') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-info-circle me-2"></i>No activity logs found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>1])) ?>" aria-label="First">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>max(1, $page-1)])) ?>" aria-label="Previous">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$i])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>min($totalPages, $page+1)])) ?>" aria-label="Next">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$totalPages])) ?>" aria-label="Last">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');
        const startDate = document.getElementById('startDate');
        const endDate = document.getElementById('endDate');
        const roleFilter = document.getElementById('roleFilter');
        
        let searchTimeout;

        function handleSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchForm.submit();
            }, 500); // 500ms delay for search input
        }

        // Add event listeners for live search
        searchInput.addEventListener('input', handleSearch);
        startDate.addEventListener('change', () => searchForm.submit());
        endDate.addEventListener('change', () => searchForm.submit());
        roleFilter.addEventListener('change', () => searchForm.submit());

        // Validate date range
        startDate.addEventListener('change', function() {
            if (endDate.value && this.value > endDate.value) {
                endDate.value = this.value;
            }
        });

        endDate.addEventListener('change', function() {
            if (startDate.value && this.value < startDate.value) {
                startDate.value = this.value;
            }
        });
    });
    </script>
</body>

</html>
