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
</head>

<body style="font-family: 'Poppins', sans-serif;">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="sidebar-header">
                <img src="../PSC_banner.png" alt="Paanakan Logo">
            </div>
            <ul>
                <li><a href="dashboard.php" class="active"><span class="material-icons">dashboard</span><span class="link-text">Dashboard</span></a></li>
                <li><a href="manage_appointments.php"><span class="material-icons">event</span><span class="link-text">Appointments</span></a></li>
                <li><a href="manage_health_records.php"><span class="material-icons">folder</span><span class="link-text">Health Records</span></a></li>
                <li><a href="transactions.php"><span class="material-icons">local_hospital</span><span class="link-text">Medical Services</span></a></li>
                <li><a href="patient.php"><span class="material-icons">person</span><span class="link-text">Patients</span></a></li>
                <li><a href="supply.php"><span class="material-icons">inventory_2</span><span class="link-text">Supplies</span></a></li>
                <li><a href="billing.php"><span class="material-icons">receipt</span><span class="link-text">Billing</span></a></li>
                <li><a href="reports.php"><span class="material-icons">assessment</span><span class="link-text">Reports</span></a></li>
                <li><a href="manage_users.php"><span class="material-icons">people</span><span class="link-text">Users</span></a></li>
                <li><a href="logs.php"><span class="material-icons">history</span><span class="link-text">Logs</span></a></li>
                <li><a href="../logout.php"><span class="material-icons">logout</span><span class="link-text">Logout</span></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main-content">
            <div class="container mt-5">
                <!-- Summary Cards -->
                <div class="row g-3">
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="card shadow-sm rounded text-center p-3 h-100">
                            <div class="card-body">
                                <span class="material-icons text-primary" style="font-size: 40px;">people</span>
                                <h5 class="mt-2 fw-bold">Total Users</h5>
                                <p class="text-dark fw-bold">
                                    <?php
                                    $totalUsersQuery = "SELECT COUNT(*) AS count FROM users";
                                    $totalUsers = $con->query($totalUsersQuery)->fetch()['count'];
                                    echo $totalUsers;
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="card shadow-sm rounded text-center p-3 h-100">
                            <div class="card-body">
                                <span class="material-icons text-warning" style="font-size: 40px;">check_circle</span>
                                <h5 class="mt-2 fw-bold">Successful Logins</h5>
                                <p class="text-dark fw-bold">
                                    <?php
                                    $successfulLoginsQuery = "SELECT COUNT(*) AS count FROM logs WHERE action = 'Login Successful'";
                                    $successfulLogins = $con->query($successfulLoginsQuery)->fetch()['count'];
                                    echo $successfulLogins;
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="card shadow-sm rounded text-center p-3 h-100">
                            <div class="card-body">
                                <span class="material-icons text-danger" style="font-size: 40px;">history</span>
                                <h5 class="mt-2 fw-bold">Total Logs</h5>
                                <p class="text-dark fw-bold">
                                    <?php
                                    echo $totalLogs;
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="mt-5 table-container shadow rounded bg-white p-3">
                <h4 class="mb-3">Recent Logs</h4>
                    <table class="table table-striped w-100">
                        <thead>
                            <tr>
                                <th>Log ID</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($logs)): ?>
                                <?php $counter = 1; // Initialize the counter ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= $counter++ ?></td> <!-- Display sequential number -->
                                        <td><?= htmlspecialchars($log['username'] ?? 'Guest') ?></td>
                                        <td><?= htmlspecialchars($log['role'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($log['action']) ?></td>
                                        <td><?= (new DateTime($log['timestamp']))->format('F j, Y g:i a') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No logs found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav>
                    <ul class="pagination justify-content-center mt-4">
                        <!-- Start Button -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1" aria-label="Start">
                                    <span class="material-icons">first_page</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Previous Button -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                                    <span class="material-icons">chevron_left</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php
                        $startPage = max(1, $page - 1);
                        $endPage = min($totalPages, $page + 1);
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next Button -->
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                                    <span class="material-icons">chevron_right</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- End Button -->
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $totalPages ?>" aria-label="End">
                                    <span class="material-icons">last_page</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>


            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
