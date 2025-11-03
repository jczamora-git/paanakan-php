<?php
require 'connections/connections.php';
$con = connection();
$con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Query to fetch user details by username or email (include contact_number, email and names)
    $query = "SELECT user_id, password, username, role, contact_number, email, first_name, last_name FROM users WHERE username = :username OR email = :username";
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
                exit();
            } elseif ($user['role'] == 'Midwife') {
                header("Location: midwife/dashboard.php");
                exit();
            } elseif ($user['role'] == 'Patient') {
                // Ensure the user has a linked patient record
                $stmtPatient = $con->prepare("SELECT patient_id, case_id FROM patients WHERE user_id = :user_id LIMIT 1");
                $stmtPatient->execute([':user_id' => $user['user_id']]);
                $patient = $stmtPatient->fetch();

                if ($patient) {
                    // store patient info in session and redirect to patient dashboard
                    $_SESSION['patient_id'] = $patient['patient_id'];
                    $_SESSION['case_id'] = $patient['case_id'];
                    header("Location: patient/dashboard.php");
                    exit();
                } else {
                    // User has an account but no patient record. Show a client-side prompt to create one.
                    $showPatientPrompt = true;
                    // Do not exit; allow the page to render so JS can prompt the user
                }
            } else {
                // If role is not recognized
                log_action($con, $userId, 'Login Failed: Invalid Role');
                $errorMessage = "Invalid role.";
                exit();
            }
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
    <link rel="stylesheet" href="css/toast-alert.css">
  

</head>

<body>

    <div class="container d-flex justify-content-center align-items-center min-vh-300 ">
        <div class="login-container">
          
            <div class="logo mb-4">
                <a href="index.php">
                    <img src="PSC.png" alt="Paanakan Logo"> <!-- Home logo -->
                </a>
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
                    <p>Don't have an account? <a href="register.php">Create Account</a></p>
                    <p><a href="forgot_password.php">Forgot Password?</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/login.js"></script>
    <script src="js/toast-alert.js"></script>
    <?php if (!empty($showPatientPrompt) && $showPatientPrompt === true): ?>
    <!-- Modal to prompt user to create/link patient record -->
    <div class="modal fade" id="patientLinkModal" tabindex="-1" aria-labelledby="patientLinkModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="patientLinkModalLabel">Link Patient Record</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
                    <form id="createPatientForm" action="register_info.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['user_id'] ?? '') ?>">

            <p>Your account is not linked to a patient record. Would you like to create one now?</p>

            <div class="mb-3">
                <label class="form-label">Do you already have a Case ID?</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="has_case_id" id="has_case_no" value="no" checked>
                        <label class="form-check-label" for="has_case_no">No</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="has_case_id" id="has_case_yes" value="yes">
                        <label class="form-check-label" for="has_case_yes">Yes</label>
                    </div>
                </div>
            </div>

            <div class="mb-3" id="caseIdWrapper" style="display:none;">
                <label for="case_id_input" class="form-label">Enter Case ID</label>
                    <div class="input-group">
                        <span class="input-group-text">C</span>
                        <input type="text" class="form-control" id="case_id_input" name="case_id" placeholder="001" pattern="\d+" inputmode="numeric">
                    </div>
                <div id="caseIdError" class="text-danger mt-2" style="display:none; font-size:0.9rem;"></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="patientLinkCancel">Cancel</button>
            <button type="submit" class="btn btn-primary">Continue</button>
          </div>
          </form>
        </div>
      </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var patientModalEl = document.getElementById('patientLinkModal');
            var patientModal = new bootstrap.Modal(patientModalEl);
            patientModal.show();

            var hasYes = document.getElementById('has_case_yes');
            var hasNo = document.getElementById('has_case_no');
            var caseWrapper = document.getElementById('caseIdWrapper');

            function toggleCaseInput() {
                if (hasYes.checked) {
                    caseWrapper.style.display = 'block';
                    document.getElementById('case_id_input').required = true;
                } else {
                    caseWrapper.style.display = 'none';
                    document.getElementById('case_id_input').required = false;
                }
            }

            hasYes.addEventListener('change', toggleCaseInput);
            hasNo.addEventListener('change', toggleCaseInput);

            document.getElementById('patientLinkCancel').addEventListener('click', function() {
                // Log out to clear session if user cancels
                window.location.href = 'logout.php';
            });

            // Intercept form submit to check case id before posting to register_info.php
            var createForm = document.getElementById('createPatientForm');
            createForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var hasCase = document.querySelector('input[name="has_case_id"]:checked').value;
                var caseInput = document.getElementById('case_id_input').value.trim();
                var caseErrorEl = document.getElementById('caseIdError');
                caseErrorEl.style.display = 'none';

                if (hasCase === 'yes') {
                    if (!caseInput) {
                        caseErrorEl.textContent = 'Please enter your Case ID.';
                        caseErrorEl.style.display = 'block';
                        return;
                    }

                        // normalize: extract digits and prefix with C, zero-pad to 3
                        var digits = caseInput.replace(/\D/g, '');
                        if (!digits) {
                            caseErrorEl.textContent = 'Please enter numeric Case ID.';
                            caseErrorEl.style.display = 'block';
                            return;
                        }
                        var normalized = 'C' + digits.padStart(3, '0');

                    // Call server to check case id status
                        fetch('check_case.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({ case_id: normalized })
                        }).then(function(res) { return res.json(); })
                      .then(function(json) {
                          if (json.status === 'linked') {
                              // Show toast and do not submit
                              Toast.error(json.message || 'Case ID already linked.');
                          } else if (json.status === 'unlinked') {
                                  // set normalized value into the input then submit
                                  document.getElementById('case_id_input').value = digits;
                                  // ensure the posted value is the normalized ID
                                  // create a hidden input to carry the normalized case id
                                  var hidden = document.createElement('input');
                                  hidden.type = 'hidden';
                                  hidden.name = 'case_id';
                                  hidden.value = normalized;
                                  createForm.appendChild(hidden);
                                  createForm.submit();
                          } else if (json.status === 'not_found') {
                                  // Case ID not found: proceed to create new profile. submit normalized case id
                                  var hidden = document.createElement('input');
                                  hidden.type = 'hidden';
                                  hidden.name = 'case_id';
                                  hidden.value = normalized;
                                  createForm.appendChild(hidden);
                                  createForm.submit();
                          } else {
                              caseErrorEl.textContent = json.message || 'Error checking Case ID.';
                              caseErrorEl.style.display = 'block';
                          }
                      }).catch(function(err) {
                          caseErrorEl.textContent = 'Failed to validate Case ID. Please try again.';
                          caseErrorEl.style.display = 'block';
                      });
                } else {
                    // hasCase == no -> submit directly
                    createForm.submit();
                }
            });
        });
    </script>
    <?php endif; ?>
</body>

</html>
