<?php
require_once '../../connections/connections.php';
require_once '../../activity_log.php';

$pdo = connection();
$activityLog = new ActivityLog($pdo);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$view_mode = false;
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : (isset($_POST['patient_id']) ? intval($_POST['patient_id']) : (isset($_SESSION['patient_id']) ? intval($_SESSION['patient_id']) : null));
$patient_name = '';
if ($patient_id) {
    $view_mode = true;
    // Fetch patient name and case ID
    $stmt = $pdo->prepare("SELECT first_name, last_name, case_id FROM patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $patient_name = $row['first_name'] . ' ' . $row['last_name'];
        $case_id = $row['case_id'];
    }
}

// Get appointment_id from GET or POST
$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : (isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0);

// If patient_id is still not set, but appointment_id is set, fetch patient_id from DB
if (!$patient_id && isset($appointment_id) && $appointment_id) {
    $stmt = $pdo->prepare("SELECT patient_id FROM appointments WHERE appointment_id = ?");
    $stmt->execute([$appointment_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && isset($row['patient_id'])) {
        $patient_id = intval($row['patient_id']);
    }
}

// Fetch existing record for this appointment (if any)
$existingRecord = null;
if ($appointment_id && !$view_mode) {
    $stmt = $pdo->prepare("SELECT * FROM follow_up_records WHERE appointment_id = ? LIMIT 1");
    $stmt->execute([$appointment_id]);
    $existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Add this near the top of the file after other includes
date_default_timezone_set('Asia/Manila');

// Handle form submission (Create or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$view_mode) {
    // Get user's full name for logging
    $userQuery = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS user_name FROM users WHERE user_id = :user_id");
    $userQuery->execute([':user_id' => $_SESSION['user_id']]);
    $userRow = $userQuery->fetch();
    $user_name = $userRow ? $userRow['user_name'] : 'Unknown User';

    // Get patient info for logging
    $patientQuery = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS patient_name, case_id FROM patients WHERE patient_id = ?");
    $patientQuery->execute([$patient_id]);
    $patientRow = $patientQuery->fetch();
    $patient_name = $patientRow ? $patientRow['patient_name'] : 'Unknown Patient';
    $case_id = $patientRow ? $patientRow['case_id'] : 'Unknown';

    // Check if this is a completion request
    if (isset($_POST['complete_appointment'])) {
        // Only update the appointment status with Manila time
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'Done', completed_date = NOW() WHERE appointment_id = ?");
        $stmt->execute([$appointment_id]);
        
        // Log the appointment completion
        $action_desc = $user_name . " completed follow-up appointment for " . $patient_name . " (" . $case_id . ")";
        $activityLog->logActivity($_SESSION['user_id'], $action_desc);
        
        header("Location: follow_up.php?appointment_id=$appointment_id");
        exit();
    }

    // Handle follow-up record update/creation (only if not completing appointment)
    if (!isset($_POST['complete_appointment'])) {
        $fields = [
            'previous_diagnosis' => $_POST['previous_diagnosis'],
            'progress_notes' => $_POST['progress_notes'],
            'current_status' => $_POST['current_status'],
            'recommendations' => $_POST['recommendations'],
            'next_followup_date' => !empty($_POST['next_followup_date']) ? $_POST['next_followup_date'] : null,
        ];

        if (isset($_POST['record_id']) && $_POST['record_id']) {
            // Update
            $sql = "UPDATE follow_up_records SET previous_diagnosis=?, progress_notes=?, current_status=?, recommendations=?, next_followup_date=?, patient_id=? WHERE record_id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge(array_values($fields), [$patient_id, $_POST['record_id']]));
            
            // Log the update activity
            $action_desc = $user_name . " updated follow-up record for " . $patient_name . " (" . $case_id . ")";
            $activityLog->logActivity($_SESSION['user_id'], $action_desc);
        } else {
            // Insert
            $sql = "INSERT INTO follow_up_records (appointment_id, patient_id, previous_diagnosis, progress_notes, current_status, recommendations, next_followup_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge([$appointment_id, $patient_id], array_values($fields)));
            
            // Log the creation activity
            $action_desc = $user_name . " created follow-up record for " . $patient_name . " (" . $case_id . ")";
            $activityLog->logActivity($_SESSION['user_id'], $action_desc);
        }

        header("Location: follow_up.php?appointment_id=$appointment_id");
        exit();
    }
}

// Fetch records
$records = [];
if ($patient_id) {
    // Always get all follow up records for this patient_id (across all appointments)
    $stmt = $pdo->prepare("SELECT f.*, a.scheduled_date FROM follow_up_records f LEFT JOIN appointments a ON f.appointment_id = a.appointment_id WHERE f.patient_id = ? ORDER BY f.created_at DESC");
    $stmt->execute([$patient_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($appointment_id) {
    $stmt = $pdo->prepare("SELECT * FROM follow_up_records WHERE appointment_id = ?");
    $stmt->execute([$appointment_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// After the existing appointment_id fetch, add this function to get patient info
function getPatientInfoByAppointment($pdo, $appointment_id) {
    $stmt = $pdo->prepare("
        SELECT p.first_name, p.last_name, p.case_id, p.patient_id 
        FROM patients p 
        JOIN appointments a ON p.patient_id = a.patient_id 
        WHERE a.appointment_id = ?
    ");
    $stmt->execute([$appointment_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get patient info if we have an appointment_id
$patient_info = null;
if ($appointment_id) {
    $patient_info = getPatientInfoByAppointment($pdo, $appointment_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Follow-up Records</title>
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
        .card { border-radius: 1rem; box-shadow: 0 2px 8px rgba(44, 62, 80, 0.08); }
        .section-title { color: #2E8B57; font-weight: 600; margin-bottom: 1rem; }
        .form-label { font-weight: 500; color: #333; }
        .btn-primary { background: #2E8B57; border: none; }
        .btn-primary:hover { background: #256d47; }
        .table thead th { background: #e8f5e9; color: #2E8B57; }
        .patient-info-card {
            background: #f8fafc;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.08);
            padding: 1.2rem 1.5rem;
            margin-bottom: 1rem;
            min-width: 270px;
        }
        .patient-avatar {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #2E8B57 60%, #4CAF50 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .patient-avatar i {
            color: #fff;
            font-size: 2.5rem;
        }
        .patient-label {
            font-size: 0.85rem;
            color: #888;
            font-weight: 500;
            margin-bottom: 0.1rem;
            letter-spacing: 0.5px;
        }
        .patient-name {
            font-size: 1.15rem;
            font-weight: 700;
            color: #222;
            margin-bottom: 0.2rem;
        }
        .case-label {
            font-size: 0.8rem;
            color: #888;
            font-weight: 500;
            margin-top: 0.2rem;
            letter-spacing: 0.5px;
        }
        .case-id {
            display: inline-block;
            font-size: 1rem;
            font-weight: 600;
            color: #219150;
            background: #e8f5e9;
            border-radius: 0.5rem;
            padding: 0.1rem 0.7rem;
            margin-top: 0.1rem;
        }
    </style>
    <script>
    function confirmCompleteAppointment() {
        return confirm('Are you sure you want to mark this appointment as completed? This action cannot be undone.');
    }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add click event listener to the complete appointment button
            const completeBtn = document.getElementById('completeAppointmentBtn');
            if (completeBtn) {
                completeBtn.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to mark this appointment as completed? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</head>
<body>
<div class="dashboard-container">
    <?php include('../sidebar.php'); ?>
    <main class="dashboard-main-content">
        <div class="header-section mb-4">
            <div>
                <h2><i class="fas fa-clipboard-list me-2"></i>Follow-up Records</h2>
                <?php if ($view_mode): ?>
                    <div class="fs-6">Patient ID: <span class="fw-bold">#<?= htmlspecialchars($patient_id) ?></span> &mdash; <span class="fw-bold"><?= htmlspecialchars($patient_name) ?></span></div>
                <?php else: ?>
                    <div class="fs-6">Appointment ID: <span class="fw-bold">#<?= htmlspecialchars($appointment_id) ?></span></div>
                <?php endif; ?>
            </div>
            <a href="../manage_appointments.php" class="btn btn-light"><i class="fas fa-arrow-left me-2"></i>Back to Appointments</a>
        </div>

        <!-- Records Table -->
        <div class="card p-4">
            <h5 class="section-title"><i class="fas fa-list me-2"></i>Follow-up Records</h5>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Previous Diagnosis</th>
                            <th>Progress Notes</th>
                            <th>Current Status</th>
                            <th>Recommendations</th>
                            <th>Next Follow-up</th>
                            <th>Scheduled Date</th>
                            <?php if ($view_mode) echo '<th>Appointment ID</th><th>Schedule</th>'; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($records)): ?>
                        <?php foreach ($records as $rec): ?>
                            <tr>
                                <td><?= $rec['record_id'] ?></td>
                                <td><?= htmlspecialchars($rec['previous_diagnosis']) ?></td>
                                <td><?= htmlspecialchars($rec['progress_notes']) ?></td>
                                <td><?= htmlspecialchars($rec['current_status']) ?></td>
                                <td><?= htmlspecialchars($rec['recommendations']) ?></td>
                                <td><?= $rec['next_followup_date'] ? date('F j, Y', strtotime($rec['next_followup_date'])) : 'Not Scheduled' ?></td>
                                <td><?= isset($rec['scheduled_date']) && $rec['scheduled_date'] ? date('F j, Y g:i A', strtotime($rec['scheduled_date'])) : 'N/A' ?></td>
                                <?php if ($view_mode): ?>
                                    <td><?= htmlspecialchars($rec['appointment_id']) ?></td>
                                    <td><?= isset($rec['scheduled_date']) ? date('F j, Y g:i A', strtotime($rec['scheduled_date'])) : 'N/A' ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $view_mode ? '9' : '7' ?>" class="text-center py-4">
                                <i class="fas fa-clipboard-list text-muted mb-2" style="font-size: 2rem;"></i>
                                <p class="mb-0 text-muted">No follow-up records found.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 