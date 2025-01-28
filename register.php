<?php
require 'connections/connections.php';
$con = connection();
$con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

session_start();
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';

$errorMessage = ''; // Initialize the error message variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password
    $contact_number = $_POST['contact_number'];
    $role = $isAdmin ? $_POST['role'] : 'Patient'; // Default role is 'Patient' for non-admins

    // Check if email or username already exists
    $checkQuery = "SELECT COUNT(*) AS count FROM users WHERE email = ? OR username = ?";
    $stmt = $con->prepare($checkQuery);
    $stmt->execute([$email, $username]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        $errorMessage = 'Email or Username is already in use. Please choose another one.';
    } else {
        // Insert the new user into the database
        $sql = "INSERT INTO users (username, email, password, contact_number, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $con->prepare($sql);
        
        try {
            $stmt->execute([$username, $email, $password, $contact_number, $role]);
            // Redirect to login page after successful registration
            header("Location: login.php");
            exit();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paanakan | Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
<?php if ($errorMessage): ?>
    <div id="error-message" class="alert alert-warning alert-dismissible d-flex align-items-center" role="alert" style="display: inline-flex; max-width: fit-content; padding: 15px 20px; font-size: 16px; position: fixed; top: 10px; right: 10px; z-index: 1050; border-radius: 8px; height:100px;">
        <div>
            <?php echo $errorMessage; ?>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="padding: 0.25rem;"></button>
    </div>
<?php endif; ?>



    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="register-container">
            <div class="logo mb-4">
                <img src="PSC.png" alt="Paanakan Logo"> <!-- Replace with your logo path -->
            </div>

            <h2 class="title">Paanakan sa Calapan <span>Health Record Management System</span></h2>

            <form action="register.php" method="POST" id="register-form">
                <!-- Username Input -->
                <div class="input-container mb-3">
                    <i class="material-icons">person</i>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                </div>

                <!-- Email Input -->
                <div class="input-container mb-3">
                    <i class="material-icons">email</i>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                </div>

                <!-- Contact Number Input (with validation for 11 digits) -->
                <div class="input-container mb-3">
                    <i class="material-icons">phone</i>
                    <input type="text" class="form-control" id="contact_number" name="contact_number" placeholder="Contact Number" required>
                    <small id="contact-number-error" class="text-danger" style="display: none;">Contact number must be exactly 11 digits.</small>
                </div>

                <!-- Password Input -->
                <div class="input-container mb-3">
                    <i class="material-icons">lock</i>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <button class="show-hide-btn" type="button" id="toggle-password">
                        <i class="material-icons" id="eye-icon">visibility_off</i>
                    </button>
                </div>

                <!-- Confirm Password Input (with validation) -->
                <div class="input-container mb-3">
                    <i class="material-icons">lock</i>
                    <input type="password" class="form-control" id="confirm-password" name="confirm-password" placeholder="Confirm Password" required>
                    <small id="password-error" class="text-danger" style="display: none;">Passwords do not match.</small>
                </div>

                <!-- Role Input (Visible Only to Admins) -->
                <?php if ($isAdmin): ?>
                <div class="input-container mb-3">
                    <i class="material-icons">assignment_ind</i>
                    <select class="form-control" id="role" name="role" required>
                        <option value="Patient">Patient</option>
                        <option value="Midwife">Midwife</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <?php endif; ?>

                <button type="submit" class="custom-btn" id="submit-button">Register</button>

                <div class="small-text mt-3">
                    <p>Already have an account? <a href="login.php">Login</a></p>
                </div>
            </form>

            

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/register.js"></script>

</body>

</html>
