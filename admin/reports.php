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

// Placeholder variables for data
$appointmentsToday = 0;
$totalUsers = 0;
$successfulLogins = 0;
$totalLogs = 0;

// Fetch data for reports
// Total appointments scheduled today
$appointmentsTodayQuery = "SELECT COUNT(*) AS count FROM appointments WHERE DATE(scheduled_date) = CURDATE()";
$appointmentsToday = $con->query($appointmentsTodayQuery)->fetch()['count'];

// Total users
$totalUsersQuery = "SELECT COUNT(*) AS count FROM users";
$totalUsers = $con->query($totalUsersQuery)->fetch()['count'];

// Total successful logins
$successfulLoginsQuery = "SELECT COUNT(*) AS count FROM logs WHERE action = 'Login Successful'";
$successfulLogins = $con->query($successfulLoginsQuery)->fetch()['count'];

// Total logs
$totalLogsQuery = "SELECT COUNT(*) AS count FROM logs";
$totalLogs = $con->query($totalLogsQuery)->fetch()['count'];
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
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css"><!-- Sidebar styles -->
    <link rel="stylesheet" href="../css/components.css"><!-- Table styles -->
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
                <h2 class="mb-4">Reports</h2>

                <!-- Summary Cards -->
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6 col-sm-12">
                        <div class="card shadow-sm rounded text-center p-3">
                            <div class="card-body">
                                <span class="material-icons text-success" style="font-size: 40px;">event</span>
                                <h5 class="mt-2 fw-bold">Appointments Today</h5>
                                <p class="text-dark fw-bold"><?= $appointmentsToday ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-12">
                        <div class="card shadow-sm rounded text-center p-3">
                            <div class="card-body">
                                <span class="material-icons text-primary" style="font-size: 40px;">people</span>
                                <h5 class="mt-2 fw-bold">Total Users</h5>
                                <p class="text-dark fw-bold"><?= $totalUsers ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-12">
                        <div class="card shadow-sm rounded text-center p-3">
                            <div class="card-body">
                                <span class="material-icons text-warning" style="font-size: 40px;">check_circle</span>
                                <h5 class="mt-2 fw-bold">Successful Logins</h5>
                                <p class="text-dark fw-bold"><?= $successfulLogins ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-12">
                        <div class="card shadow-sm rounded text-center p-3">
                            <div class="card-body">
                                <span class="material-icons text-danger" style="font-size: 40px;">history</span>
                                <h5 class="mt-2 fw-bold">Total Logs</h5>
                                <p class="text-dark fw-bold"><?= $totalLogs ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reports Table -->
                <div class="mt-5 table-container shadow rounded bg-white p-3">
                    <h4 class="mb-3">Recent Logs</h4>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Log ID</th>
                                <th>User ID</th>
                                <th>Action</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recentLogsQuery = "SELECT * FROM logs ORDER BY timestamp DESC LIMIT 10";
                            $recentLogs = $con->query($recentLogsQuery)->fetchAll();
                            ?>
                            <?php if (!empty($recentLogs)): ?>
                                <?php foreach ($recentLogs as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['log_id']) ?></td>
                                        <td><?= htmlspecialchars($log['user_id'] ?? 'Guest') ?></td>
                                        <td><?= htmlspecialchars($log['action']) ?></td>
                                        <td><?= htmlspecialchars($log['timestamp']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No logs found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
