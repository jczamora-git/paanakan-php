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
        
        // Check if record exists
        $checkQuery = "SELECT * FROM urinalysis WHERE case_id = :case_id AND transaction_id = :transaction_id";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->bindParam(':case_id', $case_id, PDO::PARAM_STR);
        $checkStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
        $checkStmt->execute();
        $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);

        // Get form data and sanitize
        $formData = [
            // Physical Examination
            'color' => !empty($_POST['color']) ? $_POST['color'] : null,
            'transparency' => !empty($_POST['transparency']) ? $_POST['transparency'] : null,
            'ph' => !empty($_POST['ph']) ? $_POST['ph'] : null,
            'specific_gravity' => !empty($_POST['specific_gravity']) ? $_POST['specific_gravity'] : null,
            // Chemical Examination
            'protein' => !empty($_POST['protein']) ? $_POST['protein'] : null,
            'glucose' => !empty($_POST['glucose']) ? $_POST['glucose'] : null,
            'leukocyte_esterase' => !empty($_POST['leukocyte_esterase']) ? $_POST['leukocyte_esterase'] : null,
            'nitrite' => !empty($_POST['nitrite']) ? $_POST['nitrite'] : null,
            'urobilinogen' => !empty($_POST['urobilinogen']) ? $_POST['urobilinogen'] : null,
            'blood' => !empty($_POST['blood']) ? $_POST['blood'] : null,
            'ketone' => !empty($_POST['ketone']) ? $_POST['ketone'] : null,
            'bilirubin' => !empty($_POST['bilirubin']) ? $_POST['bilirubin'] : null,
            // Microscopic Examination
            'rbc' => !empty($_POST['rbc']) ? $_POST['rbc'] : null,
            'wbc' => !empty($_POST['wbc']) ? $_POST['wbc'] : null,
            'epithelial_cells' => !empty($_POST['epithelial_cells']) ? $_POST['epithelial_cells'] : null,
            'mucus_threads' => !empty($_POST['mucus_threads']) ? $_POST['mucus_threads'] : null,
            'bacteria' => !empty($_POST['bacteria']) ? $_POST['bacteria'] : null,
            'amorphous_urates' => !empty($_POST['amorphous_urates']) ? $_POST['amorphous_urates'] : null,
            'calcium_oxalate' => !empty($_POST['calcium_oxalate']) ? $_POST['calcium_oxalate'] : null,
            'triple_phosphate' => !empty($_POST['triple_phosphate']) ? $_POST['triple_phosphate'] : null,
            // Others and Processing
            'others' => !empty($_POST['others']) ? $_POST['others'] : null,
            'remarks' => !empty($_POST['remarks']) ? $_POST['remarks'] : null,
            'medical_technologist' => !empty($_POST['medical_technologist']) ? $_POST['medical_technologist'] : null,
            'pathologist' => !empty($_POST['pathologist']) ? $_POST['pathologist'] : null,
            'report_date' => !empty($_POST['report_date']) ? $_POST['report_date'] : date('Y-m-d')
        ];

        if ($existingRecord) {
            $sql = "UPDATE urinalysis SET 
                color = :color,
                transparency = :transparency,
                ph = :ph,
                specific_gravity = :specific_gravity,
                protein = :protein,
                glucose = :glucose,
                leukocyte_esterase = :leukocyte_esterase,
                nitrite = :nitrite,
                urobilinogen = :urobilinogen,
                blood = :blood,
                ketone = :ketone,
                bilirubin = :bilirubin,
                rbc = :rbc,
                wbc = :wbc,
                epithelial_cells = :epithelial_cells,
                mucus_threads = :mucus_threads,
                bacteria = :bacteria,
                amorphous_urates = :amorphous_urates,
                calcium_oxalate = :calcium_oxalate,
                triple_phosphate = :triple_phosphate,
                others = :others,
                remarks = :remarks,
                medical_technologist = :medical_technologist,
                pathologist = :pathologist,
                report_date = :report_date
                WHERE case_id = :case_id AND transaction_id = :transaction_id";
        } else {
            $sql = "INSERT INTO urinalysis (
                case_id, transaction_id, color, transparency, ph, specific_gravity,
                protein, glucose, leukocyte_esterase, nitrite, urobilinogen, blood,
                ketone, bilirubin, rbc, wbc, epithelial_cells, mucus_threads,
                bacteria, amorphous_urates, calcium_oxalate, triple_phosphate,
                others, remarks, medical_technologist, pathologist, report_date
            ) VALUES (
                :case_id, :transaction_id, :color, :transparency, :ph, :specific_gravity,
                :protein, :glucose, :leukocyte_esterase, :nitrite, :urobilinogen, :blood,
                :ketone, :bilirubin, :rbc, :wbc, :epithelial_cells, :mucus_threads,
                :bacteria, :amorphous_urates, :calcium_oxalate, :triple_phosphate,
                :others, :remarks, :medical_technologist, :pathologist, :report_date
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
            $_SESSION['message'] = "Urinalysis report {$message} successfully!";
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
        $checkQuery = "SELECT * FROM urinalysis WHERE case_id = :case_id AND transaction_id = :transaction_id";
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
    'Urinalysis Report' => array('link' => '#', 'icon' => 'fas fa-file-medical')
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urinalysis Report</title>
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
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid #e0e0e0;
        }
        .hospital-logo {
            max-height: 80px;
            width: auto;
        }
        .report-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2E8B57;
            margin-bottom: 0.5rem;
            text-align: right;
        }
        .contact-info {
            font-size: 0.9rem;
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
                    <i class="fas fa-edit me-2"></i>Edit Mode - Updating existing Urinalysis report
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-plus me-2"></i>New Report - Creating new Urinalysis report
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
                                    <div class="report-title">URINALYSIS REPORT</div>
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

                            <!-- Physical Examination -->
                            <div class="section">
                                <h4>Physical Examination</h4>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="color">Color:</label>
                                            <input type="text" class="form-control" id="color" name="color" 
                                                value="<?= isset($existingRecord['color']) ? htmlspecialchars($existingRecord['color']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="transparency">Transparency:</label>
                                            <input type="text" class="form-control" id="transparency" name="transparency" 
                                                value="<?= isset($existingRecord['transparency']) ? htmlspecialchars($existingRecord['transparency']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="ph">pH:</label>
                                            <input type="number" step="0.1" class="form-control" id="ph" name="ph" 
                                                value="<?= isset($existingRecord['ph']) ? htmlspecialchars($existingRecord['ph']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="specific_gravity">Specific Gravity:</label>
                                            <input type="number" step="0.001" class="form-control" id="specific_gravity" name="specific_gravity" 
                                                value="<?= isset($existingRecord['specific_gravity']) ? htmlspecialchars($existingRecord['specific_gravity']) : '' ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Chemical Examination -->
                            <div class="section">
                                <h4>Chemical Examination</h4>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="protein">Protein:</label>
                                            <input type="text" class="form-control" id="protein" name="protein" 
                                                value="<?= isset($existingRecord['protein']) ? htmlspecialchars($existingRecord['protein']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="glucose">Glucose:</label>
                                            <input type="text" class="form-control" id="glucose" name="glucose" 
                                                value="<?= isset($existingRecord['glucose']) ? htmlspecialchars($existingRecord['glucose']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="leukocyte_esterase">Leukocyte Esterase:</label>
                                            <input type="text" class="form-control" id="leukocyte_esterase" name="leukocyte_esterase" 
                                                value="<?= isset($existingRecord['leukocyte_esterase']) ? htmlspecialchars($existingRecord['leukocyte_esterase']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="nitrite">Nitrite:</label>
                                            <input type="text" class="form-control" id="nitrite" name="nitrite" 
                                                value="<?= isset($existingRecord['nitrite']) ? htmlspecialchars($existingRecord['nitrite']) : '' ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="urobilinogen">Urobilinogen:</label>
                                            <input type="text" class="form-control" id="urobilinogen" name="urobilinogen" 
                                                value="<?= isset($existingRecord['urobilinogen']) ? htmlspecialchars($existingRecord['urobilinogen']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="blood">Blood:</label>
                                            <input type="text" class="form-control" id="blood" name="blood" 
                                                value="<?= isset($existingRecord['blood']) ? htmlspecialchars($existingRecord['blood']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="ketone">Ketone:</label>
                                            <input type="text" class="form-control" id="ketone" name="ketone" 
                                                value="<?= isset($existingRecord['ketone']) ? htmlspecialchars($existingRecord['ketone']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="bilirubin">Bilirubin:</label>
                                            <input type="text" class="form-control" id="bilirubin" name="bilirubin" 
                                                value="<?= isset($existingRecord['bilirubin']) ? htmlspecialchars($existingRecord['bilirubin']) : '' ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Microscopic Examination -->
                            <div class="section">
                                <h4>Microscopic Examination</h4>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="rbc">RBC:</label>
                                            <input type="text" class="form-control" id="rbc" name="rbc" 
                                                value="<?= isset($existingRecord['rbc']) ? htmlspecialchars($existingRecord['rbc']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="wbc">WBC:</label>
                                            <input type="text" class="form-control" id="wbc" name="wbc" 
                                                value="<?= isset($existingRecord['wbc']) ? htmlspecialchars($existingRecord['wbc']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="epithelial_cells">Epithelial Cells:</label>
                                            <input type="text" class="form-control" id="epithelial_cells" name="epithelial_cells" 
                                                value="<?= isset($existingRecord['epithelial_cells']) ? htmlspecialchars($existingRecord['epithelial_cells']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="mucus_threads">Mucus Threads:</label>
                                            <input type="text" class="form-control" id="mucus_threads" name="mucus_threads" 
                                                value="<?= isset($existingRecord['mucus_threads']) ? htmlspecialchars($existingRecord['mucus_threads']) : '' ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="bacteria">Bacteria:</label>
                                            <input type="text" class="form-control" id="bacteria" name="bacteria" 
                                                value="<?= isset($existingRecord['bacteria']) ? htmlspecialchars($existingRecord['bacteria']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="amorphous_urates">Amorphous Urates:</label>
                                            <input type="text" class="form-control" id="amorphous_urates" name="amorphous_urates" 
                                                value="<?= isset($existingRecord['amorphous_urates']) ? htmlspecialchars($existingRecord['amorphous_urates']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="calcium_oxalate">Calcium Oxalate:</label>
                                            <input type="text" class="form-control" id="calcium_oxalate" name="calcium_oxalate" 
                                                value="<?= isset($existingRecord['calcium_oxalate']) ? htmlspecialchars($existingRecord['calcium_oxalate']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="triple_phosphate">Triple Phosphate:</label>
                                            <input type="text" class="form-control" id="triple_phosphate" name="triple_phosphate" 
                                                value="<?= isset($existingRecord['triple_phosphate']) ? htmlspecialchars($existingRecord['triple_phosphate']) : '' ?>">
                                        </div>
                                    </div>
                                </div>
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