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

// Get patient ID from query parameters
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

    // Insert into the prenatal_records table
    $query = "
        INSERT INTO prenatal_records (
            patient_id, visit_date, attending_physician, gravida, para, ob_score, 
            lmp, aog_by_lmp, edc_by_lmp, aog_by_usg, edc_by_usg, blood_pressure, 
            weight, temperature, respiratory_rate, fundal_height, fetal_heart_tones, 
            internal_examination, chief_complaint, history_of_present_illness, 
            past_medical_history, past_social_history, family_history, tt_dose, 
            plan, lab_results
        ) VALUES (
            :patient_id, :visit_date, :attending_physician, :gravida, :para, :ob_score, 
            :lmp, :aog_by_lmp, :edc_by_lmp, :aog_by_usg, :edc_by_usg, :blood_pressure, 
            :weight, :temperature, :respiratory_rate, :fundal_height, :fetal_heart_tones, 
            :internal_examination, :chief_complaint, :history_of_present_illness, 
            :past_medical_history, :past_social_history, :family_history, :tt_dose, 
            :plan, :lab_results
        )
    ";
    $stmt = $pdo->prepare($query);

    try {
        $stmt->execute([
            ':patient_id' => $patient_id,
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
        ]);
        $_SESSION['message'] = "Prenatal record added successfully.";
        header("Location: manage_health_records.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to add prenatal record: " . $e->getMessage();
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
    <link rel="stylesheet" href="../css/components.css"><!-- Table styles -->
    <link rel="stylesheet" href="../css/form.css"><!-- Form styles -->
</head>

<body style="font-family: 'Poppins', sans-serif;">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="sidebar-header">
                <img src="../PSC_banner.png" alt="Paanakan Logo">
            </div>
            <ul>
                <li><a href="dashboard.php"><span class="material-icons">dashboard</span><span class="link-text">Dashboard</span></a></li>
                <li><a href="manage_appointments.php"><span class="material-icons">event</span><span class="link-text">Appointments</span></a></li>
                <li><a href="manage_health_records.php"><span class="material-icons">folder</span><span class="link-text">Health Records</span></a></li>
                <li><a href="transactions.php"><span class="material-icons">local_hospital</span><span class="link-text">Medical Services</span></a></li>
                <li><a href="patient.php"><span class="material-icons">person</span><span class="link-text">Patients</span></a></li>
                <li><a href="supply.php"><span class="material-icons">inventory_2</span><span class="link-text">Supplies</span></a></li>
                <li><a href="billing.php"><span class="material-icons">receipt</span><span class="link-text">Billing</span></a></li>
                <li><a href="reports.php"><span class="material-icons">assessment</span><span class="link-text">Reports</span></a></li>
                <li><a href="manage_users.php"><span class="material-icons">people</span><span class="link-text">Users</span></a></li>
                <li><a href="logs.php"><span class="material-icons">history</span><span class="link-text">Logs</span></a></li>
                <li><a href="../logout.php"><span class="material-icons">logout</span><span class="link-text">Logout</span></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main-content">
            <div class="container mt-5">
                <h2 class="mb-4">Add Prenatal Record</h2>

                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php elseif (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <form action="" method="POST" class="shadow p-4 bg-white rounded">
                    <!-- Section: Visit Details -->
                    <div class="section-container">
                        <h5 class="mb-3">Visit Details</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="visit_date" class="form-label">Visit Date</label>
                                <input type="datetime-local" name="visit_date" id="visit_date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="attending_physician" class="form-label">Attending Physician</label>
                                <input type="text" name="attending_physician" id="attending_physician" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Section: Obstetric History -->
                    <div class="section-container">
                        <h5 class="mb-3">Obstetric History</h5>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="gravida" class="form-label">Gravida</label>
                                <input type="number" name="gravida" id="gravida" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="para" class="form-label">Para</label>
                                <input type="number" name="para" id="para" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="ob_score" class="form-label">OB Score</label>
                                <input type="text" name="ob_score" id="ob_score" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Section: Menstrual and Pregnancy Details -->
                    <div class="section-container">
                        <h5 class="mb-3">Menstrual and Pregnancy Details</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="lmp" class="form-label">Last Menstrual Period (LMP)</label>
                                <input type="date" name="lmp" id="lmp" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="aog_by_lmp" class="form-label">Age of Gestation (AOG) by LMP</label>
                                <input type="number" step="0.01" name="aog_by_lmp" id="aog_by_lmp" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edc_by_lmp" class="form-label">EDC by LMP</label>
                                <input type="date" name="edc_by_lmp" id="edc_by_lmp" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="aog_by_usg" class="form-label">AOG by USG</label>
                                <input type="number" step="0.01" name="aog_by_usg" id="aog_by_usg" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edc_by_usg" class="form-label">EDC by USG</label>
                                <input type="date" name="edc_by_usg" id="edc_by_usg" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Section: Vital Signs -->
                    <div class="section-container">
                        <h5 class="mb-3">Vital Signs</h5>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="blood_pressure" class="form-label">Blood Pressure</label>
                                <input type="text" name="blood_pressure" id="blood_pressure" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="weight" class="form-label">Weight</label>
                                <input type="number" step="0.01" name="weight" id="weight" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="temperature" class="form-label">Temperature</label>
                                <input type="number" step="0.01" name="temperature" id="temperature" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="respiratory_rate" class="form-label">Respiratory Rate</label>
                                <input type="number" name="respiratory_rate" id="respiratory_rate" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="fundal_height" class="form-label">Fundal Height</label>
                                <input type="number" step="0.01" name="fundal_height" id="fundal_height" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="fetal_heart_tones" class="form-label">Fetal Heart Tones</label>
                                <input type="number" name="fetal_heart_tones" id="fetal_heart_tones" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Section: Clinical Notes -->
                    <div class="section-container">
                        <h5 class="mb-3">Clinical Notes</h5>
                        <div class="mb-3">
                            <label for="internal_examination" class="form-label">Internal Examination</label>
                            <textarea name="internal_examination" id="internal_examination" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="chief_complaint" class="form-label">Chief Complaint</label>
                            <textarea name="chief_complaint" id="chief_complaint" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="history_of_present_illness" class="form-label">History of Present Illness</label>
                            <textarea name="history_of_present_illness" id="history_of_present_illness" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="past_medical_history" class="form-label">Past Medical History</label>
                            <textarea name="past_medical_history" id="past_medical_history" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="past_social_history" class="form-label">Past Social History</label>
                            <textarea name="past_social_history" id="past_social_history" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="family_history" class="form-label">Family History</label>
                            <textarea name="family_history" id="family_history" class="form-control" rows="3"></textarea>
                        </div>
                    </div>

                    <!-- Section: Additional Information -->
                    <div class="section-container">
                        <h5 class="mb-3">Additional Information</h5>
                        <div class="mb-3">
                            <label for="tt_dose" class="form-label">TT Dose</label>
                            <input type="text" name="tt_dose" id="tt_dose" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="plan" class="form-label">Plan</label>
                            <textarea name="plan" id="plan" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="lab_results" class="form-label">Lab Results</label>
                            <textarea name="lab_results" id="lab_results" class="form-control" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Add Prenatal Record</button>
                    </div>
                </form>



            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
