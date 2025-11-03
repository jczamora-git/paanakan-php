<?php
require '../connections/connections.php';
header('Content-Type: application/json');

$pdo = connection();
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$results = [];

if ($query !== '') {
    $sql = "SELECT case_id, first_name, last_name FROM patients WHERE case_id LIKE :q OR CONCAT(first_name, ' ', last_name) LIKE :q LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':q' => "%$query%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($results); 