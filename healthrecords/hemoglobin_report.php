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
        $pdo->beginTransaction();
        
        $case_id = $_GET['case_id'];
        $transaction_id = $_GET['transaction_id'];
        
        // Debug information
        error_log("Processing form submission for case_id: $case_id, transaction_id: $transaction_id");
        
        // Check if record exists
        $checkQuery = "SELECT * FROM hemoglobin WHERE case_id = :case_id AND transaction_id = :transaction_id";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->bindParam(':case_id', $case_id, PDO::PARAM_STR);
        $checkStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
        $checkStmt->execute();
        $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);

        // Get form data and sanitize
        $formData = [
            'hemoglobin' => !empty($_POST['hemoglobin']) ? $_POST['hemoglobin'] : null,
            'hematocrit' => !empty($_POST['hematocrit']) ? $_POST['hematocrit'] : null,
            'rbc' => !empty($_POST['rbc']) ? $_POST['rbc'] : null,
            'wbc' => !empty($_POST['wbc']) ? $_POST['wbc'] : null,
            'neutrophils' => !empty($_POST['neutrophils']) ? $_POST['neutrophils'] : null,
            'lymphocytes' => !empty($_POST['lymphocytes']) ? $_POST['lymphocytes'] : null,
            'monocytes' => !empty($_POST['monocytes']) ? $_POST['monocytes'] : null,
            'eosinophils' => !empty($_POST['eosinophils']) ? $_POST['eosinophils'] : null,
            'basophils' => !empty($_POST['basophils']) ? $_POST['basophils'] : null,
            'platelet_count' => !empty($_POST['platelet_count']) ? $_POST['platelet_count'] : null,
            'others' => !empty($_POST['others']) ? $_POST['others'] : null,
            'remarks' => !empty($_POST['remarks']) ? $_POST['remarks'] : null,
            'medical_technologist' => !empty($_POST['medical_technologist']) ? $_POST['medical_technologist'] : null,
            'pathologist' => !empty($_POST['pathologist']) ? $_POST['pathologist'] : null,
            'report_date' => !empty($_POST['report_date']) ? $_POST['report_date'] : date('Y-m-d')
        ];

        if ($existingRecord) {
            $sql = "UPDATE hemoglobin SET 
                hemoglobin = :hemoglobin,
                hematocrit = :hematocrit,
                rbc = :rbc,
                wbc = :wbc,
                neutrophils = :neutrophils,
                lymphocytes = :lymphocytes,
                monocytes = :monocytes,
                eosinophils = :eosinophils,
                basophils = :basophils,
                platelet_count = :platelet_count,
                others = :others,
                remarks = :remarks,
                medical_technologist = :medical_technologist,
                pathologist = :pathologist,
                report_date = :report_date
                WHERE case_id = :case_id AND transaction_id = :transaction_id";
        } else {
            $sql = "INSERT INTO hemoglobin (
                case_id, transaction_id, hemoglobin, hematocrit, rbc, wbc,
                neutrophils, lymphocytes, monocytes, eosinophils, basophils,
                platelet_count, others, remarks, medical_technologist, pathologist, report_date
            ) VALUES (
                :case_id, :transaction_id, :hemoglobin, :hematocrit, :rbc, :wbc,
                :neutrophils, :lymphocytes, :monocytes, :eosinophils, :basophils,
                :platelet_count, :others, :remarks, :medical_technologist, :pathologist, :report_date
            )";
        }

        $stmt = $pdo->prepare($sql);
        
        // Bind case_id and transaction_id
        $stmt->bindValue(':case_id', $case_id, PDO::PARAM_STR);
        $stmt->bindValue(':transaction_id', $transaction_id, PDO::PARAM_INT);
        
        // Bind all form data
        foreach ($formData as $key => $value) {
            $stmt->bindValue(":$key", $value, PDO::PARAM_STR);
        }

        $result = $stmt->execute();
        
        if ($result) {
            $pdo->commit();
            $message = $existingRecord ? 'updated' : 'saved';
            $_SESSION['message'] = "Hemoglobin report {$message} successfully!";
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        } else {
            $pdo->rollBack();
            $error = $stmt->errorInfo();
            $_SESSION['error'] = "Error saving report: " . $error[2];
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("PDO Exception: " . $e->getMessage());
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

    $patientQuery = "SELECT 
                        CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                        TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as age,
                        p.gender,
                        t.transaction_date
                    FROM patients p
                    JOIN medical_transactions t ON p.case_id = t.case_id
                    WHERE p.case_id = :case_id";
    
    if ($transaction_id) {
        $patientQuery .= " AND t.transaction_id = :transaction_id";
    }
    
    $stmt = $pdo->prepare($patientQuery);
    $stmt->bindParam(':case_id', $case_id);
    if ($transaction_id) {
        $stmt->bindParam(':transaction_id', $transaction_id);
    }
    $stmt->execute();
    $patientInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($transaction_id) {
        $checkQuery = "SELECT * FROM hemoglobin WHERE case_id = :case_id AND transaction_id = :transaction_id";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->bindParam(':case_id', $case_id);
        $checkStmt->bindParam(':transaction_id', $transaction_id);
        $checkStmt->execute();
        $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Fetch all active staff for dropdowns (no role filter)
$all_staff = [];
$stmt = $pdo->prepare("SELECT staff_id, first_name, last_name FROM staff WHERE status = 'Active' ORDER BY first_name, last_name");
$stmt->execute();
$all_staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

$breadcrumb = array(
    'Health Records' => array('link' => 'manage_health_records.php', 'icon' => 'fas fa-hospital'),
    'Hemoglobin Report' => array('link' => '#', 'icon' => 'fas fa-file-medical')
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hemoglobin Report</title>
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
        .statement-header {
            padding: 20px;
        }
        .hospital-logo {
            max-height: 80px;
            width: auto;
        }
        .hospital-name {
            color: #2E8B57;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .hospital-address {
            color: #2E8B57;
            font-size: 14px;
        }
        .report-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            text-align: right;
        }
        .contact-info {
            font-size: 14px;
            color: #666;
            text-align: right;
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
        .test-results td {
            padding: 8px;
            vertical-align: middle;
        }
        .test-results input {
            width: 100px;
        }
        .reference-range {
            color: #666;
            font-size: 0.9em;
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
                    <i class="fas fa-edit me-2"></i>Edit Mode - Updating existing Hemoglobin report
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-plus me-2"></i>New Report - Creating new Hemoglobin report
                </div>
            <?php endif; ?>

            <div class="container">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="statement-header">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <img src="../psc_greenbanner.png" alt="Hospital Logo" class="hospital-logo mb-2">
                                </div>
                                <div class="col-md-6">
                                    <div class="report-title">HEMOGLOBIN TEST REPORT</div>
                                    <div class="contact-info">Contact No.: 043-738-1874</div>
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
                                            <label for="name">Patient's Name:</label>
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
                                                value="<?= isset($existingRecord['report_date']) ? $existingRecord['report_date'] : date('Y-m-d') ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Test Results -->
                            <div class="section">
                                <h4>Test Results</h4>
                                <table class="table table-bordered test-results">
                                    <thead>
                                        <tr>
                                            <th>Test</th>
                                            <th>Result</th>
                                            <th>Reference Range</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Hemoglobin</td>
                                            <td><input type="number" step="0.01" class="form-control" name="hemoglobin" value="<?= isset($existingRecord['hemoglobin']) ? htmlspecialchars($existingRecord['hemoglobin']) : '' ?>"></td>
                                            <td class="reference-range">120-150 g/L</td>
                                        </tr>
                                        <tr>
                                            <td>Hematocrit</td>
                                            <td><input type="number" step="0.01" class="form-control" name="hematocrit" value="<?= isset($existingRecord['hematocrit']) ? htmlspecialchars($existingRecord['hematocrit']) : '' ?>"></td>
                                            <td class="reference-range">0.38-0.47 L/L</td>
                                        </tr>
                                        <tr>
                                            <td>RBC</td>
                                            <td><input type="number" step="0.01" class="form-control" name="rbc" value="<?= isset($existingRecord['rbc']) ? htmlspecialchars($existingRecord['rbc']) : '' ?>"></td>
                                            <td class="reference-range">4.50-5.50 x10^12/L</td>
                                        </tr>
                                        <tr>
                                            <td>WBC</td>
                                            <td><input type="number" step="0.01" class="form-control" name="wbc" value="<?= isset($existingRecord['wbc']) ? htmlspecialchars($existingRecord['wbc']) : '' ?>"></td>
                                            <td class="reference-range">4.0-11.0 x10^9/L</td>
                                        </tr>
                                        <tr>
                                            <td>Neutrophils</td>
                                            <td><input type="number" step="0.01" class="form-control" name="neutrophils" value="<?= isset($existingRecord['neutrophils']) ? htmlspecialchars($existingRecord['neutrophils']) : '' ?>"></td>
                                            <td class="reference-range">0.40-0.80</td>
                                        </tr>
                                        <tr>
                                            <td>Lymphocytes</td>
                                            <td><input type="number" step="0.01" class="form-control" name="lymphocytes" value="<?= isset($existingRecord['lymphocytes']) ? htmlspecialchars($existingRecord['lymphocytes']) : '' ?>"></td>
                                            <td class="reference-range">0.20-0.40</td>
                                        </tr>
                                        <tr>
                                            <td>Monocytes</td>
                                            <td><input type="number" step="0.01" class="form-control" name="monocytes" value="<?= isset($existingRecord['monocytes']) ? htmlspecialchars($existingRecord['monocytes']) : '' ?>"></td>
                                            <td class="reference-range">0.02-0.10</td>
                                        </tr>
                                        <tr>
                                            <td>Eosinophils</td>
                                            <td><input type="number" step="0.01" class="form-control" name="eosinophils" value="<?= isset($existingRecord['eosinophils']) ? htmlspecialchars($existingRecord['eosinophils']) : '' ?>"></td>
                                            <td class="reference-range">0.01-0.06</td>
                                        </tr>
                                        <tr>
                                            <td>Basophils</td>
                                            <td><input type="number" step="0.01" class="form-control" name="basophils" value="<?= isset($existingRecord['basophils']) ? htmlspecialchars($existingRecord['basophils']) : '' ?>"></td>
                                            <td class="reference-range">0.00-0.02</td>
                                        </tr>
                                        <tr>
                                            <td>Platelet Count</td>
                                            <td><input type="number" step="0.01" class="form-control" name="platelet_count" value="<?= isset($existingRecord['platelet_count']) ? htmlspecialchars($existingRecord['platelet_count']) : '' ?>"></td>
                                            <td class="reference-range">150-400 x10^9/L</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Others and Remarks -->
                            <div class="section">
                                <h4>Additional Information</h4>
                                <div class="form-group mb-3">
                                    <label for="others">Others:</label>
                                    <textarea class="form-control" id="others" name="others" rows="2"><?= isset($existingRecord['others']) ? htmlspecialchars($existingRecord['others']) : '' ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="remarks">Remarks:</label>
                                    <textarea class="form-control" id="remarks" name="remarks" rows="2"><?= isset($existingRecord['remarks']) ? htmlspecialchars($existingRecord['remarks']) : '' ?></textarea>
                                </div>
                            </div>

                            <!-- Processing Information -->
                            <div class="section">
                                <h4>Processing Information</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="medical_technologist">Medical Technologist:</label>
                                            <select class="form-control" id="medical_technologist" name="medical_technologist">
                                                <option value="">Select Medical Technologist</option>
                                                <?php foreach ($all_staff as $staff): 
                                                    $fullName = $staff['first_name'] . ' ' . $staff['last_name'];
                                                    $selected = (isset($existingRecord['medical_technologist']) && $existingRecord['medical_technologist'] === $fullName) ? 'selected' : '';
                                                ?>
                                                    <option value="<?= htmlspecialchars($fullName) ?>" <?= $selected ?>><?= htmlspecialchars($fullName) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="pathologist">Pathologist:</label>
                                            <select class="form-control" id="pathologist" name="pathologist">
                                                <option value="">Select Pathologist</option>
                                                <?php foreach ($all_staff as $staff): 
                                                    $fullName = $staff['first_name'] . ' ' . $staff['last_name'];
                                                    $selected = (isset($existingRecord['pathologist']) && $existingRecord['pathologist'] === $fullName) ? 'selected' : '';
                                                ?>
                                                    <option value="<?= htmlspecialchars($fullName) ?>" <?= $selected ?>><?= htmlspecialchars($fullName) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
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
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 