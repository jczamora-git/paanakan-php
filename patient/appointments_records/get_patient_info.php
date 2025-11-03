<?php
require_once '../connections/connections.php';
header('Content-Type: application/json');

$response = ['success' => false, 'patient_name' => '', 'case_id' => '', 'patient_id' => ''];

if (isset($_GET['appointment_id'])) {
    $appointment_id = intval($_GET['appointment_id']);
    
    try {
        $pdo = connection();
        $stmt = $pdo->prepare("
            SELECT p.first_name, p.last_name, p.case_id, p.patient_id 
            FROM patients p 
            JOIN appointments a ON p.patient_id = a.patient_id 
            WHERE a.appointment_id = ?
        ");
        $stmt->execute([$appointment_id]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($patient) {
            $response = [
                'success' => true,
                'patient_name' => $patient['first_name'] . ' ' . $patient['last_name'],
                'case_id' => $patient['case_id'],
                'patient_id' => $patient['patient_id']
            ];
        }
    } catch (PDOException $e) {
        $response['error'] = 'Database error: ' . $e->getMessage();
    }
}

echo json_encode($response); 