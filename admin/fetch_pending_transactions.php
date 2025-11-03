<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require '../connections/connections.php';
$pdo = connection();
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

try {
    // Get pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8;
    $offset = ($page - 1) * $limit;

    // Get total count of pending transactions
    $countQuery = "SELECT COUNT(*) FROM medical_transactions WHERE payment_status = 'Pending'";
    $totalRecords = $pdo->query($countQuery)->fetchColumn();

    // Fetch pending transactions with pagination
    $query = "
        SELECT 
            t.transaction_id,
            t.case_id,
            t.transaction_date,
            t.amount,
            s.service_name,
            CONCAT(p.first_name, ' ', p.last_name) as patient_name
        FROM medical_transactions t
        LEFT JOIN medical_services s ON t.service_id = s.service_id
        LEFT JOIN patients p ON t.case_id = p.case_id
        WHERE t.payment_status = 'Pending'
        ORDER BY t.transaction_date DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $transactions = $stmt->fetchAll();

    // Format the transaction date
    foreach ($transactions as &$transaction) {
        $date = new DateTime($transaction['transaction_date']);
        $transaction['transaction_date'] = $date->format('M d, Y h:i A');
    }

    // Format the response
    $response = [
        'records' => $transactions,
        'total' => $totalRecords,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($totalRecords / $limit)
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?> 