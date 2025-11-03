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
    $stmt = $pdo->prepare("SELECT * FROM under_observation_records WHERE appointment_id = ? LIMIT 1");
    $stmt->execute([$appointment_id]);
    $existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle form submission (Create or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$view_mode) {
    $fields = [
        'observation_notes' => $_POST['observation_notes'],
        'vital_signs' => $_POST['vital_signs'],
        'duration_observed' => $_POST['duration_observed'],
        'outcome' => $_POST['outcome'],
    ];
    if (isset($_POST['record_id']) && $_POST['record_id']) {
        // Update
        $sql = "UPDATE under_observation_records SET observation_notes=?, vital_signs=?, duration_observed=?, outcome=? WHERE record_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge(array_values($fields), [$_POST['record_id']]));
    } else {
        // Insert
        $sql = "INSERT INTO under_observation_records (appointment_id, observation_notes, vital_signs, duration_observed, outcome) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([$appointment_id], array_values($fields)));
    }
    // If Complete Appointment button was pressed
    if (isset($_POST['complete_appointment'])) {
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'Done' WHERE appointment_id = ?");
        $stmt->execute([$appointment_id]);
    }
    header("Location: under_observation.php?appointment_id=$appointment_id");
    exit();
}

// Fetch records
$records = [];
if ($view_mode && $patient_id) {
    // Get all under observation records for this patient_id (across all appointments)
    $stmt = $pdo->prepare("SELECT r.*, a.scheduled_date, a.appointment_id FROM under_observation_records r JOIN appointments a ON r.appointment_id = a.appointment_id WHERE a.patient_id = ? ORDER BY a.scheduled_date DESC");
    $stmt->execute([$patient_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($appointment_id) {
    $stmt = $pdo->prepare("SELECT * FROM under_observation_records WHERE appointment_id = ?");
    $stmt->execute([$appointment_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Under Observation Records</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
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
                <h2><i class="fas fa-eye me-2"></i>Under Observation Records</h2>
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
                <div class="mb-3">
                    <label class="form-label">Observation Notes</label>
                    <textarea name="observation_notes" class="form-control" rows="3" placeholder="Enter observation notes..."><?= $existingRecord ? htmlspecialchars($existingRecord['observation_notes']) : '' ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Vital Signs</label>
                    <textarea name="vital_signs" class="form-control" rows="2" placeholder="Enter vital signs..."><?= $existingRecord ? htmlspecialchars($existingRecord['vital_signs']) : '' ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Duration Observed</label>
                    <input type="text" name="duration_observed" class="form-control" placeholder="e.g. 2 hours" value="<?= $existingRecord ? htmlspecialchars($existingRecord['duration_observed']) : '' ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Outcome</label>
                    <textarea name="outcome" class="form-control" rows="2" placeholder="Enter outcome..."><?= $existingRecord ? htmlspecialchars($existingRecord['outcome']) : '' ?></textarea>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>Save Record</button>
                    <?php if ($existingRecord): ?>
                        <button type="submit" name="complete_appointment" value="1" class="btn btn-success px-4 ms-2"><i class="fas fa-check-circle me-2"></i>Complete Appointment</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php endif; ?>
        <div class="card p-4">
            <h5 class="section-title"><i class="fas fa-list me-2"></i>Existing Records</h5>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead><tr><th>ID</th><th>Observation Notes</th><th>Vital Signs</th><th>Duration</th><th>Outcome</th><th>Created</th><?php if ($view_mode) echo '<th>Appointment ID</th><th>Schedule</th>'; ?></tr></thead>
                    <tbody>
                    <?php foreach ($records as $rec): ?>
                        <tr>
                            <td><?= $rec['record_id'] ?></td>
                            <td><?= htmlspecialchars($rec['observation_notes']) ?></td>
                            <td><?= htmlspecialchars($rec['vital_signs']) ?></td>
                            <td><?= htmlspecialchars($rec['duration_observed']) ?></td>
                            <td><?= htmlspecialchars($rec['outcome']) ?></td>
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
</body>
</html> 