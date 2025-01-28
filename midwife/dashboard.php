<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Midwife') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
include_once '../connections/connections.php';

$pdo = connection();
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Placeholder data
$roomAvailability = 0;
$checkUpToday = 0;
$followUpCheckup = 0;

// Fetch today's appointment count for Pending and Approved statuses
$appointmentsCountQuery = "
    SELECT COUNT(*) AS count
    FROM Appointments
    WHERE DATE(scheduled_date) = CURDATE()
      AND status IN ('Pending', 'Approved')";
$appointmentsCountResult = $pdo->query($appointmentsCountQuery)->fetch();
$appointmentsCount = $appointmentsCountResult['count'];

// Fetch appointments for today
$todayAppointmentsQuery = "
    SELECT a.appointment_id, a.scheduled_date, a.status, p.first_name, p.last_name, p.contact_number
    FROM Appointments a
    JOIN Patients p ON a.patient_id = p.patient_id
    WHERE DATE(a.scheduled_date) = CURDATE()
      AND a.status IN ('Pending', 'Approved')
    ORDER BY a.scheduled_date ASC";
$todayAppointments = $pdo->query($todayAppointmentsQuery)->fetchAll();

$statusCountsQuery = "
    SELECT status, COUNT(*) as count
    FROM Appointments
    GROUP BY status";
$statusCountsResult = $pdo->query($statusCountsQuery)->fetchAll();

// Prepare data for Chart.js
$statusData = [
    'Cancelled' => 0,
    'Approved' => 0,
    'Pending' => 0,
];
foreach ($statusCountsResult as $row) {
    $statusData[$row['status']] = (int)$row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <!-- Google Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/sidebar.css"><!-- Sidebar styles -->
    <link rel="stylesheet" href="../css/components.css"><!-- Components styles -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <li><a href="manage_users.php"><span class="material-icons">people</span><span class="link-text">Users</span></a></li>
            <li><a href="logs.php"><span class="material-icons">history</span><span class="link-text">Logs</span></a></li>
            <li><a href="reports.php"><span class="material-icons">assessment</span><span class="link-text">Reports</span></a></li>
            <li><a href="../logout.php"><span class="material-icons">logout</span><span class="link-text">Logout</span></a></li>
        </ul>
    </aside>
   
    <!-- Main Content -->
    <main class="dashboard-main-content">
        <div class="container mt-5">
            <h2 class="mb-4 fw-bold">Dashboard</h2>
            <div class="row g-3">
                <!-- Room Availability Card -->
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="card shadow-sm rounded text-center p-3">
                        <div class="card-body">
                            <span class="material-icons text-success" style="font-size: 40px;">meeting_room</span>
                            <h5 class="mt-2 fw-bold">Room Availability</h5>
                            <p class="text-dark fw-bold"><?= $roomAvailability; ?></p>
                        </div>
                    </div>
                </div>
                <!-- Appointments Count Card -->
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="card shadow-sm rounded text-center p-3">
                        <div class="card-body">
                            <span class="material-icons text-primary" style="font-size: 40px;">event_note</span>
                            <h5 class="mt-2 fw-bold">No. of Appointments</h5>
                            <p class="text-dark fw-bold"><?= $appointmentsCount; ?></p>
                        </div>
                    </div>
                </div>
                <!-- Check-Up Today Card -->
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="card shadow-sm rounded text-center p-3">
                        <div class="card-body">
                            <span class="material-icons text-warning" style="font-size: 40px;">medical_services</span>
                            <h5 class="mt-2 fw-bold">Check Up Today</h5>
                            <p class="text-dark fw-bold"><?= $checkUpToday; ?></p>
                        </div>
                    </div>
                </div>
                <!-- Follow-Up Checkup Card -->
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <div class="card shadow-sm rounded text-center p-3">
                        <div class="card-body">
                            <span class="material-icons text-danger" style="font-size: 40px;">task</span>
                            <h5 class="mt-2 fw-bold">Follow Up Checkup</h5>
                            <p class="text-dark fw-bold"><?= $followUpCheckup; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <h2 style="text-align: center;">Appointment Status Overview</h2>
    <canvas id="myChart" style="max-width: 600px; margin: 0 auto;"></canvas>

    <script>
        // Data for the chart
        const data = {
            labels: ['Cancelled', 'Approved', 'Pending'], // Labels for segments
            datasets: [{
                label: 'Appointment Status',
                data: [10, 25, 15], // Data for each segment
                backgroundColor: ['#ff9999', '#66b3ff', '#99ff99'], // Segment colors
                hoverOffset: 4
            }]
        };

        // Chart configuration
        const config = {
            type: 'pie', // Chart type
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        };

        // Render the chart
        const ctx = document.getElementById('myChart').getContext('2d');
        new Chart(ctx, config);
    </script>
            <!-- Today's Appointments Table -->
            <div class="table-container shadow rounded bg-white p-3 mt-5">
                <h3 class="mb-4">Appointments Today</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Appointment ID</th>
                            <th>Patient Name</th>
                            <th>Contact Number</th>
                            <th>Scheduled Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($todayAppointments)): ?>
                            <?php foreach ($todayAppointments as $appointment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($appointment['appointment_id']); ?></td>
                                    <td><?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></td>
                                    <td><?= htmlspecialchars($appointment['contact_number']); ?></td>
                                    <td><?= htmlspecialchars($appointment['scheduled_date']); ?></td>
                                    <td>
                                        <span style="color: <?= 
                                            $appointment['status'] === 'Approved' ? 'green' : 'black'; ?>;">
                                            <?= htmlspecialchars($appointment['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No appointments scheduled for today.</td>
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
