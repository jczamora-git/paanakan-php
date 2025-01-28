<?php
require 'connections/connections.php';
$con = connection();
$con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Query to fetch user details by username or email
    $query = "SELECT user_id, password, username, role FROM users WHERE username = :username OR email = :username";
    $stmt = $con->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    $user = $stmt->fetch();

    // Function to log user actions
    function log_action($con, $userId, $action) {
        $logQuery = "INSERT INTO logs (user_id, action) VALUES (:user_id, :action)";
        $logStmt = $con->prepare($logQuery);
        $logStmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
        ]);
    }

    // Check if user exists
    if ($user) {
        // Log the user ID (even for failed password attempts)
        $userId = $user['user_id'];

        // Check if the password is correct
        if (password_verify($password, $user['password'])) {
            // Start the session
            session_start();
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role']; // Ensure 'role' is set properly

            // Log successful login
            log_action($con, $userId, 'Login Successful');

            // Redirect based on the user role
            if ($user['role'] == 'Admin') {
                header("Location: admin/dashboard.php");
            } elseif ($user['role'] == 'Midwife') {
                header("Location: midwife/dashboard.php");
            } elseif ($user['role'] == 'Patient') {
                header("Location: patient/dashboard.php");
            } else {
                // If role is not recognized
                log_action($con, $userId, 'Login Failed: Invalid Role');
                $errorMessage = "Invalid role.";
            }
            exit();
        } else {
            // Log failed login with user ID
            log_action($con, $userId, 'Login Failed: Invalid Password');
            $errorMessage = "Invalid username or password.";
        }
    } else {
        // Log failed login without user ID
        log_action($con, null, 'Login Failed: Username/Email Not Found');
        $errorMessage = "Invalid username or password.";
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paanakan | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">

</head>

<body>

    <div class="container d-flex justify-content-center align-items-center min-vh-300 ">
        <div class="login-container">
            <div class="logo mb-4">
                <img src="PSC.png" alt="Paanakan Logo"> <!-- Replace with your logo path -->
            </div>

            <h2 class="title">Paanakan sa Calapan <span>Health Record Management System</span></h2>
            <?php if (isset($errorMessage)): ?>
                <div class="alert alert-danger">
                    <?php echo $errorMessage; ?>
                </div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <!-- Username Input -->
                <div class="input-container mb-3">
                    <i class="material-icons">person</i>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username/Email" required>
                </div>

                <!-- Password Input -->
                <div class="input-container mb-3">
                    <i class="material-icons">lock</i>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <button class="show-hide-btn" type="button" id="toggle-password">
                        <i class="material-icons" id="eye-icon">visibility_off</i>
                    </button>
                </div>

                <button type="submit" class="custom-btn">Login</button>
                
                <div class="small-text mt-3">
                    <p>Do you have an account? <a href="register.php">Create Account</a></p>
                    <p><a href="forgot_password.php">Forgot Password?</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/login.js"></script>
</body>

</html>
