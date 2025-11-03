<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require '../connections/connections.php';
$con = connection();
$con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$category = isset($_GET['category']) ? $_GET['category'] : '';

if ($category) {
    $query = "SELECT service_id, service_name, price FROM medical_services WHERE category = :category ORDER BY service_name";
    $stmt = $con->prepare($query);
    $stmt->bindParam(':category', $category);
    $stmt->execute();
    $services = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    echo json_encode($services);
} else {
    header('Content-Type: application/json');
    echo json_encode([]);
}
?> 