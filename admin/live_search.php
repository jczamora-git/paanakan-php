<?php
require '../connections/connections.php';
$pdo = connection();

$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$type = isset($_POST['type']) ? $_POST['type'] : '';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$searchCondition = '';
$params = [];

if (!empty($search)) {
    $searchCondition = "AND (p.first_name LIKE :search OR p.last_name LIKE :search)";
    $params[':search'] = "%$search%";
}

// Query Type Based on Search
if ($type === "completed") {
    $stmt = $pdo->prepare("
        SELECT a.*, p.first_name, p.last_name, p.contact_number
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.status = 'Done' $searchCondition
        ORDER BY a.completed_date DESC
        LIMIT :limit OFFSET :offset
    ");
    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM appointments a JOIN patients p ON a.patient_id = p.patient_id WHERE a.status = 'Done' $searchCondition");
} elseif ($type === "missed") {
    $stmt = $pdo->prepare("
        SELECT a.*, p.first_name, p.last_name, p.contact_number
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.status = 'Missed' $searchCondition
        ORDER BY a.scheduled_date DESC
        LIMIT :limit OFFSET :offset
    ");
    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM appointments a JOIN patients p ON a.patient_id = p.patient_id WHERE a.status = 'Missed' $searchCondition");
} else {
    echo json_encode(["completed" => "", "missed" => "", "pagination" => ""]);
    exit;
}

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
    $totalStmt->bindValue($key, $value);
}

$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$totalStmt->execute();

$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalCount = $totalStmt->fetchColumn();
$totalPages = ceil($totalCount / $limit);

// Function to generate table rows
function generateRows($appointments, $isCompleted) {
    $output = '';
    if (!empty($appointments)) {
        foreach ($appointments as $index => $row) {
            $output .= "
                <tr>
                    <td>" . ($index + 1) . "</td>
                    <td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>
                    <td>" . htmlspecialchars($row['contact_number']) . "</td>
                    <td>" . htmlspecialchars($row['appointment_type']) . "</td>
                    <td>" . date("F j, Y g:i A", strtotime($row['scheduled_date'])) . "</td>
                    " . ($isCompleted ? "<td>" . date("F j, Y g:i A", strtotime($row['completed_date'])) . "</td>" : "") . "
                </tr>
            ";
        }
    } else {
        $output = '<tr><td colspan="' . ($isCompleted ? "6" : "5") . '" class="text-center">No results found.</td></tr>';
    }
    return $output;
}

// Generate pagination (only 3 pages at a time, no "..." buttons)
function generatePagination($totalPages, $currentPage, $type) {
    if ($totalPages <= 1) return "";

    $pagination = '<nav><ul class="pagination justify-content-center mt-3">';

    // First Page Button
    $pagination .= '<li class="page-item ' . ($currentPage == 1 ? 'disabled' : '') . '">
                        <a href="#" class="page-link pagination-link" data-page="1" data-type="'.$type.'">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    </li>';

    // Previous Page Button
    $pagination .= '<li class="page-item ' . ($currentPage == 1 ? 'disabled' : '') . '">
                        <a href="#" class="page-link pagination-link" data-page="'.($currentPage - 1).'" data-type="'.$type.'">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    </li>';

    // **Logic for Showing 3 Page Numbers Without "..."**
    $startPage = max(1, $currentPage - 1);
    $endPage = min($totalPages, $currentPage + 1);

    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = ($i == $currentPage) ? "active" : "";
        $pagination .= "<li class='page-item $active'>
                            <a href='#' class='page-link pagination-link' data-page='$i' data-type='$type'>$i</a>
                        </li>";
    }

    // Next Page Button
    $pagination .= '<li class="page-item ' . ($currentPage >= $totalPages ? 'disabled' : '') . '">
                        <a href="#" class="page-link pagination-link" data-page="'.($currentPage + 1).'" data-type="'.$type.'">
                            <i class="fas fa-angle-right"></i>
                        </a>
                    </li>';

    // Last Page Button
    $pagination .= '<li class="page-item ' . ($currentPage >= $totalPages ? 'disabled' : '') . '">
                        <a href="#" class="page-link pagination-link" data-page="'.$totalPages.'" data-type="'.$type.'">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </li>';

    $pagination .= '</ul></nav>';
    return $pagination;
}

// Response data
$response = [
    'completed' => generateRows($appointments, true),
    'missed' => generateRows($appointments, false),
    'pagination' => generatePagination($totalPages, $page, $type)
];

echo json_encode($response);

?>
