<?php
require 'connections/connections.php';
$con = connection();
$con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

session_start();
$errorMessage = '';
$step = $_SESSION['step'] ?? '1';
$toastMessage = '';
$toastType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST['step'] == '1') {
        // Step 1: Register User
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        // Server-side email format validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = "Invalid email format.";
        }
        $contact_number = trim($_POST['contact_number']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Check if username or email already exists
        $stmt = $con->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            $errorMessage = "Username or Email already exists.";
        } else {
            try {
                $sql = "INSERT INTO users (first_name, last_name, username, email, password, contact_number) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $con->prepare($sql);
                $stmt->execute([$first_name, $last_name, $username, $email, $password, $contact_number]);

                $_SESSION['user_id'] = $con->lastInsertId(); // Store user ID
                        // Move to step 2 and preserve contact/email and names in session
                        $_SESSION['step'] = '2'; // Move to step 2
                        $_SESSION['user_email'] = $email;
                        $_SESSION['contact_number'] = $contact_number;
                        $_SESSION['first_name'] = $first_name;
                        $_SESSION['last_name'] = $last_name;

                        // Instead of redirecting immediately, set the current step and show a
                        // success toast so the user sees confirmation. The page will render
                        // step 2 below in the same request.
                        $step = '2';
                        $toastMessage = 'Account created successfully. Proceed to Case ID step.';
                        $toastType = 'success';
            } catch (PDOException $e) {
                $errorMessage = "Error: " . $e->getMessage();
            }
        }
    } elseif ($_POST['step'] == '2') {
        // Step 2: Check for Case ID
        $_SESSION['has_case_id'] = $_POST['has_case_id'];

        if ($_POST['has_case_id'] == 'yes') {
            // Normalize: accept digits only and prefix with C, zero-pad to 3 digits
            $raw = preg_replace('/\D/', '', trim($_POST['case_id'] ?? ''));
            if ($raw === '') {
                $errorMessage = "Invalid Case ID.";
            } else {
                $case_input = 'C' . str_pad($raw, 3, '0', STR_PAD_LEFT);
                $_SESSION['case_id'] = $case_input;

                // Validate if case_id exists and whether it's already linked to a user
                $stmt = $con->prepare("SELECT patient_id, user_id FROM patients WHERE case_id = ?");
                $stmt->execute([$_SESSION['case_id']]);
                $patient = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($patient) {
                // If patient already linked to a user account, show toast and do not proceed
                if (!empty($patient['user_id'])) {
                    $toastMessage = 'Patient already has an account. Please contact the clinic or support for assistance.';
                    $toastType = 'error';
                    // keep the user on step 2 so they can see the message or choose 'No' to create a new patient
                } else {
                    // Link user_id to existing patient record
                    $stmt = $con->prepare("UPDATE patients SET user_id = ? WHERE case_id = ?");
                    $stmt->execute([$_SESSION['user_id'], $_SESSION['case_id']]);

                    session_destroy();
                    header("Location: login.php");
                    exit();
                }
            } else {
                // Case ID not found in patients: accept legacy Case ID and proceed to create a new patient profile
                $_SESSION['case_id'] = trim($_POST['case_id']);
                // preserve prefill names/email/contact if available
                $_SESSION['prefill_first_name'] = $_SESSION['first_name'] ?? $first_name ?? '';
                $_SESSION['prefill_last_name'] = $_SESSION['last_name'] ?? $last_name ?? '';
                $_SESSION['user_email'] = $_SESSION['user_email'] ?? $email ?? '';
                $_SESSION['contact_number'] = $_SESSION['contact_number'] ?? $contact_number ?? '';
                header("Location: register_info.php");
                exit();
            }
        } else {
            // Redirect to register_info.php for new patient details
            header("Location: register_info.php");
            exit();
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/toast-alert.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="register-container">
            <div class="text-center">
                <img src="PSC.png" alt="Paanakan Logo" width="80">
                <h2 class="mt-3">Paanakan sa Calapan</h2>
                <p>Health Record Management System</p>
            </div>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST" id="register-form">
                <input type="hidden" id="step-input" name="step" value="<?= $step ?>">

                <?php if ($step == '1'): ?>
                    <h5 class="mb-3">Create Account</h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card p-3">
                                <h6 class="mb-3">User Details</h6>
                                <div class="input-container mb-2">
                                    <i class="material-icons">badge</i>
                                    <input type="text" id="first_name" name="first_name" class="form-control" placeholder="First Name" required value="<?= htmlspecialchars(
                                        $_POST['first_name'] ?? $_SESSION['first_name'] ?? ''
                                    ) ?>">
                                </div>
                                <div class="input-container mb-2">
                                    <i class="material-icons">badge</i>
                                    <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Last Name" required value="<?= htmlspecialchars(
                                        $_POST['last_name'] ?? $_SESSION['last_name'] ?? ''
                                    ) ?>">
                                </div>
                                <div class="input-container mb-2">
                                    <i class="material-icons">email</i>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="Email" required value="<?= htmlspecialchars(
                                        $_POST['email'] ?? ''
                                    ) ?>">
                                </div>
                                <div class="input-container mb-0">
                                    <i class="material-icons">phone</i>
                                    <input type="text" name="contact_number" class="form-control" placeholder="Contact Number" required value="<?= htmlspecialchars(
                                        $_POST['contact_number'] ?? ''
                                    ) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card p-3">
                                <h6 class="mb-3">Account Details</h6>
                                <div class="input-container mb-2">
                                    <i class="material-icons">person</i>
                                    <input type="text" id="username" name="username" class="form-control" placeholder="Username" required>
                                </div>
                                <small id="username-error" class="text-danger" style="display: none;">Username is already taken.</small>

                                <div class="input-container mb-2 position-relative">
                                    <i class="material-icons">lock</i>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                    <button type="button" class="show-hide-btn" id="toggle-password">
                                        <i class="material-icons" id="eye-icon-password">visibility_off</i>
                                    </button>
                                </div>

                                <div class="input-container mb-2 position-relative">
                                    <i class="material-icons">lock</i>
                                    <input type="password" class="form-control" id="confirm-password" name="confirm-password" placeholder="Confirm Password" required>
                                    <button type="button" class="show-hide-btn" id="toggle-confirm-password">
                                        <i class="material-icons" id="eye-icon-confirm">visibility_off</i>
                                    </button>
                                </div>
                                <small id="password-error" class="text-danger" style="display: none;">Passwords do not match.</small>

                                <div class="mt-3 text-end">
                                    <button type="button" class="custom-btn" id="next-button">Next</button>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($step == '2'): ?>
                    <h5>Do you already have a Case ID?</h5>
                    <select name="has_case_id" id="has_case_id" class="form-select mb-2">
                        <option value="no">No</option>
                        <option value="yes">Yes</option>
                    </select>

                    <div id="case_id_input" class="input-container" style="display:none;">
                        <i class="material-icons">person</i>
                        <div class="input-group">
                            <span class="input-group-text">C</span>
                            <input type="text" name="case_id" id="case_id_field" class="form-control" placeholder="001" pattern="\d+" inputmode="numeric">
                        </div>
                    </div>

                    <button type="submit" class="custom-btn mt-3">Next</button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function () {
        $("#has_case_id").change(function () {
            if ($(this).val() === "yes") {
                $("#case_id_input").show();
            } else {
                $("#case_id_input").hide();
            }
        });

        $("#next-button").click(function () {
            // Use HTML5 form validation before programmatic submit. programmatic
            // .submit() can bypass browser constraint validation, so checkValidity
            // / reportValidity ensures the email (and other required fields) are valid.
            var form = document.getElementById('register-form');
            if (!form.checkValidity()) {
                // Show native validation messages
                form.reportValidity();
                return;
            }

            if ($("#password").val() !== $("#confirm-password").val()) {
                $("#password-error").show();
                return;
            }
            $("#password-error").hide();

            // All good — submit the form
            form.submit();
        });

        function checkAvailability(field, value, errorElement) {
            if (value.trim() === '') return;

            $.post("check_availability.php", { [field]: value }, function (data) {
                if (data.status === 'taken') {
                    $(errorElement).show();
                } else {
                    $(errorElement).hide();
                }
            }, "json");
        }

        $("#username").blur(function () { checkAvailability("username", $(this).val(), "#username-error"); });
        $("#email").blur(function () { checkAvailability("email", $(this).val(), "#email-error"); });
    });
    </script>
    <script>
    
    function toggleVisibility(field, icon) {
        if (field.type === "password") {
            field.type = "text";
            icon.textContent = "visibility";
        } else {
            field.type = "password";
            icon.textContent = "visibility_off";
        }
    }

    document.getElementById("toggle-password").addEventListener("click", function () {
        toggleVisibility(document.getElementById("password"), document.getElementById("eye-icon-password"));
    });

    document.getElementById("toggle-confirm-password").addEventListener("click", function () {
        toggleVisibility(document.getElementById("confirm-password"), document.getElementById("eye-icon-confirm"));
    });
    
    </script>
    <?php if (!empty($toastMessage)): ?>
    <script src="js/toast-alert.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var t = <?= json_encode($toastType) ?>;
            var fn = t === 'error' ? 'error' : (t === 'success' ? 'success' : 'info');
            Toast[fn](<?= json_encode($toastMessage) ?>);
            // If this is a success on account creation, wait a moment so the user
            // sees the toast; no redirect needed because we render step 2 in-place.
        });
    </script>
    <?php endif; ?>
</body>
</html>
