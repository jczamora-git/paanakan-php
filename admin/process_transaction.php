<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require '../connections/connections.php';
require '../activity_log.php';

$con = connection();
$con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Create an instance of the ActivityLog class
$activityLog = new ActivityLog($con);

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the POST data
    $case_id = $_POST['case_id'];
    $transaction_date = $_POST['transaction_date'];
    $amount = $_POST['amount'];
    $payment_status = $_POST['payment_status'];
    $service_id = $_POST['service_id'];

    // Validate the data (basic validation)
    if (empty($case_id) || empty($transaction_date) || empty($amount) || empty($payment_status) || empty($service_id)) {
        // Redirect back to the form with an error message
        $_SESSION['error'] = 'All fields are required.';
        header('Location: transactions.php');
        exit();
    }

    try {
        // Insert transaction data into the database
        $stmt = $con->prepare("INSERT INTO medical_transactions (case_id, transaction_date,amount, payment_status, service_id) 
                              VALUES (:case_id, :transaction_date,  :amount, :payment_status, :service_id)");

        $stmt->bindParam(':case_id', $case_id);
        $stmt->bindParam(':transaction_date', $transaction_date);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':payment_status', $payment_status);
        $stmt->bindParam(':service_id', $service_id);

        // Execute the query
        $stmt->execute();

        // Get user's full name for logging
        $userQuery = $con->prepare("SELECT CONCAT(first_name, ' ', last_name) AS user_name FROM users WHERE user_id = :user_id");
        $userQuery->execute([':user_id' => $_SESSION['user_id']]);
        $userRow = $userQuery->fetch();
        $user_name = $userRow ? $userRow['user_name'] : 'Unknown User';

        // Get patient name for logging
        $patientQuery = $con->prepare("SELECT CONCAT(first_name, ' ', last_name) AS patient_name FROM patients WHERE case_id = :case_id");
        $patientQuery->execute([':case_id' => $case_id]);
        $patientRow = $patientQuery->fetch();
        $patient_name = $patientRow ? $patientRow['patient_name'] : 'Unknown Patient';

        // Get the service name from the database based on the service_id
        $serviceQuery = $con->prepare("SELECT service_name FROM medical_services WHERE service_id = :service_id");
        $serviceQuery->bindParam(':service_id', $service_id);
        $serviceQuery->execute();
        $service = $serviceQuery->fetch(PDO::FETCH_ASSOC);

        // Create a more detailed activity log description with user's full name and patient's name
        $action_desc = $user_name . " created transaction for " . $patient_name . " (" . $case_id . ") – " . 
                      htmlspecialchars($service['service_name']) . ", ₱" . number_format($amount, 2) . 
                      ", Payment: " . htmlspecialchars($payment_status);

        $activityLog->logActivity($_SESSION['user_id'], $action_desc);

        // Redirect back to the transactions page with a success message
        $_SESSION['success'] = 'Transaction successfully recorded!';
        header('Location: transactions.php');
        exit();
    } catch (PDOException $e) {
        // Handle any errors during the insertion
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
        header('Location: transactions.php');
        exit();
    }
}
?>
