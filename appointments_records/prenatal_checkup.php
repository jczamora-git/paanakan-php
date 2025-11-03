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
    SELECT * FROM prenatal_records 
    WHERE patient_id = ? 
    ORDER BY visit_date DESC
");
$stmt->execute([$patient_id]);
$existingRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing record for this appointment
$existingRecord = null;
$stmt = $pdo->prepare("SELECT * FROM prenatal_records WHERE appointment_id = ? LIMIT 1");
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

    try {
        $pdo->beginTransaction();
        
        if ($existingRecord) {
            // Update existing record
            $query = "
                UPDATE prenatal_records SET 
                    visit_date = :visit_date,
                    attending_physician = :attending_physician,
                    gravida = :gravida,
                    para = :para,
                    ob_score = :ob_score,
                    lmp = :lmp,
                    aog_by_lmp = :aog_by_lmp,
                    edc_by_lmp = :edc_by_lmp,
                    aog_by_usg = :aog_by_usg,
                    edc_by_usg = :edc_by_usg,
                    blood_pressure = :blood_pressure,
                    weight = :weight,
                    temperature = :temperature,
                    respiratory_rate = :respiratory_rate,
                    fundal_height = :fundal_height,
                    fetal_heart_tones = :fetal_heart_tones,
                    internal_examination = :internal_examination,
                    chief_complaint = :chief_complaint,
                    history_of_present_illness = :history_of_present_illness,
                    past_medical_history = :past_medical_history,
                    past_social_history = :past_social_history,
                    family_history = :family_history,
                    tt_dose = :tt_dose,
                    plan = :plan,
                    lab_results = :lab_results
                WHERE record_id = :record_id
            ";
            $stmt = $pdo->prepare($query);
            $params = [
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
                ':record_id' => $existingRecord['record_id']
            ];
            $stmt->execute($params);
        } else {
            // Insert new record
            $query = "
                INSERT INTO prenatal_records (
                    patient_id, appointment_id, visit_date, attending_physician,
                    gravida, para, ob_score, lmp, aog_by_lmp, edc_by_lmp, aog_by_usg, edc_by_usg,
                    blood_pressure, weight, temperature, respiratory_rate, fundal_height,
                    fetal_heart_tones, internal_examination, chief_complaint,
                    history_of_present_illness, past_medical_history, past_social_history,
                    family_history, tt_dose, plan, lab_results
                ) VALUES (
                    :patient_id, :appointment_id, :visit_date, :attending_physician,
                    :gravida, :para, :ob_score, :lmp, :aog_by_lmp, :edc_by_lmp, :aog_by_usg, :edc_by_usg,
                    :blood_pressure, :weight, :temperature, :respiratory_rate, :fundal_height,
                    :fetal_heart_tones, :internal_examination, :chief_complaint,
                    :history_of_present_illness, :past_medical_history, :past_social_history,
                    :family_history, :tt_dose, :plan, :lab_results
                )
            ";
            $stmt = $pdo->prepare($query);
            $params = [
                ':patient_id' => $patient_id,
                ':appointment_id' => $appointment_id,
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
                ':lab_results' => $lab_results
            ];
            $stmt->execute($params);
        }
        
        // Check if complete button was pressed and confirmed (via hidden input confirm_complete)
        if ($complete_appointment && isset($_POST['confirm_complete']) && $_POST['confirm_complete'] === '1') {
            $updateAppointment = "UPDATE appointments SET status = 'Done' WHERE appointment_id = ?";
            $updateStmt = $pdo->prepare($updateAppointment);
            $updateStmt->execute([$appointment_id]);
        }
        
        $pdo->commit();
        $_SESSION['toast_message'] = 'Prenatal record ' . ($existingRecord ? 'updated' : 'added') . ' successfully.';
        $_SESSION['toast_type'] = 'success';
        if ($complete_appointment) {
            $_SESSION['toast_message'] .= ' Appointment marked as done.';
        }
        header("Location: /paanakan/appointments_records/prenatal_checkup.php?appointment_id=" . $appointment_id);
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['toast_message'] = 'Failed to ' . ($existingRecord ? 'update' : 'add') . ' prenatal record: ' . $e->getMessage();
        $_SESSION['toast_type'] = 'error';
        header("Location: /paanakan/appointments_records/prenatal_checkup.php?appointment_id=" . $appointment_id);
        exit();
    }
}

// Include the form template
include '../templates/prenatal_form.php';
?> 