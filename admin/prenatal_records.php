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

// Initialize variables
$view_mode = false;
$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : null;
$transaction_id = isset($_GET['transaction_id']) ? intval($_GET['transaction_id']) : null;
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : null;

// If appointment_id is provided, get the patient_id from the appointment
if ($appointment_id && !$patient_id) {
    $stmt = $pdo->prepare("
        SELECT patient_id 
        FROM appointments 
        WHERE appointment_id = ?
    ");
    $stmt->execute([$appointment_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $patient_id = $result['patient_id'];
    }
}

// If patient_id is provided, we're in view mode
if ($patient_id) {
    $view_mode = true;
}

// Fetch the patient's information and appointment details if available
if ($patient_id) {
    if ($appointment_id) {
        // Get patient info and appointment details
        $query = "
            SELECT 
                CONCAT(p.first_name, ' ', p.last_name) AS fullname,
                a.appointment_id,
                a.scheduled_date,
                a.appointment_type,
                a.status
            FROM patients p
            LEFT JOIN appointments a ON a.appointment_id = ?
            WHERE p.patient_id = ?
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$appointment_id, $patient_id]);
    } else {
        // Get only patient info
        $query = "SELECT CONCAT(first_name, ' ', last_name) AS fullname FROM patients WHERE patient_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$patient_id]);
    }
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$patient) {
        $_SESSION['error'] = "Patient not found.";
        header("Location: prenatal_records.php");
        exit();
    }
}

// Fetch existing records for this patient
$existingRecords = [];
$stmt = $pdo->prepare("
    SELECT * FROM prenatal_records 
    WHERE patient_id = ? 
    ORDER BY visit_date DESC
");
$stmt->execute([$patient_id]);
$existingRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing record for this transaction
$existingRecord = null;
if ($transaction_id) {
    $stmt = $pdo->prepare("SELECT * FROM prenatal_records WHERE transaction_id = ? LIMIT 1");
    $stmt->execute([$transaction_id]);
    $existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch records
$records = [];
if ($view_mode && $patient_id) {
    // Get all prenatal records for this patient_id
    $stmt = $pdo->prepare("SELECT * FROM prenatal_records WHERE patient_id = ? ORDER BY visit_date DESC");
    $stmt->execute([$patient_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($appointment_id) {
    // Get records for this appointment
    $stmt = $pdo->prepare("SELECT * FROM prenatal_records WHERE appointment_id = ? ORDER BY visit_date DESC");
    $stmt->execute([$appointment_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($transaction_id) {
    // Get records for this transaction
    $stmt = $pdo->prepare("SELECT * FROM prenatal_records WHERE transaction_id = ? ORDER BY visit_date DESC");
    $stmt->execute([$transaction_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch existing record for this transaction
$latestStatic = null;
if (!$existingRecord && $patient_id) {
    $stmt = $pdo->prepare("SELECT lmp, edc_by_lmp, edc_by_usg FROM prenatal_records WHERE patient_id = ? ORDER BY visit_date DESC LIMIT 1");
    $stmt->execute([$patient_id]);
    $latestStatic = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if complete appointment button was pressed
    $complete_appointment = isset($_POST['complete_appointment']) ? true : false;

    // Prepare data
    $visit_date = $_POST['visit_date'] ?: null;
    $attending_physician = $_POST['attending_physician'] ?: null;
    $gravida = $_POST['gravida'] ?: null;
    $para = $_POST['para'] ?: null;
    $ob_score = $_POST['ob_score'] ?: null;
    $lmp = $_POST['lmp'] ?: null;
    $aog_by_lmp = $_POST['aog_by_lmp'] ?: null;
    $edc_by_lmp = $_POST['edc_by_lmp'] ?: null;
    $aog_by_usg = $_POST['aog_by_usg'] ?: null;
    $edc_by_usg = $_POST['edc_by_usg'] ?: null;
    $blood_pressure = $_POST['blood_pressure'] ?: null;
    $weight = $_POST['weight'] ?: null;
    $temperature = $_POST['temperature'] ?: null;
    $respiratory_rate = $_POST['respiratory_rate'] ?: null;
    $fundal_height = $_POST['fundal_height'] ?: null;
    $fetal_heart_tones = $_POST['fetal_heart_tones'] ?: null;
    $internal_examination = $_POST['internal_examination'] ?: null;
    $chief_complaint = $_POST['chief_complaint'] ?: null;
    $history_of_present_illness = $_POST['history_of_present_illness'] ?: null;
    $past_medical_history = $_POST['past_medical_history'] ?: null;
    $past_social_history = $_POST['past_social_history'] ?: null;
    $family_history = $_POST['family_history'] ?: null;
    $tt_dose = $_POST['tt_dose'] ?: null;
    $plan = $_POST['plan'] ?: null;
    $lab_results = $_POST['lab_results'] ?: null;

    if ($existingRecord) {
        // Update existing record
        $query = "
            UPDATE prenatal_records SET 
                visit_date = :visit_date,
                attending_physician = :attending_physician,
                gravida = :gravida,
                para = :para,
                ob_score = :ob_score,
                lmp = :lmp,
                aog_by_lmp = :aog_by_lmp,
                edc_by_lmp = :edc_by_lmp,
                aog_by_usg = :aog_by_usg,
                edc_by_usg = :edc_by_usg,
                blood_pressure = :blood_pressure,
                weight = :weight,
                temperature = :temperature,
                respiratory_rate = :respiratory_rate,
                fundal_height = :fundal_height,
                fetal_heart_tones = :fetal_heart_tones,
                internal_examination = :internal_examination,
                chief_complaint = :chief_complaint,
                history_of_present_illness = :history_of_present_illness,
                past_medical_history = :past_medical_history,
                past_social_history = :past_social_history,
                family_history = :family_history,
                tt_dose = :tt_dose,
                plan = :plan,
                lab_results = :lab_results
            WHERE record_id = :record_id
        ";
        $stmt = $pdo->prepare($query);
        $params = [
            ':visit_date' => $visit_date,
            ':attending_physician' => $attending_physician,
            ':gravida' => $gravida,
            ':para' => $para,
            ':ob_score' => $ob_score,
            ':lmp' => $lmp,
            ':aog_by_lmp' => $aog_by_lmp,
            ':edc_by_lmp' => $edc_by_lmp,
            ':aog_by_usg' => $aog_by_usg,
            ':edc_by_usg' => $edc_by_usg,
            ':blood_pressure' => $blood_pressure,
            ':weight' => $weight,
            ':temperature' => $temperature,
            ':respiratory_rate' => $respiratory_rate,
            ':fundal_height' => $fundal_height,
            ':fetal_heart_tones' => $fetal_heart_tones,
            ':internal_examination' => $internal_examination,
            ':chief_complaint' => $chief_complaint,
            ':history_of_present_illness' => $history_of_present_illness,
            ':past_medical_history' => $past_medical_history,
            ':past_social_history' => $past_social_history,
            ':family_history' => $family_history,
            ':tt_dose' => $tt_dose,
            ':plan' => $plan,
            ':lab_results' => $lab_results,
            ':record_id' => $existingRecord['record_id']
        ];
    } else {
        // Insert new record
        $query = "
            INSERT INTO prenatal_records (
                patient_id, appointment_id, transaction_id, visit_date, attending_physician,
                gravida, para, ob_score, lmp, aog_by_lmp, edc_by_lmp, aog_by_usg, edc_by_usg,
                blood_pressure, weight, temperature, respiratory_rate, fundal_height,
                fetal_heart_tones, internal_examination, chief_complaint,
                history_of_present_illness, past_medical_history, past_social_history,
                family_history, tt_dose, plan, lab_results
            ) VALUES (
                :patient_id, :appointment_id, :transaction_id, :visit_date, :attending_physician,
                :gravida, :para, :ob_score, :lmp, :aog_by_lmp, :edc_by_lmp, :aog_by_usg, :edc_by_usg,
                :blood_pressure, :weight, :temperature, :respiratory_rate, :fundal_height,
                :fetal_heart_tones, :internal_examination, :chief_complaint,
                :history_of_present_illness, :past_medical_history, :past_social_history,
                :family_history, :tt_dose, :plan, :lab_results
            )
        ";
        $stmt = $pdo->prepare($query);
        $params = [
            ':patient_id' => $patient_id,
            ':appointment_id' => $appointment_id,
            ':transaction_id' => $transaction_id,
            ':visit_date' => $visit_date,
            ':attending_physician' => $attending_physician,
            ':gravida' => $gravida,
            ':para' => $para,
            ':ob_score' => $ob_score,
            ':lmp' => $lmp,
            ':aog_by_lmp' => $aog_by_lmp,
            ':edc_by_lmp' => $edc_by_lmp,
            ':aog_by_usg' => $aog_by_usg,
            ':edc_by_usg' => $edc_by_usg,
            ':blood_pressure' => $blood_pressure,
            ':weight' => $weight,
            ':temperature' => $temperature,
            ':respiratory_rate' => $respiratory_rate,
            ':fundal_height' => $fundal_height,
            ':fetal_heart_tones' => $fetal_heart_tones,
            ':internal_examination' => $internal_examination,
            ':chief_complaint' => $chief_complaint,
            ':history_of_present_illness' => $history_of_present_illness,
            ':past_medical_history' => $past_medical_history,
            ':past_social_history' => $past_social_history,
            ':family_history' => $family_history,
            ':tt_dose' => $tt_dose,
            ':plan' => $plan,
            ':lab_results' => $lab_results
        ];
    }

    try {
        $pdo->beginTransaction();
        
        // Execute the prenatal record query
        $stmt->execute($params);
        
        // If this is an appointment and complete button was pressed, update appointment status
        if ($appointment_id && $complete_appointment) {
            $updateAppointment = "UPDATE appointments SET status = 'Completed' WHERE appointment_id = ?";
            $updateStmt = $pdo->prepare($updateAppointment);
            $updateStmt->execute([$appointment_id]);
        }
        
        $pdo->commit();
        $_SESSION['message'] = "Prenatal record " . ($existingRecord ? "updated" : "added") . " successfully.";
        
        // Redirect to the same page with the same parameters
        $redirect_url = "../healthrecords/prenatal_records.php?";
        if ($appointment_id) {
            $redirect_url .= "appointment_id=" . $appointment_id;
        } elseif ($transaction_id) {
            $redirect_url .= "transaction_id=" . $transaction_id;
        }
        if (isset($_GET['case_id'])) {
            $redirect_url .= ($appointment_id || $transaction_id ? "&" : "") . "case_id=" . $_GET['case_id'];
        }
        if ($patient_id) {
            $redirect_url .= (($appointment_id || $transaction_id || isset($_GET['case_id'])) ? "&" : "") . "patient_id=" . $patient_id;
        }
        header("Location: " . $redirect_url);
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to " . ($existingRecord ? "update" : "add") . " prenatal record: " . $e->getMessage();
        // Stay on the same page on error
        $redirect_url = "../healthrecords/prenatal_records.php?";
        if (isset($_GET['case_id'])) {
            $redirect_url .= "case_id=" . $_GET['case_id'];
        }
        if ($transaction_id) {
            $redirect_url .= (isset($_GET['case_id']) ? "&" : "") . "transaction_id=" . $transaction_id;
        }
        if ($patient_id) {
            $redirect_url .= ((isset($_GET['case_id']) || $transaction_id) ? "&" : "") . "patient_id=" . $patient_id;
        }
        header("Location: " . $redirect_url);
        exit();
    }
}

if (!$transaction_id && !$patient_id) {
    $_SESSION['error'] = "Transaction ID or Patient ID is required.";
    header("Location: prenatal_records.php");
    exit();
}

// Get transaction and patient details
if ($transaction_id) {
    $query = "
        SELECT 
            mt.*,
            p.patient_id,
            CONCAT(p.first_name, ' ', p.last_name) AS fullname
        FROM medical_transactions mt
        JOIN patients p ON mt.case_id = p.case_id
        WHERE mt.transaction_id = ?
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        $_SESSION['error'] = "Transaction not found.";
        header("Location: prenatal_records.php");
        exit();
    }

    $patient_id = $transaction['patient_id'];
} else {
    // Get only patient info
    $query = "SELECT CONCAT(first_name, ' ', last_name) AS fullname FROM patients WHERE patient_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        $_SESSION['error'] = "Patient not found.";
        header("Location: prenatal_records.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Prenatal Record</title>
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

        .form-control {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 8px 12px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.25);
        }

        textarea.form-control {
            min-height: 100px;
        }

        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 30px;
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
                            <h2><i class="fas fa-notes-medical me-2"></i><?= $existingRecord ? 'Update' : 'Add' ?> Prenatal Record</h2>
                            <?php if (isset($patient)): ?>
                            <p class="mb-0">
                                <i class="fas fa-user me-2"></i>Patient: <?= htmlspecialchars($patient['fullname']) ?>
                                <?php if (isset($patient['appointment_id'])): ?>
                                    <br>
                                    <i class="fas fa-calendar me-2"></i>Appointment ID: <?= htmlspecialchars($patient['appointment_id']) ?>
                                    <br>
                                    <i class="fas fa-clock me-2"></i>Scheduled: <?= date('F j, Y g:i A', strtotime($patient['scheduled_date'])) ?>
                                    <br>
                                    <i class="fas fa-tag me-2"></i>Type: <?= htmlspecialchars($patient['appointment_type']) ?>
                                <?php endif; ?>
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

                <form action="" method="POST" class="needs-validation" novalidate>
                    <!-- Visit Details -->
                    <div class="section-container">
                        <h5><i class="fas fa-calendar-check me-2"></i>Visit Details</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="datetime-local" class="form-control" id="visit_date" name="visit_date" 
                                        value="<?= $existingRecord ? date('Y-m-d\TH:i', strtotime($existingRecord['visit_date'])) : '' ?>" required>
                                    <label for="visit_date">Visit Date</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="attending_physician" name="attending_physician" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['attending_physician']) : '' ?>" required>
                                    <label for="attending_physician">Attending Physician</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Obstetric History -->
                    <div class="section-container">
                        <h5><i class="fas fa-file-medical me-2"></i>Obstetric History</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="gravida" name="gravida" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['gravida']) : '' ?>" required>
                                    <label for="gravida">Gravida</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="para" name="para" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['para']) : '' ?>" required>
                                    <label for="para">PARA</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="ob_score" name="ob_score" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['ob_score']) : '' ?>" required>
                                    <label for="ob_score">OB Score</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Menstrual and Pregnancy Details -->
                    <div class="section-container">
                        <h5><i class="fas fa-calendar-alt me-2"></i>Menstrual and Pregnancy Details</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="lmp" name="lmp" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['lmp']) : '' ?>" required>
                                    <label for="lmp">Last Menstrual Period (LMP)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="edc_by_lmp" name="edc_by_lmp" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['edc_by_lmp']) : '' ?>" required>
                                    <label for="edc_by_lmp">EDC by LMP</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="number" step="0.01" class="form-control" id="aog_by_lmp" name="aog_by_lmp" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['aog_by_lmp']) : '' ?>" required>
                                    <label for="aog_by_lmp">Age of Gestation (AOG) by LMP</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="edc_by_usg" name="edc_by_usg" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['edc_by_usg']) : '' ?>">
                                    <label for="edc_by_usg">EDC by USG</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Vital Signs -->
                    <div class="section-container">
                        <h5><i class="fas fa-heartbeat me-2"></i>Vital Signs</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="blood_pressure" name="blood_pressure" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['blood_pressure']) : '' ?>" required>
                                    <label for="blood_pressure">Blood Pressure</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="number" step="0.01" class="form-control" id="weight" name="weight" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['weight']) : '' ?>" required>
                                    <label for="weight">Weight (kg)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="number" step="0.01" class="form-control" id="temperature" name="temperature" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['temperature']) : '' ?>" required>
                                    <label for="temperature">Temperature (°C)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="respiratory_rate" name="respiratory_rate" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['respiratory_rate']) : '' ?>" required>
                                    <label for="respiratory_rate">Respiratory Rate</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="number" step="0.01" class="form-control" id="fundal_height" name="fundal_height" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['fundal_height']) : '' ?>" required>
                                    <label for="fundal_height">Fundal Height (cm)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="fetal_heart_tones" name="fetal_heart_tones" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['fetal_heart_tones']) : '' ?>" required>
                                    <label for="fetal_heart_tones">Fetal Heart Tones (bpm)</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Clinical Notes -->
                    <div class="section-container">
                        <h5><i class="fas fa-notes-medical me-2"></i>Clinical Notes</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="internal_examination" name="internal_examination" style="height: 100px"><?= $existingRecord ? htmlspecialchars($existingRecord['internal_examination']) : '' ?></textarea>
                                    <label for="internal_examination">Internal Examination</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="chief_complaint" name="chief_complaint" style="height: 100px"><?= $existingRecord ? htmlspecialchars($existingRecord['chief_complaint']) : '' ?></textarea>
                                    <label for="chief_complaint">Chief Complaint</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="history_of_present_illness" name="history_of_present_illness" style="height: 100px"><?= $existingRecord ? htmlspecialchars($existingRecord['history_of_present_illness']) : '' ?></textarea>
                                    <label for="history_of_present_illness">History of Present Illness</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="past_medical_history" name="past_medical_history" style="height: 100px"><?= $existingRecord ? htmlspecialchars($existingRecord['past_medical_history']) : '' ?></textarea>
                                    <label for="past_medical_history">Past Medical History</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="past_social_history" name="past_social_history" style="height: 100px"><?= $existingRecord ? htmlspecialchars($existingRecord['past_social_history']) : '' ?></textarea>
                                    <label for="past_social_history">Past Social History</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <textarea class="form-control" id="family_history" name="family_history" style="height: 100px"><?= $existingRecord ? htmlspecialchars($existingRecord['family_history']) : '' ?></textarea>
                                    <label for="family_history">Family History</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="section-container">
                        <h5><i class="fas fa-info-circle me-2"></i>Additional Information</h5>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="tt_dose" name="tt_dose" 
                                        value="<?= $existingRecord ? htmlspecialchars($existingRecord['tt_dose']) : '' ?>">
                                    <label for="tt_dose">TT Dose</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <textarea class="form-control" id="plan" name="plan" style="height: 100px"><?= $existingRecord ? htmlspecialchars($existingRecord['plan']) : '' ?></textarea>
                                    <label for="plan">Plan</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <textarea class="form-control" id="lab_results" name="lab_results" style="height: 100px"><?= $existingRecord ? htmlspecialchars($existingRecord['lab_results']) : '' ?></textarea>
                                    <label for="lab_results">Lab Results</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4 mb-5">
                        <button type="submit" class="btn btn-success btn-lg px-5 me-2">
                            <i class="fas fa-save me-2"></i>Save Prenatal Record
                        </button>
                        <?php if ($appointment_id): ?>
                        <button type="submit" name="complete_appointment" class="btn btn-primary btn-lg px-5 me-2">
                            <i class="fas fa-check-circle me-2"></i>Complete Appointment
                        </button>
                        <?php endif; ?>
                        <a href="javascript:history.back()" class="btn btn-secondary btn-lg px-5">
                            <i class="fas fa-arrow-left me-2"></i>Back
                        </a>
                    </div>
                </form>

                <!-- After the form, add the records table -->
                <div class="card p-4 mt-4">
                    <h5 class="section-title"><i class="fas fa-list me-2"></i>Existing Records</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Visit Date</th>
                                    <th>Attending Physician</th>
                                    <th>Gravida</th>
                                    <th>PARA</th>
                                    <th>OB Score</th>
                                    <th>LMP</th>
                                    <th>AOG by LMP</th>
                                    <th>EDC by LMP</th>
                                    <th>Blood Pressure</th>
                                    <th>Weight</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($records as $rec): ?>
                                <tr>
                                    <td><?= $rec['record_id'] ?></td>
                                    <td><?= date('F j, Y g:i A', strtotime($rec['visit_date'])) ?></td>
                                    <td><?= htmlspecialchars($rec['attending_physician']) ?></td>
                                    <td><?= htmlspecialchars($rec['gravida']) ?></td>
                                    <td><?= htmlspecialchars($rec['para']) ?></td>
                                    <td><?= htmlspecialchars($rec['ob_score']) ?></td>
                                    <td><?= htmlspecialchars($rec['lmp']) ?></td>
                                    <td><?= htmlspecialchars($rec['aog_by_lmp']) ?></td>
                                    <td><?= htmlspecialchars($rec['edc_by_lmp']) ?></td>
                                    <td><?= htmlspecialchars($rec['blood_pressure']) ?></td>
                                    <td><?= htmlspecialchars($rec['weight']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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
