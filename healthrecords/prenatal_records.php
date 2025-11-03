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
$transaction_id = isset($_GET['transaction_id']) ? intval($_GET['transaction_id']) : null;
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : null;

if (!$transaction_id && !$patient_id) {
    $_SESSION['error'] = "Transaction ID or Patient ID is required.";
    header("Location: prenatal_records.php");
    exit();
}

// Get transaction and patient details
if ($transaction_id) {
    $query = "
        SELECT 
            mt.*,
            p.patient_id,
            CONCAT(p.first_name, ' ', p.last_name) AS fullname
        FROM medical_transactions mt
        JOIN patients p ON mt.case_id = p.case_id
        WHERE mt.transaction_id = ?
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        $_SESSION['error'] = "Transaction not found.";
        header("Location: prenatal_records.php");
        exit();
    }

    $patient_id = $transaction['patient_id'];
} else {
    // Get only patient info
    $query = "SELECT CONCAT(first_name, ' ', last_name) AS fullname FROM patients WHERE patient_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        $_SESSION['error'] = "Patient not found.";
        header("Location: prenatal_records.php");
        exit();
    }
}

// Fetch existing records for this patient
$existingRecords = [];
$stmt = $pdo->prepare("
    SELECT * FROM prenatal_records 
    WHERE patient_id = ? 
    ORDER BY visit_date DESC
");
$stmt->execute([$patient_id]);
$existingRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing record for this transaction
$existingRecord = null;
if ($transaction_id) {
    $stmt = $pdo->prepare("SELECT * FROM prenatal_records WHERE transaction_id = ? LIMIT 1");
    $stmt->execute([$transaction_id]);
    $existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch staff for attending physician dropdown
$physicians = [];
$stmt = $pdo->prepare("SELECT staff_id, first_name, last_name, role FROM staff WHERE role IN ('Doctor', 'Midwife') AND status = 'Active' ORDER BY first_name, last_name");
$stmt->execute();
$physicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing record for this transaction
$latestStatic = null;
if (!$existingRecord && $patient_id) {
    $stmt = $pdo->prepare("SELECT lmp, edc_by_lmp, edc_by_usg FROM prenatal_records WHERE patient_id = ? ORDER BY visit_date DESC LIMIT 1");
    $stmt->execute([$patient_id]);
    $latestStatic = $stmt->fetch(PDO::FETCH_ASSOC);
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
    } else {
        // Insert new record
        $query = "
            INSERT INTO prenatal_records (
                patient_id, transaction_id, visit_date, attending_physician,
                gravida, para, ob_score, lmp, aog_by_lmp, edc_by_lmp, aog_by_usg, edc_by_usg,
                blood_pressure, weight, temperature, respiratory_rate, fundal_height,
                fetal_heart_tones, internal_examination, chief_complaint,
                history_of_present_illness, past_medical_history, past_social_history,
                family_history, tt_dose, plan, lab_results
            ) VALUES (
                :patient_id, :transaction_id, :visit_date, :attending_physician,
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
            ':transaction_id' => $transaction_id,
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
    }

    try {
        $pdo->beginTransaction();
        $stmt->execute($params);
        $pdo->commit();
        $_SESSION['message'] = "Prenatal record " . ($existingRecord ? "updated" : "added") . " successfully.";
        
        // Redirect to the same page with the same parameters
        $redirect_url = "prenatal_records.php?";
        if ($transaction_id) {
            $redirect_url .= "transaction_id=" . $transaction_id;
        }
        if (isset($_GET['case_id'])) {
            $redirect_url .= ($transaction_id ? "&" : "") . "case_id=" . $_GET['case_id'];
        }
        if ($patient_id) {
            $redirect_url .= (($transaction_id || isset($_GET['case_id'])) ? "&" : "") . "patient_id=" . $patient_id;
        }
        header("Location: " . $redirect_url);
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to " . ($existingRecord ? "update" : "add") . " prenatal record: " . $e->getMessage();
        // Stay on the same page on error
        $redirect_url = "prenatal_records.php?";
        if ($transaction_id) {
            $redirect_url .= "transaction_id=" . $transaction_id;
        }
        if (isset($_GET['case_id'])) {
            $redirect_url .= ($transaction_id ? "&" : "") . "case_id=" . $_GET['case_id'];
        }
        if ($patient_id) {
            $redirect_url .= (($transaction_id || isset($_GET['case_id'])) ? "&" : "") . "patient_id=" . $patient_id;
        }
        header("Location: " . $redirect_url);
        exit();
    }
}

// Include the form template
include '../templates/prenatal_form.php';
?> 