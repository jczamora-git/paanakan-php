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
        
        // First check if a record exists
        $checkQuery = "SELECT * FROM ob_ultrasound WHERE case_id = :case_id AND transaction_id = :transaction_id";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->bindParam(':case_id', $case_id, PDO::PARAM_STR);
        $checkStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
        $checkStmt->execute();
        $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug information
        error_log("Existing record found: " . ($existingRecord ? 'Yes' : 'No'));

        // Get all form data and sanitize
        $formData = [
            'ob_score' => !empty($_POST['ob_score']) ? $_POST['ob_score'] : null,
            'lmp' => !empty($_POST['lmp']) ? $_POST['lmp'] : null,
            'aog' => !empty($_POST['aog']) ? $_POST['aog'] : null,
            'edg' => !empty($_POST['edg']) ? $_POST['edg'] : null,
            'fetus_count' => !empty($_POST['fetus_count']) ? $_POST['fetus_count'] : 1,
            'fetal_presentation' => !empty($_POST['fetal_presentation']) ? $_POST['fetal_presentation'] : 'unknown',
            'fetal_heart_rate' => !empty($_POST['fetal_heart_rate']) ? $_POST['fetal_heart_rate'] : null,
            'amniotic_fluid_index' => !empty($_POST['amniotic_fluid_index']) ? $_POST['amniotic_fluid_index'] : null,
            'bpd' => !empty($_POST['bpd']) ? $_POST['bpd'] : null,
            'hc' => !empty($_POST['hc']) ? $_POST['hc'] : null,
            'fl' => !empty($_POST['fl']) ? $_POST['fl'] : null,
            'ac' => !empty($_POST['ac']) ? $_POST['ac'] : null,
            'usg_gestational_age' => !empty($_POST['usg_gestational_age']) ? $_POST['usg_gestational_age'] : null,
            'estimated_fetal_weight' => !empty($_POST['estimated_fetal_weight']) ? $_POST['estimated_fetal_weight'] : null,
            'placenta_position' => !empty($_POST['placenta_position']) ? $_POST['placenta_position'] : null,
            'cord_coil' => !empty($_POST['cord_coil']) ? $_POST['cord_coil'] : null,
            'diagnosis' => !empty($_POST['diagnosis']) ? $_POST['diagnosis'] : null,
            'notes' => !empty($_POST['notes']) ? $_POST['notes'] : null,
            'report_date' => !empty($_POST['report_date']) ? $_POST['report_date'] : date('Y-m-d')
        ];

        if ($existingRecord) {
            // Update existing record
            $sql = "UPDATE ob_ultrasound SET 
                ob_score = :ob_score,
                lmp = :lmp,
                aog = :aog,
                edg = :edg,
                fetus_count = :fetus_count,
                fetal_presentation = :fetal_presentation,
                fetal_heart_rate = :fetal_heart_rate,
                amniotic_fluid_index = :amniotic_fluid_index,
                bpd = :bpd,
                hc = :hc,
                fl = :fl,
                ac = :ac,
                usg_gestational_age = :usg_gestational_age,
                estimated_fetal_weight = :estimated_fetal_weight,
                placenta_position = :placenta_position,
                cord_coil = :cord_coil,
                diagnosis = :diagnosis,
                notes = :notes,
                report_date = :report_date
                WHERE case_id = :case_id AND transaction_id = :transaction_id";
            
            error_log("Executing UPDATE query");
        } else {
            // Insert new record
            $sql = "INSERT INTO ob_ultrasound (
                case_id, transaction_id, ob_score, lmp, aog, edg, fetus_count,
                fetal_presentation, fetal_heart_rate, amniotic_fluid_index,
                bpd, hc, fl, ac, usg_gestational_age, estimated_fetal_weight,
                placenta_position, cord_coil, diagnosis, notes, report_date
            ) VALUES (
                :case_id, :transaction_id, :ob_score, :lmp, :aog, :edg, :fetus_count,
                :fetal_presentation, :fetal_heart_rate, :amniotic_fluid_index,
                :bpd, :hc, :fl, :ac, :usg_gestational_age, :estimated_fetal_weight,
                :placenta_position, :cord_coil, :diagnosis, :notes, :report_date
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
        
        if ($result) {
            // Commit the transaction
            $pdo->commit();
            
            $message = $existingRecord ? 'updated' : 'saved';
            error_log("Report successfully {$message}");
            $_SESSION['message'] = "OB Ultrasound report {$message} successfully!";
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
    $existingRecord = null;

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

    // Check if ultrasound record exists
    if ($transaction_id) {
        $checkQuery = "SELECT * FROM ob_ultrasound WHERE case_id = :case_id AND transaction_id = :transaction_id";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->bindParam(':case_id', $case_id);
        $checkStmt->bindParam(':transaction_id', $transaction_id);
        $checkStmt->execute();
        $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);

        // If record exists, use its values
        if ($existingRecord) {
            $ob_score = $existingRecord['ob_score'];
            $lmp = $existingRecord['lmp'];
            $aog = $existingRecord['aog'];
            $edg = $existingRecord['edg'];
            $fetus_count = $existingRecord['fetus_count'];
            $fetal_presentation = $existingRecord['fetal_presentation'];
            $fetal_heart_rate = $existingRecord['fetal_heart_rate'];
            $amniotic_fluid_index = $existingRecord['amniotic_fluid_index'];
            $bpd = $existingRecord['bpd'];
            $hc = $existingRecord['hc'];
            $fl = $existingRecord['fl'];
            $ac = $existingRecord['ac'];
            $usg_gestational_age = $existingRecord['usg_gestational_age'];
            $estimated_fetal_weight = $existingRecord['estimated_fetal_weight'];
            $placenta_position = $existingRecord['placenta_position'];
            $cord_coil = $existingRecord['cord_coil'];
            $diagnosis = $existingRecord['diagnosis'];
            $notes = $existingRecord['notes'];
            $report_date = $existingRecord['report_date'];
        }
    }
}

// Set up breadcrumb
$breadcrumb = array(
    'Health Records' => array('link' => 'manage_health_records.php', 'icon' => 'fas fa-hospital'),
    'OB Ultrasound Report' => array('link' => '#', 'icon' => 'fas fa-file-medical')
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OB Ultrasound Report</title>
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
        .statement-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
        }
        .hospital-logo {
            max-width: 500px;
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
                    <i class="fas fa-edit me-2"></i>Edit Mode - Updating existing OB ultrasound report
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-plus me-2"></i>New Report - Creating new OB ultrasound report
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
                                    <div class="soa-title">OB ULTRASOUND REPORT</div>
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
                                            <input type="text" class="form-control" id="name" name="name" 
                                                value="<?= isset($patientInfo) ? htmlspecialchars($patientInfo['patient_name']) : '' ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="age">Age:</label>
                                            <input type="number" class="form-control" id="age" name="age" 
                                                value="<?= isset($patientInfo) ? htmlspecialchars($patientInfo['age']) : '' ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="report_date">Report Date:</label>
                                            <input type="date" class="form-control" id="report_date" name="report_date" 
                                                value="<?= isset($report_date) ? $report_date : date('Y-m-d') ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Obstetric Information -->
                            <div class="section">
                                <h4>Obstetric Information</h4>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="ob_score">OB Score (G_P_):</label>
                                            <input type="text" class="form-control" id="ob_score" name="ob_score" 
                                                value="<?= isset($ob_score) ? htmlspecialchars($ob_score) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="lmp">LMP:</label>
                                            <input type="date" class="form-control" id="lmp" name="lmp" 
                                                value="<?= isset($lmp) ? $lmp : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="aog">AOG (weeks+days):</label>
                                            <input type="text" class="form-control" id="aog" name="aog" 
                                                value="<?= isset($aog) ? htmlspecialchars($aog) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="edg">EDG:</label>
                                            <input type="date" class="form-control" id="edg" name="edg" 
                                                value="<?= isset($edg) ? $edg : '' ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fetal Assessment -->
                            <div class="section">
                                <h4>Fetal Assessment</h4>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="fetus_count">Number of Fetuses:</label>
                                            <input type="number" class="form-control" id="fetus_count" name="fetus_count" 
                                                value="<?= isset($fetus_count) ? htmlspecialchars($fetus_count) : '1' ?>" min="1">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="fetal_presentation">Fetal Presentation:</label>
                                            <select class="form-control" id="fetal_presentation" name="fetal_presentation">
                                                <option value="cephalic" <?= isset($fetal_presentation) && $fetal_presentation == 'cephalic' ? 'selected' : '' ?>>Cephalic</option>
                                                <option value="breech" <?= isset($fetal_presentation) && $fetal_presentation == 'breech' ? 'selected' : '' ?>>Breech</option>
                                                <option value="transverse" <?= isset($fetal_presentation) && $fetal_presentation == 'transverse' ? 'selected' : '' ?>>Transverse</option>
                                                <option value="unknown" <?= isset($fetal_presentation) && $fetal_presentation == 'unknown' ? 'selected' : '' ?>>Unknown</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="fetal_heart_rate">Fetal Heart Rate (bpm):</label>
                                            <input type="text" class="form-control" id="fetal_heart_rate" name="fetal_heart_rate" 
                                                value="<?= isset($fetal_heart_rate) ? htmlspecialchars($fetal_heart_rate) : '' ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Measurements -->
                            <div class="section">
                                <h4>Fetal Measurements</h4>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="bpd">BPD (cm):</label>
                                            <input type="number" step="0.01" class="form-control" id="bpd" name="bpd" 
                                                value="<?= isset($bpd) ? htmlspecialchars($bpd) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="hc">HC (cm):</label>
                                            <input type="number" step="0.01" class="form-control" id="hc" name="hc" 
                                                value="<?= isset($hc) ? htmlspecialchars($hc) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="ac">AC (cm):</label>
                                            <input type="number" step="0.01" class="form-control" id="ac" name="ac" 
                                                value="<?= isset($ac) ? htmlspecialchars($ac) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="fl">FL (cm):</label>
                                            <input type="number" step="0.01" class="form-control" id="fl" name="fl" 
                                                value="<?= isset($fl) ? htmlspecialchars($fl) : '' ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="usg_gestational_age">USG Gestational Age:</label>
                                            <input type="text" class="form-control" id="usg_gestational_age" name="usg_gestational_age" 
                                                value="<?= isset($usg_gestational_age) ? htmlspecialchars($usg_gestational_age) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="estimated_fetal_weight">Estimated Fetal Weight (g):</label>
                                            <input type="number" step="0.01" class="form-control" id="estimated_fetal_weight" name="estimated_fetal_weight" 
                                                value="<?= isset($estimated_fetal_weight) ? htmlspecialchars($estimated_fetal_weight) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="amniotic_fluid_index">Amniotic Fluid Index:</label>
                                            <input type="text" class="form-control" id="amniotic_fluid_index" name="amniotic_fluid_index" 
                                                value="<?= isset($amniotic_fluid_index) ? htmlspecialchars($amniotic_fluid_index) : '' ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Placenta and Cord -->
                            <div class="section">
                                <h4>Placenta and Cord</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="placenta_position">Placenta Position:</label>
                                            <input type="text" class="form-control" id="placenta_position" name="placenta_position" 
                                                value="<?= isset($placenta_position) ? htmlspecialchars($placenta_position) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="cord_coil">Cord Coil:</label>
                                            <input type="text" class="form-control" id="cord_coil" name="cord_coil" 
                                                value="<?= isset($cord_coil) ? htmlspecialchars($cord_coil) : '' ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Diagnosis and Notes -->
                            <div class="section">
                                <h4>Diagnosis and Notes</h4>
                                <div class="form-group mb-3">
                                    <label for="diagnosis">Diagnosis:</label>
                                    <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3"><?= isset($diagnosis) ? htmlspecialchars($diagnosis) : '' ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="notes">Additional Notes:</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"><?= isset($notes) ? htmlspecialchars($notes) : '' ?></textarea>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="fas <?= isset($existingRecord) && $existingRecord ? 'fa-save' : 'fa-save' ?> me-2"></i>
                                    <?= isset($existingRecord) && $existingRecord ? 'Update Report' : 'Save Report' ?>
                                </button>
                                <a href="javascript:history.back()" class="btn btn-secondary btn-lg px-5 ms-2">
                                    <i class="fas fa-arrow-left me-2"></i>Back
                                </a>
                            </div>

                            <div class="disclaimer mt-4">
                                <p class="text-center text-muted mb-0">
                                    THIS REPORT IS UNOFFICIAL AND IS BASED ENTIRELY ON ULTRASOUND FINDINGS<br>
                                    AND SHOULD BE CORRELATED CLINICALLY.
                                </p>
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