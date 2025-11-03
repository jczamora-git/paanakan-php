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

// Set up redirect URL based on mode
$redirect_url = 'manage_health_records.php';  // Default redirect URL
if ($mode === 'view' || $mode === 'edit') {
    $redirect_url = "patient_health_records.php?patient_id=" . $patient_id;
}

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'Admin') {
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

    // Redirect back to the appropriate page
    header("Location: " . $redirect_url);
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Details</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            transition: margin-left 0.4s ease;
            background-color: #f8f9fa;
        }

        .sidebar.collapsed ~ .dashboard-main-content {
            margin-left: 85px;
        }

        .patient-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .patient-header h2 {
            font-weight: 600;
            margin-bottom: 10px;
        }

        .patient-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .section-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 25px;
        }

        .section-container h5 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
        }

        .form-label {
            font-weight: 500;
            color: #444;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 8px 12px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.25);
        }

        .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%232E8B57' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        }

        textarea.form-control {
            min-height: 100px;
        }

        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background-color: var(--primary-light);
            border-color: var(--primary-light);
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 15px 20px;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .form-floating > .form-control {
            padding-top: 1.625rem;
            padding-bottom: 0.625rem;
        }

        .form-floating > label {
            padding: 1rem 0.75rem;
        }

        .admission-info {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .admission-info p {
            margin: 0;
            font-size: 0.95rem;
        }

        .readonly-field {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }

        .edit-mode-badge {
            background: rgba(255, 255, 255, 0.3);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-left: 15px;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include '../sidebar.php'; ?>

        <main class="dashboard-main-content">
            <?php include '../admin/breadcrumb.php'; ?>
            
            <div class="container">
                <!-- Patient Header Section -->
                <div class="patient-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2>
                                <i class="fas fa-hospital-user me-2"></i>
                                <?= $mode === 'add' ? 'New Admission' : ($mode === 'edit' ? 'Edit Admission' : 'View Admission') ?>
                                <?php if ($_SESSION['role'] === 'Admin'): ?>
                                    <span class="edit-mode-badge">
                                        <i class="fas fa-edit me-1"></i>Edit Mode Enabled
                                    </span>
                                <?php endif; ?>
                            </h2>
                            <?php if ($patient_name): ?>
                            <p class="mb-0">
                                <i class="fas fa-user me-2"></i>Patient: <?= htmlspecialchars($patient_name) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="<?= $redirect_url ?>" class="btn btn-light btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
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

                <!-- Admission Form -->
                <form action="" method="POST" class="needs-validation" novalidate>
                    <!-- Section: Patient Details -->
                    <div class="section-container">
                        <h5><i class="fas fa-user-circle me-2"></i>Patient Details</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" id="patient_name" class="form-control" 
                                        value="<?= htmlspecialchars($patient_name ?? '') ?>" readonly>
                                    <label for="patient_name">Patient Name</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="datetime-local" name="admission_date" id="admission_date" 
                                        class="form-control <?= $_SESSION['role'] !== 'Admin' ? 'readonly-field' : '' ?>" 
                                        value="<?= htmlspecialchars($admission_date ?? '') ?>" 
                                        <?= $_SESSION['role'] !== 'Admin' ? 'readonly' : '' ?> required>
                                    <label for="admission_date">Admission Date and Time</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Physician and Diagnosis -->
                    <div class="section-container">
                        <h5><i class="fas fa-stethoscope me-2"></i>Physician and Diagnosis</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="admitting_physician" id="admitting_physician" 
                                        class="form-control <?= $_SESSION['role'] !== 'Admin' ? 'readonly-field' : '' ?>" 
                                        value="<?= htmlspecialchars($admitting_physician ?? '') ?>" 
                                        <?= $_SESSION['role'] !== 'Admin' ? 'readonly' : '' ?> required>
                                    <label for="admitting_physician">Admitting Physician</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea name="admitting_diagnosis" id="admitting_diagnosis" 
                                        class="form-control <?= $_SESSION['role'] !== 'Admin' ? 'readonly-field' : '' ?>" 
                                        style="height: 100px" <?= $_SESSION['role'] !== 'Admin' ? 'readonly' : '' ?> 
                                        required><?= htmlspecialchars($admitting_diagnosis ?? '') ?></textarea>
                                    <label for="admitting_diagnosis">Admitting Diagnosis</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Discharge Information -->
                    <div class="section-container">
                        <h5><i class="fas fa-clipboard-check me-2"></i>Discharge Information</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="datetime-local" name="discharge_date" id="discharge_date" 
                                        class="form-control <?= $_SESSION['role'] !== 'Admin' ? 'readonly-field' : '' ?>" 
                                        value="<?= htmlspecialchars($discharge_date ?? '') ?>" 
                                        <?= $_SESSION['role'] !== 'Admin' ? 'readonly' : '' ?>>
                                    <label for="discharge_date">Discharge Date and Time</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea name="discharge_diagnosis" id="discharge_diagnosis" 
                                        class="form-control <?= $_SESSION['role'] !== 'Admin' ? 'readonly-field' : '' ?>" 
                                        style="height: 100px" <?= $_SESSION['role'] !== 'Admin' ? 'readonly' : '' ?>
                                        ><?= htmlspecialchars($discharge_diagnosis ?? '') ?></textarea>
                                    <label for="discharge_diagnosis">Discharge Diagnosis</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Discharge Condition and Disposition -->
                    <div class="section-container">
                        <h5><i class="fas fa-notes-medical me-2"></i>Discharge Condition and Disposition</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="discharge_condition" id="discharge_condition" 
                                        class="form-select <?= $_SESSION['role'] !== 'Admin' ? 'readonly-field' : '' ?>" 
                                        <?= $_SESSION['role'] !== 'Admin' ? 'disabled' : '' ?>>
                                        <option value="">Select Condition...</option>
                                        <option value="Recovered" <?= ($discharge_condition === 'Recovered') ? 'selected' : '' ?>>Recovered</option>
                                        <option value="Improved" <?= ($discharge_condition === 'Improved') ? 'selected' : '' ?>>Improved</option>
                                        <option value="Unimproved" <?= ($discharge_condition === 'Unimproved') ? 'selected' : '' ?>>Unimproved</option>
                                        <option value="Died" <?= ($discharge_condition === 'Died') ? 'selected' : '' ?>>Died</option>
                                    </select>
                                    <label for="discharge_condition">Discharge Condition</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="disposition" id="disposition" 
                                        class="form-select <?= $_SESSION['role'] !== 'Admin' ? 'readonly-field' : '' ?>" 
                                        <?= $_SESSION['role'] !== 'Admin' ? 'disabled' : '' ?>>
                                        <option value="">Select Disposition...</option>
                                        <option value="Discharged" <?= ($disposition === 'Discharged') ? 'selected' : '' ?>>Discharged</option>
                                        <option value="Transferred" <?= ($disposition === 'Transferred') ? 'selected' : '' ?>>Transferred</option>
                                        <option value="Home Against Medical Advice" <?= ($disposition === 'Home Against Medical Advice') ? 'selected' : '' ?>>Home Against Medical Advice</option>
                                        <option value="Absconded" <?= ($disposition === 'Absconded') ? 'selected' : '' ?>>Absconded</option>
                                    </select>
                                    <label for="disposition">Disposition</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Additional Notes -->
                    <div class="section-container">
                        <h5><i class="fas fa-file-medical-alt me-2"></i>Additional Notes</h5>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <textarea name="complications" id="complications" 
                                        class="form-control <?= $_SESSION['role'] !== 'Admin' ? 'readonly-field' : '' ?>" 
                                        style="height: 100px" <?= $_SESSION['role'] !== 'Admin' ? 'readonly' : '' ?>
                                        ><?= htmlspecialchars($complications ?? '') ?></textarea>
                                    <label for="complications">Complications</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <textarea name="surgical_procedure" id="surgical_procedure" 
                                        class="form-control <?= $_SESSION['role'] !== 'Admin' ? 'readonly-field' : '' ?>" 
                                        style="height: 100px" <?= $_SESSION['role'] !== 'Admin' ? 'readonly' : '' ?>
                                        ><?= htmlspecialchars($surgical_procedure ?? '') ?></textarea>
                                    <label for="surgical_procedure">Surgical Procedure</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <textarea name="pathological_report" id="pathological_report" 
                                        class="form-control <?= $_SESSION['role'] !== 'Admin' ? 'readonly-field' : '' ?>" 
                                        style="height: 100px" <?= $_SESSION['role'] !== 'Admin' ? 'readonly' : '' ?>
                                        ><?= htmlspecialchars($pathological_report ?? '') ?></textarea>
                                    <label for="pathological_report">Pathological Report</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Buttons -->
                    <?php if ($_SESSION['role'] === 'Admin' && ($mode === 'edit' || $mode === 'add')): ?>
                    <div class="text-center mt-4 mb-5">
                        <button type="submit" class="btn btn-success btn-lg px-5">
                            <i class="fas <?= $mode === 'edit' ? 'fa-save' : 'fa-plus-circle' ?> me-2"></i>
                            <?= $mode === 'edit' ? 'Update Record' : 'Add Record' ?>
                        </button>
                        <a href="<?= $redirect_url ?>" class="btn btn-secondary btn-lg px-5 ms-2">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Form Validation Script -->
    <script>
        (() => {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>
