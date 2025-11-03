<?php
require 'connections/connections.php';
$con = connection();
header('Content-Type: application/json');

$case_id = isset($_POST['case_id']) ? trim($_POST['case_id']) : '';
if ($case_id === '') {
    echo json_encode(['status' => 'error', 'message' => 'No case_id provided']);
    exit;
}

try {
    $stmt = $con->prepare("SELECT patient_id, user_id FROM patients WHERE case_id = ? LIMIT 1");
    $stmt->execute([$case_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        if (!empty($row['user_id'])) {
            echo json_encode(['status' => 'linked', 'message' => 'Case ID is already linked to an account.']);
        } else {
            echo json_encode(['status' => 'unlinked']);
        }
    } else {
        echo json_encode(['status' => 'not_found']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

?>
