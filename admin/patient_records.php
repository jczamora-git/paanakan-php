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

// Get the patient ID from the URL
if (!isset($_GET['patient_id'])) {
    $_SESSION['error'] = "No patient ID provided.";
    header("Location: manage_health_records.php");
    exit();
}

$patient_id = intval($_GET['patient_id']);

// Fetch patient details
$patient_query = "SELECT first_name, last_name FROM patients WHERE patient_id = :patient_id";
$patient_stmt = $pdo->prepare($patient_query);
$patient_stmt->execute([':patient_id' => $patient_id]);
$patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    $_SESSION['error'] = "Patient not found.";
    header("Location: manage_health_records.php");
    exit();
}

// Pagination setup
$limit = 10; // Records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch all health records for the patient with pagination
$records_query = "SELECT * FROM health_records WHERE patient_id = :patient_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$records_stmt = $pdo->prepare($records_query);
$records_stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$records_stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$records_stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$records_stmt->execute();
$records = $records_stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total records for pagination
$total_query = "SELECT COUNT(*) AS total FROM health_records WHERE patient_id = :patient_id";
$total_stmt = $pdo->prepare($total_query);
$total_stmt->execute([':patient_id' => $patient_id]);
$total_result = $total_stmt->fetch(PDO::FETCH_ASSOC);
$totalRecords = $total_result['total'];
$totalPages = ceil($totalRecords / $limit);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Health Records</title>
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
                
                <!-- Handle success/error messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php elseif (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Add New Health Record -->
                <button class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addHealthRecordModal">Add Health Record</button>

                <!-- Health Records Table -->
                <div class="table-container shadow rounded bg-white p-3">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Diagnosis</th>
                                <th>Results</th>
                                <th>Prescribed Medicine</th>
                                <th>Follow-Up Date</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($records)): ?>
                                <?php foreach ($records as $index => $record): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($record['diagnosis']) ?></td>
                                        <td><?= htmlspecialchars($record['results']) ?></td>
                                        <td><?= htmlspecialchars($record['prescribed_medicine']) ?></td>
                                        <td><?= htmlspecialchars($record['follow_up_date'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($record['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No health records found for this patient.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center mt-3">
                            <!-- Previous Button -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?patient_id=<?= $patient_id ?>&page=<?= $page - 1 ?>" aria-label="Previous">&laquo;</a>
                                </li>
                            <?php endif; ?>

                            <!-- Page Numbers -->
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?patient_id=<?= $patient_id ?>&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <!-- Next Button -->
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?patient_id=<?= $patient_id ?>&page=<?= $page + 1 ?>" aria-label="Next">&raquo;</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <!-- Add Health Record Modal -->
    <div class="modal fade" id="addHealthRecordModal" tabindex="-1" aria-labelledby="addHealthRecordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="patient_records.php?patient_id=<?= $patient_id ?>" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addHealthRecordModalLabel">Add Health Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <!-- Diagnosis -->
                        <div class="mb-3">
                            <label for="diagnosis" class="form-label">Diagnosis</label>
                            <textarea name="diagnosis" id="diagnosis" class="form-control" rows="2" required></textarea>
                        </div>
                        <!-- Results -->
                        <div class="mb-3">
                            <label for="results" class="form-label">Treatment</label>
                            <textarea name="results" id="results" class="form-control" rows="2"></textarea>
                        </div>
                        <!-- Prescribed Medicine -->
                        <div class="mb-3">
                            <label for="prescribed_medicine" class="form-label">Prescribed Medicine</label>
                            <textarea name="prescribed_medicine" id="prescribed_medicine" class="form-control" rows="2"></textarea>
                        </div>
                        <!-- Follow-Up Date -->
                        <div class="mb-3">
                            <label for="follow_up_date" class="form-label">Follow-Up Date</label>
                            <input type="date" name="follow_up_date" id="follow_up_date" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Add Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
