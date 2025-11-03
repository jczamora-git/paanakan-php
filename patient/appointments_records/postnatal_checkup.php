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

// Initialize variables
$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : null;

if (!$appointment_id) {
    $_SESSION['error'] = "Appointment ID is required.";
    header("Location: /paanakan/appointments_records/manage_appointments.php");
    exit();
}

// Get appointment and patient details
$query = "
    SELECT 
        a.*,
        p.patient_id,
        CONCAT(p.first_name, ' ', p.last_name) AS fullname
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.appointment_id = ?
";
$stmt = $pdo->prepare($query);
$stmt->execute([$appointment_id]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    $_SESSION['error'] = "Appointment not found.";
    header("Location: /paanakan/appointments_records/manage_appointments.php");
    exit();
}

$patient_id = $appointment['patient_id'];

// Fetch existing records for this patient
$existingRecords = [];
$stmt = $pdo->prepare("
    SELECT * FROM postnatal_records 
    WHERE patient_id = ? 
    ORDER BY visit_date DESC
");
$stmt->execute([$patient_id]);
$existingRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing record for this appointment
$existingRecord = null;
$stmt = $pdo->prepare("SELECT * FROM postnatal_records WHERE appointment_id = ? LIMIT 1");
$stmt->execute([$appointment_id]);
$existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch staff for attending physician dropdown
$physicians = [];
$stmt = $pdo->prepare("SELECT staff_id, first_name, last_name, role FROM staff WHERE role IN ('Doctor', 'Midwife') AND status = 'Active' ORDER BY first_name, last_name");
$stmt->execute();
$physicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if complete appointment button was pressed
    $complete_appointment = isset($_POST['complete_appointment']) ? true : false;

    // Prepare data
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

    if ($existingRecord) {
        // Update existing record
        $query = "
            UPDATE postnatal_records SET 
                visit_date = :visit_date,
                attending_physician = :attending_physician,
                delivery_date = :delivery_date,
                delivery_type = :delivery_type,
                birth_weight = :birth_weight,
                birth_length = :birth_length,
                apgar_score = :apgar_score,
                maternal_complications = :maternal_complications,
                neonatal_complications = :neonatal_complications,
                breastfeeding_initiated = :breastfeeding_initiated,
                postpartum_bleeding = :postpartum_bleeding,
                uterine_involution = :uterine_involution,
                perineal_healing = :perineal_healing,
                contraceptive_counseling = :contraceptive_counseling,
                remarks = :remarks
            WHERE record_id = :record_id
        ";
        $stmt = $pdo->prepare($query);
        $params = [
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
            ':record_id' => $existingRecord['record_id']
        ];
    } else {
        // Insert new record
        $query = "
            INSERT INTO postnatal_records (
                patient_id, appointment_id, visit_date, attending_physician,
                delivery_date, delivery_type, birth_weight, birth_length, apgar_score,
                maternal_complications, neonatal_complications, breastfeeding_initiated,
                postpartum_bleeding, uterine_involution, perineal_healing,
                contraceptive_counseling, remarks
            ) VALUES (
                :patient_id, :appointment_id, :visit_date, :attending_physician,
                :delivery_date, :delivery_type, :birth_weight, :birth_length, :apgar_score,
                :maternal_complications, :neonatal_complications, :breastfeeding_initiated,
                :postpartum_bleeding, :uterine_involution, :perineal_healing,
                :contraceptive_counseling, :remarks
            )
        ";
        $stmt = $pdo->prepare($query);
        $params = [
            ':patient_id' => $patient_id,
            ':appointment_id' => $appointment_id,
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
            ':remarks' => $remarks
        ];
    }

    try {
        $pdo->beginTransaction();
        
        // Execute the postnatal record query
        $stmt->execute($params);
        
        // If complete button was pressed, update appointment status
        if ($complete_appointment) {
            $updateAppointment = "UPDATE appointments SET status = 'Done' WHERE appointment_id = ?";
            $updateStmt = $pdo->prepare($updateAppointment);
            $updateStmt->execute([$appointment_id]);
        }
        
        $pdo->commit();
        $_SESSION['message'] = "Postnatal record " . ($existingRecord ? "updated" : "added") . " successfully.";
        if ($complete_appointment) {
            $_SESSION['message'] .= " Appointment marked as done.";
        }
        header("Location: /paanakan/appointments_records/postnatal_checkup.php?appointment_id=" . $appointment_id);
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to " . ($existingRecord ? "update" : "add") . " postnatal record: " . $e->getMessage();
    }
}

// Include the form template
include '../templates/postnatal_form.php';
?> 