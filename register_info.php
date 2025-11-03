<?php
require 'connections/connections.php';
$con = connection();
$con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

session_start();

// If user_id wasn't set in session, allow POST from login.php to provide it (hidden form)
if (!isset($_SESSION['user_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
        // Accept only user_id from POST (do not trust/need names/email/contact here)
        $_SESSION['user_id'] = trim($_POST['user_id']);
        // We'll fetch contact/email/names from the users table below using user_id
    } else {
        // No session and no POSTed user_id -> redirect back to register
        header("Location: register.php");
        exit();
    }
}

$errorMessage = '';
$user_id = $_SESSION['user_id'];
$toastMessage = '';
$toastType = '';

// If contact/email/name not present in session, try to fetch from users table for better accuracy
try {
    if (empty($_SESSION['contact_number']) || empty($_SESSION['user_email']) || empty($_SESSION['prefill_first_name']) || empty($_SESSION['prefill_last_name'])) {
        $uStmt = $con->prepare("SELECT contact_number, email, first_name, last_name FROM users WHERE user_id = ? LIMIT 1");
        $uStmt->execute([$user_id]);
        $u = $uStmt->fetch(PDO::FETCH_ASSOC);
        if ($u) {
            if (empty($_SESSION['contact_number']) && !empty($u['contact_number'])) {
                $_SESSION['contact_number'] = $u['contact_number'];
            }
            if (empty($_SESSION['user_email']) && !empty($u['email'])) {
                $_SESSION['user_email'] = $u['email'];
            }
            if (empty($_SESSION['prefill_first_name']) && !empty($u['first_name'])) {
                $_SESSION['prefill_first_name'] = $u['first_name'];
            }
            if (empty($_SESSION['prefill_last_name']) && !empty($u['last_name'])) {
                $_SESSION['prefill_last_name'] = $u['last_name'];
            }
        }
    }
} catch (Exception $e) {
    // ignore fetch errors — we'll fallback to posted values
}

// Function to generate a new Case ID if needed
function generateCaseId($con) {
    $stmt = $con->query("SELECT case_id FROM patients ORDER BY patient_id DESC LIMIT 1");
    $last_case_id = $stmt->fetchColumn();
    
    if ($last_case_id) {
        $last_number = (int)substr($last_case_id, 1);
        return 'C' . str_pad($last_number + 1, 3, '0', STR_PAD_LEFT);
    }
    return 'C001';
}

// Handle quick POST from login modal: case ID check (run regardless of whether first_name was supplied)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['has_case_id'])) {
    $has_case = $_POST['has_case_id'];
    if ($has_case === 'yes') {
        $case_input = trim($_POST['case_id'] ?? '');
        if ($case_input === '') {
            $errorMessage = 'Please enter your Case ID.';
        } else {
            // Check existing case
            $stmt = $con->prepare("SELECT patient_id, user_id FROM patients WHERE case_id = ? LIMIT 1");
            $stmt->execute([$case_input]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                if (!empty($existing['user_id'])) {
                    // Already linked
                    $toastMessage = 'Patient already has an account. Please contact the clinic for assistance.';
                    $toastType = 'error';
                    // After showing toast we'll redirect back to login via JS below
                } else {
                    // Link patient record to this user
                    $stmt = $con->prepare("UPDATE patients SET user_id = ? WHERE case_id = ?");
                    $stmt->execute([$user_id, $case_input]);
                    session_destroy();
                    header("Location: login.php");
                    exit();
                }
            } else {
                // Case ID not found in patients: accept the provided Case ID and continue
                // Store it in session and redirect to this page so the user can fill the full info form.
                $_SESSION['case_id'] = $case_input;
                // Ensure any posted names/email are preserved in session (if they were sent)
                if (isset($_POST['first_name'])) {
                    $_SESSION['prefill_first_name'] = trim($_POST['first_name']);
                }
                if (isset($_POST['last_name'])) {
                    $_SESSION['prefill_last_name'] = trim($_POST['last_name']);
                }
                if (isset($_POST['email'])) {
                    $_SESSION['user_email'] = trim($_POST['email']);
                }
                header("Location: register_info.php");
                exit();
            }
        }
    }
}

// Only perform the DB insert when required fields are present (gender and date_of_birth)
if (
    $_SERVER["REQUEST_METHOD"] == "POST" &&
    isset($_POST['first_name']) &&
    isset($_POST['gender']) &&
    isset($_POST['date_of_birth'])
) {
    $first_name = trim($_POST['first_name']);
    $middle_name = isset($_POST['middle_name']) && $_POST['middle_name'] !== '' ? trim($_POST['middle_name']) : null;
    $last_name = trim($_POST['last_name']);
    $gender = $_POST['gender'];
    $dob = $_POST['date_of_birth'];

    // Additional fields requested
    $philhealth_no = trim($_POST['philhealth_no'] ?? ($_SESSION['philhealth_no'] ?? ''));
    $religion = trim($_POST['religion'] ?? ($_SESSION['religion'] ?? ''));
    $civil_status = trim($_POST['civil_status'] ?? ($_SESSION['civil_status'] ?? ''));
    $nationality = trim($_POST['nationality'] ?? ($_SESSION['nationality'] ?? ''));
    $occupation = trim($_POST['occupation'] ?? ($_SESSION['occupation'] ?? ''));
    $address = trim($_POST['address'] ?? ($_SESSION['address'] ?? ''));

    // Generate a new Case ID if the user does not have one
    $case_id = $_SESSION['case_id'] ?? generateCaseId($con);

    // Prefer values from POST (final form) but fall back to session values
    $email_to_store = trim($_POST['email'] ?? ($_SESSION['user_email'] ?? ''));
    $contact_to_store = trim($_POST['contact_number'] ?? ($_SESSION['contact_number'] ?? ''));

    // Validate DOB is not in the future
    if ($dob > date('Y-m-d')) {
        $errorMessage = 'Date of birth cannot be in the future.';
    } else {
        try {
            $con->beginTransaction();

        // Insert new patient record (include additional demographic fields).
        // patient_status intentionally set to NULL for now.
        $sql = "INSERT INTO patients (
                    case_id, user_id, first_name, middle_name, last_name, gender, date_of_birth,
                    philhealth_no, religion, civil_status, nationality, occupation, address,
                    email, contact_number, patient_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $con->prepare($sql);
        // Debug: log values used for insert
        error_log('register_info: inserting patient - case_id=' . $case_id . ' user_id=' . $user_id . ' email=' . $email_to_store . ' contact=' . $contact_to_store);

        $stmt->execute([
            $case_id,
            $user_id, // Use the retrieved user ID
            $first_name,
            $middle_name,
            $last_name,
            $gender,
            $dob,
            // new demographic fields
            $philhealth_no,
            $religion,
            $civil_status,
            $nationality,
            $occupation,
            $address,
            // Prefer POST-stored or session-stored email/contact
            $email_to_store,
            $contact_to_store,
            null
        ]);

    $con->commit();

    // Destroy session after successful registration but show a success toast
    // and redirect to login after a short delay so the user sees confirmation.

    // Send welcome email to the newly registered user (if email present)
    try {
        if (!empty($email_to_store)) {
            require_once __DIR__ . '/connections/EmailService.php';
            $emailService = new EmailService();
            $toName = trim($first_name . ' ' . $last_name);
            $emailResult = $emailService->sendWelcomeEmail($email_to_store, $toName, $case_id);
            // Log result for debugging
            error_log('register_info: welcome email result: ' . json_encode($emailResult));
        }
    } catch (Exception $e) {
        error_log('register_info: failed to send welcome email: ' . $e->getMessage());
    }

    session_destroy();
    $toastMessage = 'Registration successful. Redirecting to login...';
    $toastType = 'success';
    // Do not redirect here; render the page so the toast can be shown and JS will redirect.
    } catch (PDOException $e) {
        $con->rollBack();
        $errorMessage = "Database Error: " . $e->getMessage();
    } // Added closing brace for the try/catch block
    }
}
// If POST includes first_name but missing required fields, store prefill values to session and render the form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['first_name']) && (!isset($_POST['gender']) || !isset($_POST['date_of_birth']))) {
    $_SESSION['prefill_first_name'] = trim($_POST['first_name']);
    if (isset($_POST['middle_name'])) {
        $_SESSION['prefill_middle_name'] = trim($_POST['middle_name']);
    }
    $_SESSION['prefill_last_name'] = trim($_POST['last_name'] ?? '');
    // If case_id was posted, ensure it's saved
    if (isset($_POST['case_id']) && $_POST['case_id'] !== '') {
        $_SESSION['case_id'] = trim($_POST['case_id']);
    }
    // Preserve contact_number and email across re-renders
    if (isset($_POST['contact_number'])) {
        $_SESSION['contact_number'] = trim($_POST['contact_number']);
    }
    if (isset($_POST['email'])) {
        $_SESSION['user_email'] = trim($_POST['email']);
    }
    // Preserve newly added demographic fields if posted
    if (isset($_POST['philhealth_no'])) {
        $_SESSION['philhealth_no'] = trim($_POST['philhealth_no']);
    }
    if (isset($_POST['religion'])) {
        $_SESSION['religion'] = trim($_POST['religion']);
    }
    if (isset($_POST['civil_status'])) {
        $_SESSION['civil_status'] = trim($_POST['civil_status']);
    }
    if (isset($_POST['nationality'])) {
        $_SESSION['nationality'] = trim($_POST['nationality']);
    }
    if (isset($_POST['occupation'])) {
        $_SESSION['occupation'] = trim($_POST['occupation']);
    }
    if (isset($_POST['address'])) {
        $_SESSION['address'] = trim($_POST['address']);
    }
    // Do not attempt to insert; fall through to render the form with prefilled values
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paanakan | Register Info</title>
    <!-- Favicon: prefer psc.ico at site root; fall back to PSC.png if .ico not present -->
    <link rel="icon" href="/psc.ico" type="image/x-icon">
    <link rel="icon" href="/PSC.png" type="image/png">
    <link rel="apple-touch-icon" href="/PSC.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/toast-alert.css">
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

            <form action="register_info.php" method="POST">
                <input type="hidden" name="case_id" value="<?= htmlspecialchars($_SESSION['case_id'] ?? generateCaseId($con)) ?>">
                <!-- Two-column grouped layout similar to register.php -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card p-3">
                            <h6 class="mb-3">Personal Information</h6>
                            <div class="input-container mb-2">
                                <i class="material-icons">person</i>
                                <input type="text" name="first_name" class="form-control ps-5" placeholder="First Name" required value="<?= htmlspecialchars(
                                    $_POST['first_name'] ?? $_SESSION['prefill_first_name'] ?? ''
                                ) ?>">
                            </div>
                            <div class="input-container mb-2">
                                <i class="material-icons">person</i>
                                <input type="text" name="middle_name" class="form-control ps-5" placeholder="Middle Name" value="<?= htmlspecialchars(
                                    $_POST['middle_name'] ?? $_SESSION['prefill_middle_name'] ?? ''
                                ) ?>">
                            </div>
                            <div class="input-container mb-2">
                                <i class="material-icons">person</i>
                                <input type="text" name="last_name" class="form-control ps-5" placeholder="Last Name" required value="<?= htmlspecialchars(
                                    $_POST['last_name'] ?? $_SESSION['prefill_last_name'] ?? ''
                                ) ?>">
                            </div>
                            <div class="input-container mb-2">
                                <select name="gender" class="form-select" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?= (($_POST['gender'] ?? ($_SESSION['prefill_gender'] ?? '')) === 'Male') ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= (($_POST['gender'] ?? ($_SESSION['prefill_gender'] ?? '')) === 'Female') ? 'selected' : '' ?>>Female</option>
                                </select>
                            </div>
                            <div class="input-container">
                                <i class="material-icons">event</i>
                                <input type="date" name="date_of_birth" class="form-control ps-5" required max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars(
                                    $_POST['date_of_birth'] ?? $_SESSION['prefill_date_of_birth'] ?? ''
                                ) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card p-3">
                            <h6 class="mb-3">Additional Details</h6>
                            <div class="input-container mb-2">
                                <i class="material-icons">badge</i>
                                <input type="text" name="philhealth_no" class="form-control ps-5" placeholder="PhilHealth No." value="<?= htmlspecialchars(
                                    $_POST['philhealth_no'] ?? $_SESSION['philhealth_no'] ?? ''
                                ) ?>">
                            </div>
                            <div class="input-container mb-2">
                                <i class="material-icons">people</i>
                                <?php $sel_civil = $_POST['civil_status'] ?? ($_SESSION['civil_status'] ?? ''); ?>
                                <select name="civil_status" class="form-select ps-5">
                                    <option value="" disabled <?= $sel_civil === '' ? 'selected' : '' ?>>Civil Status</option>
                                    <option value="Single" <?= $sel_civil === 'Single' ? 'selected' : '' ?>>Single</option>
                                    <option value="Married" <?= $sel_civil === 'Married' ? 'selected' : '' ?>>Married</option>
                                    <option value="Divorced" <?= $sel_civil === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                                    <option value="Widowed" <?= $sel_civil === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                                    <option value="Separated" <?= $sel_civil === 'Separated' ? 'selected' : '' ?>>Separated</option>
                                </select>
                            </div>
                            <div class="input-container mb-2">
                                <i class="material-icons">forum</i>
                                <input type="text" name="religion" class="form-control ps-5" placeholder="Religion" value="<?= htmlspecialchars(
                                    $_POST['religion'] ?? $_SESSION['religion'] ?? ''
                                ) ?>">
                            </div>
                            <div class="input-container mb-2">
                                <i class="material-icons">flag</i>
                                <input type="text" name="nationality" class="form-control ps-5" placeholder="Nationality" value="<?= htmlspecialchars(
                                    $_POST['nationality'] ?? $_SESSION['nationality'] ?? ''
                                ) ?>">
                            </div>
                            <div class="input-container mb-2">
                                <i class="material-icons">work</i>
                                <input type="text" name="occupation" class="form-control ps-5" placeholder="Occupation" value="<?= htmlspecialchars(
                                    $_POST['occupation'] ?? $_SESSION['occupation'] ?? ''
                                ) ?>">
                            </div>
                            <div class="input-container mb-2">
                                <i class="material-icons">home</i>
                                <textarea name="address" class="form-control ps-5" placeholder="Address"><?= htmlspecialchars($_POST['address'] ?? $_SESSION['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email and contact will be submitted via hidden inputs so the user cannot edit them here. -->
                <input type="hidden" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $_SESSION['user_email'] ?? '') ?>">
                <input type="hidden" name="contact_number" value="<?= htmlspecialchars($_POST['contact_number'] ?? $_SESSION['contact_number'] ?? '') ?>">

                <div class="d-flex justify-content-center mt-3">
                    <button type="submit" class="custom-btn">Register</button>
                </div>
            </form>
        </div>
    </div>
</body>
<?php if (!empty($toastMessage)): ?>
    <script src="js/toast-alert.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Choose toast function based on type
            var fn = <?= json_encode($toastType) ?> === 'error' ? 'error' : (<?= json_encode($toastType) ?> === 'success' ? 'success' : 'info');
            Toast[fn](<?= json_encode($toastMessage) ?>);
            // If success, redirect to login after a short delay so user sees the toast
            if (fn === 'success') {
                setTimeout(function() { window.location.href = 'login.php'; }, 2500);
            }
        });
    </script>
<?php endif; ?>
</html>
