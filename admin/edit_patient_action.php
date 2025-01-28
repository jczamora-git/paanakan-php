<?php
// Start session and include database connection
session_start();
require '../connections/connections.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $patient_id = intval($_POST['patient_id']);
    $medical_history = trim($_POST['medical_history']); // Sanitize input

    // Ensure a valid patient ID is provided
    if (empty($patient_id)) {
        $_SESSION['error'] = "Invalid patient ID.";
        header("Location: manage_health_records.php");
        exit();
    }

    // Get the database connection
    $pdo = connection();

    // Prepare the query to update the medical history
    $query = "UPDATE patients SET medical_history = :medical_history WHERE patient_id = :patient_id";
    $stmt = $pdo->prepare($query);

    try {
        // Execute the query
        $stmt->execute([
            ':medical_history' => $medical_history,
            ':patient_id' => $patient_id,
        ]);

        // Set a success message
        $_SESSION['message'] = "Medical history updated successfully.";
    } catch (PDOException $e) {
        // Set an error message in case of failure
        $_SESSION['error'] = "Failed to update medical history: " . $e->getMessage();
    }

    // Redirect back to the health records management page
    header("Location: manage_health_records.php");
    exit();
} else {
    // If accessed directly, redirect back to the health records page
    $_SESSION['error'] = "Invalid request method.";
    header("Location: manage_health_records.php");
    exit();
}
