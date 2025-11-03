<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require '../connections/connections.php';
$pdo = connection();

try {
    if (!isset($_GET['search'])) {
        throw new Exception('Search term is required');
    }

    $searchTerm = $_GET['search'];

    // Query to search transactions by ID or patient name
    $query = "SELECT 
                mt.transaction_id,
                mt.case_id,
                CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                ms.service_name,
                ms.service_amount
              FROM medical_transactions mt
              JOIN patients p ON mt.case_id = p.case_id
              JOIN medical_services ms ON mt.service_id = ms.service_id
              WHERE (mt.transaction_id LIKE :search 
                    OR CONCAT(p.first_name, ' ', p.last_name) LIKE :search_name)
              AND mt.payment_status = 'Pending'
              ORDER BY mt.transaction_date DESC
              LIMIT 10";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':search' => "%$searchTerm%",
        ':search_name' => "%$searchTerm%"
    ]);
    
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($transactions);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?> 