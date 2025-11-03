<?php
session_start();
require 'connections/connections.php';
$pdo = connection();

// Function to generate Case ID
function generateCaseId($pdo) {
    $query = "SELECT case_id FROM patients ORDER BY case_id DESC LIMIT 1";
    $stmt = $pdo->query($query);
    $last_case_id = $stmt->fetchColumn();

    if ($last_case_id) {
        $last_number = (int)substr($last_case_id, 1);
        $new_number = $last_number + 1;
        $new_case_id = 'C' . str_pad($new_number, 3, '0', STR_PAD_LEFT);
    } else {
        $new_case_id = 'C001';
    }
    return $new_case_id;
}

$case_id = generateCaseId($pdo);
$patient = null;
$showModal = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    // Collect form data
    $case_id = $_POST['case_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $gender = $_POST['gender'];
    $dob = $_POST['date_of_birth'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate password match
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
    } else {
        // Check for unique username and email
        $userCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
        $userCheck->execute([':username' => $username, ':email' => $email]);
        if ($userCheck->fetchColumn() > 0) {
            $_SESSION['error'] = "Username or email already exists.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Insert into users table
            $userInsert = $pdo->prepare("INSERT INTO users (username, email, password, contact_number, role) VALUES (:username, :email, :password, :contact_number, 'Patient')");
            $userInsert->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $hashed_password,
                ':contact_number' => $contact_number
            ]);
            $user_id = $pdo->lastInsertId();
            // Insert into patients table
            $query = "INSERT INTO patients (case_id, first_name, last_name, gender, date_of_birth, contact_number, email, user_id)
                      VALUES (:case_id, :first_name, :last_name, :gender, :dob, :contact_number, :email, :user_id)";
    $stmt = $pdo->prepare($query);
    try {
        $stmt->execute([
            ':case_id' => $case_id,
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':gender' => $gender,
            ':dob' => $dob,
            ':contact_number' => $contact_number,
                    ':email' => $email,
                    ':user_id' => $user_id
                ]);
        // Fetch the newly inserted patient ID
        $query = "SELECT * FROM patients WHERE case_id = :case_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':case_id' => $case_id]);
        $patient = $stmt->fetch();
        if ($patient) {
            $showModal = true;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to add patient: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="css/form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #e8f5e9;
            font-family: 'Poppins', sans-serif;
        }
        .registration-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.18);
            max-width: 650px;
            margin: 40px auto 30px auto;
            padding: 40px 35px 30px 35px;
            position: relative;
            z-index: 2;
        }
        .section-divider {
            display: flex;
            align-items: center;
            margin: 30px 0 20px 0;
        }
        .section-divider .section-icon {
            background: #2E8B57;
            color: #fff;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-right: 12px;
        }
        .section-divider .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2E8B57;
            margin-bottom: 0;
        }
        .form-label {
            font-weight: 500;
            color: #2E8B57;
        }
        .form-control, .form-select {
            border-radius: 8px;
            font-size: 1rem;
        }
        .capitalize {
            text-transform: capitalize;
        }
        .btn-success {
            background: #2E8B57;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            padding: 10px 28px;
            font-weight: 500;
            transition: 0.2s;
        }
        .btn-success:hover {
            background: #23845E;
        }
        .alert {
            font-size: 1rem;
        }
        @media (max-width: 768px) {
            .registration-card {
                padding: 25px 10px 20px 10px;
            }
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<main>
    <div class="registration-card">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php elseif (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
        <form action="add_patient.php" method="POST">
        <input type="hidden" name="action" value="add">
            <div class="section-divider">
                <div class="section-icon"><i class="fas fa-address-card"></i></div>
                <div class="section-title">Patient Details</div>
            </div>
        <div class="row mb-3">
                <div class="col-md-6 mb-3">
                <label for="case_id" class="form-label">Case ID</label>
                <input type="text" name="case_id" id="case_id" class="form-control" value="<?= htmlspecialchars($case_id) ?>" readonly>
            </div>
                <div class="col-md-6 mb-3">
                <label for="first_name" class="form-label">First Name</label>
                    <input type="text" name="first_name" id="first_name" class="form-control capitalize" oninput="capitalizeInput(this)" required value="<?= isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : '' ?>">
            </div>
                <div class="col-md-6 mb-3">
                <label for="middle_name" class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" id="middle_name" class="form-control capitalize" oninput="capitalizeInput(this)" value="<?= isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : '' ?>">
            </div>
                <div class="col-md-6 mb-3">
                <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" name="last_name" id="last_name" class="form-control capitalize" oninput="capitalizeInput(this)" required value="<?= isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : '' ?>">
            </div>
                <div class="col-md-6 mb-3">
                <label for="gender" class="form-label">Gender</label>
                <select name="gender" id="gender" class="form-select" required>
                        <option value="Male" <?= (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
                </select>
            </div>
                <div class="col-md-6 mb-3">
                <label for="date_of_birth" class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" id="date_of_birth" class="form-control" required value="<?= isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : '' ?>">
            </div>
                <div class="col-md-6 mb-3">
                <label for="contact_number" class="form-label">Contact Number</label>
                <input type="tel" name="contact_number" id="contact_number" class="form-control" pattern="[0-9]{11}" maxlength="11"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);" required value="<?= isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : '' ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
            </div>
            <div class="section-divider">
                <div class="section-icon"><i class="fas fa-user-lock"></i></div>
                <div class="section-title">Account Details</div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" name="username" id="username" class="form-control" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control" required minlength="6">
                        <span class="input-group-text" style="cursor:pointer;" onclick="togglePassword('password', this)">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6">
                        <span class="input-group-text" style="cursor:pointer;" onclick="togglePassword('confirm_password', this)">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
            </div>
        </div>
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-success">Continue</button>
        </div>
    </form>
    </div>
</main>

<?php if ($showModal && $patient): ?>
    <!-- Patient Details Modal -->
    <div class="modal fade" id="patientDetailsModal" tabindex="-1" aria-labelledby="patientDetailsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="patientDetailsModalLabel">Welcome, <?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?>!</h5>
                    <form action="step2.php" method="POST">
                        <input type="hidden" name="patient_id" value="<?= htmlspecialchars($patient['patient_id']) ?>">
                        <button type="submit" class="btn-close"></button>
                    </form>
                </div>
                <div class="modal-body">
                    <p>You are about to access the Paanakan sa Calapan Appointment System.</p>
                </div>
                <div class="modal-footer">
                    <form action="step2.php" method="POST">
                        <input type="hidden" name="patient_id" value="<?= htmlspecialchars($patient['patient_id']) ?>">
                        <button type="submit" class="btn btn-success">Continue</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Script to Trigger Modal -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var patientModal = new bootstrap.Modal(document.getElementById('patientDetailsModal'));
            patientModal.show();
        });
    </script>
    <script>
        function capitalizeInput(element) {
            element.value = element.value.replace(/\b\w/g, function(l) { return l.toUpperCase(); });
        }
        function validatePhoneNumber(event) {
            event.target.value = event.target.value.replace(/\D/g, '');
        if (event.target.value.length > 11) {
                event.target.value = event.target.value.slice(0, 11);
            }
        }
        function togglePassword(fieldId, el) {
            const input = document.getElementById(fieldId);
            const icon = el.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
        }
    }
    </script>
<?php endif; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function capitalizeInput(element) {
        element.value = element.value.replace(/\b\w/g, function(l) { return l.toUpperCase(); });
    }
    function togglePassword(fieldId, el) {
        const input = document.getElementById(fieldId);
        const icon = el.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
</body>
</html>

