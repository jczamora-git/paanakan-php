<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

require '../connections/connections.php';
$pdo = connection();

$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : null;

// Fetch the patient's information
if ($patient_id) {
    $query = "SELECT CONCAT(first_name, ' ', last_name) AS fullname FROM patients WHERE patient_id = :patient_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':patient_id' => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$patient) {
        $_SESSION['error'] = "Patient not found.";
        header("Location: manage_health_records.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visit_date = $_POST['visit_date'] ?: null;
    $attending_physician = $_POST['attending_physician'] ?: null;
    $delivery_date = $_POST['delivery_date'] ?: null;
    $delivery_type = $_POST['delivery_type'] ?: null;
    $birth_weight = $_POST['birth_weight'] ?: null;
    $birth_length = $_POST['birth_length'] ?: null;
    $apgar_score = $_POST['apgar_score'] ?: null;
    $maternal_complications = $_POST['maternal_complications'] ?: null;
    $neonatal_complications = $_POST['neonatal_complications'] ?: null;
    $breastfeeding_initiated = $_POST['breastfeeding_initiated'] ?: null;
    $postpartum_bleeding = $_POST['postpartum_bleeding'] ?: null;
    $uterine_involution = $_POST['uterine_involution'] ?: null;
    $perineal_healing = $_POST['perineal_healing'] ?: null;
    $contraceptive_counseling = $_POST['contraceptive_counseling'] ?: null;
    $remarks = $_POST['remarks'] ?: null;

    $query = "
        INSERT INTO postnatal_records (
            patient_id, visit_date, attending_physician, delivery_date, delivery_type, birth_weight, birth_length, apgar_score, maternal_complications, neonatal_complications, breastfeeding_initiated, postpartum_bleeding, uterine_involution, perineal_healing, contraceptive_counseling, remarks
        ) VALUES (
            :patient_id, :visit_date, :attending_physician, :delivery_date, :delivery_type, :birth_weight, :birth_length, :apgar_score, :maternal_complications, :neonatal_complications, :breastfeeding_initiated, :postpartum_bleeding, :uterine_involution, :perineal_healing, :contraceptive_counseling, :remarks
        )
    ";
    $stmt = $pdo->prepare($query);
    try {
        $stmt->execute([
            ':patient_id' => $patient_id,
            ':visit_date' => $visit_date,
            ':attending_physician' => $attending_physician,
            ':delivery_date' => $delivery_date,
            ':delivery_type' => $delivery_type,
            ':birth_weight' => $birth_weight,
            ':birth_length' => $birth_length,
            ':apgar_score' => $apgar_score,
            ':maternal_complications' => $maternal_complications,
            ':neonatal_complications' => $neonatal_complications,
            ':breastfeeding_initiated' => $breastfeeding_initiated,
            ':postpartum_bleeding' => $postpartum_bleeding,
            ':uterine_involution' => $uterine_involution,
            ':perineal_healing' => $perineal_healing,
            ':contraceptive_counseling' => $contraceptive_counseling,
            ':remarks' => $remarks,
        ]);
        $_SESSION['message'] = "Postnatal record added successfully.";
        header("Location: manage_health_records.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to add postnatal record: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Postnatal Record</title>
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
        body { font-family: 'Poppins', sans-serif; background-color: #f5f5f5; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .dashboard-main-content { flex-grow: 1; padding: 20px; margin-left: 270px; transition: margin-left 0.4s ease; background-color: #f8f9fa; }
        .sidebar.collapsed ~ .dashboard-main-content { margin-left: 85px; }
        .patient-header { background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%); color: white; padding: 25px; border-radius: 15px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .patient-header h2 { font-weight: 600; margin-bottom: 10px; }
        .patient-header p { font-size: 1.1rem; opacity: 0.9; margin-bottom: 0; }
        .section-container { background: #fff; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px; margin-bottom: 25px; }
        .section-container h5 { color: var(--primary-color); font-weight: 600; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--primary-color); }
        .form-label { font-weight: 500; color: #444; margin-bottom: 8px; }
        .form-control { border: 1px solid #ddd; border-radius: 6px; padding: 8px 12px; transition: border-color 0.3s ease; }
        .form-control:focus { border-color: var(--primary-color); box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.25); }
        textarea.form-control { min-height: 100px; }
        .btn-success { background-color: var(--primary-color); border-color: var(--primary-color); padding: 10px 30px; font-weight: 500; transition: all 0.3s ease; }
        .btn-success:hover { background-color: var(--primary-light); border-color: var(--primary-light); transform: translateY(-1px); }
        .alert { border-radius: 8px; margin-bottom: 20px; }
        .form-floating > .form-control { padding-top: 1.625rem; padding-bottom: 0.625rem; }
        .form-floating > label { padding: 1rem 0.75rem; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include '../sidebar.php'; ?>
    <main class="dashboard-main-content">
        <?php include '../admin/breadcrumb.php'; ?>
        <div class="container">
            <div class="patient-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2><i class="fas fa-baby me-2"></i>Add Postnatal Record</h2>
                        <?php if (isset($patient)): ?>
                        <p class="mb-0">
                            <i class="fas fa-user me-2"></i>Patient: <?= htmlspecialchars($patient['fullname']) ?>
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
                <div class="section-container">
                    <h5><i class="fas fa-calendar-check me-2"></i>Visit Details</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="datetime-local" class="form-control" id="visit_date" name="visit_date" required>
                                <label for="visit_date">Visit Date</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="attending_physician" name="attending_physician" required>
                                <label for="attending_physician">Attending Physician</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="section-container">
                    <h5><i class="fas fa-baby me-2"></i>Delivery & Baby Details</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="date" class="form-control" id="delivery_date" name="delivery_date" required>
                                <label for="delivery_date">Delivery Date</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="delivery_type" name="delivery_type" required>
                                <label for="delivery_type">Delivery Type</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="number" step="0.01" class="form-control" id="birth_weight" name="birth_weight" required>
                                <label for="birth_weight">Birth Weight (kg)</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="number" step="0.01" class="form-control" id="birth_length" name="birth_length">
                                <label for="birth_length">Birth Length (cm)</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="apgar_score" name="apgar_score">
                                <label for="apgar_score">APGAR Score</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="section-container">
                    <h5><i class="fas fa-user-md me-2"></i>Postnatal Assessment</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <textarea class="form-control" id="maternal_complications" name="maternal_complications" style="height: 100px"></textarea>
                                <label for="maternal_complications">Maternal Complications</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <textarea class="form-control" id="neonatal_complications" name="neonatal_complications" style="height: 100px"></textarea>
                                <label for="neonatal_complications">Neonatal Complications</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <select class="form-control" id="breastfeeding_initiated" name="breastfeeding_initiated" required>
                                    <option value="">Select...</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                                <label for="breastfeeding_initiated">Breastfeeding Initiated</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="postpartum_bleeding" name="postpartum_bleeding">
                                <label for="postpartum_bleeding">Postpartum Bleeding</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="uterine_involution" name="uterine_involution">
                                <label for="uterine_involution">Uterine Involution</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="perineal_healing" name="perineal_healing">
                                <label for="perineal_healing">Perineal Healing</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="contraceptive_counseling" name="contraceptive_counseling">
                                <label for="contraceptive_counseling">Contraceptive Counseling</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="section-container">
                    <h5><i class="fas fa-info-circle me-2"></i>Additional Information</h5>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="form-floating">
                                <textarea class="form-control" id="remarks" name="remarks" style="height: 100px"></textarea>
                                <label for="remarks">Remarks</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4 mb-5">
                    <button type="submit" class="btn btn-success btn-lg px-5 me-2">
                        <i class="fas fa-save me-2"></i>Save Postnatal Record
                    </button>
                    <a href="javascript:history.back()" class="btn btn-secondary btn-lg px-5">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                </div>
            </form>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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