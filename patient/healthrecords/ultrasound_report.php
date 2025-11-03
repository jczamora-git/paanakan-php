<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

require '../connections/connections.php';
$pdo = connection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
    // Get form data
        $case_id = $_GET['case_id'];
        $transaction_id = $_GET['transaction_id'];
        
        // Debug information
        error_log("Processing form submission for case_id: $case_id, transaction_id: $transaction_id");
        error_log("POST data: " . print_r($_POST, true));
        
        // First check if a record exists with this transaction_id and case_id
        $checkQuery = "SELECT * FROM tv_ultrasound WHERE case_id = :case_id AND transaction_id = :transaction_id";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->bindParam(':case_id', $case_id, PDO::PARAM_STR);
        $checkStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
        $checkStmt->execute();
        $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug information
        error_log("Existing record found: " . ($existingRecord ? 'Yes' : 'No'));

        // Get all form data and sanitize
        $formData = [
            'date' => !empty($_POST['date']) ? $_POST['date'] : null,
            'referred_by' => !empty($_POST['referred_by']) ? $_POST['referred_by'] : null,
            'lmp' => !empty($_POST['lmp']) ? $_POST['lmp'] : null,
            'g' => !empty($_POST['g']) ? $_POST['g'] : null,
            'p' => !empty($_POST['p']) ? $_POST['p'] : null,
            'uterus_measurement' => !empty($_POST['uterus_measurement']) ? $_POST['uterus_measurement'] : null,
            'uterus_position' => !empty($_POST['uterus_position']) ? $_POST['uterus_position'] : null,
            'uterus_abnormalities' => !empty($_POST['uterus_abnormalities']) ? $_POST['uterus_abnormalities'] : null,
            'endometrium_thickness' => !empty($_POST['endometrium_thickness']) ? $_POST['endometrium_thickness'] : null,
            'endometrium_type' => !empty($_POST['endometrium_type']) ? $_POST['endometrium_type'] : null,
            'menstrual_phase' => !empty($_POST['menstrual_phase']) ? $_POST['menstrual_phase'] : null,
            'endometrium_abnormalities' => !empty($_POST['endometrium_abnormalities']) ? $_POST['endometrium_abnormalities'] : null,
            'right_ovary_measurements' => !empty($_POST['right_ovary_measurements']) ? $_POST['right_ovary_measurements'] : null,
            'right_ovary_location' => !empty($_POST['right_ovary_location']) ? $_POST['right_ovary_location'] : null,
            'right_ovary_follicle' => !empty($_POST['right_ovary_follicle']) ? $_POST['right_ovary_follicle'] : null,
            'right_ovary_abnormalities' => !empty($_POST['right_ovary_abnormalities']) ? $_POST['right_ovary_abnormalities'] : null,
            'left_ovary_measurements' => !empty($_POST['left_ovary_measurements']) ? $_POST['left_ovary_measurements'] : null,
            'left_ovary_location' => !empty($_POST['left_ovary_location']) ? $_POST['left_ovary_location'] : null,
            'left_ovary_follicle' => !empty($_POST['left_ovary_follicle']) ? $_POST['left_ovary_follicle'] : null,
            'left_ovary_abnormalities' => !empty($_POST['left_ovary_abnormalities']) ? $_POST['left_ovary_abnormalities'] : null,
            'cervix_measurements' => !empty($_POST['cervix_measurements']) ? $_POST['cervix_measurements'] : null,
            'nabothian_cyst' => !empty($_POST['nabothian_cyst']) ? $_POST['nabothian_cyst'] : null,
            'others' => !empty($_POST['others']) ? $_POST['others'] : null,
            'diagnosis' => !empty($_POST['diagnosis']) ? $_POST['diagnosis'] : null
        ];

        // Debug form data
        error_log("Processed form data: " . print_r($formData, true));

        if ($existingRecord) {
            // Update existing record
            $sql = "UPDATE tv_ultrasound SET 
                date = :date,
                referred_by = :referred_by,
                lmp = :lmp,
                g = :g,
                p = :p,
                uterus_measurement = :uterus_measurement,
                uterus_position = :uterus_position,
                uterus_abnormalities = :uterus_abnormalities,
                endometrium_thickness = :endometrium_thickness,
                endometrium_type = :endometrium_type,
                menstrual_phase = :menstrual_phase,
                endometrium_abnormalities = :endometrium_abnormalities,
                right_ovary_measurements = :right_ovary_measurements,
                right_ovary_location = :right_ovary_location,
                right_ovary_follicle = :right_ovary_follicle,
                right_ovary_abnormalities = :right_ovary_abnormalities,
                left_ovary_measurements = :left_ovary_measurements,
                left_ovary_location = :left_ovary_location,
                left_ovary_follicle = :left_ovary_follicle,
                left_ovary_abnormalities = :left_ovary_abnormalities,
                cervix_measurements = :cervix_measurements,
                nabothian_cyst = :nabothian_cyst,
                others = :others,
                diagnosis = :diagnosis
                WHERE case_id = :case_id AND transaction_id = :transaction_id";
            
            error_log("Executing UPDATE query");
        } else {
            // Insert new record
            $sql = "INSERT INTO tv_ultrasound (
                case_id, transaction_id, date, referred_by, lmp, g, p,
        uterus_measurement, uterus_position, uterus_abnormalities,
        endometrium_thickness, endometrium_type, menstrual_phase, endometrium_abnormalities,
        right_ovary_measurements, right_ovary_location, right_ovary_follicle, right_ovary_abnormalities,
        left_ovary_measurements, left_ovary_location, left_ovary_follicle, left_ovary_abnormalities,
        cervix_measurements, nabothian_cyst, others, diagnosis
    ) VALUES (
                :case_id, :transaction_id, :date, :referred_by, :lmp, :g, :p,
                :uterus_measurement, :uterus_position, :uterus_abnormalities,
                :endometrium_thickness, :endometrium_type, :menstrual_phase, :endometrium_abnormalities,
                :right_ovary_measurements, :right_ovary_location, :right_ovary_follicle, :right_ovary_abnormalities,
                :left_ovary_measurements, :left_ovary_location, :left_ovary_follicle, :left_ovary_abnormalities,
                :cervix_measurements, :nabothian_cyst, :others, :diagnosis
            )";
            
            error_log("Executing INSERT query");
        }

        $stmt = $pdo->prepare($sql);
        
        // Debug SQL query
        error_log("Prepared SQL: " . $sql);
        
        // Bind case_id and transaction_id first
        $stmt->bindValue(':case_id', $case_id, PDO::PARAM_STR);
        $stmt->bindValue(':transaction_id', $transaction_id, PDO::PARAM_INT);
        
        // Bind all form data with proper types
        foreach ($formData as $key => $value) {
            if ($value === null) {
                $stmt->bindValue(":$key", null, PDO::PARAM_NULL);
            } else {
                $paramType = is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue(":$key", $value, $paramType);
            }
            error_log("Binding $key with value: " . ($value === null ? 'NULL' : $value));
        }

        // Execute the query
        $result = $stmt->execute();
        
        // Debug execution result
        error_log("Query execution result: " . ($result ? 'Success' : 'Failed'));
        if (!$result) {
            error_log("Error info: " . print_r($stmt->errorInfo(), true));
        }
        
        if ($result) {
            // Commit the transaction
            $pdo->commit();
            
            $message = $existingRecord ? 'updated' : 'saved';
            error_log("Report successfully {$message}");
            $_SESSION['message'] = "Ultrasound report {$message} successfully!";
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
    } else {
            // Rollback the transaction
            $pdo->rollBack();
            
            $error = $stmt->errorInfo();
            error_log("Error executing query: " . print_r($error, true));
            $_SESSION['error'] = "Error saving report: " . $error[2];
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }
    } catch (PDOException $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        
        error_log("PDO Exception: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}

// Fetch patient information if case_id is provided
if (isset($_GET['case_id'])) {
    $case_id = $_GET['case_id'];
    $transaction_id = isset($_GET['transaction_id']) ? $_GET['transaction_id'] : null;
    $existingRecord = null; // Initialize as null

    // Query to get patient information
    $patientQuery = "SELECT 
                        CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                        TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as age,
                        t.transaction_date
                    FROM patients p
                    JOIN medical_transactions t ON p.case_id = t.case_id
                    WHERE p.case_id = :case_id";
    
    if ($transaction_id) {
        $patientQuery .= " AND t.transaction_id = :transaction_id";
    }
    
    $patientQuery .= " LIMIT 1";
    
    $stmt = $pdo->prepare($patientQuery);
    $stmt->bindParam(':case_id', $case_id);
    if ($transaction_id) {
        $stmt->bindParam(':transaction_id', $transaction_id);
    }
    $stmt->execute();
    $patientInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if ultrasound record exists only if we have both case_id and transaction_id
    if ($transaction_id) {
        $checkQuery = "SELECT * FROM tv_ultrasound WHERE case_id = :case_id AND transaction_id = :transaction_id";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->bindParam(':case_id', $case_id);
        $checkStmt->bindParam(':transaction_id', $transaction_id);
        $checkStmt->execute();
        $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);

        // If record exists, use its values for the form
        if ($existingRecord) {
            $patientInfo['transaction_date'] = $existingRecord['date'];
            $referred_by = $existingRecord['referred_by'];
            $lmp = $existingRecord['lmp'];
            $g = $existingRecord['g'];
            $p = $existingRecord['p'];
            $uterus_measurement = $existingRecord['uterus_measurement'];
            $uterus_position = $existingRecord['uterus_position'];
            $uterus_abnormalities = $existingRecord['uterus_abnormalities'];
            $endometrium_thickness = $existingRecord['endometrium_thickness'];
            $endometrium_type = $existingRecord['endometrium_type'];
            $menstrual_phase = $existingRecord['menstrual_phase'];
            $endometrium_abnormalities = $existingRecord['endometrium_abnormalities'];
            $right_ovary_measurements = $existingRecord['right_ovary_measurements'];
            $right_ovary_location = $existingRecord['right_ovary_location'];
            $right_ovary_follicle = $existingRecord['right_ovary_follicle'];
            $right_ovary_abnormalities = $existingRecord['right_ovary_abnormalities'];
            $left_ovary_measurements = $existingRecord['left_ovary_measurements'];
            $left_ovary_location = $existingRecord['left_ovary_location'];
            $left_ovary_follicle = $existingRecord['left_ovary_follicle'];
            $left_ovary_abnormalities = $existingRecord['left_ovary_abnormalities'];
            $cervix_measurements = $existingRecord['cervix_measurements'];
            $nabothian_cyst = $existingRecord['nabothian_cyst'];
            $others = $existingRecord['others'];
            $diagnosis = $existingRecord['diagnosis'];
        }
    }
}

// Set up breadcrumb
$breadcrumb = array(
    'Health Records' => array('link' => 'manage_health_records.php', 'icon' => 'fas fa-hospital'),
    'Ultrasound Report' => array('link' => '#', 'icon' => 'fas fa-file-medical')
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ultrasound Report - Gynecology</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        .form-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .section {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .section h4 {
            color: #2E8B57;
            margin-bottom: 20px;
            font-weight: 600;
            border-bottom: 2px solid #2E8B57;
            padding-bottom: 10px;
        }
        .form-group label {
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .form-control:focus {
            border-color: #2E8B57;
            box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.25);
        }
        .btn-primary {
            background-color: #2E8B57;
            border-color: #2E8B57;
        }
        .btn-primary:hover {
            background-color: #1b5e3d;
            border-color: #1b5e3d;
        }
        .header-logo {
            max-width: 150px;
            height: auto;
        }
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            border-bottom: 2px solid #eee;
        }
        .report-header h2 {
            color: #2E8B57;
            margin: 15px 0;
        }
        .report-header h3 {
            color: #666;
        }
        .form-section {
            margin-bottom: 30px;
        }
        .form-section-title {
            color: #2E8B57;
            font-size: 1.2rem;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .disclaimer {
            margin-top: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 0.9rem;
            color: #666;
            text-align: center;
        }
        /* Billing Header Styles */
        .statement-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
        }
        .hospital-logo {
            max-width: 500px;
        }
        .soa-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .hospital-details {
            font-size: 14px;
            color: #666;
        }
        .patient-info {
            margin: 20px 0;
            font-size: 14px;
        }
        .patient-info .row {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include('sidebar.php'); ?>


        <main class="dashboard-main-content">
            <?php include('../admin/breadcrumb.php'); ?>
            <?php if (isset($existingRecord) && $existingRecord): ?>
                <div class="alert alert-info">
                    <i class="fas fa-edit me-2"></i>Edit Mode - Updating existing transvaginal ultrasound report
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-plus me-2"></i>New Report - Creating new transvaginal ultrasound report
                </div>
            <?php endif; ?>
            <div class="container">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="statement-header">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <img src="../psc_greenbanner.png" alt="Hospital Logo" class="hospital-logo">
                                </div>
                                <div class="col-md-6 text-end">
                                    <div class="soa-title">Transvaginal Ultrasound Report</div>
                                    <div class="hospital-details">
                                        Contact No.: 043-738-1874
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="">
                            <!-- Patient Information -->
                            <div class="section">
                                <h4>Patient Information</h4>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="name">Name:</label>
                                            <input type="text" class="form-control" id="name" name="name" value="<?= isset($patientInfo) ? htmlspecialchars($patientInfo['patient_name']) : '' ?>" required readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="age">Age:</label>
                                            <input type="number" class="form-control" id="age" name="age" value="<?= isset($patientInfo) ? htmlspecialchars($patientInfo['age']) : '' ?>" required readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="date">Date:</label>
                                            <input type="date" class="form-control" id="date" name="date" value="<?= isset($patientInfo) ? (new DateTime($patientInfo['transaction_date']))->format('Y-m-d') : '' ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="referred_by">Referred By:</label>
                                            <input type="text" class="form-control" id="referred_by" name="referred_by" value="<?= isset($referred_by) ? htmlspecialchars($referred_by) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="lmp">LMP:</label>
                                            <input type="date" class="form-control" id="lmp" name="lmp" value="<?= isset($lmp) && $lmp ? htmlspecialchars(date('Y-m-d', strtotime($lmp))) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>G P:</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="g" placeholder="G" value="<?= isset($g) ? htmlspecialchars($g) : '' ?>">
                                                <input type="text" class="form-control" name="p" placeholder="P" value="<?= isset($p) ? htmlspecialchars($p) : '' ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Uterus Section -->
                            <div class="section">
                                <h4>I. UTERUS</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Measurements (cms):</label>
                                            <input type="text" class="form-control" name="uterus_measurement" value="<?= isset($uterus_measurement) ? htmlspecialchars($uterus_measurement) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Position:</label>
                                            <select class="form-control" name="uterus_position">
                                                <option value="Anteverted" <?= isset($uterus_position) && $uterus_position == 'Anteverted' ? 'selected' : '' ?>>Anteverted</option>
                                                <option value="Retroverted" <?= isset($uterus_position) && $uterus_position == 'Retroverted' ? 'selected' : '' ?>>Retroverted</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group mt-3">
                                    <label>Abnormalities Noted:</label>
                                    <textarea class="form-control" name="uterus_abnormalities" rows="2"><?= isset($uterus_abnormalities) ? htmlspecialchars($uterus_abnormalities) : '' ?></textarea>
                                </div>
                            </div>

                            <!-- Endometrium Section -->
                            <div class="section">
                                <h4>II. ENDOMETRIUM</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Thickness:</label>
                                            <select class="form-control" name="endometrium_thickness">
                                                <option value="Thin" <?= isset($endometrium_thickness) && $endometrium_thickness == 'Thin' ? 'selected' : '' ?>>Thin</option>
                                                <option value="Thick" <?= isset($endometrium_thickness) && $endometrium_thickness == 'Thick' ? 'selected' : '' ?>>Thick</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Type:</label>
                                            <select class="form-control" name="endometrium_type">
                                                <option value="hypo" <?= isset($endometrium_type) && $endometrium_type == 'hypo' ? 'selected' : '' ?>>Hypo</option>
                                                <option value="iso" <?= isset($endometrium_type) && $endometrium_type == 'iso' ? 'selected' : '' ?>>Iso</option>
                                                <option value="hyperechoic" <?= isset($endometrium_type) && $endometrium_type == 'hyperechoic' ? 'selected' : '' ?>>Hyperechoic</option>
                                                <option value="triple line" <?= isset($endometrium_type) && $endometrium_type == 'triple line' ? 'selected' : '' ?>>Triple Line</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group mt-3">
                                    <label>Phase of Menstrual Cycle:</label>
                                    <input type="text" class="form-control" name="menstrual_phase" value="<?= isset($menstrual_phase) ? htmlspecialchars($menstrual_phase) : '' ?>">
                                </div>
                                <div class="form-group mt-3">
                                    <label>Abnormalities Noted:</label>
                                    <textarea class="form-control" name="endometrium_abnormalities" rows="2"><?= isset($endometrium_abnormalities) ? htmlspecialchars($endometrium_abnormalities) : '' ?></textarea>
                                </div>
                            </div>

                            <!-- Adnexae Section -->
                            <div class="section">
                                <h4>III. Adnexae</h4>
                                <!-- Right Ovary -->
                                <div class="form-section">
                                    <div class="form-section-title">Right Ovary</div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Measurements (cm):</label>
                                                <input type="text" class="form-control" name="right_ovary_measurements" value="<?= isset($right_ovary_measurements) ? htmlspecialchars($right_ovary_measurements) : '' ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Location:</label>
                                                <select class="form-control" name="right_ovary_location">
                                                    <option value="Lateral" <?= isset($right_ovary_location) && $right_ovary_location == 'Lateral' ? 'selected' : '' ?>>Lateral</option>
                                                    <option value="Posterolateral" <?= isset($right_ovary_location) && $right_ovary_location == 'Posterolateral' ? 'selected' : '' ?>>Posterolateral</option>
                                                    <option value="Posterior" <?= isset($right_ovary_location) && $right_ovary_location == 'Posterior' ? 'selected' : '' ?>>Posterior</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Dominant Follicle (cm):</label>
                                                <input type="text" class="form-control" name="right_ovary_follicle" value="<?= isset($right_ovary_follicle) ? htmlspecialchars($right_ovary_follicle) : '' ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group mt-3">
                                        <label>Abnormalities Noted:</label>
                                        <textarea class="form-control" name="right_ovary_abnormalities" rows="2"><?= isset($right_ovary_abnormalities) ? htmlspecialchars($right_ovary_abnormalities) : '' ?></textarea>
                                    </div>
                                </div>

                                <!-- Left Ovary -->
                                <div class="form-section">
                                    <div class="form-section-title">Left Ovary</div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Measurements (cm):</label>
                                                <input type="text" class="form-control" name="left_ovary_measurements" value="<?= isset($left_ovary_measurements) ? htmlspecialchars($left_ovary_measurements) : '' ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Location:</label>
                                                <select class="form-control" name="left_ovary_location">
                                                    <option value="Lateral" <?= isset($left_ovary_location) && $left_ovary_location == 'Lateral' ? 'selected' : '' ?>>Lateral</option>
                                                    <option value="Posterolateral" <?= isset($left_ovary_location) && $left_ovary_location == 'Posterolateral' ? 'selected' : '' ?>>Posterolateral</option>
                                                    <option value="Posterior" <?= isset($left_ovary_location) && $left_ovary_location == 'Posterior' ? 'selected' : '' ?>>Posterior</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Dominant Follicle (cm):</label>
                                                <input type="text" class="form-control" name="left_ovary_follicle" value="<?= isset($left_ovary_follicle) ? htmlspecialchars($left_ovary_follicle) : '' ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group mt-3">
                                        <label>Abnormalities Noted:</label>
                                        <textarea class="form-control" name="left_ovary_abnormalities" rows="2"><?= isset($left_ovary_abnormalities) ? htmlspecialchars($left_ovary_abnormalities) : '' ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Cervix Section -->
                            <div class="section">
                                <h4>IV. Cervix</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Measurements (cms):</label>
                                            <input type="text" class="form-control" name="cervix_measurements" value="<?= isset($cervix_measurements) ? htmlspecialchars($cervix_measurements) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Nabothian Cyst:</label>
                                            <select class="form-control" name="nabothian_cyst">
                                                <option value="none" <?= isset($nabothian_cyst) && $nabothian_cyst == 'none' ? 'selected' : '' ?>>None</option>
                                                <option value="present" <?= isset($nabothian_cyst) && $nabothian_cyst == 'present' ? 'selected' : '' ?>>Present</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Others Section -->
                            <div class="section">
                                <h4>V. Others</h4>
                                <div class="form-group">
                                    <textarea class="form-control" name="others" rows="3"><?= isset($others) ? htmlspecialchars($others) : '' ?></textarea>
                                </div>
                            </div>

                            <!-- Diagnosis Section -->
                            <div class="section">
                                <h4>Diagnosis</h4>
                                <div class="form-group">
                                    <textarea class="form-control" name="diagnosis" rows="3"><?= isset($diagnosis) ? htmlspecialchars($diagnosis) : '' ?></textarea>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="fas <?= isset($existingRecord) && $existingRecord ? 'fa-save' : 'fa-save' ?> me-2"></i>
                                    <?= isset($existingRecord) && $existingRecord ? 'Update' : 'Save' ?>  
                                </button>
                                <a href="javascript:history.back()" class="btn btn-secondary btn-lg px-5 ms-2">
                                    <i class="fas fa-arrow-left me-2"></i>Back
                                </a>
                            </div>

                            <div class="disclaimer">
                                <p class="mb-0">THIS REPORT IS UNOFFICIAL AND IS BASED ENTIRELY ON ULTRASOUND FINDINGS<br>AND SHOULD BE CORRELATED CLINICALLY.</p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 