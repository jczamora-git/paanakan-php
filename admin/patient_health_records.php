<?php
// Start session and include database connection
session_start();
require '../connections/connections.php';

// Check if the user is logged in as Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Get the database connection
$pdo = connection();

// Retrieve patient_id from query parameters
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : null;

// Fetch patient information if patient_id is provided
if ($patient_id) {
    $query = "
        SELECT 
            'Admission' AS record_type,
            a.admission_id AS record_id,
            a.admission_date AS record_date,
            a.admitting_diagnosis AS description,
            a.discharge_date AS end_date
        FROM admissions a
        WHERE a.patient_id = :patient_id

        UNION ALL

        SELECT 
            'Prenatal' AS record_type,
            pr.record_id AS record_id,
            pr.visit_date AS record_date,
            pr.chief_complaint AS description,
            NULL AS end_date
        FROM prenatal_records pr
        WHERE pr.patient_id = :patient_id

        ORDER BY record_date DESC;
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':patient_id' => $patient_id]);
    $health_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $_SESSION['error'] = "No patient selected.";
    header("Location: manage_health_records.php");
    exit();
}
// Fetch patient details if `patient_id` is provided
if (isset($_GET['patient_id'])) {
    $patient_id = intval($_GET['patient_id']);
    $query = "SELECT first_name, last_name FROM patients WHERE patient_id = :patient_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':patient_id' => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        $_SESSION['error'] = "Patient not found.";
        header("Location: manage_health_records.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Patient ID not provided.";
    header("Location: manage_health_records.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Health Records</title>
<!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"><!-- Font Awesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css"><!-- Table styles -->
    <link rel="stylesheet" href="../css/form.css"><!-- Form styles -->
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
                <li><a href="manage_appointments.php"><span class="material-icons">event</span><span class="link-text">Appointments</span></a></li>
                <li><a href="manage_health_records.php" class="active"><span class="material-icons">folder</span><span class="link-text">Health Records</span></a></li>
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
                <div class="d-flex align-items-center mb-4">
                <a href="manage_health_records.php" class="btn mb-0">
                    <span class="material-icons">arrow_back</span>
                </a>
                <h2 class="mb-0">Health Records for <?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?></h2>
                </div>

                <!-- Display Success/Error Messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php elseif (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Health Records Table -->
                <div class="table-container shadow rounded bg-white p-3">
                    <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Record Type</th>
                            <th>Record Date</th>
                            <th>Description</th>
                            <th>End Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($health_records)): ?>
                            <?php foreach ($health_records as $record): ?>
                                <tr>
                                    <td><?= htmlspecialchars($record['record_type']) ?></td>
                                    <td><?= htmlspecialchars($record['record_date']) ?></td>
                                    <td><?= htmlspecialchars($record['description'] ?: 'N/A') ?></td>
                                    <td><?= htmlspecialchars($record['end_date'] ?: 'N/A') ?></td>
                                    <td>
                                        <!-- View Button -->
                                        <a href="in_action.php?mode=view&record_id=<?= $record['record_id'] ?>&patient_id=<?= $patient_id?>" 
                                        class="btn btn-info btn-sm" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <!-- Edit Button -->
                                        <a href="in_action.php?mode=edit&record_id=<?= $record['record_id'] ?>&patient_id=<?= $patient_id?>" 
                                        class="btn btn-warning btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No health records found for this patient.</td>
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
