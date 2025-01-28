<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require '../connections/connections.php';

// Get the database connection
$pdo = connection();
// Handle appointment status update (Done or Missed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'], $_POST['action'])) {
    $appointmentId = intval($_POST['appointment_id']);
    $action = $_POST['action'];

    // Determine new status and completed date
    if ($action === 'done') {
        $newStatus = 'Done';
        $completedDate = (new DateTime("now", new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s');  // Set the current timestamp for completed date in Manila timezone
    } else if ($action === 'missed') {
        $newStatus = 'Missed';
        $completedDate = null;  // Leave completed date as null if missed
    }

    // Prepare the SQL query to update status and completed_date
    $query = "UPDATE Appointments 
              SET status = :status, completed_date = :completed_date 
              WHERE appointment_id = :appointment_id";
    $stmt = $pdo->prepare($query);

    try {
        // Execute the query with the new status and completed date
        $stmt->execute([
            ':status' => $newStatus,
            ':completed_date' => $completedDate,
            ':appointment_id' => $appointmentId
        ]);
        $_SESSION['message'] = "Appointment has been $newStatus successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to update appointment status: " . $e->getMessage();
    }

    header("Location: manage_appointments.php");
    exit();
}

$searchTerm = '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 8;
$offset = ($page - 1) * $limit;

// If search term is provided, use it
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
}

// Prepare the query for ongoing appointments with search functionality
$ongoingAppointmentsQuery = "
    SELECT a.appointment_id, a.scheduled_date, a.status, p.first_name, p.last_name, p.contact_number 
    FROM Appointments a
    JOIN Patients p ON a.patient_id = p.patient_id
    WHERE a.status = 'Ongoing'
    AND (p.first_name LIKE :searchTerm OR p.last_name LIKE :searchTerm)
    ORDER BY a.scheduled_date ASC
    LIMIT :limit OFFSET :offset";

$ongoingStmt = $pdo->prepare($ongoingAppointmentsQuery);
$searchParam = "%" . $searchTerm . "%"; // Add percentage for partial matching
$ongoingStmt->bindParam(':searchTerm', $searchParam, PDO::PARAM_STR);
$ongoingStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$ongoingStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$ongoingStmt->execute();
$ongoingAppointments = $ongoingStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch **Completed Appointments** (Done)
$completedAppointmentsQuery = "
    SELECT a.appointment_id, a.scheduled_date, a.status, a.completed_date, 
           p.first_name, p.last_name, p.contact_number 
    FROM Appointments a
    JOIN Patients p ON a.patient_id = p.patient_id
    WHERE a.status = 'Done'
    ORDER BY a.completed_date DESC  -- Sort by completed_at (recent first)
    LIMIT :limit OFFSET :offset";
$completedStmt = $pdo->prepare($completedAppointmentsQuery);
$completedStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$completedStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$completedStmt->execute();
$completedAppointments = $completedStmt->fetchAll(PDO::FETCH_ASSOC);


// Count total records for Ongoing Appointments
$totalOngoingQuery = "SELECT COUNT(*) AS total FROM Appointments WHERE status = 'Ongoing'";
$totalOngoingResult = $pdo->query($totalOngoingQuery)->fetch();
$totalOngoing = $totalOngoingResult['total'];
$totalOngoingPages = ceil($totalOngoing / $limit);

// Count total records for Completed (Done) Appointments
$totalCompletedQuery = "SELECT COUNT(*) AS total FROM Appointments WHERE status = 'Done'";
$totalCompletedResult = $pdo->query($totalCompletedQuery)->fetch();
$totalCompleted = $totalCompletedResult['total'];
$totalCompletedPages = ceil($totalCompleted / $limit);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
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

        <main class="dashboard-main-content">
            <div class="container mt-5">
                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php elseif (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Ongoing Appointments Section -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Ongoing Appointments</h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleAppointmentModal">Schedule Appointment</button>
                </div>

                <!-- Live Search Input -->
                <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search by patient name...">

                <!-- Ongoing Appointments Table -->
                <div class="table-container shadow rounded bg-white p-3">
                    <table class="table table-striped" id="ongoingAppointmentsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Patient Name</th>
                                <th>Contact Number</th>
                                <th>Scheduled Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($ongoingAppointments)): ?>
                                <?php $counter = $offset + 1; ?>
                                <?php foreach ($ongoingAppointments as $row): ?>
                                    <tr>
                                        <td><?= $counter++ ?></td>
                                        <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                        <td><?= htmlspecialchars($row['contact_number']) ?></td>
                                        <td><?= (new DateTime($row['scheduled_date']))->format('F j, Y g:i a') ?></td>
                                        <td>Ongoing</td>
                                        <td>
                                            <form action="" method="POST" class="d-inline">
                                                <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                                                <button type="submit" name="action" value="done" class="btn btn-success btn-sm">Done</button>
                                            </form>
                                            <form action="" method="POST" class="d-inline">
                                                <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                                                <button type="submit" name="action" value="missed" class="btn btn-danger btn-sm">Missed</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No ongoing appointments found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination for Ongoing Appointments -->
                <?php if ($totalOngoingPages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center mt-3" id="ongoingPagination">
                            <!-- First Button -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="#" data-page="1" aria-label="First">
                                        <span class="material-icons">first_page</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Previous Button -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="#" data-page="<?= $page - 1 ?>" aria-label="Previous">
                                        <span class="material-icons">navigate_before</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Page Numbers -->
                            <?php for ($i = 1; $i <= $totalOngoingPages; $i++): ?>
                                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="#" data-page="<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <!-- Next Button -->
                            <?php if ($page < $totalOngoingPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="#" data-page="<?= $page + 1 ?>" aria-label="Next">
                                        <span class="material-icons">navigate_next</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Last Button -->
                            <?php if ($page < $totalOngoingPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="#" data-page="<?= $totalOngoingPages ?>" aria-label="Last">
                                        <span class="material-icons">last_page</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>


                <!-- Completed Appointments Section -->
                <h3 class="mt-5">Completed Appointments</h3>
                <div class="table-container shadow rounded bg-white p-3">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Patient Name</th>
                                <th>Contact Number</th>
                                <th>Scheduled Date</th>
                                <th>Completed Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($completedAppointments)): ?>
                                <?php $counter = $offset + 1; ?>
                                <?php foreach ($completedAppointments as $row): ?>
                                    <tr>
                                        <td><?= $counter++ ?></td>
                                        <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                        <td><?= htmlspecialchars($row['contact_number']) ?></td>
                                        <td><?= (new DateTime($row['scheduled_date']))->format('F j, Y g:i a') ?></td>
                                        <td><?= (new DateTime($row['completed_date']))->format('F j, Y g:i a') ?></td>
                                        <td>Completed</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No completed appointments found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination for Completed Appointments -->
                <?php if ($totalCompletedPages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center mt-3">
                            <!-- First Button -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1&section=completed" aria-label="First">
                                        <span class="material-icons">first_page</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Previous Button -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&section=completed" aria-label="Previous">
                                        <span class="material-icons">navigate_before</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Page Numbers -->
                            <?php for ($i = 1; $i <= $totalCompletedPages; $i++): ?>
                                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&section=completed"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <!-- Next Button -->
                            <?php if ($page < $totalCompletedPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&section=completed" aria-label="Next">
                                        <span class="material-icons">navigate_next</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Last Button -->
                            <?php if ($page < $totalCompletedPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $totalCompletedPages ?>&section=completed" aria-label="Last">
                                        <span class="material-icons">last_page</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>



            </div>
        </main>
    </div>

   <!-- Schedule Appointment Modal -->
<div class="modal fade" id="scheduleAppointmentModal" tabindex="-1" aria-labelledby="scheduleAppointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="schedule_appointment.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleAppointmentModalLabel">Schedule Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="patient_id" class="form-label">Patient</label>
                        <select name="patient_id" id="patient_id" class="form-select" required>
                            <?php
                            // Fetch patient list from the database
                            $patientsQuery = "SELECT patient_id, first_name, last_name FROM Patients ORDER BY last_name ASC";
                            $stmt = $pdo->prepare($patientsQuery);
                            $stmt->execute();
                            $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            // Populate the dropdown
                            foreach ($patients as $patient): ?>
                                <option value="<?= htmlspecialchars($patient['patient_id']) ?>">
                                    <?= htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="scheduled_date" class="form-label">Scheduled Date</label>
                        <input type="datetime-local" name="scheduled_date" id="scheduled_date" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Schedule Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>


    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            // Live search functionality
            $('#searchInput').on('keyup', function () {
                const searchTerm = $(this).val();
                fetchOngoingAppointments(1, searchTerm);
            });

            // Handle pagination click
            $(document).on('click', '#ongoingPagination .page-link', function (e) {
                e.preventDefault();
                const page = $(this).data('page');
                const searchTerm = $('#searchInput').val();
                fetchOngoingAppointments(page, searchTerm);
            });

            // Function to fetch ongoing appointments
            function fetchOngoingAppointments(page, searchTerm = '') {
                $.ajax({
                    url: 'manage_appointments.php',
                    method: 'GET',
                    data: { page: page, search: searchTerm },
                    success: function (response) {
                        const tableBody = $(response).find('#ongoingAppointmentsTable tbody').html();
                        const pagination = $(response).find('#ongoingPagination').html();
                        $('#ongoingAppointmentsTable tbody').html(tableBody);
                        $('#ongoingPagination').html(pagination);
                    },
                    error: function (error) {
                        console.error('Error:', error);
                    }
                });
            }
        });
    </script>
</body>

</html>
