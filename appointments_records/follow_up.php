<?php
require_once '../connections/connections.php';
require_once '../activity_log.php';

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

    // Check if this is a completion request and confirmed (via hidden input confirm_complete)
    if (isset($_POST['complete_appointment']) && isset($_POST['confirm_complete']) && $_POST['confirm_complete'] === '1') {
        // Only update the appointment status with Manila time
        $stmt = $pdo->prepare("UPDATE appointments SET status = 'Done', completed_date = NOW() WHERE appointment_id = ?");
        $stmt->execute([$appointment_id]);
        
        // Log the appointment completion
        $action_desc = $user_name . " completed follow-up appointment for " . $patient_name . " (" . $case_id . ")";
        $activityLog->logActivity($_SESSION['user_id'], $action_desc);
        
        $_SESSION['toast_message'] = 'Appointment completed successfully!';
        $_SESSION['toast_type'] = 'success';
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
    <?php include('sidebar.php'); ?>
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
                    <div class="col-md-6">
                        <label class="form-label">Previous Diagnosis</label>
                        <input type="text" name="previous_diagnosis" class="form-control" placeholder="Previous Diagnosis" value="<?= $existingRecord ? htmlspecialchars($existingRecord['previous_diagnosis']) : '' ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Progress Notes</label>
                        <input type="text" name="progress_notes" class="form-control" placeholder="Progress Notes" value="<?= $existingRecord ? htmlspecialchars($existingRecord['progress_notes']) : '' ?>">
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Current Status</label>
                        <input type="text" name="current_status" class="form-control" placeholder="Current Status" value="<?= $existingRecord ? htmlspecialchars($existingRecord['current_status']) : '' ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Recommendations</label>
                        <input type="text" name="recommendations" class="form-control" placeholder="Recommendations" value="<?= $existingRecord ? htmlspecialchars($existingRecord['recommendations']) : '' ?>">
                    </div>
                </div>

                <!-- Separate Next Follow-up Section -->
                <?php if ($existingRecord): ?>
                <div class="row mt-4" id="nextFollowupSection">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title mb-3">
                                    <i class="fas fa-calendar-check me-2"></i>Next Follow-up Appointment
                                </h6>
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1" id="nextFollowupInfo">
                                        <?php if ($existingRecord['next_followup_date']): ?>
                                            <p class="mb-0">
                                                <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                                Scheduled for: <strong><?= date('F j, Y', strtotime($existingRecord['next_followup_date'])) ?></strong>
                                            </p>
                                        <?php else: ?>
                                            <p class="mb-0 text-muted">
                                                <i class="fas fa-info-circle me-2"></i>
                                                No follow-up appointment scheduled
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if (!$existingRecord['next_followup_date']): ?>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleFollowupModal">
                                            <i class="fas fa-calendar-plus me-2"></i>Schedule Follow-up
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mt-4">
                    <button type="button" id="saveRecordBtn" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>Save Record</button>
                    <?php if ($existingRecord): ?>
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
                    <thead><tr><th>ID</th><th>Previous Diagnosis</th><th>Progress Notes</th><th>Current Status</th><th>Recommendations</th><th>Next Follow-up</th><th>Scheduled Date</th><?php if ($view_mode) echo '<th>Appointment ID</th><th>Schedule</th>'; ?></tr></thead>
                    <tbody>
                    <?php foreach ($records as $rec): ?>
                        <tr>
                            <td><?= $rec['record_id'] ?></td>
                            <td><?= htmlspecialchars($rec['previous_diagnosis']) ?></td>
                            <td><?= htmlspecialchars($rec['progress_notes']) ?></td>
                            <td><?= htmlspecialchars($rec['current_status']) ?></td>
                            <td><?= htmlspecialchars($rec['recommendations']) ?></td>
                            <td><?= $rec['next_followup_date'] ? date('F j, Y', strtotime($rec['next_followup_date'])) : '' ?></td>
                            <td><?= isset($rec['scheduled_date']) && $rec['scheduled_date'] ? date('F j, Y g:i A', strtotime($rec['scheduled_date'])) : '' ?></td>
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

<!-- Schedule Follow-up Modal -->
<div class="modal fade" id="scheduleFollowupModal" tabindex="-1" aria-labelledby="scheduleFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleFollowupModalLabel">
                    <i class="fas fa-calendar-plus me-2"></i>Schedule Follow-up Appointment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Modern patient information card -->
                <div class="mb-4">
                  <div class="d-flex align-items-center patient-info-card">
                    <div class="patient-avatar me-3">
                      <i class="fas fa-user-circle"></i>
                    </div>
                    <div>
                      <div class="patient-label">Patient</div>
                      <div class="patient-name" id="modalPatientName">Loading...</div>
                      <div class="case-label">Case ID</div>
                      <div class="case-id" id="modalCaseId">Loading...</div>
                    </div>
                  </div>
                </div>
                <form id="scheduleFollowupForm" action="schedule_followup.php" method="POST">
                    <input type="hidden" name="patient_id" id="modalPatientId" value="">
                    <input type="hidden" name="current_appointment_id" value="<?= $appointment_id ?>">
                    <div class="mb-3">
                        <label for="followupDate" class="form-label">Select Date</label>
                        <input type="date" class="form-control" id="followupDate" name="scheduled_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="followupTime" class="form-label">Select Time</label>
                        <select class="form-select" id="followupTime" name="scheduled_time" required>
                            <option value="">Select a time slot</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="scheduleFollowupBtn">
                    <i class="fas fa-calendar-plus me-2"></i>Schedule Follow-up
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const followupDate = document.getElementById('followupDate');
    const followupTime = document.getElementById('followupTime');
    const scheduleFollowupBtn = document.getElementById('scheduleFollowupBtn');
    const nextFollowupDate = document.getElementById('nextFollowupDate');

    // Set minimum date to tomorrow
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    followupDate.min = tomorrow.toISOString().split('T')[0];

    // Disable weekends
    followupDate.addEventListener('input', function() {
        const selectedDate = new Date(this.value);
        const day = selectedDate.getDay();
        
        if (day === 0 || day === 6) { // 0 is Sunday, 6 is Saturday
            alert('Weekends are not available for scheduling. Please select a weekday.');
            this.value = '';
            return;
        }
        
        // Fetch available times for selected date
        fetchAvailableTimes(this.value);
    });

    function fetchAvailableTimes(date) {
        // Show loading state
        followupTime.innerHTML = '<option value="">Loading available times...</option>';
        followupTime.disabled = true;

        // Use the same endpoint as manage_appointments.php
        fetch(`../admin/fetch_available_times.php?date=${date}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(response => {
                followupTime.innerHTML = '<option value="">Select a time slot</option>';
                
                if (response) {
                    try {
                        const times = JSON.parse(response);
                        if (Array.isArray(times) && times.length > 0) {
                            times.forEach(time => {
                                const option = document.createElement('option');
                                option.value = time;
                                option.textContent = time;
                                followupTime.appendChild(option);
                            });
                        } else {
                            followupTime.innerHTML = '<option value="">No available times</option>';
                        }
                    } catch (e) {
                        console.error('Error parsing times:', e);
                        followupTime.innerHTML = '<option value="">Error loading times</option>';
                    }
                } else {
                    followupTime.innerHTML = '<option value="">No available times</option>';
                }
            })
            .catch(error => {
                console.error('Error fetching available times:', error);
                followupTime.innerHTML = '<option value="">Error loading times</option>';
            })
            .finally(() => {
                followupTime.disabled = false;
            });
    }

    scheduleFollowupBtn.addEventListener('click', function() {
        if (!followupDate.value || !followupTime.value) {
            alert('Please select both date and time for the follow-up appointment.');
            return;
        }

        // Show confirmation alert before proceeding
        if (!confirm('Are you sure you want to schedule this follow-up appointment?')) {
            return;
        }

        const form = document.getElementById('scheduleFollowupForm');
        const formData = new FormData(form);

        // Disable the button while processing
        scheduleFollowupBtn.disabled = true;
        scheduleFollowupBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Scheduling...';

        fetch('schedule_followup.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the display in the card
                const nextFollowupInfo = document.getElementById('nextFollowupInfo');
                if (nextFollowupInfo) {
                    nextFollowupInfo.innerHTML = `
                        <i class="fas fa-calendar-alt me-2 text-primary"></i>
                        Scheduled for: <strong>${new Date(followupDate.value).toLocaleDateString('en-US', { 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric' 
                        })} at ${followupTime.value}</strong>
                    `;
                }
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('scheduleFollowupModal')).hide();
                // Show success message
                alert('Follow-up appointment scheduled successfully!');
                // Reload the page to update the Existing Records table and all UI
                location.reload();
            } else {
                alert(data.message || 'Error scheduling follow-up appointment');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error scheduling follow-up appointment');
        })
        .finally(() => {
            // Re-enable the button
            scheduleFollowupBtn.disabled = false;
            scheduleFollowupBtn.innerHTML = '<i class="fas fa-calendar-plus me-2"></i>Schedule Follow-up';
        });
    });

    // Add this new code to populate patient info when modal opens
    const scheduleFollowupModal = document.getElementById('scheduleFollowupModal');
    if (scheduleFollowupModal) {
        scheduleFollowupModal.addEventListener('show.bs.modal', function () {
            // Fetch patient info using the appointment_id
            fetch(`get_patient_info.php?appointment_id=<?= $appointment_id ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('modalPatientName').textContent = data.patient_name;
                        document.getElementById('modalCaseId').textContent = data.case_id || 'N/A';
                        document.getElementById('modalPatientId').value = data.patient_id;
                    } else {
                        document.getElementById('modalPatientName').textContent = 'Error loading patient info';
                        document.getElementById('modalCaseId').textContent = 'N/A';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('modalPatientName').textContent = 'Error loading patient info';
                    document.getElementById('modalCaseId').textContent = 'N/A';
                });
        });
    }
});
</script>

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