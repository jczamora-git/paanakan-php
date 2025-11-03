<?php
require '../connections/connections.php';
$pdo = connection();

// Check for AJAX request action
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // 🔹 Fetch Case ID Suggestions (if needed)
    if ($action == 'fetch_cases' && isset($_GET['term'])) {
        $term = $_GET['term'];
        $query = "SELECT case_id, CONCAT(first_name, ' ', last_name) AS fullname 
                  FROM patients 
                  WHERE case_id LIKE :term OR first_name LIKE :term OR last_name LIKE :term 
                  LIMIT 10";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':term' => "%$term%"]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit();
    }

    // 🔹 Fetch Transaction ID Suggestions with Service Details and Patient Info
    if ($action == 'fetch_transactions' && isset($_GET['term'])) {
        $term = $_GET['term'];
        $query = "SELECT mt.transaction_id, s.service_name, s.price AS service_amount, mt.case_id, 
                         CONCAT(p.first_name, ' ', p.last_name) AS patient_name
                  FROM medical_transactions mt
                  JOIN medical_services s ON mt.service_id = s.service_id
                  JOIN patients p ON mt.case_id = p.case_id
                  WHERE payment_status = 'Pending' AND mt.transaction_id LIKE :term
                  LIMIT 10";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':term' => "%$term%"]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit();
    }

    // 🔄 Fetch Service Details Based on Transaction ID (if needed separately)
    if ($action == 'fetch_service' && isset($_GET['transaction_id'])) {
        $transaction_id = $_GET['transaction_id'];
        $query = "SELECT s.service_name, s.price AS service_amount 
                  FROM medical_transactions mt
                  JOIN medical_services s ON mt.service_id = s.service_id
                  WHERE mt.transaction_id = :transaction_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':transaction_id' => $transaction_id]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        exit();
    }

    // 📦 Fetch Inventory Items with Pagination
    if ($action == 'fetch_items') {
        $limit = 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;
        $search = isset($_GET['search']) ? $_GET['search'] : '';

        $query = "SELECT item_id, item_name, category, price 
                  FROM inventory 
                  WHERE item_name LIKE :search OR category LIKE :search 
                  ORDER BY item_name 
                  LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get Total Records Count for Pagination
        $countQuery = "SELECT COUNT(*) AS total FROM inventory WHERE item_name LIKE :search OR category LIKE :search";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute([':search' => "%$search%"]);
        $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($totalRecords / $limit);

        // Generate Pagination HTML
        $pagination = '<nav><ul class="pagination">';
        for ($i = 1; $i <= $totalPages; $i++) {
            $pagination .= '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                                <a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a>
                            </li>';
        }
        $pagination .= '</ul></nav>';

        echo json_encode(['items' => $items, 'pagination' => $pagination]);
        exit();
    }
}
?>
