<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Midwife') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require '../connections/connections.php';

// Get the database connection
$pdo = connection();

// Handle appointment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'], $_POST['action'])) {
    $appointmentId = intval($_POST['appointment_id']);
    $action = $_POST['action'];

    // Update appointment status in the database
    $newStatus = ($action === 'approve') ? 'Approved' : 'Cancelled';
    $query = "UPDATE Appointments SET status = :status WHERE appointment_id = :appointment_id";
    $stmt = $pdo->prepare($query);

    try {
        $stmt->execute([':status' => $newStatus, ':appointment_id' => $appointmentId]);
        $_SESSION['message'] = "Appointment has been $newStatus successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to update appointment status: " . $e->getMessage();
    }

    header("Location: manage_appointments.php");
    exit();
}

// Fetch pending appointments
$pendingAppointmentsQuery = "
    SELECT a.appointment_id, a.scheduled_date, a.status, p.first_name, p.last_name, p.contact_number 
    FROM Appointments a
    JOIN Patients p ON a.patient_id = p.patient_id
    WHERE a.status = 'Pending'
    ORDER BY a.scheduled_date ASC";
$pendingAppointments = $pdo->query($pendingAppointmentsQuery)->fetchAll(PDO::FETCH_ASSOC);

// Fetch approved appointments
$approvedAppointmentsQuery = "
    SELECT a.appointment_id, a.scheduled_date, a.status, p.first_name, p.last_name, p.contact_number 
    FROM Appointments a
    JOIN Patients p ON a.patient_id = p.patient_id
    WHERE a.status = 'Approved'
    ORDER BY a.scheduled_date ASC";
$approvedAppointments = $pdo->query($approvedAppointmentsQuery)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <li><a href="dashboard.php"><span class="material-icons">dashboard</span><span class="link-text">Dashboard</span></a></li>
            <li><a href="manage_appointments.php" class="active"><span class="material-icons">event</span><span class="link-text">Appointments</span></a></li>
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
                <!-- Handle success/error messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php elseif (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Pending Appointments Section -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Pending Appointments</h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleAppointmentModal">Schedule Appointment</button>
                </div>
                <div class="table-container shadow rounded bg-white p-3 mb-5">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Appointment ID</th>
                                <th>Patient Name</th>
                                <th>Contact Number</th>
                                <th>Scheduled Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pendingAppointments)): ?>
                                <?php foreach ($pendingAppointments as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['appointment_id']) ?></td>
                                        <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                        <td><?= htmlspecialchars($row['contact_number']) ?></td>
                                        <td><?= htmlspecialchars($row['scheduled_date']) ?></td>
                                        <td>Pending</td>
                                        <td>
                                            <form action="" method="POST" class="d-inline">
                                                <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                            </form>
                                            <form action="" method="POST" class="d-inline">
                                                <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                                                <button type="submit" name="action" value="cancel" class="btn btn-danger btn-sm">Cancel</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No pending appointments found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Approved Appointments Section -->
                <h3>Approved Appointments</h3>
                <div class="table-container shadow rounded bg-white p-3">
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
                            <?php if (!empty($approvedAppointments)): ?>
                                <?php foreach ($approvedAppointments as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['appointment_id']) ?></td>
                                        <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                        <td><?= htmlspecialchars($row['contact_number']) ?></td>
                                        <td><?= htmlspecialchars($row['scheduled_date']) ?></td>
                                        <td>Approved</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No approved appointments found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="patient_id" class="form-label">Patient</label>
                            <select name="patient_id" id="patient_id" class="form-select" required>
                                <!-- Fetch patient list dynamically -->
                                <?php
                                $patientsQuery = "SELECT patient_id, first_name, last_name FROM Patients ORDER BY last_name ASC";
                                $patients = $pdo->query($patientsQuery)->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($patients as $patient) {
                                    echo "<option value='{$patient['patient_id']}'>" . htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name']) . "</option>";
                                }
                                ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
