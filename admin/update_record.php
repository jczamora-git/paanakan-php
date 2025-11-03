<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
require '../connections/connections.php';
$con = connection();
$con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Get POST data
$record_id = isset($_POST['record_id']) ? $_POST['record_id'] : null;
$diagnosis = isset($_POST['diagnosis']) ? $_POST['diagnosis'] : '';
$results = isset($_POST['results']) ? $_POST['results'] : '';

// Validate input
if (!$record_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing record ID']);
    exit();
}

try {
    // Update the record
    $updateQuery = "
        UPDATE medical_transactions 
        SET diagnosis = :diagnosis,
            results = :results
        WHERE transaction_id = :record_id
    ";
    
    $updateStmt = $con->prepare($updateQuery);
    $updateStmt->bindParam(':diagnosis', $diagnosis);
    $updateStmt->bindParam(':results', $results);
    $updateStmt->bindParam(':record_id', $record_id);
    
    if ($updateStmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update record']);
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 