<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require '../connections/connections.php';

// Get the database connection
$pdo = connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId = intval($_POST['patient_id']);
    $scheduledDate = $_POST['scheduled_date'];

    // Validate input
    if (empty($patientId) || empty($scheduledDate)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: manage_appointments.php");
        exit();
    }

    try {
        // Insert the new appointment into the database
        $query = "INSERT INTO Appointments (patient_id, scheduled_date, status) VALUES (:patient_id, :scheduled_date, 'Ongoing')";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':patient_id' => $patientId,
            ':scheduled_date' => $scheduledDate,
        ]);

        $_SESSION['message'] = "Appointment scheduled successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to schedule appointment: " . $e->getMessage();
    }

    header("Location: manage_appointments.php");
    exit();
}
?>
