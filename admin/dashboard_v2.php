<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
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
      AND status IN ('Approved', 'Ongoing')";
$appointmentsCountResult = $pdo->query($appointmentsCountQuery)->fetch();
$appointmentsCount = $appointmentsCountResult['count'];

// Define pagination variables
$limit = 5; // Number of rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total rows for pagination
$totalRowsQuery = "
    SELECT COUNT(*) AS count
    FROM Appointments
    WHERE DATE(scheduled_date) = CURDATE()
      AND status IN ('Approved', 'Ongoing')";
$totalRowsResult = $pdo->query($totalRowsQuery)->fetch();
$totalRows = $totalRowsResult['count'];
$totalPages = ceil($totalRows / $limit);

// Fetch paginated data for the table
$paginatedAppointmentsQuery = "
    SELECT a.appointment_id, a.scheduled_date, a.status, p.first_name, p.last_name, p.contact_number
    FROM Appointments a
    JOIN Patients p ON a.patient_id = p.patient_id
    WHERE DATE(a.scheduled_date) = CURDATE()
      AND a.status IN ('Approved', 'Ongoing')
    ORDER BY a.scheduled_date ASC
    LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($paginatedAppointmentsQuery);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$todayAppointments = $stmt->fetchAll();


// Fetch total appointment count
$totalAppointmentsQuery = "SELECT COUNT(*) AS total FROM Appointments";
$totalAppointmentsResult = $pdo->query($totalAppointmentsQuery)->fetch();
$totalAppointments = $totalAppointmentsResult['total'] ?? 0;

// Fetch today's appointment count
$todayAppointmentsCountQuery = "
    SELECT COUNT(*) AS count
    FROM Appointments
    WHERE DATE(scheduled_date) = CURDATE()";
$todayAppointmentsCountResult = $pdo->query($todayAppointmentsCountQuery)->fetch();
$todayAppointmentsCount = $todayAppointmentsCountResult['count'] ?? 0;

// Calculate percentage of today's appointments
$todayPercentage = ($totalAppointments > 0) ? round(($todayAppointmentsCount / $totalAppointments) * 100) : 0;

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
     <!-- Include Sidebar -->
     <?php include '../sidebar.php'; ?>
   
    <!-- Main Content -->
    <main class="dashboard-main-content">
        <div class="container mt-5">
            <!-- Breadcrumb Navigation -->
        <?php include '../admin/breadcrumb.php'; ?>
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
                <div class="row g-3">
                <!-- Appointments Table -->
                <div class="col-lg-8 col-md-12">
                    <div class="card table-container shadow rounded bg-white p-3">
                        <h3 class="mb-4">Appointments Today</h3>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Patient Name</th>
                                    <th>Contact Number</th>
                                    <th>Scheduled Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($todayAppointments)): ?>
                                    <?php 
                                    $counter = ($page - 1) * $limit + 1;  // Start counter based on page number and limit
                                    foreach ($todayAppointments as $appointment): ?>
                                        <tr>
                                            <!-- Sequential Number for Appointment ID -->
                                            <td><?= $counter++; ?></td> <!-- Use the counter and increment it -->
                                            <td><?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></td>
                                            <td><?= htmlspecialchars($appointment['contact_number']); ?></td>
                                            <td><?= (new DateTime($appointment['scheduled_date']))->format('F j, Y g:i a') ?></td>
                                            <td>
                                                <span style="color: <?= 
                                                    $appointment['status'] === 'Ongoing' ? 'green' : 'black'; ?>;">
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
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav>
                                <ul class="pagination justify-content mt-3">
                                    <!-- Previous Button -->
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Page Numbers -->
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <!-- Next Button -->
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    </div>
                </div>

                <!-- Appointment Status Chart -->
                <div class="col-lg-4 col-md-12">
                    <div class="card table-container shadow rounded text-center p-3 h-100">
                        <h5 class="mt-2 fw-bold">Today's Appointment Percentage</h5>
                        <div class="card-body">
                            <canvas id="statusChart" style="max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            </div>
                                        
            <script>
                    // Prepare data for the chart
                    const todayPercentage = <?= $todayPercentage; ?>;

                    const data = {
                        labels: ['Today\'s Appointments', 'Other Appointments'], // Labels for segments
                        datasets: [{
                            data: [<?= $todayAppointmentsCount; ?>, <?= $totalAppointments - $todayAppointmentsCount; ?>], // Data for each segment
                            backgroundColor: ['#264B96', '#F9A73E'], // Segment colors
                            hoverOffset: 4
                        }]
                    };

                    // Chart configuration
                    const config = {
                        type: 'doughnut', // Chart type
                        data: data,
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'bottom', // Legend at the bottom
                                }
                            },
                            cutout: '80%' // Converts pie chart to donut chart
                        },
                        plugins: [
                            {
                                id: 'centerText',
                                beforeDraw: function(chart) {
                                    const { width } = chart;
                                    const { height } = chart;
                                    const ctx = chart.ctx;
                                    ctx.restore();

                                    const fontSize = (height / 100).toFixed(2);
                                    ctx.font = `${fontSize}em Poppins`;

                                    const text = `${todayPercentage}%`;
                                    const textX = Math.round((width - ctx.measureText(text).width) / 2);
                                    const textY = height / 2;

                                    ctx.fillStyle = '#000'; // Text color
                                    ctx.fillText(text, textX, textY);
                                    ctx.save();
                                }
                            }
                        ]
                    };

                    // Render the chart
                    const ctx = document.getElementById('statusChart').getContext('2d');
                    new Chart(ctx, config);
                </script>


            
        </div>
    </main>
</div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>