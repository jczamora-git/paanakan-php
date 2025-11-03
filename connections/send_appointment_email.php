<?php
// Endpoint: connections/send_appointment_email.php
// Expects POST: patient_id, appointment_type, dateTime

require_once __DIR__ . '/connections.php';
require_once __DIR__ . '/EmailService.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
$appointment_type = isset($_POST['appointment_type']) ? trim($_POST['appointment_type']) : '';
$dateTime = isset($_POST['dateTime']) ? trim($_POST['dateTime']) : '';

if (!$patient_id || !$dateTime) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

try {
    $pdo = connection();
    // Fetch patient and linked user info
    $stmt = $pdo->prepare("SELECT p.first_name AS p_first, p.last_name AS p_last, p.email AS p_email, p.case_id, u.first_name AS u_first, u.last_name AS u_last, u.email AS u_email
        FROM patients p
        LEFT JOIN users u ON p.user_id = u.user_id
        WHERE p.patient_id = ? LIMIT 1");
    $stmt->execute([$patient_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Patient not found']);
        exit;
    }

    // Determine recipient email and name
    $to_email = $row['p_email'] ?: $row['u_email'] ?: null;
    $to_name = trim(($row['p_first'] ?: $row['u_first']) . ' ' . ($row['p_last'] ?: $row['u_last']));
    $case_id = $row['case_id'] ?? '';

    if (!$to_email) {
        echo json_encode(['success' => false, 'message' => 'No email on file for patient']);
        exit;
    }

    // Parse date/time
    $timestamp = strtotime($dateTime);
    $scheduled_date = $timestamp ? date('F j, Y', $timestamp) : $dateTime;
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

    $emailService = new EmailService();
    $res = $emailService->sendAppointmentScheduled($to_email, $to_name ?: 'Patient', $appointment_data);

    echo json_encode($res);
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

?>
