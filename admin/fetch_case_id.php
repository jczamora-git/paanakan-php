<?php
// Include database connection
require '../connections/connections.php';

// Get the database connection
$pdo = connection();

// Check if the search term exists
if (isset($_GET['term'])) {
    $term = '%' . $_GET['term'] . '%'; // Add wildcards for partial matching

    // Query to fetch case_id and patient name
    $query = "SELECT case_id, CONCAT(first_name, ' ', last_name) AS patient_name
              FROM patients
              WHERE case_id LIKE :term OR first_name LIKE :term OR last_name LIKE :term
              LIMIT 10";  // Limit to 10 results for suggestion

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':term', $term, PDO::PARAM_STR);
    $stmt->execute();
    
    // Fetch results as an array
    $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare the suggestions in a format that will be used in the frontend
    $formattedSuggestions = [];
    foreach ($suggestions as $suggestion) {
        $formattedSuggestions[] = $suggestion['case_id'] . " (" . $suggestion['patient_name'] . ")";
    }
    
    // Return JSON response
    echo json_encode($formattedSuggestions);
}
?>
