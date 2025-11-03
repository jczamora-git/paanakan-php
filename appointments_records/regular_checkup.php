<?php
require_once '../connections/connections.php';
$pdo = connection();

$view_mode = false;
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : null;
$patient_name = '';
if ($patient_id) {
    $view_mode = true;
    // Fetch patient name
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $patient_name = $row['first_name'] . ' ' . $row['last_name'];
    }
}

// Get appointment_id from GET or POST
$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : (isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0);

// Fetch existing record for this appointment (if any)
$existingRecord = null;
if ($appointment_id && !$view_mode) {
    $stmt = $pdo->prepare("SELECT * FROM regular_checkup_records WHERE appointment_id = ? LIMIT 1");
    $stmt->execute([$appointment_id]);
    $existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle form submission (Create or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$view_mode) {
    $fields = [
        'blood_pressure' => $_POST['blood_pressure'],
        'temperature' => $_POST['temperature'],
        'pulse_rate' => $_POST['pulse_rate'],
        'respiratory_rate' => $_POST['respiratory_rate'],
        'weight' => $_POST['weight'],
        'height' => $_POST['height'],
        'bmi' => $_POST['bmi'],
        'chief_complaint' => $_POST['chief_complaint'],
        'diagnosis' => $_POST['diagnosis'],
        'treatment_plan' => $_POST['treatment_plan'],
    ];
    if (isset($_POST['record_id']) && $_POST['record_id']) {
        // Update
        $sql = "UPDATE regular_checkup_records SET blood_pressure=?, temperature=?, pulse_rate=?, respiratory_rate=?, weight=?, height=?, bmi=?, chief_complaint=?, diagnosis=?, treatment_plan=? WHERE record_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge(array_values($fields), [$_POST['record_id']]));
    } else {
        // Insert
        $sql = "INSERT INTO regular_checkup_records (appointment_id, blood_pressure, temperature, pulse_rate, respiratory_rate, weight, height, bmi, chief_complaint, diagnosis, treatment_plan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([$appointment_id], array_values($fields)));
    }
    // If Complete Appointment button was pressed and confirmed (via hidden input confirm_complete)
    if (isset($_POST['complete_appointment']) && isset($_POST['confirm_complete']) && $_POST['confirm_complete'] === '1') {
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'Done' WHERE appointment_id = ?");
        $stmt->execute([$appointment_id]);
        $_SESSION['toast_message'] = 'Appointment completed successfully!';
        $_SESSION['toast_type'] = 'success';
    }
    header("Location: regular_checkup.php?appointment_id=$appointment_id");
    exit();
}

// Fetch records
$records = [];
if ($view_mode && $patient_id) {
    // Get all regular checkup records for this patient_id (across all appointments)
    $stmt = $pdo->prepare("SELECT r.*, a.scheduled_date, a.appointment_id FROM regular_checkup_records r JOIN appointments a ON r.appointment_id = a.appointment_id WHERE a.patient_id = ? ORDER BY a.scheduled_date DESC");
    $stmt->execute([$patient_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($appointment_id) {
    $stmt = $pdo->prepare("SELECT * FROM regular_checkup_records WHERE appointment_id = ?");
    $stmt->execute([$appointment_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Regular Checkup Records</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/toast-alert.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-container { display: flex; min-height: 100vh; }
        .dashboard-main-content { flex-grow: 1; padding: 30px 20px; margin-left: 270px; transition: margin-left 0.4s ease; }
        .sidebar.collapsed ~ .dashboard-main-content { margin-left: 85px; }
        .header-section {
            background: linear-gradient(90deg, #2E8B57 0%, #4CAF50 100%);
            color: #fff;
            padding: 2rem 2.5rem 1.5rem 2.5rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-section h2 { margin: 0; font-weight: 700; letter-spacing: 1px; }
        .card { border-radius: 1rem; box-shadow: 0 2px 8px rgba(44, 62, 80, 0.08); }
        .section-title { color: #2E8B57; font-weight: 600; margin-bottom: 1rem; }
        .form-label { font-weight: 500; color: #333; }
        .btn-primary { background: #2E8B57; border: none; }
        .btn-primary:hover { background: #256d47; }
        .table thead th { background: #e8f5e9; color: #2E8B57; }
    </style>
</head>
<body>
<div class="dashboard-container">
<?php include('sidebar.php'); ?>
    <main class="dashboard-main-content">
        <div class="header-section mb-4">
            <div>
                <h2><i class="fas fa-stethoscope me-2"></i>Regular Checkup Records</h2>
                <?php if ($view_mode): ?>
                    <div class="fs-6">Patient ID: <span class="fw-bold">#<?= htmlspecialchars($patient_id) ?></span> &mdash; <span class="fw-bold"><?= htmlspecialchars($patient_name) ?></span></div>
                <?php else: ?>
                    <div class="fs-6">Appointment ID: <span class="fw-bold">#<?= htmlspecialchars($appointment_id) ?></span></div>
                <?php endif; ?>
            </div>
            <a href="/paanakan/admin/manage_appointments.php" class="btn btn-light"><i class="fas fa-arrow-left me-2"></i>Back to Appointments</a>
        </div>
        <?php if (!$view_mode): ?>
        <div class="card mb-4 p-4">
            <h5 class="section-title"><i class="fas fa-notes-medical me-2"></i>Add / Update Record</h5>
            <form method="post">
                <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($appointment_id) ?>">
                <?php if ($existingRecord): ?>
                    <input type="hidden" name="record_id" value="<?= $existingRecord['record_id'] ?>">
                <?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Blood Pressure</label>
                        <input type="text" name="blood_pressure" class="form-control" placeholder="e.g. 120/80" value="<?= $existingRecord ? htmlspecialchars($existingRecord['blood_pressure']) : '' ?>">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Temp (°C)</label>
                        <input type="number" step="0.1" name="temperature" class="form-control" placeholder="36.8" value="<?= $existingRecord ? htmlspecialchars($existingRecord['temperature']) : '' ?>">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Pulse</label>
                        <input type="number" name="pulse_rate" class="form-control" placeholder="75" value="<?= $existingRecord ? htmlspecialchars($existingRecord['pulse_rate']) : '' ?>">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Resp Rate</label>
                        <input type="number" name="respiratory_rate" class="form-control" placeholder="16" value="<?= $existingRecord ? htmlspecialchars($existingRecord['respiratory_rate']) : '' ?>">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Weight (kg)</label>
                        <input type="number" step="0.01" name="weight" class="form-control" placeholder="65.5" value="<?= $existingRecord ? htmlspecialchars($existingRecord['weight']) : '' ?>">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Height (cm)</label>
                        <input type="number" step="0.01" name="height" class="form-control" placeholder="165" value="<?= $existingRecord ? htmlspecialchars($existingRecord['height']) : '' ?>">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">BMI</label>
                        <input type="number" step="0.01" name="bmi" class="form-control" placeholder="24.0" value="<?= $existingRecord ? htmlspecialchars($existingRecord['bmi']) : '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Chief Complaint</label>
                        <input type="text" name="chief_complaint" class="form-control" placeholder="Chief Complaint" value="<?= $existingRecord ? htmlspecialchars($existingRecord['chief_complaint']) : '' ?>">
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Diagnosis</label>
                        <input type="text" name="diagnosis" class="form-control" placeholder="Diagnosis" value="<?= $existingRecord ? htmlspecialchars($existingRecord['diagnosis']) : '' ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Treatment Plan</label>
                        <input type="text" name="treatment_plan" class="form-control" placeholder="Treatment Plan" value="<?= $existingRecord ? htmlspecialchars($existingRecord['treatment_plan']) : '' ?>">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="button" id="saveRecordBtn" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>Save Record</button>
                    <?php if ($existingRecord): ?>
                        <input type="hidden" name="confirm_complete" id="confirm_complete" value="0" />
                        <button type="button" id="completeAppointmentBtn" class="btn btn-success px-4 ms-2"><i class="fas fa-check-circle me-2"></i>Complete</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php endif; ?>
        <div class="card p-4">
            <h5 class="section-title"><i class="fas fa-list me-2"></i>Existing Records</h5>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead><tr><th>ID</th><th>BP</th><th>Temp</th><th>Pulse</th><th>Resp</th><th>Weight</th><th>Height</th><th>BMI</th><th>Complaint</th><th>Diagnosis</th><th>Treatment</th><th>Created</th><?php if ($view_mode) echo '<th>Appointment ID</th><th>Schedule</th>'; ?></tr></thead>
                    <tbody>
                    <?php foreach ($records as $rec): ?>
                        <tr>
                            <td><?= $rec['record_id'] ?></td>
                            <td><?= htmlspecialchars($rec['blood_pressure']) ?></td>
                            <td><?= htmlspecialchars($rec['temperature']) ?></td>
                            <td><?= htmlspecialchars($rec['pulse_rate']) ?></td>
                            <td><?= htmlspecialchars($rec['respiratory_rate']) ?></td>
                            <td><?= htmlspecialchars($rec['weight']) ?></td>
                            <td><?= htmlspecialchars($rec['height']) ?></td>
                            <td><?= htmlspecialchars($rec['bmi']) ?></td>
                            <td><?= htmlspecialchars($rec['chief_complaint']) ?></td>
                            <td><?= htmlspecialchars($rec['diagnosis']) ?></td>
                            <td><?= htmlspecialchars($rec['treatment_plan']) ?></td>
                            <td><?= date('F j, Y g:i A', strtotime($rec['created_at'])) ?></td>
                            <?php if ($view_mode): ?>
                                <td><?= htmlspecialchars($rec['appointment_id']) ?></td>
                                <td><?= isset($rec['scheduled_date']) ? date('F j, Y g:i A', strtotime($rec['scheduled_date'])) : '' ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Toast Alert Component -->
<script src="../js/toast-alert.js"></script>
<!-- Appointment Actions Handler -->
<script src="../js/appointment-actions.js"></script>

<!-- Session Toast Messages -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toastMessage = '<?php echo isset($_SESSION['toast_message']) ? addslashes($_SESSION['toast_message']) : ''; ?>';
        const toastType = '<?php echo isset($_SESSION['toast_type']) ? $_SESSION['toast_type'] : ''; ?>';
        
        if (toastMessage && toastType) {
            Toast[toastType](toastMessage, 3000);
            <?php 
            unset($_SESSION['toast_message']); 
            unset($_SESSION['toast_type']); 
            ?>
        }
    });
</script>

</body>
</html> 