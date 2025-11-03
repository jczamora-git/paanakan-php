<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require '../connections/connections.php';
require '../activity_log.php';
// Get database connection
$pdo = connection();

// Create an instance of the ActivityLog class
$activityLog = new ActivityLog($pdo);

// Handle updating appointment status
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $appointment_id = $_POST['appointment_id'] ?? null;
    $status = $_POST['status'] ?? null;

    // Validate inputs
    if (!$appointment_id || !$status || !in_array($status, ['Done', 'Missed'])) {
        $_SESSION['error'] = "Invalid request.";
        header("Location: manage_appointments.php");
        exit();
    }

    // Get user's full name for logging
    $userQuery = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS user_name FROM users WHERE user_id = :user_id");
    $userQuery->execute([':user_id' => $_SESSION['user_id']]);
    $userRow = $userQuery->fetch();
    $user_name = $userRow ? $userRow['user_name'] : 'Unknown User';

    // Fetch the case_id and patient name from the patients table
    $stmt = $pdo->prepare("SELECT p.case_id, CONCAT(p.first_name, ' ', p.last_name) AS patient_name 
                          FROM appointments a
                          JOIN patients p ON a.patient_id = p.patient_id
                          WHERE a.appointment_id = ?");
    $stmt->execute([$appointment_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        $_SESSION['error'] = "Appointment not found.";
        header("Location: manage_appointments.php");
        exit();
    }

    $case_id = $patient['case_id'];
    $patient_name = $patient['patient_name'];
    
    // Update the appointment status and set completed_date if marked as "Done"
    if ($status === 'Done') {
        // Insert health record when appointment is completed
        $insertHealthRecord = "INSERT INTO health_records (case_id, appointment_id, created_at) 
                            VALUES (?, ?, NOW())";
        $stmtHealthRecord = $pdo->prepare($insertHealthRecord);
        $stmtHealthRecord->execute([$case_id, $appointment_id]);

        // Update appointment status to Done
        $updateQuery = "UPDATE appointments SET status = 'Done', completed_date = NOW() WHERE appointment_id = ?";
        $action_desc = $user_name . " marked appointment as completed for " . $patient_name . " (Case ID: " . $case_id . ")";
    } else {
        $updateQuery = "UPDATE appointments SET status = 'Missed' WHERE appointment_id = ?";
        $action_desc = $user_name . " marked appointment as missed for " . $patient_name . " (Case ID: " . $case_id . ")";
    }

    $stmt = $pdo->prepare($updateQuery);
    if ($stmt->execute([$appointment_id])) {
        // Log activity for updating the status
        $activityLog->logActivity($_SESSION['user_id'], $action_desc);
        $_SESSION['message'] = "Appointment status updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update appointment status.";
    }

    header("Location: manage_appointments.php");
    exit();
}

// Handle scheduling an appointment
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'schedule') {
    $case_id = $_POST['case_id'] ?? '';
    $scheduled_date = $_POST['scheduled_date'] ?? '';
    $scheduled_time = $_POST['scheduled_time'] ?? '';
    $appointment_type = $_POST['appointment_type'] ?? 'Regular Checkup';

    // Validate inputs
    if (empty($case_id) || empty($scheduled_date) || empty($scheduled_time) || empty($appointment_type)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: manage_appointments.php");
        exit();
    }

    // Get user's full name for logging
    $userQuery = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS user_name FROM users WHERE user_id = :user_id");
    $userQuery->execute([':user_id' => $_SESSION['user_id']]);
    $userRow = $userQuery->fetch();
    $user_name = $userRow ? $userRow['user_name'] : 'Unknown User';

    // Check if case ID exists and get patient name
    $stmt = $pdo->prepare("SELECT patient_id, CONCAT(first_name, ' ', last_name) AS patient_name 
                          FROM patients WHERE case_id = ?");
    $stmt->execute([$case_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        $_SESSION['error'] = "Invalid Case ID.";
        header("Location: manage_appointments.php");
        exit();
    }

    $patient_id = $patient['patient_id'];
    $patient_name = $patient['patient_name'];

    // Combine date and time into a single string
    $scheduled_date_time = $scheduled_date . ' ' . $scheduled_time;
    $scheduled_date_time = date('Y-m-d H:i:s', strtotime($scheduled_date_time));

    // Insert appointment with appointment type
    $insertQuery = "INSERT INTO appointments (patient_id, scheduled_date, status, appointment_type) VALUES (?, ?, 'Approved', ?)";
    $stmt = $pdo->prepare($insertQuery);

    if ($stmt->execute([$patient_id, $scheduled_date_time, $appointment_type])) {
        // Format the scheduled date to English format
        $formattedDate = date('F j, Y', strtotime($scheduled_date_time));
        $formattedTime = date('g:i A', strtotime($scheduled_date_time));

        // Log activity for scheduling the appointment
        $action_desc = $user_name . " scheduled " . $appointment_type . " appointment for " . $patient_name . 
                      " (Case ID: " . $case_id . ") on " . $formattedDate . " at " . $formattedTime;
        $activityLog->logActivity($_SESSION['user_id'], $action_desc);

        $_SESSION['message'] = "Appointment scheduled successfully.";
    } else {
        $_SESSION['error'] = "Failed to schedule appointment.";
    }

    header("Location: manage_appointments.php");
    exit();
}

date_default_timezone_set('Asia/Manila'); // Set the timezone to Manila
$selectedDate = date('Y-m-d'); // Default: Today in Manila time

    // Pagination setup
    $limit = 10; // Records per page
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

// Fetch total appointment count for pagination
$totalAppointmentsQuery = "SELECT COUNT(*) AS total FROM Appointments WHERE DATE(scheduled_date) = :selectedDate AND Status='Approved'";
$totalAppointmentsStmt = $pdo->prepare($totalAppointmentsQuery);
$totalAppointmentsStmt->bindParam(':selectedDate', $selectedDate, PDO::PARAM_STR);
$totalAppointmentsStmt->execute();
$totalAppointments = $totalAppointmentsStmt->fetch()['total'];
$totalPages = ceil($totalAppointments / $limit);

// Fetch appointments for the selected date (paginated)
$appointmentsQuery = "
SELECT a.appointment_id, a.scheduled_date, a.status, p.first_name, p.last_name, p.contact_number, a.appointment_type, p.patient_id
FROM Appointments a
JOIN Patients p ON a.patient_id = p.patient_id
WHERE DATE(a.scheduled_date) = :selectedDate AND a.status = 'Approved'       
ORDER BY a.scheduled_date ASC
LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($appointmentsQuery);
$stmt->bindParam(':selectedDate', $selectedDate, PDO::PARAM_STR);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/5.11.3/main.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">  
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/calendar.css">
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

        .calendar-wrapper {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .date-details {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .date-left {
            display: flex;
            align-items: center;
        }

        .day-number {
            font-size: 2.2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-right: 12px;
        }

        .date-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .month-year, .weekday {
            font-size: 1.05rem;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .date-right {
            display: flex;
            align-items: center;
        }

        .date-right .btn {
            padding: 6px 18px;
            font-size: 1rem;
            border-radius: 8px;
        }

        .appointment-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .appointment-container h5 {
            font-size: 1.1rem;
            margin-bottom: 10px;
        }

        .table {
            font-size: 1.04rem;
            margin-bottom: 0;
        }

        .table thead th {
            background-color: var(--secondary-color);
            color: var(--text-color);
            font-weight: 600;
            border-bottom: 2px solid var(--primary-color);
            padding: 8px 6px;
        }

        .table tbody td {
            padding: 7px 6px;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: rgba(46, 139, 87, 0.04);
        }

        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-success:hover {
            background-color: var(--primary-light);
            border-color: var(--primary-light);
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }

        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }

        .btn-xs {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 0.2rem;
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .modal-body {
            padding: 25px;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            padding: 8px 12px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.25);
        }

        .pagination {
            margin-top: 20px;
        }

        .page-link {
            color: var(--primary-color);
            border-color: var(--border-color);
            padding: 4px 10px;
            font-size: 1rem;
        }

        .page-link:hover {
            color: var(--primary-light);
            background-color: var(--secondary-color);
        }

        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .action-icon {
            background: none;
            border: none;
            padding: 0 4px;
            font-size: 1.1rem;
            line-height: 1;
            vertical-align: middle;
            transition: color 0.2s, box-shadow 0.2s;
            box-shadow: none;
            outline: none;
        }
        .action-icon:focus, .action-icon:hover {
            color: #17633c !important;
            background: none;
            box-shadow: none;
            outline: none;
        }
        .action-icon .fa-times:hover {
            color: #a71d2a !important;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include '../sidebar.php'; ?>

        <!-- Main Content -->
        <main class="dashboard-main-content">
            <div class="container mt-4">  
                <!-- Display Success/Error Messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?= $_SESSION['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                        <?php unset($_SESSION['message']); ?>
                    <?php elseif (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                        
        <!-- Breadcrumb Navigation -->
        <?php include '../admin/breadcrumb.php'; ?>

                <!-- Page Header -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h2 class="mb-0">
                                <i class="fas fa-calendar-check me-2"></i>Manage Appointments
                            </h2>
                            <p class="mb-0 mt-2">
                                <i class="fas fa-list me-2"></i>Total Appointments: <?= $totalAppointments ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Calendar Section -->
                    <div class="col-lg-6">
                        <div class="calendar-wrapper">
                            <div id="calendar"></div>
                        </div>
                    </div>

                    <!-- Date & Appointments Section -->
                    <div class="col-lg-6">
                    <div class="date-details shadow p-4 rounded">
                        <div class="date-left">
                            <!-- Large Day Number -->
                            <span class="day-number" id="selected-date"><?= date('d') ?></span>
                            <div class="date-text">
                                <!-- Month / Year -->
                                    <span class="month-year" id="selected-month">
                                        <i class="fas fa-calendar-alt" style="color: #2E8B57;"></i> <?= date('m/Y') ?>
                                    </span>
                                <!-- Weekday -->
                                    <span class="weekday" id="selected-day">
                                        <i class="fas fa-calendar-day" style="color: #2E8B57;"></i> <?= date('l') ?>
                                    </span>
                                </div>
                            </div>
                        <!-- Right Section: Only Schedule Button -->
                        <div class="date-right">
                                <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#scheduleAppointmentModal">
                                    <i class="fas fa-plus" style="color: #fff;"></i> Schedule
                                </button>
                            </div>
                        </div>
                        <!-- Appointments Table -->
                        <div class="appointment-container">
                            <h5 class="mb-3">
                                <i class="fas fa-calendar-day me-2"></i>Today's Appointments
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Patient Name</th>
                                        <th>Contact</th>
                                        <th>Type</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="appointments-list">
                                    <?php if (!empty($appointments)): ?>
                                        <?php foreach ($appointments as $appointment): ?>
                                            <tr>
                                                <td><?= date("g:i A", strtotime($appointment['scheduled_date'])) ?></td>
                                                <td><?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></td>
                                                <td><?= htmlspecialchars($appointment['contact_number']) ?></td>
                                                <td><?= htmlspecialchars($appointment['appointment_type']) ?></td>
                                                <td>
                                                        <div class="btn-group" role="group">
                                                    <?php if (strtolower($appointment['appointment_type']) === 'regular checkup'): ?>
                                                        <a href="/appointments_records/regular_checkup.php?appointment_id=<?= $appointment['appointment_id'] ?>"
                                                                   class="action-icon me-2"
                                                           data-bs-toggle="tooltip"
                                                                   data-bs-placement="top"
                                                                   title="Regular Checkup Record">
                                                                    <i class="fas fa-file-medical" style="color: #2E8B57;"></i>
                                                        </a>
                                                    <?php elseif (strtolower($appointment['appointment_type']) === 'under observation'): ?>
                                                        <a href="/appointments_records/under_observation.php?appointment_id=<?= $appointment['appointment_id'] ?>"
                                                                   class="action-icon me-2"
                                                           data-bs-toggle="tooltip"
                                                                   data-bs-placement="top"
                                                                   title="Under Observation Record">
                                                                    <i class="fas fa-file-medical" style="color: #2E8B57;"></i>
                                                        </a>
                                                    <?php elseif (strtolower($appointment['appointment_type']) === 'pre-natal checkup'): ?>
                                                                <a href="/appointments_records/prenatal_checkup.php?appointment_id=<?= $appointment['appointment_id'] ?>"
                                                                   class="action-icon me-2"
                                                           data-bs-toggle="tooltip"
                                                                   data-bs-placement="top"
                                                                   title="Pre-Natal Records">
                                                                    <i class="fas fa-file-medical" style="color: #2E8B57;"></i>
                                                        </a>
                                                    <?php elseif (strtolower($appointment['appointment_type']) === 'post-natal checkup'): ?>
                                                        <a href="/appointments_records/postnatal_checkup.php?appointment_id=<?= $appointment['appointment_id'] ?>"
                                                                   class="action-icon me-2"
                                                           data-bs-toggle="tooltip"
                                                                   data-bs-placement="top"
                                                                   title="Post-Natal Records">
                                                                    <i class="fas fa-file-medical" style="color: #2E8B57;"></i>
                                                        </a>
                                                    <?php elseif (strtolower($appointment['appointment_type']) === 'medical consultation'): ?>
                                                        <a href="/appointments_records/medical_consultation.php?appointment_id=<?= $appointment['appointment_id'] ?>"
                                                                   class="action-icon me-2"
                                                           data-bs-toggle="tooltip"
                                                                   data-bs-placement="top"
                                                                   title="Medical Consultation Record">
                                                                    <i class="fas fa-file-medical" style="color: #2E8B57;"></i>
                                                        </a>
                                                    <?php elseif (strtolower($appointment['appointment_type']) === 'vaccination'): ?>
                                                        <a href="/appointments_records/vaccination.php?appointment_id=<?= $appointment['appointment_id'] ?>"
                                                                   class="action-icon me-2"
                                                           data-bs-toggle="tooltip"
                                                                   data-bs-placement="top"
                                                                   title="Vaccination Record">
                                                                    <i class="fas fa-file-medical" style="color: #2E8B57;"></i>
                                                        </a>
                                                    <?php elseif (strtolower($appointment['appointment_type']) === 'follow-up'): ?>
                                                        <a href="appointments_records/follow_up.php?appointment_id=<?= $appointment['appointment_id'] ?>"
                                                                   class="action-icon me-2"
                                                           data-bs-toggle="tooltip"
                                                                   data-bs-placement="top"
                                                                   title="Follow Up Record">
                                                                    <i class="fas fa-file-medical" style="color: #2E8B57;"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <form method="POST" action="manage_appointments.php" style="display:inline;" onsubmit="return confirmMarkAsMissed(this);">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="appointment_id" value="<?= $appointment['appointment_id'] ?>">
                                                        <input type="hidden" name="status" value="Missed">
                                                        <button type="submit" class="action-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="Mark as Missed">
                                                            <i class="fas fa-times" style="color: #dc3545;"></i>
                                                        </button>
                                                    </form>
                                                        </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <i class="fas fa-info-circle me-2"></i>No appointments for this day.
                                                </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <nav>
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?date=<?= $selectedDate ?>&page=1" aria-label="First">
                                                <i class="fas fa-angle-double-left"></i>
                                            </a>
                                        </li>
                                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?date=<?= $selectedDate ?>&page=<?= max(1, $page - 1) ?>" aria-label="Previous">
                                                <i class="fas fa-angle-left"></i>
                                            </a>
                                        </li>
                                        <?php
                                        $startPage = max(1, $page - 2);
                                        $endPage = min($totalPages, $page + 2);
                                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                                            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                                <a class="page-link" href="?date=<?= $selectedDate ?>&page=<?= $i ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?date=<?= $selectedDate ?>&page=<?= min($totalPages, $page + 1) ?>" aria-label="Next">
                                                <i class="fas fa-angle-right"></i>
                                            </a>
                                        </li>
                                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?date=<?= $selectedDate ?>&page=<?= $totalPages ?>" aria-label="Last">
                                                <i class="fas fa-angle-double-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Schedule Appointment Modal -->
                <div class="modal fade" id="scheduleAppointmentModal" tabindex="-1" aria-labelledby="scheduleAppointmentLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="manage_appointments.php" method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="scheduleAppointmentLabel">
                                        <i class="fas fa-calendar-plus me-2"></i>Schedule Appointment for <span id="modalSelectedDateLabel"></span>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="schedule">
                                    <input type="hidden" id="modalScheduledDate" name="scheduled_date" value="<?= $selectedDate ?>">

                                    <div class="mb-3 position-relative">
                                        <label for="caseId" class="form-label">
                                            <i class="fas fa-id-card me-2"></i>Case ID or Patient Name
                                        </label>
                                        <input type="text" class="form-control" id="caseId" name="case_id" required placeholder="Enter Case ID or Patient Name" autocomplete="off">
                                        <div id="caseIdSuggestions" class="list-group position-absolute w-100" style="z-index: 1050; display: none;"></div>
                                        <div id="selectedPatientName" class="form-text text-success"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="appointmentTime" class="form-label">
                                            <i class="fas fa-clock me-2"></i>Select Available Time
                                        </label>
                                        <select class="form-select" id="appointmentTime" name="scheduled_time" required>
                                            <option value="">Select a time slot</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="appointmentType" class="form-label">
                                            <i class="fas fa-stethoscope me-2"></i>Appointment Type
                                        </label>
                                        <select class="form-select" id="appointmentType" name="appointment_type" required>
                                            <option value="Regular Checkup">Regular Checkup</option>
                                            <option value="Follow-up">Follow-up</option>
                                            <option value="Under Observation">Under Observation</option>
                                            <option value="Pre-Natal Checkup">Pre-Natal Checkup</option>
                                            <option value="Post-Natal Checkup">Post-Natal Checkup</option>
                                            <option value="Medical Consultation">Medical Consultation</option>
                                            <option value="Vaccination">Vaccination</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-check me-2"></i>Schedule Appointment
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/calendar.js"></script>
    <script>
        let formToSubmit = null;

        function confirmMarkAsMissed(form) {
            if (confirm("Are you sure you want to mark this appointment as missed? This action cannot be undone.")) {
                form.submit();
            }
            return false;
        }

        // --- SCHEDULE MODAL: Use selected calendar date ---
        function formatDateLong(dateStr) {
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        }

        let selectedCalendarDate = '<?= $selectedDate ?>';

        // Update selectedCalendarDate when calendar date is changed
        function updateSelectedDateFromCalendar(dateStr) {
            selectedCalendarDate = dateStr;
            console.log('Calendar date updated to:', dateStr);
        }

        // Fetch available times for selected date
        function fetchAvailableTimes(dateStr) {
            console.log('Starting fetchAvailableTimes for date:', dateStr);
            
            // Ensure dateStr is valid
            if (!dateStr) {
                console.error('No date provided to fetchAvailableTimes');
                return;
            }

            // Show loading state
            $('#appointmentTime').html('<option value="">Loading time slots...</option>');

            // Make the AJAX request
            $.ajax({
                url: 'fetch_available_times.php',
                method: 'GET',
                data: { date: dateStr },
                dataType: 'json',
                success: function(response) {
                    console.log('Successfully received response:', response);
                    $('#appointmentTime').html('<option value="">Select a time slot</option>');
                    
                    if (Array.isArray(response) && response.length > 0) {
                        response.forEach(function(time) {
                            $('#appointmentTime').append('<option value="' + time + '">' + time + '</option>');
                        });
                        console.log('Time slots populated:', response.length, 'slots added');
                    } else {
                        console.log('No available time slots found');
                        $('#appointmentTime').append('<option value="">No available times</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    $('#appointmentTime').html('<option value="">Error loading time slots</option>');
                }
            });
        }

        // Initialize when document is ready
        $(document).ready(function() {
            console.log('Document ready, initializing handlers');
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Handle schedule button click
            $(document).on('click', '[data-bs-target="#scheduleAppointmentModal"]', function(e) {
                console.log('Schedule button clicked');
                const dateToUse = selectedCalendarDate || '<?= $selectedDate ?>';
                console.log('Using date:', dateToUse);
                
                $('#modalScheduledDate').val(dateToUse);
                $('#modalSelectedDateLabel').text(formatDateLong(dateToUse));
                
                // Fetch available times
                fetchAvailableTimes(dateToUse);
            });

            // Handle modal shown event (in case modal is triggered by other means)
            $('#scheduleAppointmentModal').on('shown.bs.modal', function () {
                console.log('Modal shown, fetching times for:', selectedCalendarDate);
                fetchAvailableTimes(selectedCalendarDate);
            });
        });

        // Listen for calendar date changes
        document.addEventListener('calendarDateChanged', function(e) {
            console.log('Calendar date changed event received:', e.detail.date);
            selectedCalendarDate = e.detail.date;
        });

        // --- Live search for Case ID/Patient Name ---
        $('#caseId').on('input', function() {
            const query = $(this).val().trim();
            if (query.length < 2) {
                $('#caseIdSuggestions').hide();
                $('#selectedPatientName').text('');
                return;
            }
            $.get('search_patients.php', { query: query }, function(data) {
                if (data.length > 0) {
                    let html = '';
                    data.forEach(function(patient) {
                        html += `<button type="button" class="list-group-item list-group-item-action" data-case-id="${patient.case_id}" data-name="${patient.first_name} ${patient.last_name}">
                            ${patient.first_name} ${patient.last_name} <span class="text-muted">(${patient.case_id})</span>
                        </button>`;
                    });
                    $('#caseIdSuggestions').html(html).show();
                } else {
                    $('#caseIdSuggestions').hide();
                }
            }, 'json');
        });

        // Handle suggestion click
        $('#caseIdSuggestions').on('click', 'button', function() {
            const caseId = $(this).data('case-id');
            const name = $(this).data('name');
            $('#caseId').val(caseId);
            $('#selectedPatientName').text('Selected: ' + name);
            $('#caseIdSuggestions').hide();
        });

        // Hide suggestions when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#caseId, #caseIdSuggestions').length) {
                $('#caseIdSuggestions').hide();
            }
        });
    </script>
</body>
</html>
