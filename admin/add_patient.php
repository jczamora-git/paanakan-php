<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require '../connections/connections.php';
$pdo = connection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    // Collect form data
    $case_id = $_POST['case_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $gender = $_POST['gender'];
    $dob = $_POST['date_of_birth'];
    $contact_number = $_POST['contact_number'];
    $philhealth_no = $_POST['philhealth_no'];
    $religion = $_POST['religion'];
    $civil_status = $_POST['civil_status'];
    $nationality = $_POST['nationality'];
    $occupation = $_POST['occupation'];
    $address = $_POST['address'];
    $medical_history = $_POST['medical_history'];
    $patient_status = $_POST['patient_status'];

    // Debugging step: Check what data is being sent
    // var_dump($_POST); exit; // Uncomment this for debugging

    // Prepare the insert query
    $query = "INSERT INTO patients (case_id, first_name, last_name, gender, date_of_birth, contact_number, philhealth_no, religion, civil_status, nationality, occupation, address, medical_history, patient_status)
              VALUES (:case_id, :first_name, :last_name, :gender, :dob, :contact_number, :philhealth_no, :religion, :civil_status, :nationality, :occupation, :address, :medical_history, :patient_status)";
    $stmt = $pdo->prepare($query);

    // Execute the query
    try {
        $stmt->execute([
            ':case_id' => $case_id,
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':gender' => $gender,
            ':dob' => $dob,
            ':contact_number' => $contact_number,
            ':philhealth_no' => $philhealth_no,
            ':religion' => $religion,
            ':civil_status' => $civil_status,
            ':nationality' => $nationality,
            ':occupation' => $occupation,
            ':address' => $address,
            ':medical_history' => $medical_history,
            ':patient_status' => $patient_status
        ]);
        $_SESSION['message'] = "Patient added successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to add patient: " . $e->getMessage();
    }

    // Redirect back to patient list
    header("Location: patient.php");
    exit();
}

// Generate the next Case ID
function generateCaseId($pdo) {
    // Get the last case_id from the patients table
    $query = "SELECT case_id FROM patients ORDER BY case_id DESC LIMIT 1";
    $stmt = $pdo->query($query);
    $last_case_id = $stmt->fetchColumn();

    if ($last_case_id) {
        // Extract the numeric part and increment it
        $last_number = (int)substr($last_case_id, 1);  // Remove the 'C' and convert to int
        $new_number = $last_number + 1;
        // Format the new case_id with leading zeros (e.g., C009)
        $new_case_id = 'C' . str_pad($new_number, 3, '0', STR_PAD_LEFT);
    } else {
        // Default to C001 if no case_id exists
        $new_case_id = 'C001';
    }

    return $new_case_id;
}

$case_id = ''; // Initially empty, will be set by button click
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Patient</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/form.css">
</head>
<body style="font-family: 'Poppins', sans-serif;">
    <div class="dashboard-container">
        <?php include '../sidebar.php'; ?>

        <!-- Main Content -->
        <main class="dashboard-main-content">
            <div class="container mt-5">
                <!-- Breadcrumb Navigation -->
                <?php include '../admin/breadcrumb.php'; ?>
                <div class="d-flex align-items-center mb-4">
                    <h2 class="mb-0">Add New Patient</h2>
                </div>

                <!-- Handle success/error messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php elseif (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Add Patient Form -->
                <form action="add_patient.php" method="POST" class="shadow p-4 bg-white rounded">
                    <input type="hidden" name="action" value="add">
                    <!-- Section: Patient Details -->
                    <div class="section-container">
                        <h5 class="mb-3">Patient Details</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="case_id" class="form-label">Case ID</label>
                                <div class="d-flex">
                                    <input type="text" name="case_id" id="case_id" class="form-control" value="<?= htmlspecialchars($case_id) ?>">
                                    <button type="button" class="btn btn-success ms-2" id="generateCaseIdBtn">New</button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" name="first_name" id="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" name="last_name" id="last_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="gender" class="form-label">Gender</label>
                                <select name="gender" id="gender" class="form-select" required>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" name="date_of_birth" id="date_of_birth" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" id="contact_number" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="philhealth_no" class="form-label">PhilHealth No.</label>
                                <input type="text" name="philhealth_no" id="philhealth_no" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="religion" class="form-label">Religion</label>
                                <input type="text" name="religion" id="religion" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Section: Civil Status and Nationality -->
                    <div class="section-container">
                        <h5 class="mb-3">Civil Status and Nationality</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="civil_status" class="form-label">Civil Status</label>
                                <select name="civil_status" id="civil_status" class="form-select">
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Widowed">Widowed</option>
                                    <option value="Separated">Separated</option>
                                    <option value="Divorced">Divorced</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="nationality" class="form-label">Nationality</label>
                                <input type="text" name="nationality" id="nationality" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Section: Occupation and Address -->
                    <div class="section-container">
                        <h5 class="mb-3">Occupation and Address</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="occupation" class="form-label">Occupation</label>
                                <input type="text" name="occupation" id="occupation" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="address" class="form-label">Address</label>
                                <textarea name="address" id="address" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Medical History -->
                    <div class="section-container">
                        <h5 class="mb-3">Medical History</h5>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="medical_history" class="form-label">Medical History</label>
                                <textarea name="medical_history" id="medical_history" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-success">Add Patient</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
                let caseIdGenerated = false; // Track if the Case ID has been generated

        document.getElementById('generateCaseIdBtn').addEventListener('click', function() {
            const caseIdInput = document.getElementById('case_id');

            if (!caseIdGenerated) {
                // Generate new Case ID
                fetch('generate_case_id.php')
                    .then(response => response.text())
                    .then(data => {
                        caseIdInput.value = data; // Set the generated case_id
                        caseIdInput.setAttribute('readonly', 'true'); // Make it readonly
                        caseIdGenerated = true; // Mark the Case ID as generated
                        document.getElementById('generateCaseIdBtn').textContent = 'Undo'; // Change button text to "Undo"
                    })
                    .catch(error => {
                        console.error("Error generating Case ID:", error);
                    });
            } else {
                // Allow manual editing by removing readonly
                caseIdInput.removeAttribute('readonly'); // Remove the readonly attribute
                caseIdInput.value = ''; // Clear the value of the Case ID input field
                caseIdGenerated = false; // Reset the flag to allow manual input
                document.getElementById('generateCaseIdBtn').textContent = 'New'; // Change button text to "New Patient"
            }
        });

    </script>
</body>
</html>

