<?php
// Include database connection
require 'connections/connections.php';
require_once 'connections/EmailService.php';
$pdo = connection(); // Use the correct database connection function

header('Content-Type: application/json');

// Check if 'dateTime', 'patient_id', and 'appointment_type' are set
if (isset($_POST['dateTime']) && isset($_POST['patient_id']) && isset($_POST['appointment_type'])) {
    // Get the date and time value sent from the AJAX request
    $dateTime = trim($_POST['dateTime']);

    // Get the patient_id and appointment_type
    $patient_id = intval($_POST['patient_id']);
    $appointment_type = trim($_POST['appointment_type']);

    // Normalize incoming datetime
    $fullDateTime = date("Y-m-d H:i:s", strtotime($dateTime));

    // Prepare the SQL statement to insert the new appointment using PDO
    $sql = "INSERT INTO appointments (patient_id, appointment_type, scheduled_date, status) 
            VALUES (:patient_id, :appointment_type, :scheduled_date, :status)";

    try {
        // Prepare the statement
        $stmt = $pdo->prepare($sql);

        // Default status is 'pending'
        $status = 'pending';

        // Bind the parameters
        $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
        $stmt->bindParam(':appointment_type', $appointment_type, PDO::PARAM_STR);
        $stmt->bindParam(':scheduled_date', $fullDateTime, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);

        // Execute the query
        if ($stmt->execute()) {
            $appointment_id = $pdo->lastInsertId();

            // Fetch patient info to get email and name
            $stmt2 = $pdo->prepare("SELECT p.first_name AS p_first, p.last_name AS p_last, p.email AS p_email, p.case_id, u.first_name AS u_first, u.last_name AS u_last, u.email AS u_email
                FROM patients p
                LEFT JOIN users u ON p.user_id = u.user_id
                WHERE p.patient_id = ? LIMIT 1");
            $stmt2->execute([$patient_id]);
            $row = $stmt2->fetch(PDO::FETCH_ASSOC);

            $to_email = $row['p_email'] ?: $row['u_email'] ?: null;
            $to_name = trim(($row['p_first'] ?: $row['u_first']) . ' ' . ($row['p_last'] ?: $row['u_last']));
            $case_id = $row['case_id'] ?? '';

            // Build appointment data for email
            $timestamp = strtotime($fullDateTime);
            $scheduled_date = $timestamp ? date('F j, Y', $timestamp) : $fullDateTime;
            $time = $timestamp ? date('g:i A', $timestamp) : '';

            $appointment_data = [
                'scheduled_date' => $scheduled_date,
                'time' => $time,
                'appointment_type' => $appointment_type,
                'location' => 'Paanakan sa Calapan Clinic',
                'case_id' => $case_id,
                'doctor' => null,
                'instructions' => 'Please arrive 10 minutes before your scheduled time.'
            ];

            $email_result = null;
            if ($to_email) {
                try {
                    $emailService = new EmailService();
                    $email_result = $emailService->sendAppointmentScheduled($to_email, $to_name ?: 'Patient', $appointment_data);
                } catch (Exception $e) {
                    $email_result = ['success' => false, 'message' => 'Email send error: ' . $e->getMessage()];
                }
            } else {
                $email_result = ['success' => false, 'message' => 'No patient email on record.'];
            }

            // Return structured JSON so the front-end can decide what to show
            echo json_encode([
                'success' => true,
                'status' => $status,
                'message' => 'Appointment created',
                'appointment_id' => $appointment_id,
                'email' => $email_result
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Unable to create appointment.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing parameters: dateTime, patient_id or appointment_type']);
}
?>
