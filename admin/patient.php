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

// Handle adding, editing, and deleting patients
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        // Add Patient
        $case_id = $_POST['case_id'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $gender = $_POST['gender'];
        $dob = $_POST['date_of_birth'];
        $contact_number = $_POST['contact_number'];
        $address = $_POST['address'];
        $medical_history = $_POST['medical_history'];

        $query = "INSERT INTO patients (case_id, first_name, last_name, gender, date_of_birth, contact_number, address)
                  VALUES (:case_id, :first_name, :last_name, :gender, :dob, :contact_number, :address)";
        $stmt = $pdo->prepare($query);

        try {
            $stmt->execute([
                ':case_id' => $case_id,
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':gender' => $gender,
                ':dob' => $dob,
                ':contact_number' => $contact_number,
                ':address' => $address,
            ]);
            $_SESSION['message'] = "Patient added successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to add patient: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'edit') {
        // Edit Patient
        $patient_id = intval($_POST['patient_id']);
        $case_id = $_POST['case_id'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $gender = $_POST['gender'];
        $dob = $_POST['date_of_birth'];
        $contact_number = $_POST['contact_number'];
        $address = $_POST['address'];

        $query = "UPDATE patients
                  SET case_id = :case_id, first_name = :first_name, last_name = :last_name, gender = :gender,
                      date_of_birth = :dob, contact_number = :contact_number, address = :address
                  WHERE patient_id = :patient_id";
        $stmt = $pdo->prepare($query);

        try {
            $stmt->execute([
                ':case_id' => $case_id,
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':gender' => $gender,
                ':dob' => $dob,
                ':contact_number' => $contact_number,
                ':address' => $address,
                ':patient_id' => $patient_id,
            ]);
            $_SESSION['message'] = "Patient updated successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to update patient: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'delete') {
        // Delete Patient
        $patient_id = intval($_POST['patient_id']);
        $query = "DELETE FROM patients WHERE patient_id = :patient_id";
        $stmt = $pdo->prepare($query);

        try {
            $stmt->execute([':patient_id' => $patient_id]);
            $_SESSION['message'] = "Patient deleted successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to delete patient: " . $e->getMessage();
        }
    }

    header("Location: patient.php");
    exit();
}

// Fetch all patients
$query = "SELECT * FROM patients ORDER BY created_at DESC";
$stmt = $pdo->query($query);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Patients</title>
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
                <h2 class="mb-4">Manage Patients</h2>

                <!-- Handle success/error messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php elseif (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Add New Patient -->
                <button class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addPatientModal">Add Patient</button>

                <!-- Patients Table -->
                <div class="table-container shadow rounded bg-white p-3">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Case ID</th>
                                <th>Full Name</th>
                                <th>Gender</th>
                                <th>DOB</th>
                                <th>Contact</th>
                                <th>Address</th> <!-- New Address Column -->
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($patients)): ?>
                                <?php foreach ($patients as $patient): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($patient['patient_id']) ?></td>
                                        <td><?= htmlspecialchars($patient['case_id']) ?></td>
                                        <td><?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?></td>
                                        <td><?= htmlspecialchars($patient['gender']) ?></td>
                                        <td><?= (new DateTime($patient['date_of_birth']))->format('F j, Y') ?></td>
                                        <td><?= htmlspecialchars($patient['contact_number']) ?></td>
                                        <td><?= htmlspecialchars($patient['address']) ?></td> <!-- Display Address -->
                        
                                        <td>
                                            <!-- Edit Button -->
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editPatientModal<?= $patient['patient_id'] ?>">Edit</button>
                                        </td>
                                    </tr>

                                    <!-- Edit Patient Modal -->
                                    <div class="modal fade" id="editPatientModal<?= $patient['patient_id'] ?>" tabindex="-1" aria-labelledby="editPatientModalLabel<?= $patient['patient_id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="" method="POST">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editPatientModalLabel<?= $patient['patient_id'] ?>">Edit Patient</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="edit">
                                                        <input type="hidden" name="patient_id" value="<?= $patient['patient_id'] ?>">

                                                        <!-- Case ID -->
                                                        <div class="mb-3">
                                                            <label for="case_id" class="form-label">Case ID</label>
                                                            <input type="text" name="case_id" id="case_id" value="<?= htmlspecialchars($patient['case_id']) ?>" class="form-control" required>
                                                        </div>
                                                        <!-- First Name -->
                                                        <div class="mb-3">
                                                            <label for="first_name" class="form-label">First Name</label>
                                                            <input type="text" name="first_name" id="first_name" value="<?= htmlspecialchars($patient['first_name']) ?>" class="form-control" required>
                                                        </div>
                                                        <!-- Last Name -->
                                                        <div class="mb-3">
                                                            <label for="last_name" class="form-label">Last Name</label>
                                                            <input type="text" name="last_name" id="last_name" value="<?= htmlspecialchars($patient['last_name']) ?>" class="form-control" required>
                                                        </div>
                                                        <!-- Gender -->
                                                        <div class="mb-3">
                                                            <label for="gender" class="form-label">Gender</label>
                                                            <select name="gender" id="gender" class="form-select" required>
                                                                <option value="Male" <?= $patient['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                                                <option value="Female" <?= $patient['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                                            </select>
                                                        </div>
                                                        <!-- Date of Birth -->
                                                        <div class="mb-3">
                                                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                                                            <input type="date" name="date_of_birth" id="date_of_birth" value="<?= htmlspecialchars($patient['date_of_birth']) ?>" class="form-control" required>
                                                        </div>
                                                        <!-- Contact Number -->
                                                        <div class="mb-3">
                                                            <label for="contact_number" class="form-label">Contact Number</label>
                                                            <input type="text" name="contact_number" id="contact_number" value="<?= htmlspecialchars($patient['contact_number']) ?>" class="form-control" required>
                                                        </div>
                                                        <!-- Address -->
                                                        <div class="mb-3">
                                                            <label for="address" class="form-label">Address</label>
                                                            <textarea name="address" id="address" class="form-control"><?= htmlspecialchars($patient['address']) ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="submit" class="btn btn-primary">Update Patient</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No patients found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Patient Modal -->
    <div class="modal fade" id="addPatientModal" tabindex="-1" aria-labelledby="addPatientModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPatientModalLabel">Add Patient</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <!-- Case ID -->
                        <div class="mb-3">
                            <label for="case_id" class="form-label">Case ID</label>
                            <input type="text" name="case_id" id="case_id" class="form-control" required>
                        </div>
                        <!-- First Name -->
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" name="first_name" id="first_name" class="form-control" required>
                        </div>
                        <!-- Last Name -->
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="form-control" required>
                        </div>
                        <!-- Gender -->
                        <div class="mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select name="gender" id="gender" class="form-select" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <!-- Date of Birth -->
                        <div class="mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" id="date_of_birth" class="form-control" required>
                        </div>
                        <!-- Contact Number -->
                        <div class="mb-3">
                            <label for="contact_number" class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" id="contact_number" class="form-control" required>
                        </div>
                        <!-- Address -->
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea name="address" id="address" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Add Patient</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
