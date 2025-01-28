<?php
require 'connections/connections.php';

// Start the session
session_start();

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // Check if email exists in the database
    $query = "SELECT * FROM users WHERE email = :email";
    $stmt = $con->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    $user = $stmt->fetch();

    if ($user) {
        // Generate a reset token
        $resetToken = bin2hex(random_bytes(32));
        $resetTokenExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store the reset token in the database
        $updateQuery = "UPDATE users SET reset_token = :reset_token, reset_token_expiry = :reset_token_expiry WHERE email = :email";
        $updateStmt = $con->prepare($updateQuery);
        $updateStmt->bindParam(':reset_token', $resetToken);
        $updateStmt->bindParam(':reset_token_expiry', $resetTokenExpiry);
        $updateStmt->bindParam(':email', $email);

        if ($updateStmt->execute()) {
            // Display reset link for testing
            $resetLink = "http://localhost/reset_password.php?token=$resetToken";
            $successMessage = "Reset link: <a href=\"$resetLink\" target=\"_blank\">$resetLink</a>";
        } else {
            $errorMessage = 'Failed to generate reset link. Please try again.';
        }
    } else {
        $errorMessage = 'No account found with that email address.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <?php if ($errorMessage): ?>
            <div class="error-message">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="success-message">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST">
            <label for="email">Enter your email address:</label>
            <input type="email" id="email" name="email" required>
            <button type="submit">Submit</button>
        </form>
    </div>
</body>

</html>
