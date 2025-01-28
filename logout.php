<?php
// Start the session
session_start();

// Include database connection
require 'connections/connections.php';
$con = connection();
$con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Function to log user actions
function log_action($con, $userId, $action) {
    $logQuery = "INSERT INTO logs (user_id, action) VALUES (:user_id, :action)";
    $logStmt = $con->prepare($logQuery);
    $logStmt->execute([
        ':user_id' => $userId,
        ':action' => $action,
    ]);
}

// Check if a user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    // Log the logout action based on the user's role
    if ($role === 'Admin') {
        log_action($con, $userId, 'Admin Logged Out');
    } else {
        log_action($con, $userId, 'User Logged Out');
    }
}

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to the login page
header("Location: login.php");
exit();
?>
