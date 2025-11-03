<?php
// Start session and check if the user is logged in
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once '../connections/connections.php';
require_once '../activity_log.php';

header('Content-Type: application/json');

// Get database connection
$pdo = connection();
$activityLog = new ActivityLog($pdo);

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and validate required fields
$patient_id = filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT);
$scheduled_date = filter_input(INPUT_POST, 'scheduled_date', FILTER_SANITIZE_STRING);
$scheduled_time = filter_input(INPUT_POST, 'scheduled_time', FILTER_SANITIZE_STRING);
$current_appointment_id = filter_input(INPUT_POST, 'current_appointment_id', FILTER_VALIDATE_INT);

// Validate all required fields
if (!$patient_id || !$scheduled_date || !$scheduled_time) {
    echo json_encode([
        'success' => false, 
        'message' => 'Missing required fields',
        'debug' => [
            'patient_id' => $patient_id,
            'scheduled_date' => $scheduled_date,
            'scheduled_time' => $scheduled_time
        ]
    ]);
    exit;
}

try {
    // Validate date is not in the past and not a weekend
    $selected_date = new DateTime($scheduled_date);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($selected_date < $today) {
        echo json_encode(['success' => false, 'message' => 'Cannot schedule appointments in the past']);
        exit;
    }
    
    $day_of_week = $selected_date->format('N'); // 1 (Monday) to 7 (Sunday)
    if ($day_of_week >= 6) { // 6 is Saturday, 7 is Sunday
        echo json_encode(['success' => false, 'message' => 'Cannot schedule appointments on weekends']);
        exit;
    }

    // Combine date and time into proper datetime format
    $scheduled_datetime = date('Y-m-d H:i:s', strtotime($scheduled_date . ' ' . $scheduled_time));

    // Verify patient exists
    $stmt = $pdo->prepare("SELECT patient_id, case_id FROM patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        echo json_encode(['success' => false, 'message' => 'Patient not found']);
        exit;
    }

    // Check if the time slot is available
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM appointments 
        WHERE scheduled_date = ? 
        AND status != 'Cancelled' 
        AND appointment_id != ?
    ");
    $stmt->execute([$scheduled_datetime, $current_appointment_id ?? 0]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'This time slot is already booked']);
        exit;
    }

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Insert the follow-up appointment
        $stmt = $pdo->prepare("
            INSERT INTO appointments 
            (patient_id, scheduled_date, status, appointment_type, created_at) 
            VALUES (?, ?, 'Approved', 'Follow-up', NOW())
        ");
        $stmt->execute([$patient_id, $scheduled_datetime]);
        $new_appointment_id = $pdo->lastInsertId();

        // Update the follow-up record if we have a current appointment
        if ($current_appointment_id) {
            // First check if a follow-up record exists
            $stmt = $pdo->prepare("SELECT record_id FROM follow_up_records WHERE appointment_id = ?");
            $stmt->execute([$current_appointment_id]);
            $existing_record = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_record) {
                // Update existing record
                $stmt = $pdo->prepare("
                    UPDATE follow_up_records 
                    SET next_followup_date = ? 
                    WHERE appointment_id = ?
                ");
                $stmt->execute([$scheduled_date, $current_appointment_id]);
            } else {
                // Create new follow-up record
                $stmt = $pdo->prepare("
                    INSERT INTO follow_up_records 
                    (appointment_id, next_followup_date, created_at) 
                    VALUES (?, ?, NOW())
                ");
                $stmt->execute([$current_appointment_id, $scheduled_date]);
            }
        }

        // Log the activity
        $user_id = $_SESSION['user_id'];
        $userQuery = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS user_name FROM users WHERE user_id = :user_id");
        $userQuery->execute([':user_id' => $user_id]);
        $userRow = $userQuery->fetch();
        $user_name = $userRow ? $userRow['user_name'] : 'Unknown User';

        $patientQuery = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS patient_name, case_id FROM patients WHERE patient_id = ?");
        $patientQuery->execute([$patient_id]);
        $patientRow = $patientQuery->fetch();
        $patient_name = $patientRow ? $patientRow['patient_name'] : 'Unknown Patient';
        $case_id = $patientRow ? $patientRow['case_id'] : 'Unknown';

        $formattedDate = date('F j, Y', strtotime($scheduled_date));
        $formattedTime = date('g:i A', strtotime($scheduled_time));
        $action_desc = $user_name . " scheduled follow-up appointment for " . $patient_name . " (" . $case_id . 
                      ") on " . $formattedDate . " at " . $formattedTime;
        $activityLog->logActivity($user_id, $action_desc);

        // Commit transaction
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Follow-up appointment scheduled successfully',
            'data' => [
                'appointment_id' => $new_appointment_id,
                'scheduled_date' => $scheduled_date,
                'scheduled_time' => $scheduled_time,
                'patient_id' => $patient_id,
                'case_id' => $patient['case_id']
            ]
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error in schedule_followup.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error scheduling follow-up appointment',
        'debug' => $e->getMessage()
    ]);
} 