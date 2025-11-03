<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

require '../connections/connections.php';
$pdo = connection();

// Debug POST data
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));
error_log("GET data: " . print_r($_GET, true));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        error_log("Starting POST request handling");
        $pdo->beginTransaction();
        
        $transaction_id = $_GET['transaction_id'];
        error_log("Transaction ID: " . $transaction_id);
        
        // Get case_id from medical_transactions
        $caseQuery = "SELECT case_id FROM medical_transactions WHERE transaction_id = :transaction_id";
        $caseStmt = $pdo->prepare($caseQuery);
        $caseStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
        $caseStmt->execute();
        $caseResult = $caseStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$caseResult) {
            throw new Exception("No medical transaction found for this ID.");
        }
        
        $case_id = $caseResult['case_id'];
        error_log("Case ID fetched: " . $case_id);
        
        // Check if record exists
        $checkQuery = "SELECT * FROM circumcision_consent WHERE transaction_id = :transaction_id";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
        $checkStmt->execute();
        $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
        error_log("Existing record check: " . ($existingRecord ? "Found" : "Not found"));

        // Get form data and sanitize
        $formData = [
            'child_name' => $_POST['child_name'],
            'child_age' => $_POST['child_age'],
            'child_birthdate' => $_POST['child_birthdate'],
            'parent_name' => $_POST['parent_name'],
            'parent_relationship' => $_POST['parent_relationship'],
            'parent_contact' => $_POST['parent_contact'],
            'parent_address' => $_POST['parent_address'],
            'consent_date' => $_POST['consent_date'],
            'witness_name' => $_POST['witness_name'],
            'doctor_name' => $_POST['doctor_name'],
            'scheduled_date' => $_POST['scheduled_date'],
            'medical_conditions' => $_POST['medical_conditions'],
            'allergies' => $_POST['allergies'],
            'medications' => $_POST['medications'],
            'acknowledge_procedure' => isset($_POST['acknowledge_procedure']) ? 1 : 0,
            'acknowledge_risks' => isset($_POST['acknowledge_risks']) ? 1 : 0,
            'acknowledge_aftercare' => isset($_POST['acknowledge_aftercare']) ? 1 : 0,
            'acknowledge_questions' => isset($_POST['acknowledge_questions']) ? 1 : 0,
            'special_instructions' => $_POST['special_instructions'],
            'remarks' => $_POST['remarks'],
            'parent_signature' => $_POST['parent_signature'],
            'doctor_signature' => $_POST['doctor_signature'],
            'witness_signature' => $_POST['witness_signature']
        ];
        error_log("Form data prepared: " . print_r($formData, true));

        if ($existingRecord) {
            error_log("Preparing UPDATE query");
            $sql = "UPDATE circumcision_consent SET 
                child_name = :child_name,
                child_age = :child_age,
                child_birthdate = :child_birthdate,
                parent_name = :parent_name,
                parent_relationship = :parent_relationship,
                parent_contact = :parent_contact,
                parent_address = :parent_address,
                consent_date = :consent_date,
                witness_name = :witness_name,
                doctor_name = :doctor_name,
                scheduled_date = :scheduled_date,
                medical_conditions = :medical_conditions,
                allergies = :allergies,
                medications = :medications,
                acknowledge_procedure = :acknowledge_procedure,
                acknowledge_risks = :acknowledge_risks,
                acknowledge_aftercare = :acknowledge_aftercare,
                acknowledge_questions = :acknowledge_questions,
                special_instructions = :special_instructions,
                remarks = :remarks,
                parent_signature = :parent_signature,
                doctor_signature = :doctor_signature,
                witness_signature = :witness_signature
                WHERE transaction_id = :transaction_id";
        } else {
            error_log("Preparing INSERT query");
            $sql = "INSERT INTO circumcision_consent (
                case_id, transaction_id, child_name, child_age, child_birthdate,
                parent_name, parent_relationship, parent_contact, parent_address,
                consent_date, witness_name, doctor_name, scheduled_date,
                medical_conditions, allergies, medications,
                acknowledge_procedure, acknowledge_risks, acknowledge_aftercare, acknowledge_questions,
                special_instructions, remarks,
                parent_signature, doctor_signature, witness_signature
            ) VALUES (
                :case_id, :transaction_id, :child_name, :child_age, :child_birthdate,
                :parent_name, :parent_relationship, :parent_contact, :parent_address,
                :consent_date, :witness_name, :doctor_name, :scheduled_date,
                :medical_conditions, :allergies, :medications,
                :acknowledge_procedure, :acknowledge_risks, :acknowledge_aftercare, :acknowledge_questions,
                :special_instructions, :remarks,
                :parent_signature, :doctor_signature, :witness_signature
            )";
        }

        $stmt = $pdo->prepare($sql);
        error_log("Query prepared: " . $sql);
        
        // Bind case_id and transaction_id first
        if (!$existingRecord) {
            $stmt->bindValue(':case_id', $case_id, PDO::PARAM_STR);
        }
        $stmt->bindValue(':transaction_id', $transaction_id, PDO::PARAM_INT);
        
        // Bind all form data
        foreach ($formData as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        error_log("All parameters bound");

        $result = $stmt->execute();
        error_log("Query execution result: " . ($result ? "Success" : "Failed"));
        
        if ($result) {
            $pdo->commit();
            error_log("Transaction committed");
            $_SESSION['message'] = "Circumcision consent form " . ($existingRecord ? 'updated' : 'saved') . " successfully!";
            header("Location: " . $_SERVER['PHP_SELF'] . "?transaction_id=" . $transaction_id);
            exit();
        } else {
            $pdo->rollBack();
            error_log("Transaction rolled back");
            $_SESSION['error'] = "Error saving consent form. Please try again.";
            header("Location: " . $_SERVER['PHP_SELF'] . "?transaction_id=" . $transaction_id);
            exit();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Exception: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF'] . "?transaction_id=" . $transaction_id);
        exit();
    }
}

// Fetch patient information if case_id is provided
if (isset($_GET['transaction_id'])) {
    $transaction_id = $_GET['transaction_id'];
    $existingRecord = null;

    // First fetch patient information using transaction_id
    $patientQuery = "SELECT 
                        p.*,
                        TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as age,
                        t.transaction_date,
                        t.case_id
                    FROM patients p
                    JOIN medical_transactions t ON p.case_id = t.case_id
                    WHERE t.transaction_id = :transaction_id";
    
    $stmt = $pdo->prepare($patientQuery);
    $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
    $stmt->execute();
    $patientInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    // Then fetch circumcision consent record
    try {
        $consentQuery = "SELECT * FROM circumcision_consent WHERE transaction_id = :transaction_id";
        $consentStmt = $pdo->prepare($consentQuery);
        $consentStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
        $consentStmt->execute();
        $existingRecord = $consentStmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug information
        error_log("Transaction ID: " . $transaction_id);
        error_log("Existing Record: " . print_r($existingRecord, true));
    } catch (PDOException $e) {
        error_log("Error fetching circumcision consent: " . $e->getMessage());
    }
}

// Fetch all active staff for performing doctor dropdown (no role filter)
$all_staff = [];
$stmt = $pdo->prepare("SELECT staff_id, first_name, last_name FROM staff WHERE status = 'Active' ORDER BY first_name, last_name");
$stmt->execute();
$all_staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

$breadcrumb = array(
    'Health Records' => array('link' => 'manage_health_records.php', 'icon' => 'fas fa-hospital'),
    'Circumcision Consent Form' => array('link' => '#', 'icon' => 'fas fa-file-medical')
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Circumcision Consent Form</title>
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
        .consent-text {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .form-check {
            margin-bottom: 10px;
        }
        .signature-box {
            border: 1px dashed #ccc;
            padding: 20px;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include('sidebar.php'); ?>

        <main class="dashboard-main-content">
            <?php include('../admin/breadcrumb.php'); ?>
            
            <div class="container">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-body">
                    <div class="statement-header">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <img src="../psc_greenbanner.png" alt="Hospital Logo" class="hospital-logo mb-2">
                                </div>
                                <div class="col-md-6">
                                    <div class="report-title">CIRCUMCISION CONSENT FORM</div>
                                    <div class="contact-info">Contact No.: 043-738-1874</div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="<?= $_SERVER['PHP_SELF'] . '?transaction_id=' . $_GET['transaction_id'] ?>">
                            <!-- Child Information -->
                            <div class="section">
                                <h4>Child Information</h4>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="child_name">Child's Full Name:</label>
                                            <input type="text" class="form-control" id="child_name" name="child_name" 
                                                value="<?= isset($existingRecord['child_name']) ? htmlspecialchars($existingRecord['child_name']) : (isset($patientInfo) ? htmlspecialchars($patientInfo['first_name'] . ' ' . $patientInfo['last_name']) : '') ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="child_age">Age:</label>
                                            <input type="number" class="form-control" id="child_age" name="child_age" 
                                                value="<?= isset($existingRecord['child_age']) ? htmlspecialchars($existingRecord['child_age']) : (isset($patientInfo) ? htmlspecialchars($patientInfo['age']) : '') ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="child_birthdate">Date of Birth:</label>
                                            <input type="date" class="form-control" id="child_birthdate" name="child_birthdate" 
                                                value="<?= isset($existingRecord['child_birthdate']) ? htmlspecialchars($existingRecord['child_birthdate']) : (isset($patientInfo) ? htmlspecialchars($patientInfo['date_of_birth']) : '') ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Parent/Guardian Information -->
                            <div class="section">
                                <h4>Parent/Guardian Information</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="parent_name">Parent/Guardian Name:</label>
                                            <input type="text" class="form-control" id="parent_name" name="parent_name" 
                                                value="<?= isset($existingRecord['parent_name']) ? htmlspecialchars($existingRecord['parent_name']) : '' ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="parent_relationship">Relationship to Child:</label>
                                            <select class="form-control" id="parent_relationship" name="parent_relationship" required>
                                                <option value="">Select Relationship</option>
                                                <option value="Mother" <?= (isset($existingRecord['parent_relationship']) && $existingRecord['parent_relationship'] == 'Mother') ? 'selected' : '' ?>>Mother</option>
                                                <option value="Father" <?= (isset($existingRecord['parent_relationship']) && $existingRecord['parent_relationship'] == 'Father') ? 'selected' : '' ?>>Father</option>
                                                <option value="Legal Guardian" <?= (isset($existingRecord['parent_relationship']) && $existingRecord['parent_relationship'] == 'Legal Guardian') ? 'selected' : '' ?>>Legal Guardian</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="parent_contact">Contact Number:</label>
                                            <input type="tel" class="form-control" id="parent_contact" name="parent_contact" 
                                                value="<?= isset($existingRecord['parent_contact']) ? htmlspecialchars($existingRecord['parent_contact']) : '' ?>" required pattern="[0-9]+" inputmode="numeric">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="parent_address">Address:</label>
                                            <textarea class="form-control" id="parent_address" name="parent_address" rows="2" required><?= isset($existingRecord['parent_address']) ? htmlspecialchars($existingRecord['parent_address']) : '' ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Medical Information -->
                            <div class="section">
                                <h4>Medical Information</h4>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="medical_conditions">Medical Conditions:</label>
                                            <textarea class="form-control" id="medical_conditions" name="medical_conditions" rows="3"><?= isset($existingRecord['medical_conditions']) ? htmlspecialchars($existingRecord['medical_conditions']) : '' ?></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="allergies">Known Allergies:</label>
                                            <textarea class="form-control" id="allergies" name="allergies" rows="3"><?= isset($existingRecord['allergies']) ? htmlspecialchars($existingRecord['allergies']) : '' ?></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="medications">Current Medications:</label>
                                            <textarea class="form-control" id="medications" name="medications" rows="3"><?= isset($existingRecord['medications']) ? htmlspecialchars($existingRecord['medications']) : '' ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Consent and Acknowledgment -->
                            <div class="section">
                                <h4>Consent and Acknowledgment</h4>
                                <div class="consent-text">
                                    <p>I, the undersigned parent/legal guardian, hereby:</p>
                                    <ol>
                                        <li>Give my consent for my child to undergo the circumcision procedure.</li>
                                        <li>Acknowledge that I have been fully informed about the nature of the procedure, its benefits, risks, and potential complications.</li>
                                        <li>Understand that the procedure will be performed under local anesthesia.</li>
                                        <li>Have been given the opportunity to ask questions and have received satisfactory answers.</li>
                                    </ol>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="acknowledge_procedure" name="acknowledge_procedure" 
                                        <?= (isset($existingRecord['acknowledge_procedure']) && $existingRecord['acknowledge_procedure'] == 1) ? 'checked' : '' ?> required>
                                    <label class="form-check-label" for="acknowledge_procedure">
                                        I understand the nature of the circumcision procedure
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="acknowledge_risks" name="acknowledge_risks" 
                                        <?= (isset($existingRecord['acknowledge_risks']) && $existingRecord['acknowledge_risks'] == 1) ? 'checked' : '' ?> required>
                                    <label class="form-check-label" for="acknowledge_risks">
                                        I understand the risks and potential complications
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="acknowledge_aftercare" name="acknowledge_aftercare" 
                                        <?= (isset($existingRecord['acknowledge_aftercare']) && $existingRecord['acknowledge_aftercare'] == 1) ? 'checked' : '' ?> required>
                                    <label class="form-check-label" for="acknowledge_aftercare">
                                        I understand the post-procedure care instructions
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="acknowledge_questions" name="acknowledge_questions" 
                                        <?= (isset($existingRecord['acknowledge_questions']) && $existingRecord['acknowledge_questions'] == 1) ? 'checked' : '' ?> required>
                                    <label class="form-check-label" for="acknowledge_questions">
                                        I have had the opportunity to ask questions
                                    </label>
                                </div>
                            </div>

                            <!-- Scheduling and Additional Information -->
                            <div class="section">
                                <h4>Scheduling and Additional Information</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="scheduled_date">Scheduled Date of Procedure:</label>
                                            <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" 
                                                value="<?= isset($existingRecord['scheduled_date']) ? htmlspecialchars($existingRecord['scheduled_date']) : '' ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="doctor_name">Performing Doctor:</label>
                                            <select class="form-control" id="doctor_name" name="doctor_name" required>
                                                <option value="">Select Staff</option>
                                                <?php foreach ($all_staff as $staff): 
                                                    $fullName = $staff['first_name'] . ' ' . $staff['last_name'];
                                                    $selected = (isset($existingRecord['doctor_name']) && $existingRecord['doctor_name'] === $fullName) ? 'selected' : '';
                                                ?>
                                                    <option value="<?= htmlspecialchars($fullName) ?>" <?= $selected ?>><?= htmlspecialchars($fullName) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="special_instructions">Special Instructions:</label>
                                            <textarea class="form-control" id="special_instructions" name="special_instructions" rows="3"><?= isset($existingRecord['special_instructions']) ? htmlspecialchars($existingRecord['special_instructions']) : '' ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Signatures -->
                            <div class="section">
                                <h4>Signatures (Optional)</h4>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Parent/Guardian Signature:</label>
                                            <div class="signature-box">
                                                <input type="text" class="form-control" name="parent_signature" 
                                                    value="<?= isset($existingRecord['parent_signature']) ? htmlspecialchars($existingRecord['parent_signature']) : '' ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Doctor's Signature:</label>
                                            <div class="signature-box">
                                                <input type="text" class="form-control" name="doctor_signature" 
                                                    value="<?= isset($existingRecord['doctor_signature']) ? htmlspecialchars($existingRecord['doctor_signature']) : '' ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Witness Name and Signature:</label>
                                            <input type="text" class="form-control mb-2" name="witness_name" 
                                                value="<?= isset($existingRecord['witness_name']) ? htmlspecialchars($existingRecord['witness_name']) : '' ?>" placeholder="Witness Name">
                                            <div class="signature-box">
                                                <input type="text" class="form-control" name="witness_signature" 
                                                    value="<?= isset($existingRecord['witness_signature']) ? htmlspecialchars($existingRecord['witness_signature']) : '' ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="consent_date">Date Signed:</label>
                                            <input type="date" class="form-control" id="consent_date" name="consent_date" 
                                                value="<?= isset($existingRecord['consent_date']) ? htmlspecialchars($existingRecord['consent_date']) : date('Y-m-d') ?>">
                                        </div>
                                    </div>
                                </div>
                                
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="fas fa-save me-2"></i>
                                    <?= isset($existingRecord) && $existingRecord ? 'Update Consent Form' : 'Save Consent Form' ?>
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