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
        $checkQuery = "SELECT * FROM pap_smear WHERE case_id = :case_id AND transaction_id = :transaction_id";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->bindParam(':case_id', $case_id, PDO::PARAM_STR);
        $checkStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
        $checkStmt->execute();
        $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug information
        error_log("Existing record found: " . ($existingRecord ? 'Yes' : 'No'));

        // Get all form data and sanitize
        $formData = [
            'specimen_type' => !empty($_POST['specimen_type']) ? $_POST['specimen_type'] : null,
            'interpretation_result' => !empty($_POST['interpretation_result']) ? $_POST['interpretation_result'] : null,
            'specimen_adequacy' => !empty($_POST['specimen_adequacy']) ? $_POST['specimen_adequacy'] : null,
            'remarks' => !empty($_POST['remarks']) ? $_POST['remarks'] : null,
            'processed_by' => !empty($_POST['processed_by']) ? $_POST['processed_by'] : null,
            'pathologist' => !empty($_POST['pathologist']) ? $_POST['pathologist'] : null,
            'report_date' => !empty($_POST['report_date']) ? $_POST['report_date'] : date('Y-m-d')
        ];

        if ($existingRecord) {
            // Update existing record
            $sql = "UPDATE pap_smear SET 
                specimen_type = :specimen_type,
                interpretation_result = :interpretation_result,
                specimen_adequacy = :specimen_adequacy,
                remarks = :remarks,
                processed_by = :processed_by,
                pathologist = :pathologist,
                report_date = :report_date
                WHERE case_id = :case_id AND transaction_id = :transaction_id";
        } else {
            // Insert new record
            $sql = "INSERT INTO pap_smear (
                case_id, transaction_id, specimen_type, interpretation_result,
                specimen_adequacy, remarks, processed_by, pathologist, report_date
            ) VALUES (
                :case_id, :transaction_id, :specimen_type, :interpretation_result,
                :specimen_adequacy, :remarks, :processed_by, :pathologist, :report_date
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

        // Execute the query
        $result = $stmt->execute();
        
        if ($result) {
            $pdo->commit();
            $message = $existingRecord ? 'updated' : 'saved';
            $_SESSION['message'] = "Pap smear report {$message} successfully!";
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

    // Query to get patient information
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

    // Check if pap smear record exists
    if ($transaction_id) {
        $checkQuery = "SELECT * FROM pap_smear WHERE case_id = :case_id AND transaction_id = :transaction_id";
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

// Set up breadcrumb
$breadcrumb = array(
    'Health Records' => array('link' => 'manage_health_records.php', 'icon' => 'fas fa-hospital'),
    'Pap Smear Report' => array('link' => '#', 'icon' => 'fas fa-file-medical')
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pap Smear Report</title>
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
                    <i class="fas fa-edit me-2"></i>Edit Mode - Updating existing Pap smear report
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-plus me-2"></i>New Report - Creating new Pap smear report
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
                                    <div class="soa-title">PATHOLOGY CONSULTATION RESULT</div>
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

                            <!-- Specimen Information -->
                            <div class="section">
                                <h4>Specimen Information</h4>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="specimen_type">Specimen:</label>
                                            <input type="text" class="form-control" id="specimen_type" name="specimen_type" 
                                                value="<?= isset($existingRecord['specimen_type']) ? htmlspecialchars($existingRecord['specimen_type']) : 'Liquid base' ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Interpretation/Result -->
                            <div class="section">
                                <h4>Interpretation/Result</h4>
                                <div class="form-group">
                                    <textarea class="form-control" id="interpretation_result" name="interpretation_result" rows="4"><?= isset($existingRecord['interpretation_result']) ? htmlspecialchars($existingRecord['interpretation_result']) : '' ?></textarea>
                                </div>
                            </div>

                            <!-- Specimen Adequacy -->
                            <div class="section">
                                <h4>Specimen Adequacy</h4>
                                <div class="form-group">
                                    <textarea class="form-control" id="specimen_adequacy" name="specimen_adequacy" rows="3"><?= isset($existingRecord['specimen_adequacy']) ? htmlspecialchars($existingRecord['specimen_adequacy']) : '' ?></textarea>
                                </div>
                            </div>

                            <!-- Remarks -->
                            <div class="section">
                                <h4>Remarks</h4>
                                <div class="form-group">
                                    <textarea class="form-control" id="remarks" name="remarks" rows="3"><?= isset($existingRecord['remarks']) ? htmlspecialchars($existingRecord['remarks']) : '' ?></textarea>
                                </div>
                            </div>

                            <!-- Processing Information -->
                            <div class="section">
                                <h4>Processing Information</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="processed_by">Processed By:</label>
                                            <select class="form-control" id="processed_by" name="processed_by">
                                                <option value="">Select Staff</option>
                                                <?php foreach ($all_staff as $staff): 
                                                    $fullName = $staff['first_name'] . ' ' . $staff['last_name'];
                                                    $selected = (isset($existingRecord['processed_by']) && $existingRecord['processed_by'] === $fullName) ? 'selected' : '';
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
                                                <option value="">Select Staff</option>
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