<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

require '../connections/connections.php';
$pdo = connection();

// Fetch appointment details if ID is provided
if (isset($_GET['appointment_id'])) {
    $appointment_id = $_GET['appointment_id'];
    
    // Fetch appointment and patient details
    $query = "SELECT a.*, p.first_name, p.last_name, p.date_of_birth, p.gender,
              TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as age
              FROM appointments a 
              JOIN patients p ON a.patient_id = p.patient_id 
              WHERE a.appointment_id = :appointment_id";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':appointment_id', $appointment_id, PDO::PARAM_INT);
    $stmt->execute();
    $appointmentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch existing record if any
    $recordQuery = "SELECT * FROM appointment_records WHERE appointment_id = :appointment_id";
    $recordStmt = $pdo->prepare($recordQuery);
    $recordStmt->bindParam(':appointment_id', $appointment_id, PDO::PARAM_INT);
    $recordStmt->execute();
    $existingRecord = $recordStmt->fetch(PDO::FETCH_ASSOC);

    // Fetch all active staff
    $staffQuery = "SELECT id, staff_id, CONCAT(first_name, ' ', last_name) as full_name, role 
                  FROM staff 
                  WHERE status = 'Active' 
                  ORDER BY role, full_name";
    $staffStmt = $pdo->prepare($staffQuery);
    $staffStmt->execute();
    $staffList = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $pdo->beginTransaction();

        $vital_signs = [
            'blood_pressure' => $_POST['blood_pressure'],
            'temperature' => $_POST['temperature'],
            'pulse_rate' => $_POST['pulse_rate'],
            'respiratory_rate' => $_POST['respiratory_rate'],
            'weight' => $_POST['weight'],
            'height' => $_POST['height'],
            'bmi' => $_POST['weight'] / (($_POST['height']/100) * ($_POST['height']/100)), // Calculate BMI
            'oxygen_saturation' => $_POST['oxygen_saturation']
        ];

        $formData = [
            'appointment_id' => $_POST['appointment_id'],
            'case_id' => $_POST['case_id'],
            'appointment_type' => $_POST['appointment_type'],
            'vital_signs' => json_encode($vital_signs),
            'chief_complaint' => $_POST['chief_complaint'],
            'diagnosis' => $_POST['diagnosis'],
            'treatment_plan' => $_POST['treatment_plan'],
            'prescription' => $_POST['prescription'],
            'lab_requests' => $_POST['lab_requests'],
            'notes' => $_POST['notes'],
            'next_appointment' => !empty($_POST['next_appointment']) ? $_POST['next_appointment'] : null,
            'staff_id' => $_POST['staff_id']
        ];

        // Check if record exists
        $checkQuery = "SELECT id FROM appointment_records WHERE appointment_id = :appointment_id";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->bindParam(':appointment_id', $_POST['appointment_id']);
        $checkStmt->execute();
        $existingId = $checkStmt->fetchColumn();

        if ($existingId) {
            // Update existing record
            $sql = "UPDATE appointment_records SET 
                    vital_signs = :vital_signs,
                    chief_complaint = :chief_complaint,
                    diagnosis = :diagnosis,
                    treatment_plan = :treatment_plan,
                    prescription = :prescription,
                    lab_requests = :lab_requests,
                    notes = :notes,
                    next_appointment = :next_appointment,
                    staff_id = :staff_id
                    WHERE appointment_id = :appointment_id";
        } else {
            // Insert new record
            $sql = "INSERT INTO appointment_records 
                    (appointment_id, case_id, appointment_type, vital_signs, chief_complaint, 
                    diagnosis, treatment_plan, prescription, lab_requests, notes, 
                    next_appointment, staff_id)
                    VALUES 
                    (:appointment_id, :case_id, :appointment_type, :vital_signs, :chief_complaint,
                    :diagnosis, :treatment_plan, :prescription, :lab_requests, :notes,
                    :next_appointment, :staff_id)";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($formData);

        // Update appointment status to completed
        $updateAppointment = "UPDATE appointments SET status = 'Completed' WHERE appointment_id = :appointment_id";
        $updateStmt = $pdo->prepare($updateAppointment);
        $updateStmt->bindParam(':appointment_id', $_POST['appointment_id']);
        $updateStmt->execute();

        $pdo->commit();
        $_SESSION['message'] = "Appointment details have been saved successfully!";
        header("Location: appointments.php");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error: " . $e->getMessage());
        $_SESSION['error'] = "Error saving appointment details: " . $e->getMessage();
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}

$breadcrumb = array(
    'Appointments' => array('link' => 'appointments.php', 'icon' => 'fas fa-calendar-check'),
    'Appointment Details' => array('link' => '#', 'icon' => 'fas fa-notes-medical')
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .vital-signs-card {
            background-color: #f8f9fa;
            border-left: 4px solid #2E8B57;
        }
        .section-title {
            color: #2E8B57;
            border-bottom: 2px solid #2E8B57;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .form-label {
            font-weight: 500;
            color: #333;
        }
        .patient-info {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .patient-info h5 {
            color: #2E8B57;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
       <!-- <?php include('sidebar.php'); ?> -->

        <main class="dashboard-main-content">
          <!-- <?php include('breadcrumb.php'); ?> -->

            <div class="container">
                <?php if (isset($appointmentInfo)): ?>
                    <div class="patient-info">
                        <h5><i class="fas fa-user-circle me-2"></i>Patient Information</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <p><strong>Name:</strong><br> <?= htmlspecialchars($appointmentInfo['first_name'] . ' ' . $appointmentInfo['last_name']) ?></p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>Age:</strong><br> <?= htmlspecialchars($appointmentInfo['age']) ?> years</p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>Gender:</strong><br> <?= htmlspecialchars($appointmentInfo['gender']) ?></p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>Appointment Type:</strong><br> <?= htmlspecialchars($appointmentInfo['appointment_type']) ?></p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="appointment_id" value="<?= $appointmentInfo['appointment_id'] ?>">
                        <input type="hidden" name="case_id" value="<?= isset($appointmentInfo['case_id']) ? $appointmentInfo['case_id'] : $appointmentInfo['patient_id'] ?>">
                        <input type="hidden" name="appointment_type" value="<?= $appointmentInfo['appointment_type'] ?>">

                        <!-- Vital Signs Section -->
                        <div class="card vital-signs-card mb-4">
                            <div class="card-body">
                                <h5 class="section-title">Vital Signs</h5>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Blood Pressure (mmHg)</label>
                                        <input type="text" class="form-control" name="blood_pressure" placeholder="120/80" 
                                            value="<?= isset($existingRecord) ? json_decode($existingRecord['vital_signs'], true)['blood_pressure'] : '' ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Temperature (°C)</label>
                                        <input type="number" step="0.1" class="form-control" name="temperature" placeholder="36.8"
                                            value="<?= isset($existingRecord) ? json_decode($existingRecord['vital_signs'], true)['temperature'] : '' ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Pulse Rate (bpm)</label>
                                        <input type="number" class="form-control" name="pulse_rate" placeholder="75"
                                            value="<?= isset($existingRecord) ? json_decode($existingRecord['vital_signs'], true)['pulse_rate'] : '' ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Respiratory Rate</label>
                                        <input type="number" class="form-control" name="respiratory_rate" placeholder="16"
                                            value="<?= isset($existingRecord) ? json_decode($existingRecord['vital_signs'], true)['respiratory_rate'] : '' ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Weight (kg)</label>
                                        <input type="number" step="0.1" class="form-control" name="weight" placeholder="65.5"
                                            value="<?= isset($existingRecord) ? json_decode($existingRecord['vital_signs'], true)['weight'] : '' ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Height (cm)</label>
                                        <input type="number" step="0.1" class="form-control" name="height" placeholder="165"
                                            value="<?= isset($existingRecord) ? json_decode($existingRecord['vital_signs'], true)['height'] : '' ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Oxygen Saturation (%)</label>
                                        <input type="number" step="1" class="form-control" name="oxygen_saturation" placeholder="98"
                                            value="<?= isset($existingRecord) ? json_decode($existingRecord['vital_signs'], true)['oxygen_saturation'] : '' ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Medical Assessment -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="section-title">Medical Assessment</h5>
                                <div class="mb-3">
                                    <label class="form-label">Chief Complaint</label>
                                    <textarea class="form-control" name="chief_complaint" rows="3" required><?= isset($existingRecord) ? htmlspecialchars($existingRecord['chief_complaint']) : '' ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Diagnosis</label>
                                    <textarea class="form-control" name="diagnosis" rows="3" required><?= isset($existingRecord) ? htmlspecialchars($existingRecord['diagnosis']) : '' ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Treatment Plan</label>
                                    <textarea class="form-control" name="treatment_plan" rows="3"><?= isset($existingRecord) ? htmlspecialchars($existingRecord['treatment_plan']) : '' ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Prescription and Lab Requests -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="section-title">Prescription and Laboratory</h5>
                                <div class="mb-3">
                                    <label class="form-label">Prescription</label>
                                    <textarea class="form-control" name="prescription" rows="3"><?= isset($existingRecord) ? htmlspecialchars($existingRecord['prescription']) : '' ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Laboratory Requests</label>
                                    <textarea class="form-control" name="lab_requests" rows="3"><?= isset($existingRecord) ? htmlspecialchars($existingRecord['lab_requests']) : '' ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="section-title">Additional Information</h5>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea class="form-control" name="notes" rows="3"><?= isset($existingRecord) ? htmlspecialchars($existingRecord['notes']) : '' ?></textarea>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Next Appointment</label>
                                        <input type="date" class="form-control" name="next_appointment" 
                                            value="<?= isset($existingRecord) ? htmlspecialchars($existingRecord['next_appointment']) : '' ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Attending Staff</label>
                                        <select class="form-control" name="staff_id" required>
                                            <option value="">Select Staff</option>
                                            <?php foreach ($staffList as $staff): ?>
                                                <option value="<?= $staff['id'] ?>" 
                                                    <?= (isset($existingRecord) && $existingRecord['staff_id'] == $staff['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($staff['full_name']) ?> (<?= htmlspecialchars($staff['role']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4 mb-5">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-save me-2"></i>Save Appointment Details
                            </button>
                            <a href="appointments.php" class="btn btn-secondary btn-lg px-5 ms-2">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </a>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>No appointment information found.
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Calculate BMI automatically when weight or height changes
        document.querySelectorAll('input[name="weight"], input[name="height"]').forEach(input => {
            input.addEventListener('input', calculateBMI);
        });

        function calculateBMI() {
            const weight = document.querySelector('input[name="weight"]').value;
            const height = document.querySelector('input[name="height"]').value;
            
            if (weight && height) {
                const heightInMeters = height / 100;
                const bmi = weight / (heightInMeters * heightInMeters);
                // You could display this somewhere if needed
                console.log('BMI:', bmi.toFixed(1));
            }
        }
    </script>
</body>
</html> 