<?php
// Include database connection
require '../connections/connections.php';

// Get the database connection
$pdo = connection();

// Check if search term exists
if (isset($_GET['term'])) {
    $term = '%' . $_GET['term'] . '%';

    // Query to fetch distinct supplier names from inventory table
    $query = "SELECT DISTINCT supplier FROM inventory WHERE supplier LIKE :term";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':term', $term, PDO::PARAM_STR);
    $stmt->execute();
    
    // Fetch suppliers as an array
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return JSON response
    echo json_encode($suppliers);
}
?>
