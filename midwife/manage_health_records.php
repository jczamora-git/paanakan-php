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

// Handle adding a new health record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $patient_id = intval($_POST['patient_id']);
        $diagnosis = $_POST['diagnosis'];
        $treatment = $_POST['treatment'];
        $prescribed_medicine = $_POST['prescribed_medicine'];
        $follow_up_date = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;

        $query = "INSERT INTO health_records (patient_id, diagnosis, treatment, prescribed_medicine, follow_up_date) 
                  VALUES (:patient_id, :diagnosis, :treatment, :prescribed_medicine, :follow_up_date)";
        $stmt = $pdo->prepare($query);

        try {
            $stmt->execute([
                ':patient_id' => $patient_id,
                ':diagnosis' => $diagnosis,
                ':treatment' => $treatment,
                ':prescribed_medicine' => $prescribed_medicine,
                ':follow_up_date' => $follow_up_date,
            ]);
            $_SESSION['message'] = "Health record added successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to add health record: " . $e->getMessage();
        }

        header("Location: manage_health_records.php");
        exit();
    }
}

// Fetch health records
$query = "SELECT hr.record_id, hr.diagnosis, hr.treatment, hr.prescribed_medicine, hr.follow_up_date, hr.created_at, 
                 p.first_name, p.last_name 
          FROM health_records hr
          JOIN patients p ON hr.patient_id = p.patient_id
          ORDER BY hr.created_at DESC";
$stmt = $pdo->query($query);
$health_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch patients for dropdown
$patient_query = "SELECT patient_id, first_name, last_name FROM patients ORDER BY last_name";
$patient_stmt = $pdo->query($patient_query);
$patients = $patient_stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h2 class="mb-4">Manage Health Records</h2>

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
                                <th>Record ID</th>
                                <th>Patient Name</th>
                                <th>Diagnosis</th>
                                <th>Treatment</th>
                                <th>Prescribed Medicine</th>
                                <th>Follow-Up Date</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($health_records)): ?>
                                <?php foreach ($health_records as $record): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($record['record_id']) ?></td>
                                        <td><?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) ?></td>
                                        <td><?= htmlspecialchars($record['diagnosis']) ?></td>
                                        <td><?= htmlspecialchars($record['treatment']) ?></td>
                                        <td><?= htmlspecialchars($record['prescribed_medicine']) ?></td>
                                        <td><?= htmlspecialchars($record['follow_up_date'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($record['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No health records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

        </main>
    </div>

    <!-- Add Health Record Modal -->
    <div class="modal fade" id="addHealthRecordModal" tabindex="-1" aria-labelledby="addHealthRecordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addHealthRecordModalLabel">Add Health Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="patient_id" class="form-label">Patient</label>
                            <select name="patient_id" id="patient_id" class="form-select" required>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?= $patient['patient_id'] ?>">
                                        <?= htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="diagnosis" class="form-label">Diagnosis</label>
                            <textarea name="diagnosis" id="diagnosis" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="treatment" class="form-label">Treatment</label>
                            <textarea name="treatment" id="treatment" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="prescribed_medicine" class="form-label">Prescribed Medicine</label>
                            <textarea name="prescribed_medicine" id="prescribed_medicine" class="form-control" rows="2"></textarea>
                        </div>
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
