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

// Get patient_id from the query parameter
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : null;

// Initialize variables for pre-filled data
$admission_date = $admitting_physician = $admitting_diagnosis = null;
$patient_name = null;
$case_id = null; // Variable to store the case_id

// Handle Add Mode (no record_id required)
if ($patient_id) {
    // Fetch patient details using `patient_id` and also fetch `case_id`
    $query = "SELECT CONCAT(first_name, ' ', last_name) AS fullname, case_id FROM patients WHERE patient_id = :patient_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':patient_id' => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($patient) {
        $patient_name = $patient['fullname'];
        $case_id = $patient['case_id']; // Get case_id
    } else {
        $_SESSION['error'] = "Patient not found.";
        header("Location: manage_health_records.php");
        exit();
    }
}

// Fetch staff for admitting physician dropdown
$physicians = [];
$stmt = $pdo->prepare("SELECT staff_id, first_name, last_name, role FROM staff WHERE role IN ('Doctor', 'Midwife') AND status = 'Active' ORDER BY first_name, last_name");
$stmt->execute();
$physicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $admission_date = $_POST['admission_date'];
    $admitting_physician = $_POST['admitting_physician'] ?: null;
    $admitting_diagnosis = $_POST['admitting_diagnosis'] ?: null;
    $room_id = $_POST['room'];  // Room ID selected by the user

    // Check if we're adding a new record
    if ($patient_id && $case_id) {
        // Insert new record into admissions table
        $query = "
            INSERT INTO admissions (
                patient_id, admission_date, admitting_physician, admitting_diagnosis
            ) VALUES (
                :patient_id, :admission_date, :admitting_physician, :admitting_diagnosis
            )
        ";
        $stmt = $pdo->prepare($query);

        try {
            $stmt->execute([
                ':patient_id' => $patient_id,
                ':admission_date' => $admission_date,
                ':admitting_physician' => $admitting_physician,
                ':admitting_diagnosis' => $admitting_diagnosis,
            ]);

            // Get the new admission_id
            $admission_id = $pdo->lastInsertId();

            // Insert into medical_transactions for Admission
            $service_id = 10; // Admission
            $amount = 0.00; // Or set a default if you want
            $transaction_date = date('Y-m-d H:i:s');
            $payment_status = 'Pending';

            $query = "
                INSERT INTO medical_transactions (
                    case_id, service_id, admission_id, transaction_date, amount, payment_status
                ) VALUES (
                    :case_id, :service_id, :admission_id, :transaction_date, :amount, :payment_status
                )
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':case_id' => $case_id,
                ':service_id' => $service_id,
                ':admission_id' => $admission_id,
                ':transaction_date' => $transaction_date,
                ':amount' => $amount,
                ':payment_status' => $payment_status
            ]);

            // Now update the rooms table with the fetched case_id
            $query = "
                UPDATE rooms
                SET case_id = :case_id, status = 'Occupied'
                WHERE room_id = :room_id
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':case_id' => $case_id,  // Use case_id from patients table
                ':room_id' => $room_id,
            ]);

            $_SESSION['message'] = "Admission record added, transaction created, and room updated successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to add admission record, transaction, or update room: " . $e->getMessage();
        }
    }

    // Redirect back to the manage_health_records.php page
    header("Location: manage_health_records.php");
    exit();
}

// Fetch available rooms for selection
$query = "SELECT room_id, room_number FROM rooms WHERE status = 'Available'";
$stmt = $pdo->prepare($query);
$stmt->execute();
$available_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        .room-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .room-available {
            background-color: #d4edda;
            color: #155724;
        }

        .form-floating > .form-control {
            padding-top: 1.625rem;
            padding-bottom: 0.625rem;
        }

        .form-floating > label {
            padding: 1rem 0.75rem;
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
                            <h2><i class="fas fa-procedures me-2"></i>Admit Patient</h2>
                            <?php if ($patient_name): ?>
                            <p class="mb-0">
                                <i class="fas fa-user me-2"></i>Patient: <?= htmlspecialchars($patient_name) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="javascript:history.back()" class="btn btn-light btn-lg">
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
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" id="patient_name" class="form-control" 
                                        value="<?= htmlspecialchars($patient_name ?? '') ?>" readonly>
                                    <label for="patient_name">Patient Name</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="datetime-local" name="admission_date" id="admission_date" class="form-control" 
                                        value="<?= htmlspecialchars($admission_date ?? '') ?>" required>
                                    <label for="admission_date">Admission Date and Time</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select name="room" id="room" class="form-select" required>
                                        <option value="">Select Room...</option>
                                        <?php foreach ($available_rooms as $room): ?>
                                            <option value="<?= htmlspecialchars($room['room_id']) ?>">
                                                Room <?= htmlspecialchars($room['room_number']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="room">Select Room</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Physician and Diagnosis -->
                    <div class="section-container">
                        <h5><i class="fas fa-stethoscope me-2"></i>Physician and Diagnosis</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <!-- Admitting Physician Dropdown -->
                                <div class="form-floating mb-3">
                                    <select name="admitting_physician" id="admitting_physician" class="form-select" required>
                                        <option value="">Select Physician</option>
                                        <?php foreach ($physicians as $physician): 
                                            $fullName = $physician['first_name'] . ' ' . $physician['last_name'];
                                            $selected = ($admitting_physician === $fullName) ? 'selected' : '';
                                        ?>
                                            <option value="<?= htmlspecialchars($fullName) ?>" <?= $selected ?>><?= htmlspecialchars($fullName) ?> (<?= htmlspecialchars($physician['role']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="admitting_physician">Admitting Physician</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea name="admitting_diagnosis" id="admitting_diagnosis" class="form-control" 
                                              style="height: 100px" required><?= htmlspecialchars($admitting_diagnosis ?? '') ?></textarea>
                                    <label for="admitting_diagnosis">Admitting Diagnosis</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4 mb-5">
                        <button type="submit" class="btn btn-success btn-lg px-5">
                            <i class="fas fa-hospital-user me-2"></i>Admit Patient
                        </button>
                        <a href="javascript:history.back()" class="btn btn-secondary btn-lg px-5 ms-2">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
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
