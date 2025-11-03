<?php
// Start session and check if the user is logged in as Patient
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Patient') {
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

// Get the logged-in user's ID (Patient)
$user_id = $_SESSION['user_id'];

// Fetch the patient_id linked to this user_id
$stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    $_SESSION['error'] = "You are not registered as a patient.";
    header("Location: ../dashboard.php");
    exit();
}

$patient_id = $patient['patient_id']; // Get the corresponding patient_id

// Handle scheduling an appointment
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'schedule') {
    $scheduled_date = $_POST['scheduled_date'] ?? '';
    $appointment_type = $_POST['appointment_type'] ?? 'Regular Checkup'; // Default to Regular Checkup if empty

    // Validate inputs
    if (empty($scheduled_date) || empty($appointment_type)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: manage_appointments.php");
        exit();
    }

    // Insert appointment with appointment type
    $insertQuery = "INSERT INTO appointments (patient_id, scheduled_date, status, appointment_type) VALUES (?, ?, 'Ongoing', ?)";
    $stmt = $pdo->prepare($insertQuery);

    if ($stmt->execute([$patient_id, $scheduled_date, $appointment_type])) {
        // Format the scheduled date
        $formattedDate = date('F j, Y', strtotime($scheduled_date));

        // Log activity for scheduling the appointment
        $action_desc = "Scheduled appointment on $formattedDate ($appointment_type)";
        $activityLog->logActivity($user_id, $action_desc);

        $_SESSION['message'] = "Appointment scheduled successfully.";
    } else {
        $_SESSION['error'] = "Failed to schedule appointment.";
    }

    header("Location: manage_appointments.php");
    exit();
}

// Set default date (today)
date_default_timezone_set('Asia/Manila');
$selectedDate = date('Y-m-d');

// Pagination setup
$limit = 10; // Records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total appointment count for pagination (only for this patient)
$totalAppointmentsQuery = "SELECT COUNT(*) AS total FROM appointments WHERE patient_id = :patient_id";
$totalAppointmentsStmt = $pdo->prepare($totalAppointmentsQuery);
$totalAppointmentsStmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$totalAppointmentsStmt->execute();
$totalAppointments = $totalAppointmentsStmt->fetch()['total'];
$totalPages = ceil($totalAppointments / $limit);

// Fetch appointments for this patient (paginated)
$appointmentsQuery = "
SELECT appointment_id, scheduled_date, status, appointment_type 
FROM appointments 
WHERE patient_id = :patient_id 
ORDER BY scheduled_date DESC
LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($appointmentsQuery);
$stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Records</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
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
            padding: 15px;
        }
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
        }
        .table tbody tr:hover {
            background-color: rgba(46, 139, 87, 0.05);
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-done {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-missed {
            background-color: #f8d7da;
            color: #721c24;
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
        .appointment-type {
            font-weight: 500;
            color: var(--primary-color);
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>
        <main class="dashboard-main-content">
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

            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h2 class="mb-0">
                            <i class="fas fa-calendar-check me-2"></i>Appointment Records
                        </h2>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="manage_appointments.php" class="btn btn-light">
                            <i class="fas fa-plus-circle me-2"></i>Schedule Appointment
                        </a>
                    </div>
                </div>
            </div>

            <!-- Appointments Table -->
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($appointments)): ?>
                            <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-calendar-alt me-2 text-muted"></i>
                                        <?= date("F j, Y g:i A", strtotime($appointment['scheduled_date'])) ?>
                                    </td>
                                    <td class="appointment-type">
                                        <i class="fas fa-stethoscope me-2"></i>
                                        <?= htmlspecialchars($appointment['appointment_type']) ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch($appointment['status']) {
                                            case 'Pending':
                                                $statusClass = 'status-pending';
                                                break;
                                            case 'Approved':
                                                $statusClass = 'status-approved';
                                                break;
                                            case 'Done':
                                                $statusClass = 'status-done';
                                                break;
                                            case 'Missed':
                                                $statusClass = 'status-missed';
                                                break;
                                        }
                                        ?>
                                        <span class="status-badge <?= $statusClass ?>">
                                            <?= htmlspecialchars($appointment['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-4">
                                    <i class="fas fa-info-circle me-2"></i>No appointments scheduled.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
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
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
