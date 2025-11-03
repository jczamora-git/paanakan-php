<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require '../connections/connections.php';
$con = connection();
$con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Pagination setup
$limit = 10; // Number of logs per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total logs count
$totalLogsQuery = "SELECT COUNT(*) AS total FROM logs";
$totalLogsResult = $con->query($totalLogsQuery)->fetch();
$totalLogs = $totalLogsResult['total'];
$totalPages = ceil($totalLogs / $limit);

// Fetch logs with pagination
$logsQuery = "
    SELECT l.log_id, l.user_id, u.username, u.role, l.action, l.timestamp 
    FROM logs l 
    LEFT JOIN users u ON l.user_id = u.user_id 
    ORDER BY l.timestamp DESC 
    LIMIT :limit OFFSET :offset
";
$logsStmt = $con->prepare($logsQuery);
$logsStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$logsStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$logsStmt->execute();
$logs = $logsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Logs</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css"><!-- Sidebar styles -->
    <link rel="stylesheet" href="../css/components.css"><!-- Table styles -->
    <link rel="stylesheet" href="../css/logs.css"><!-- Table styles -->
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
        .summary-cards-row {
            margin-bottom: 25px;
        }
        .summary-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 22px 18px;
            display: flex;
            align-items: center;
            gap: 18px;
            min-height: 120px;
        }
        .summary-card .icon-container {
            font-size: 2.2rem;
            color: #fff;
            border-radius: 50%;
            width: 54px;
            height: 54px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .summary-card.users .icon-container {
            background: #2E8B57;
        }
        .summary-card.logins .icon-container {
            background: #ffc107;
        }
        .summary-card.logs .icon-container {
            background: #dc3545;
        }
        .summary-card .text-container h5 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.2rem;
            color: var(--primary-color);
        }
        .summary-card .text-container .h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
            color: #333;
        }
        .summary-card .text-container p {
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
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include '../sidebar.php'; ?>
        <main class="dashboard-main-content">
            <?php include '../admin/breadcrumb.php'; ?>
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h2 class="mb-0">
                            <i class="fas fa-clipboard-list me-2"></i>User Logs
                        </h2>
                    </div>
                </div>
            </div>
            <!-- Summary Cards -->
            <div class="row g-3 summary-cards-row">
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="summary-card users">
                        <div class="icon-container">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="text-container">
                            <h5>Total Users</h5>
                            <div class="h2">
                                <?php
                                $totalUsersQuery = "SELECT COUNT(*) AS count FROM users";
                                $totalUsers = $con->query($totalUsersQuery)->fetch()['count'];
                                echo $totalUsers;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="summary-card logins">
                        <div class="icon-container">
                            <i class="fas fa-sign-in-alt"></i>
                        </div>
                        <div class="text-container">
                            <h5>Successful Logins</h5>
                            <div class="h2">
                                <?php
                                $successfulLoginsQuery = "SELECT COUNT(*) AS count FROM logs WHERE action = 'Login Successful'";
                                $successfulLogins = $con->query($successfulLoginsQuery)->fetch()['count'];
                                echo $successfulLogins;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="summary-card logs">
                        <div class="icon-container">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="text-container">
                            <h5>Total Logs</h5>
                            <div class="h2"><?php echo $totalLogs; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Logs Table -->
            <div class="table-container">
                <h4 class="mb-3">Recent Logs</h4>
                <table class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Action</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= $counter++ ?></td>
                                    <td><?= htmlspecialchars($log['username'] ?? 'Guest') ?></td>
                                    <td><?= htmlspecialchars($log['role'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($log['action']) ?></td>
                                    <td><?= (new DateTime($log['timestamp']))->format('F j, Y g:i a') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-info-circle me-2"></i>No logs found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <nav>
                <ul class="pagination justify-content-center mt-4">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1" aria-label="Start">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php
                    $startPage = max(1, $page - 1);
                    $endPage = min($totalPages, $page + 1);
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $totalPages ?>" aria-label="End">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
