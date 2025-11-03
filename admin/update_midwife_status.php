<?php
require_once '../connections/connections.php';
header('Content-Type: application/json');

if (!isset($_POST['staff_id']) || !isset($_POST['attendance_status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$staff_id = intval($_POST['staff_id']);
$attendance_status = $_POST['attendance_status'];

// Validate allowed values
$allowed = ['Present', 'Absent', 'Not Set'];
if (!in_array($attendance_status, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $pdo = connection();
    $stmt = $pdo->prepare("UPDATE staff SET attendance_status = ? WHERE staff_id = ?");
    $stmt->execute([$attendance_status, $staff_id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No rows updated']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
