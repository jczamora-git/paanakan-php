<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require '../connections/connections.php';
$con = connection();
$con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$transaction_id = isset($_POST['transaction_id']) ? $_POST['transaction_id'] : null;
$service_id = isset($_POST['service_id']) ? $_POST['service_id'] : null;

if (!$transaction_id || !$service_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

try {
    // Get the service price
    $priceQuery = "SELECT price FROM medical_services WHERE service_id = :service_id";
    $priceStmt = $con->prepare($priceQuery);
    $priceStmt->bindParam(':service_id', $service_id);
    $priceStmt->execute();
    $service = $priceStmt->fetch();
    
    if (!$service) {
        throw new Exception('Service not found');
    }

    // Update the transaction
    $updateQuery = "
        UPDATE medical_transactions 
        SET service_id = :service_id,
            amount = :amount
        WHERE transaction_id = :transaction_id
    ";
    
    $updateStmt = $con->prepare($updateQuery);
    $updateStmt->bindParam(':service_id', $service_id);
    $updateStmt->bindParam(':amount', $service['price']);
    $updateStmt->bindParam(':transaction_id', $transaction_id);
    
    if ($updateStmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update transaction']);
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 