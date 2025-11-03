<?php
require_once '../../connections/connections.php';
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
    $stmt = $pdo->prepare("SELECT * FROM medical_consultation_records WHERE appointment_id = ? LIMIT 1");
    $stmt->execute([$appointment_id]);
    $existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch records
$records = [];
if ($view_mode && $patient_id) {
    // Get all medical consultation records for this patient_id (across all appointments)
    $stmt = $pdo->prepare("SELECT r.*, a.scheduled_date, a.appointment_id FROM medical_consultation_records r JOIN appointments a ON r.appointment_id = a.appointment_id WHERE a.patient_id = ? ORDER BY a.scheduled_date DESC");
    $stmt->execute([$patient_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($appointment_id) {
    $stmt = $pdo->prepare("SELECT * FROM medical_consultation_records WHERE appointment_id = ?");
    $stmt->execute([$appointment_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medical Consultation Records</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/sidebar.css">
    <link rel="stylesheet" href="../../css/components.css">
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
        .card { 
            border-radius: 1rem; 
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.08);
            border: none;
        }
        .section-title { 
            color: #2E8B57; 
            font-weight: 600; 
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-label { 
            font-weight: 500; 
            color: #333;
            margin-bottom: 0.5rem;
        }
        .form-control {
            border-radius: 0.5rem;
            border: 1px solid #e0e0e0;
            padding: 0.6rem 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #2E8B57;
            box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.15);
        }
        .btn-primary { 
            background: #2E8B57; 
            border: none;
            border-radius: 0.5rem;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover { 
            background: #256d47;
            transform: translateY(-1px);
        }
        .btn-success {
            border-radius: 0.5rem;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-success:hover {
            transform: translateY(-1px);
        }
        .table {
            margin-bottom: 0;
        }
        .table thead th { 
            background: #e8f5e9; 
            color: #2E8B57;
            font-weight: 600;
            padding: 1rem;
            border-bottom: 2px solid #2E8B57;
        }
        .table td {
            padding: 1rem;
            vertical-align: middle;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        .form-grid .full-width {
            grid-column: 1 / -1;
        }
        .card-body {
            padding: 2rem;
        }
        .table-container {
            padding: 1.5rem;
        }
        .btn-light {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 0.5rem;
            padding: 0.6rem 1.5rem;
            transition: all 0.3s ease;
        }
        .btn-light:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }
    </style>
</head>
<body>
<div class="dashboard-container">
<?php include('../sidebar.php'); ?>
    <main class="dashboard-main-content">
        <div class="header-section mb-4">
            <div>
                <h2><i class="fas fa-user-md me-2"></i>Medical Consultation Records</h2>
                <?php if ($view_mode): ?>
                    <div class="fs-6">Patient ID: <span class="fw-bold">#<?= htmlspecialchars($patient_id) ?></span> &mdash; <span class="fw-bold"><?= htmlspecialchars($patient_name) ?></span></div>
                <?php else: ?>
                    <div class="fs-6">Appointment ID: <span class="fw-bold">#<?= htmlspecialchars($appointment_id) ?></span></div>
                <?php endif; ?>
            </div>
            <a href="../manage_appointments.php" class="btn btn-light"><i class="fas fa-arrow-left me-2"></i>Back to Appointments</a>
        </div>
        <?php if ($appointment_id && !$view_mode): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="section-title">
                    <i class="fas fa-notes-medical"></i>
                    View Record
                </h5>
                <form>
                    <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($appointment_id) ?>">
                    <?php if ($existingRecord): ?>
                        <input type="hidden" name="record_id" value="<?= $existingRecord['record_id'] ?>">
                    <?php endif; ?>
                    <div class="form-grid">
                        <div class="mb-3">
                            <label class="form-label">Chief Complaint</label>
                            <textarea name="chief_complaint" class="form-control" rows="2" placeholder="Enter chief complaint..." readonly><?= $existingRecord ? htmlspecialchars($existingRecord['chief_complaint']) : '' ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">History of Present Illness</label>
                            <textarea name="history_of_present_illness" class="form-control" rows="2" placeholder="Enter history of present illness..." readonly><?= $existingRecord ? htmlspecialchars($existingRecord['history_of_present_illness']) : '' ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Physical Exam</label>
                            <textarea name="physical_exam" class="form-control" rows="2" placeholder="Enter physical exam..." readonly><?= $existingRecord ? htmlspecialchars($existingRecord['physical_exam']) : '' ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Diagnosis</label>
                            <textarea name="diagnosis" class="form-control" rows="2" placeholder="Enter diagnosis..." readonly><?= $existingRecord ? htmlspecialchars($existingRecord['diagnosis']) : '' ?></textarea>
                        </div>
                        <div class="mb-3 full-width">
                            <label class="form-label">Treatment</label>
                            <textarea name="treatment" class="form-control" rows="2" placeholder="Enter treatment..." readonly><?= $existingRecord ? htmlspecialchars($existingRecord['treatment']) : '' ?></textarea>
                        </div>
                        <div class="mb-3 full-width">
                            <label class="form-label">Prescription</label>
                            <textarea name="prescription" class="form-control" rows="2" placeholder="Enter prescription..." readonly><?= $existingRecord ? htmlspecialchars($existingRecord['prescription']) : '' ?></textarea>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
        <div class="card">
            <div class="card-body">
                <h5 class="section-title">
                    <i class="fas fa-list"></i>
                    Existing Records
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Chief Complaint</th>
                                <th>History</th>
                                <th>Physical Exam</th>
                                <th>Diagnosis</th>
                                <th>Treatment</th>
                                <th>Prescription</th>
                                <th>Created</th>
                                <?php if ($view_mode) echo '<th>Appointment ID</th><th>Schedule</th>'; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($records as $rec): ?>
                            <tr>
                                <td><?= $rec['record_id'] ?></td>
                                <td><?= htmlspecialchars($rec['chief_complaint']) ?></td>
                                <td><?= htmlspecialchars($rec['history_of_present_illness']) ?></td>
                                <td><?= htmlspecialchars($rec['physical_exam']) ?></td>
                                <td><?= htmlspecialchars($rec['diagnosis']) ?></td>
                                <td><?= htmlspecialchars($rec['treatment']) ?></td>
                                <td><?= htmlspecialchars($rec['prescription']) ?></td>
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
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 