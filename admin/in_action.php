<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require '../connections/connections.php';
$pdo = connection();

// Determine the mode, patient ID, and record ID
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'add'; // Default to 'add'
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : null;
$record_id = isset($_GET['record_id']) ? intval($_GET['record_id']) : null;

// Initialize variables for pre-filled data
$admission_date = $admitting_physician = $admitting_diagnosis = null;
$discharge_date = $discharge_diagnosis = $discharge_condition = $disposition = null;
$complications = $surgical_procedure = $pathological_report = null;
$patient_name = null;

// Handle Add Mode
if ($mode === 'add' && $patient_id && !$record_id) {
    // Fetch patient details using `patient_id`
    $query = "SELECT CONCAT(first_name, ' ', last_name) AS fullname FROM patients WHERE patient_id = :patient_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':patient_id' => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($patient) {
        $patient_name = $patient['fullname'];
    } else {
        $_SESSION['error'] = "Patient not found.";
        header("Location: manage_health_records.php");
        exit();
    }
}

// Handle View/Edit Mode
if (($mode === 'view' || $mode === 'edit') && $record_id && $patient_id) {
    // Fetch record details from `admissions`
    $query = "SELECT * FROM admissions WHERE admission_id = :record_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':record_id' => $record_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        $_SESSION['error'] = "Record not found.";
        header("Location: manage_health_records.php");
        exit();
    }

    // Fetch patient details
    $query = "SELECT CONCAT(first_name, ' ', last_name) AS fullname FROM patients WHERE patient_id = :patient_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':patient_id' => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($patient) {
        $patient_name = $patient['fullname'];
    } else {
        $_SESSION['error'] = "Patient not found.";
        header("Location: manage_health_records.php");
        exit();
    }

    // Pre-fill record data
    $admission_date = $record['admission_date'];
    $admitting_physician = $record['admitting_physician'];
    $admitting_diagnosis = $record['admitting_diagnosis'];
    $discharge_date = $record['discharge_date'];
    $discharge_diagnosis = $record['discharge_diagnosis'];
    $discharge_condition = $record['discharge_condition'];
    $disposition = $record['disposition'];
    $complications = $record['complications'];
    $surgical_procedure = $record['surgical_procedure'];
    $pathological_report = $record['pathological_report'];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $admission_date = $_POST['admission_date'];
    $admitting_physician = $_POST['admitting_physician'] ?: null;
    $admitting_diagnosis = $_POST['admitting_diagnosis'] ?: null;
    $discharge_date = $_POST['discharge_date'] ?: null;
    $discharge_diagnosis = $_POST['discharge_diagnosis'] ?: null;
    $discharge_condition = $_POST['discharge_condition'] ?: null;
    $disposition = $_POST['disposition'] ?: null;
    $complications = $_POST['complications'] ?: null;
    $surgical_procedure = $_POST['surgical_procedure'] ?: null;
    $pathological_report = $_POST['pathological_report'] ?: null;

    // Check if we're adding a new record
    if ($mode === 'add' && $patient_id) {
        // Insert new record
        $query = "
            INSERT INTO admissions (
                patient_id, admission_date, admitting_physician, admitting_diagnosis,
                discharge_date, discharge_diagnosis, discharge_condition, disposition,
                complications, surgical_procedure, pathological_report
            ) VALUES (
                :patient_id, :admission_date, :admitting_physician, :admitting_diagnosis,
                :discharge_date, :discharge_diagnosis, :discharge_condition, :disposition,
                :complications, :surgical_procedure, :pathological_report
            )
        ";
        $stmt = $pdo->prepare($query);

        try {
            $stmt->execute([
                ':patient_id' => $patient_id,
                ':admission_date' => $admission_date,
                ':admitting_physician' => $admitting_physician,
                ':admitting_diagnosis' => $admitting_diagnosis,
                ':discharge_date' => $discharge_date,
                ':discharge_diagnosis' => $discharge_diagnosis,
                ':discharge_condition' => $discharge_condition,
                ':disposition' => $disposition,
                ':complications' => $complications,
                ':surgical_procedure' => $surgical_procedure,
                ':pathological_report' => $pathological_report,
            ]);
            $_SESSION['message'] = "Record added successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to add record: " . $e->getMessage();
        }
    } elseif ($mode === 'edit' && $record_id) {
        // Update existing record
        $query = "
            UPDATE admissions SET
                admission_date = :admission_date,
                admitting_physician = :admitting_physician,
                admitting_diagnosis = :admitting_diagnosis,
                discharge_date = :discharge_date,
                discharge_diagnosis = :discharge_diagnosis,
                discharge_condition = :discharge_condition,
                disposition = :disposition,
                complications = :complications,
                surgical_procedure = :surgical_procedure,
                pathological_report = :pathological_report
            WHERE admission_id = :record_id
        ";
        $stmt = $pdo->prepare($query);

        try {
            $stmt->execute([
                ':admission_date' => $admission_date,
                ':admitting_physician' => $admitting_physician,
                ':admitting_diagnosis' => $admitting_diagnosis,
                ':discharge_date' => $discharge_date,
                ':discharge_diagnosis' => $discharge_diagnosis,
                ':discharge_condition' => $discharge_condition,
                ':disposition' => $disposition,
                ':complications' => $complications,
                ':surgical_procedure' => $surgical_procedure,
                ':pathological_report' => $pathological_report,
                ':record_id' => $record_id,
            ]);
            $_SESSION['message'] = "Record updated successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to update record: " . $e->getMessage();
        }
    }

    // Redirect back to the manage_health_records.php page
    header("Location: manage_health_records.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admit Patient</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
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
            <div class="d-flex align-items-center mb-4">
            <?php
                // Assume $mode contains the current mode ('view', 'edit', or 'default')
                $redirect_url = 'manage_health_records.php';  // Default redirect URL

                // Change redirect if the mode is 'view' or 'edit'
                if ($mode === 'view' || $mode === 'edit') {
                    $redirect_url = "patient_health_records.php?patient_id=" . $patient_id;
                }
            ?>

            <a href="<?= $redirect_url ?>" class="btn mb-0">
                <span class="material-icons">arrow_back</span>
            </a>
            <h2 class="mb-0">Admit Patient</h2>
                </div>
                <!-- Handle success/error messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php elseif (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Admission Form -->
                <form action="" method="POST" class="shadow p-4 bg-white rounded">
                <!-- Section: Patient Details -->
                <div class="section-container">
                    <h5 class="mb-3">Patient Details</h5>
                    <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="patient_name" class="form-label">Patient Name</label>
                        <input type="text" id="patient_name" class="form-control" 
                            value="<?= htmlspecialchars($patient['fullname'] ?? '') ?>" readonly>
                    </div>

                        <div class="col-md-6">
                            <label for="admission_date" class="form-label">Admission Date and Time</label>
                            <input type="datetime-local" name="admission_date" id="admission_date" class="form-control" 
                                value="<?= htmlspecialchars($admission_date ?? '') ?>" <?= ($mode === 'view') ? 'readonly' : '' ?> required>
                        </div>
                    </div>
                </div>

                <!-- Section: Physician and Diagnosis -->
                <div class="section-container">
                    <h5 class="mb-3">Physician and Diagnosis</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="admitting_physician" class="form-label">Admitting Physician</label>
                            <input type="text" name="admitting_physician" id="admitting_physician" class="form-control" 
                                value="<?= htmlspecialchars($admitting_physician ?? '') ?>" <?= ($mode === 'view') ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-6">
                            <label for="admitting_diagnosis" class="form-label">Admitting Diagnosis</label>
                            <textarea name="admitting_diagnosis" id="admitting_diagnosis" class="form-control" rows="2" 
                                    <?= ($mode === 'view') ? 'readonly' : '' ?>><?= htmlspecialchars($admitting_diagnosis ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Section: Discharge Information -->
                <div class="section-container">
                    <h5 class="mb-3">Discharge Information</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="discharge_date" class="form-label">Discharge Date and Time</label>
                            <input type="datetime-local" name="discharge_date" id="discharge_date" class="form-control" 
                                value="<?= htmlspecialchars($discharge_date ?? '') ?>" <?= ($mode === 'view') ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-6">
                            <label for="discharge_diagnosis" class="form-label">Discharge Diagnosis</label>
                            <textarea name="discharge_diagnosis" id="discharge_diagnosis" class="form-control" rows="2" 
                                    <?= ($mode === 'view') ? 'readonly' : '' ?>><?= htmlspecialchars($discharge_diagnosis ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Section: Discharge Condition and Disposition -->
                <div class="section-container">
                    <h5 class="mb-3">Discharge Condition and Disposition</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="discharge_condition" class="form-label">Discharge Condition</label>
                            <select name="discharge_condition" id="discharge_condition" class="form-select" <?= ($mode === 'view') ? 'disabled' : '' ?>>
                                <option value="">Select...</option>
                                <option value="Recovered" <?= ($discharge_condition === 'Recovered') ? 'selected' : '' ?>>Recovered</option>
                                <option value="Improved" <?= ($discharge_condition === 'Improved') ? 'selected' : '' ?>>Improved</option>
                                <option value="Unimproved" <?= ($discharge_condition === 'Unimproved') ? 'selected' : '' ?>>Unimproved</option>
                                <option value="Died" <?= ($discharge_condition === 'Died') ? 'selected' : '' ?>>Died</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="disposition" class="form-label">Disposition</label>
                            <select name="disposition" id="disposition" class="form-select" <?= ($mode === 'view') ? 'disabled' : '' ?>>
                                <option value="">Select...</option>
                                <option value="Discharged" <?= ($disposition === 'Discharged') ? 'selected' : '' ?>>Discharged</option>
                                <option value="Transferred" <?= ($disposition === 'Transferred') ? 'selected' : '' ?>>Transferred</option>
                                <option value="Home Against Medical Advice" <?= ($disposition === 'Home Against Medical Advice') ? 'selected' : '' ?>>Home Against Medical Advice</option>
                                <option value="Absconded" <?= ($disposition === 'Absconded') ? 'selected' : '' ?>>Absconded</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section: Additional Notes -->
                <div class="section-container">
                    <h5 class="mb-3">Additional Notes</h5>
                    <div class="mb-3">
                        <label for="complications" class="form-label">Complications</label>
                        <textarea name="complications" id="complications" class="form-control" rows="2" 
                                <?= ($mode === 'view') ? 'readonly' : '' ?>><?= htmlspecialchars($complications ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="surgical_procedure" class="form-label">Surgical Procedure</label>
                        <textarea name="surgical_procedure" id="surgical_procedure" class="form-control" rows="2" 
                                <?= ($mode === 'view') ? 'readonly' : '' ?>><?= htmlspecialchars($surgical_procedure ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="pathological_report" class="form-label">Pathological Report</label>
                        <textarea name="pathological_report" id="pathological_report" class="form-control" rows="2" 
                                <?= ($mode === 'view') ? 'readonly' : '' ?>><?= htmlspecialchars($pathological_report ?? '') ?></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                <?php if (strtolower($mode) !== 'view'): ?>
                    <button type="submit" class="btn btn-primary"><?= ucfirst($mode) ?> Record</button>
                <?php endif; ?>
                    <a href="patient_health_records.php?patient_id=<?= $patient_id ?>" class="btn btn-secondary ms-2">Back</a>
                </div>
            </form>


            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
