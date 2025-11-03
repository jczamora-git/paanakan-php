<?php
require '../connections/connections.php';
$pdo = connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'], $_POST['status'])) {
    $appointmentId = (int) $_POST['appointment_id'];
    $status = $_POST['status'];

    // Ensure status is one of the allowed ENUM values
    $allowedStatuses = ['Done', 'Ongoing', 'Missed', 'Approved', 'Pending', 'Disapproved'];
    if (!in_array($status, $allowedStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    // Update the appointment status
    $stmt = $pdo->prepare("UPDATE appointments SET status = :status WHERE appointment_id = :appointment_id");
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    $stmt->bindParam(':appointment_id', $appointmentId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $email_result = null;
        // If approved, send confirmation email to patient
        if ($status === 'Approved') {
            try {
                // Fetch appointment + patient info
                $q = $pdo->prepare("SELECT a.scheduled_date, a.appointment_type, p.first_name AS p_first, p.last_name AS p_last, p.email AS p_email, p.case_id, u.first_name AS u_first, u.last_name AS u_last, u.email AS u_email
                    FROM appointments a
                    JOIN patients p ON a.patient_id = p.patient_id
                    LEFT JOIN users u ON p.user_id = u.user_id
                    WHERE a.appointment_id = ? LIMIT 1");
                $q->execute([$appointmentId]);
                $row = $q->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $to_email = $row['p_email'] ?: $row['u_email'] ?: null;
                    $to_name = trim(($row['p_first'] ?: $row['u_first']) . ' ' . ($row['p_last'] ?: $row['u_last']));

                    if ($to_email) {
                        $timestamp = strtotime($row['scheduled_date']);
                        $scheduled_date = $timestamp ? date('F j, Y', $timestamp) : $row['scheduled_date'];
                        $time = $timestamp ? date('g:i A', $timestamp) : '';

                        $appointment_details = [
                            'scheduled_date' => $scheduled_date,
                            'time' => $time,
                            'appointment_type' => $row['appointment_type'] ?? 'Appointment',
                            'location' => 'Paanakan sa Calapan Clinic',
                            'case_id' => $row['case_id'] ?? '',
                            'doctor' => null,
                            'instructions' => 'Please arrive 10 minutes before your scheduled time.'
                        ];

                        require_once __DIR__ . '/../connections/EmailService.php';
                        $emailService = new EmailService();
                        $email_result = $emailService->sendAppointmentConfirmation($to_email, $to_name ?: 'Patient', $appointment_details);
                    } else {
                        $email_result = ['success' => false, 'message' => 'No patient email on record'];
                    }
                }
            } catch (Exception $e) {
                $email_result = ['success' => false, 'message' => 'Email error: ' . $e->getMessage()];
            }
        }

        echo json_encode(['success' => true, 'email' => $email_result]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>
