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

// Set the number of records per page
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch patients (only fullname and medical history)
$query = "
    SELECT patient_id, 
           CONCAT(first_name, ' ', last_name) AS fullname, 
           medical_history 
    FROM patients
    ORDER BY last_name, first_name
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total patients
$totalQuery = "SELECT COUNT(*) AS total FROM patients";
$totalResult = $pdo->query($totalQuery)->fetch();
$totalRecords = $totalResult['total'];
$totalPages = ceil($totalRecords / $limit);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Health Records</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
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
                <h2 class="mb-4">Manage Health Records</h2>

                <!-- Handle success/error messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php elseif (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Patients Table -->
                <div class="table-container shadow rounded bg-white p-3">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Full Name</th>
                                <th>Medical History</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                                <?php if (!empty($patients)): ?>
                                    <?php foreach ($patients as $index => $patient): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($patient['fullname']) ?></td>
                                            <td><?= htmlspecialchars($patient['medical_history'] ?: 'N/A') ?></td>
                                            <td>
                                                <!-- View Button -->
                                                <a href="patient_health_records.php?patient_id=<?= $patient['patient_id'] ?>" class="btn btn-info btn-sm" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                <!-- Edit Button -->
                                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editPatientModal<?= $patient['patient_id'] ?>" title="Edit">
                                                    <i class="fas fa-user-edit"></i>
                                                </button>

                                                <!-- In Button -->
                                                <a href="in_action.php?patient_id=<?= $patient['patient_id'] ?>" class="btn btn-success btn-sm" title="In">
                                                    <i class="fas fa-hospital-user"></i>
                                                </a>

                                                <!-- Out Button -->
                                                <a href="out_action.php?patient_id=<?= $patient['patient_id'] ?>" class="btn btn-danger btn-sm" title="Out">
                                                    <i class="fas fa-user-times"></i>
                                                </a>
                                            </td>
                                        </tr>

                                        <!-- Modal for Editing Patient -->
                                        <div class="modal fade" id="editPatientModal<?= $patient['patient_id'] ?>" tabindex="-1" aria-labelledby="editPatientModalLabel<?= $patient['patient_id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="edit_patient_action.php" method="POST">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editPatientModalLabel<?= $patient['patient_id'] ?>">Edit Patient</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <!-- Patient ID (Hidden) -->
                                                            <input type="hidden" name="patient_id" value="<?= $patient['patient_id'] ?>">

                                                            <!-- Patient Name (Read-Only) -->
                                                            <div class="mb-3">
                                                                <label for="patient_name_<?= $patient['patient_id'] ?>" class="form-label">Patient Name</label>
                                                                <input type="text" id="patient_name_<?= $patient['patient_id'] ?>" class="form-control" value="<?= htmlspecialchars($patient['fullname']) ?>" readonly>
                                                            </div>

                                                            <!-- Medical History -->
                                                            <div class="mb-3">
                                                                <label for="medical_history_<?= $patient['patient_id'] ?>" class="form-label">Medical History</label>
                                                                <textarea name="medical_history" id="medical_history_<?= $patient['patient_id'] ?>" class="form-control" rows="4"><?= htmlspecialchars($patient['medical_history']) ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No patients found.</td>
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
                                    <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">&laquo;</a>
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
                                    <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">&raquo;</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            </div>
        </main>
    </div>
    <!-- Edit Modal -->
    <div class="modal fade" id="editPatientModal<?= $patient['patient_id'] ?>" tabindex="-1" aria-labelledby="editPatientModalLabel<?= $patient['patient_id'] ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="edit_patient_action.php" method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editPatientModalLabel<?= $patient['patient_id'] ?>">Edit Patient</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- Patient ID (Hidden) -->
                                    <input type="hidden" name="patient_id" value="<?= $patient['patient_id'] ?>">

                                    <!-- Patient Name (Read-Only) -->
                                    <div class="mb-3">
                                        <label for="patient_name_<?= $patient['patient_id'] ?>" class="form-label">Patient Name</label>
                                        <input type="text" id="patient_name_<?= $patient['patient_id'] ?>" class="form-control" value="<?= htmlspecialchars($patient['fullname']) ?>" readonly>
                                    </div>

                                    <!-- Medical History -->
                                    <div class="mb-3">
                                        <label for="medical_history_<?= $patient['patient_id'] ?>" class="form-label">Medical History</label>
                                        <textarea name="medical_history" id="medical_history_<?= $patient['patient_id'] ?>" class="form-control" rows="4"><?= htmlspecialchars($patient['medical_history']) ?></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
