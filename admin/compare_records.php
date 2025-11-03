<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

require '../connections/connections.php';
$pdo = connection();

// Get patient_id from query parameters
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : null;

// Fetch patient information
if ($patient_id) {
    $query = "SELECT first_name, last_name, date_of_birth, gender, case_id 
              FROM patients WHERE patient_id = :patient_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':patient_id' => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        $_SESSION['error'] = "Patient not found.";
        header("Location: manage_health_records.php");
        exit();
    }

    // Calculate Age
    $birthdate = new DateTime($patient['date_of_birth']);
    $today = new DateTime();
    $age = $birthdate->diff($today)->y;
}

// Fetch all health records for the patient
$recordsQuery = "
    -- Appointment Records
    SELECT 
        'Appointment' AS record_type,
        a.appointment_id AS record_id,
        a.created_at AS record_date,
        a.appointment_type AS service_name,
        NULL AS end_date,
        CASE 
            WHEN a.appointment_type = 'Regular Checkup' THEN 'regular_checkup'
            WHEN a.appointment_type = 'Follow Up' THEN 'follow_up'
            WHEN a.appointment_type = 'Under Observation' THEN 'under_observation'
            WHEN a.appointment_type = 'Pre Natal Checkup' THEN 'prenatal'
            WHEN a.appointment_type = 'Post Natal Checkup' THEN 'postnatal'
            WHEN a.appointment_type = 'Medical Consultation' THEN 'medical_consultation'
            WHEN a.appointment_type = 'Vaccination' THEN 'vaccination'
        END AS record_category
    FROM appointments a
    WHERE a.patient_id = :patient_id

    UNION ALL

    -- Transaction Records
    SELECT 
        'Transaction' AS record_type,
        mt.transaction_id AS record_id,
        mt.transaction_date AS record_date,
        ms.service_name,
        NULL AS end_date,
        CASE 
            WHEN ms.service_name = 'Transvaginal Ultrasound' THEN 'tv_ultrasound'
            WHEN ms.service_name = 'OB Ultrasound' THEN 'ob_ultrasound'
            WHEN ms.service_name = 'Prenatal Checkup' THEN 'prenatal'
            WHEN ms.service_name = 'Postnatal Checkup' THEN 'postnatal'
            WHEN ms.service_name = 'Pap Smear' THEN 'pap_smear'
            WHEN ms.service_name = 'Urinalysis' THEN 'urinalysis'
            WHEN ms.service_name = 'Hemoglobin Test' THEN 'hemoglobin'
            WHEN ms.service_name = 'Circumcision' THEN 'circumcision_consent'
            WHEN ms.service_name = 'Vaccination for Newborn' THEN 'vaccination'
        END AS record_category
    FROM medical_transactions mt
    JOIN medical_services ms ON mt.service_id = ms.service_id
    JOIN patients p ON mt.case_id = p.case_id
    WHERE p.patient_id = :patient_id AND mt.service_id != 10

    UNION ALL

    -- Admission Records
    SELECT 
        'Admission' AS record_type,
        ad.admission_id AS record_id,
        ad.admission_date AS record_date,
        ad.admitting_diagnosis AS service_name,
        ad.discharge_date AS end_date,
        'admission' AS record_category
    FROM admissions ad
    WHERE ad.patient_id = :patient_id

    ORDER BY record_date DESC";

$stmt = $pdo->prepare($recordsQuery);
$stmt->execute([':patient_id' => $patient_id]);
$health_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to fetch detailed record information
function fetchDetailedRecord($pdo, $record_type, $record_id, $record_category) {
    switch ($record_type) {
        case 'Appointment':
            // Get appointment details
            $query = "SELECT a.* FROM appointments a WHERE a.appointment_id = :record_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':record_id' => $record_id]);
            $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$appointment) return null;
            
            // Get specific record details based on category
            $details = [];
            switch ($record_category) {
                case 'regular_checkup':
                    $query = "SELECT * FROM regular_checkup_records WHERE appointment_id = :record_id";
                    break;
                case 'follow_up':
                    $query = "SELECT * FROM follow_up_records WHERE appointment_id = :record_id";
                    break;
                case 'under_observation':
                    $query = "SELECT * FROM under_observation_records WHERE appointment_id = :record_id";
                    break;
                case 'prenatal':
                    $query = "SELECT * FROM prenatal_records WHERE appointment_id = :record_id";
                    break;
                case 'postnatal':
                    $query = "SELECT * FROM postnatal_records WHERE appointment_id = :record_id";
                    break;
                case 'medical_consultation':
                    $query = "SELECT * FROM medical_consultation_records WHERE appointment_id = :record_id";
                    break;
                case 'vaccination':
                    $query = "SELECT * FROM vaccination_records WHERE appointment_id = :record_id";
                    break;
            }
            
            if (isset($query)) {
                $stmt = $pdo->prepare($query);
                $stmt->execute([':record_id' => $record_id]);
                $details[$record_category] = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return [
                'appointment' => $appointment,
                'details' => $details
            ];
            
        case 'Transaction':
            // Get transaction details
            $query = "SELECT mt.*, ms.service_name, ms.description as service_description 
                     FROM medical_transactions mt 
                     JOIN medical_services ms ON mt.service_id = ms.service_id 
                     WHERE mt.transaction_id = :record_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':record_id' => $record_id]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) return null;
            
            // Get specific record details based on category
            $details = [];
            switch ($record_category) {
                case 'tv_ultrasound':
                    $query = "SELECT * FROM tv_ultrasound WHERE transaction_id = :record_id";
                    break;
                case 'ob_ultrasound':
                    $query = "SELECT * FROM ob_ultrasound WHERE transaction_id = :record_id";
                    break;
                case 'prenatal':
                    $query = "SELECT * FROM prenatal_records WHERE transaction_id = :record_id";
                    break;
                case 'postnatal':
                    $query = "SELECT * FROM postnatal_records WHERE transaction_id = :record_id";
                    break;
                case 'pap_smear':
                    $query = "SELECT * FROM pap_smear WHERE transaction_id = :record_id";
                    break;
                case 'urinalysis':
                    $query = "SELECT * FROM urinalysis WHERE transaction_id = :record_id";
                    break;
                case 'hemoglobin':
                    $query = "SELECT * FROM hemoglobin WHERE transaction_id = :record_id";
                    break;
                case 'circumcision_consent':
                    $query = "SELECT * FROM circumcision_consent WHERE transaction_id = :record_id";
                    break;
                case 'vaccination':
                    $query = "SELECT * FROM vaccination_records WHERE transaction_id = :record_id";
                    break;
            }
            
            if (isset($query)) {
                $stmt = $pdo->prepare($query);
                $stmt->execute([':record_id' => $record_id]);
                $details[$record_category] = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return [
                'transaction' => $transaction,
                'details' => $details
            ];
            
        case 'Admission':
            $query = "SELECT * FROM admissions WHERE admission_id = :record_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':record_id' => $record_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        default:
            return null;
    }
}

// Handle form submission for comparison
$record1 = null;
$record2 = null;
$record1_details = null;
$record2_details = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record1']) && isset($_POST['record2'])) {
    $record1_id = $_POST['record1'];
    $record2_id = $_POST['record2'];
    
    // Fetch basic information for both records
    foreach ($health_records as $record) {
        if ($record['record_id'] == $record1_id) {
            $record1 = $record;
            $record1_details = fetchDetailedRecord($pdo, $record['record_type'], $record['record_id'], $record['record_category']);
        }
        if ($record['record_id'] == $record2_id) {
            $record2 = $record;
            $record2_details = fetchDetailedRecord($pdo, $record['record_type'], $record['record_id'], $record['record_category']);
        }
    }
}

$breadcrumb = array(
    'Health Records' => array('link' => 'manage_health_records.php', 'icon' => 'fas fa-hospital'),
    'Compare Records' => array('link' => '#', 'icon' => 'fas fa-balance-scale')
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compare Health Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
        }
        
        /* Record Selection Form */
        .select-records-form {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .select-records-form .form-label {
            font-weight: 600;
            color: #2E8B57;
            margin-bottom: 10px;
        }
        .select-records-form .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px;
            transition: all 0.3s ease;
        }
        .select-records-form .form-select:focus {
            border-color: #2E8B57;
            box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.25);
        }
        .select-records-form .btn-primary {
            background: #2E8B57;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .select-records-form .btn-primary:hover {
            background: #3CB371;
            transform: translateY(-2px);
        }
        
        /* Comparison Cards */
        .comparison-container {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .comparison-card {
            flex: 1;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .comparison-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .comparison-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 2px solid #2E8B57;
        }
        .comparison-header h5 {
            color: #2E8B57;
            font-weight: 600;
            margin: 0;
        }
        .comparison-body {
            padding: 25px;
        }
        .record-type {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2E8B57;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        .record-detail {
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .record-detail:hover {
            background: #e9ecef;
        }
        .record-detail strong {
            color: #495057;
            display: block;
            margin-bottom: 5px;
        }
        .record-detail span {
            color: #6c757d;
        }
        
        /* Back Button */
        .back-button {
            background: #6c757d;
            color: white;
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        .back-button:hover {
            background: #5a6268;
            color: white;
            transform: translateX(-5px);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .comparison-container {
                flex-direction: column;
            }
            .select-records-form .row {
                flex-direction: column;
            }
            .select-records-form .col-md-2 {
                margin-top: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../sidebar.php'; ?>

        <main class="dashboard-main-content">
            <?php include 'breadcrumb.php'; ?>

            <!-- Patient Header -->
            <div class="patient-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-1"><?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?></h2>
                        <p class="mb-0">
                            <i class="fas fa-id-card me-2"></i>Case ID: <?= $patient['case_id'] ?>
                            <span class="mx-3">|</span>
                            <i class="fas fa-birthday-cake me-2"></i>Age: <?= $age ?> years
                            <span class="mx-3">|</span>
                            <i class="fas fa-venus-mars me-2"></i>Gender: <?= htmlspecialchars($patient['gender']) ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <img src="../psc_whitebanner.png" alt="Hospital Logo" style="max-height: 60px;">
                    </div>
                </div>
            </div>

            <!-- Select Records Form -->
            <div class="select-records-form">
                <form method="POST" class="row g-3">
                    <div class="col-md-5">
                        <label for="record1" class="form-label">Select First Record:</label>
                        <select class="form-select" id="record1" name="record1" required>
                            <option value="">Choose a record...</option>
                            <?php foreach ($health_records as $record): ?>
                                <option value="<?= $record['record_id'] ?>" <?= (isset($record1) && $record1['record_id'] == $record['record_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($record['record_type']) ?> - <?= htmlspecialchars($record['service_name']) ?> (<?= date("F d, Y", strtotime($record['record_date'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="record2" class="form-label">Select Second Record:</label>
                        <select class="form-select" id="record2" name="record2" required>
                            <option value="">Choose a record...</option>
                            <?php foreach ($health_records as $record): ?>
                                <option value="<?= $record['record_id'] ?>" <?= (isset($record2) && $record2['record_id'] == $record['record_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($record['record_type']) ?> - <?= htmlspecialchars($record['service_name']) ?> (<?= date("F d, Y", strtotime($record['record_date'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-balance-scale me-2"></i>Compare
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($record1 && $record2): ?>
            <!-- Comparison Display -->
            <div class="comparison-container">
                <div class="comparison-card">
                    <div class="comparison-header">
                        <h5><i class="fas fa-file-medical me-2"></i>Record 1</h5>
                    </div>
                    <div class="comparison-body">
                        <div class="record-type">
                            <i class="fas fa-tag me-2"></i><?= htmlspecialchars($record1['record_type']) ?>
                        </div>
                        <div class="record-detail">
                            <strong>Description</strong>
                            <span><?= htmlspecialchars($record1['service_name']) ?></span>
                        </div>
                        <div class="record-detail">
                            <strong>Date</strong>
                            <span><?= date("F j, Y g:i A", strtotime($record1['record_date'])) ?></span>
                        </div>
                        
                        <?php if ($record1_details): ?>
                            <hr class="my-4">
                            <h6 class="mb-4"><i class="fas fa-info-circle me-2"></i>Detailed Information</h6>
                            <?php
                            switch ($record1['record_type']) {
                                case 'Appointment':
                                    if (isset($record1_details['appointment'])) {
                                        echo "<div class='record-detail'>";
                                        echo "<strong>Appointment Details</strong>";
                                        foreach ($record1_details['appointment'] as $key => $value) {
                                            if ($key !== 'appointment_id') {
                                                echo "<div class='record-detail'>";
                                                echo "<strong>" . ucwords(str_replace('_', ' ', $key)) . "</strong>";
                                                echo "<span>" . ($value !== null ? htmlspecialchars($value) : 'N/A') . "</span>";
                                                echo "</div>";
                                            }
                                        }
                                        echo "</div>";
                                    }
                                    
                                    if (isset($record1_details['details'])) {
                                        foreach ($record1_details['details'] as $type => $detail) {
                                            if ($detail) {
                                                echo "<div class='record-detail mt-4'>";
                                                echo "<strong>" . ucwords(str_replace('_', ' ', $type)) . " Details</strong>";
                                                foreach ($detail as $key => $value) {
                                                    if ($key !== 'appointment_id' && $key !== 'record_id') {
                                                        echo "<div class='record-detail'>";
                                                        echo "<strong>" . ucwords(str_replace('_', ' ', $key)) . "</strong>";
                                                        echo "<span>" . ($value !== null ? htmlspecialchars($value) : 'N/A') . "</span>";
                                                        echo "</div>";
                                                    }
                                                }
                                                echo "</div>";
                                            }
                                        }
                                    }
                                    break;
                                    
                                case 'Transaction':
                                    if (isset($record1_details['transaction'])) {
                                        echo "<div class='record-detail'>";
                                        echo "<strong>Transaction Details</strong>";
                                        foreach ($record1_details['transaction'] as $key => $value) {
                                            if ($key !== 'transaction_id') {
                                                echo "<div class='record-detail'>";
                                                echo "<strong>" . ucwords(str_replace('_', ' ', $key)) . "</strong>";
                                                echo "<span>" . ($value !== null ? htmlspecialchars($value) : 'N/A') . "</span>";
                                                echo "</div>";
                                            }
                                        }
                                        echo "</div>";
                                    }
                                    
                                    if (isset($record1_details['details'])) {
                                        foreach ($record1_details['details'] as $type => $detail) {
                                            if ($detail) {
                                                echo "<div class='record-detail mt-4'>";
                                                echo "<strong>" . ucwords(str_replace('_', ' ', $type)) . " Details</strong>";
                                                foreach ($detail as $key => $value) {
                                                    if ($key !== 'transaction_id' && $key !== 'case_id') {
                                                        echo "<div class='record-detail'>";
                                                        echo "<strong>" . ucwords(str_replace('_', ' ', $key)) . "</strong>";
                                                        echo "<span>" . ($value !== null ? htmlspecialchars($value) : 'N/A') . "</span>";
                                                        echo "</div>";
                                                    }
                                                }
                                                echo "</div>";
                                            }
                                        }
                                    }
                                    break;
                                    
                                case 'Admission':
                                    if ($record1_details) {
                                        foreach ($record1_details as $key => $value) {
                                            echo "<div class='record-detail'>";
                                            echo "<strong>" . ucwords(str_replace('_', ' ', $key)) . "</strong>";
                                            echo "<span>" . ($value !== null ? htmlspecialchars($value) : 'N/A') . "</span>";
                                            echo "</div>";
                                        }
                                    }
                                    break;
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="comparison-card">
                    <div class="comparison-header">
                        <h5><i class="fas fa-file-medical me-2"></i>Record 2</h5>
                    </div>
                    <div class="comparison-body">
                        <div class="record-type">
                            <i class="fas fa-tag me-2"></i><?= htmlspecialchars($record2['record_type']) ?>
                        </div>
                        <div class="record-detail">
                            <strong>Description</strong>
                            <span><?= htmlspecialchars($record2['service_name']) ?></span>
                        </div>
                        <div class="record-detail">
                            <strong>Date</strong>
                            <span><?= date("F j, Y g:i A", strtotime($record2['record_date'])) ?></span>
                        </div>
                        
                        <?php if ($record2_details): ?>
                            <hr class="my-4">
                            <h6 class="mb-4"><i class="fas fa-info-circle me-2"></i>Detailed Information</h6>
                            <?php
                            switch ($record2['record_type']) {
                                case 'Appointment':
                                    if (isset($record2_details['appointment'])) {
                                        echo "<div class='record-detail'>";
                                        echo "<strong>Appointment Details</strong>";
                                        foreach ($record2_details['appointment'] as $key => $value) {
                                            if ($key !== 'appointment_id') {
                                                echo "<div class='record-detail'>";
                                                echo "<strong>" . ucwords(str_replace('_', ' ', $key)) . "</strong>";
                                                echo "<span>" . ($value !== null ? htmlspecialchars($value) : 'N/A') . "</span>";
                                                echo "</div>";
                                            }
                                        }
                                        echo "</div>";
                                    }
                                    
                                    if (isset($record2_details['details'])) {
                                        foreach ($record2_details['details'] as $type => $detail) {
                                            if ($detail) {
                                                echo "<div class='record-detail mt-4'>";
                                                echo "<strong>" . ucwords(str_replace('_', ' ', $type)) . " Details</strong>";
                                                foreach ($detail as $key => $value) {
                                                    if ($key !== 'appointment_id' && $key !== 'record_id') {
                                                        echo "<div class='record-detail'>";
                                                        echo "<strong>" . ucwords(str_replace('_', ' ', $key)) . "</strong>";
                                                        echo "<span>" . ($value !== null ? htmlspecialchars($value) : 'N/A') . "</span>";
                                                        echo "</div>";
                                                    }
                                                }
                                                echo "</div>";
                                            }
                                        }
                                    }
                                    break;
                                    
                                case 'Transaction':
                                    if (isset($record2_details['transaction'])) {
                                        echo "<div class='record-detail'>";
                                        echo "<strong>Transaction Details</strong>";
                                        foreach ($record2_details['transaction'] as $key => $value) {
                                            if ($key !== 'transaction_id') {
                                                echo "<div class='record-detail'>";
                                                echo "<strong>" . ucwords(str_replace('_', ' ', $key)) . "</strong>";
                                                echo "<span>" . ($value !== null ? htmlspecialchars($value) : 'N/A') . "</span>";
                                                echo "</div>";
                                            }
                                        }
                                        echo "</div>";
                                    }
                                    
                                    if (isset($record2_details['details'])) {
                                        foreach ($record2_details['details'] as $type => $detail) {
                                            if ($detail) {
                                                echo "<div class='record-detail mt-4'>";
                                                echo "<strong>" . ucwords(str_replace('_', ' ', $type)) . " Details</strong>";
                                                foreach ($detail as $key => $value) {
                                                    if ($key !== 'transaction_id' && $key !== 'case_id') {
                                                        echo "<div class='record-detail'>";
                                                        echo "<strong>" . ucwords(str_replace('_', ' ', $key)) . "</strong>";
                                                        echo "<span>" . ($value !== null ? htmlspecialchars($value) : 'N/A') . "</span>";
                                                        echo "</div>";
                                                    }
                                                }
                                                echo "</div>";
                                            }
                                        }
                                    }
                                    break;
                                    
                                case 'Admission':
                                    if ($record2_details) {
                                        foreach ($record2_details as $key => $value) {
                                            echo "<div class='record-detail'>";
                                            echo "<strong>" . ucwords(str_replace('_', ' ', $key)) . "</strong>";
                                            echo "<span>" . ($value !== null ? htmlspecialchars($value) : 'N/A') . "</span>";
                                            echo "</div>";
                                        }
                                    }
                                    break;
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="mt-4">
                <a href="patient_health_records.php?patient_id=<?= $patient_id ?>" class="back-button">
                    <i class="fas fa-arrow-left"></i>Back to Health Records
                </a>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const record1Select = document.getElementById('record1');
            const record2Select = document.getElementById('record2');
            
            function updateDropdowns() {
                const selectedRecord1 = record1Select.value;
                const selectedRecord2 = record2Select.value;
                
                // Reset all options in both dropdowns
                Array.from(record1Select.options).forEach(option => {
                    option.disabled = false;
                });
                Array.from(record2Select.options).forEach(option => {
                    option.disabled = false;
                });
                
                // Disable selected options in the other dropdown
                if (selectedRecord1) {
                    Array.from(record2Select.options).forEach(option => {
                        if (option.value === selectedRecord1) {
                            option.disabled = true;
                        }
                    });
                }
                
                if (selectedRecord2) {
                    Array.from(record1Select.options).forEach(option => {
                        if (option.value === selectedRecord2) {
                            option.disabled = true;
                        }
                    });
                }
            }
            
            // Add event listeners to both dropdowns
            record1Select.addEventListener('change', updateDropdowns);
            record2Select.addEventListener('change', updateDropdowns);
            
            // Initial update
            updateDropdowns();
        });
    </script>
</body>
</html> 