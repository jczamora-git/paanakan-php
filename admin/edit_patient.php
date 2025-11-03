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

// Fetch patient details if patient_id is set
if (isset($_GET['patient_id']) && is_numeric($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];

    // Get the patient details from the database
    $query = "SELECT * FROM patients WHERE patient_id = :patient_id LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':patient_id' => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        $_SESSION['error'] = "Patient not found!";
        header("Location: patient.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid patient ID!";
    header("Location: patient.php");
    exit();
}

// Handle form submission for updating patient information
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    // Collect updated form data
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

    // Prepare the update query
    $query = "UPDATE patients SET case_id = :case_id, first_name = :first_name, last_name = :last_name, 
              gender = :gender, date_of_birth = :dob, contact_number = :contact_number, philhealth_no = :philhealth_no,
              religion = :religion, civil_status = :civil_status, nationality = :nationality, occupation = :occupation,
              address = :address WHERE patient_id = :patient_id";
    $stmt = $pdo->prepare($query);

    // Execute the update query
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
            ':patient_id' => $patient_id
        ]);
        $_SESSION['message'] = "Patient updated successfully!";
        header("Location: patient.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to update patient: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient</title>
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
                    <h2 class="mb-0">Edit Patient</h2>
                </div>

                <!-- Handle success/error messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php elseif (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Edit Patient Form -->
                <form action="edit_patient.php?patient_id=<?= $patient['patient_id'] ?>" method="POST" class="shadow p-4 bg-white rounded">
                    <input type="hidden" name="action" value="edit">
                    <!-- Section: Patient Details -->
                    <div class="section-container">
                        <h5 class="mb-3">Patient Details</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="case_id" class="form-label">Case ID</label>
                                <input type="text" name="case_id" id="case_id" class="form-control" value="<?= htmlspecialchars($patient['case_id']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" name="first_name" id="first_name" class="form-control" value="<?= htmlspecialchars($patient['first_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" name="last_name" id="last_name" class="form-control" value="<?= htmlspecialchars($patient['last_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="gender" class="form-label">Gender</label>
                                <select name="gender" id="gender" class="form-select" required>
                                    <option value="Male" <?= $patient['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $patient['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" name="date_of_birth" id="date_of_birth" class="form-control" value="<?= htmlspecialchars($patient['date_of_birth']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" id="contact_number" class="form-control" value="<?= htmlspecialchars($patient['contact_number']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="philhealth_no" class="form-label">PhilHealth No.</label>
                                <input type="text" name="philhealth_no" id="philhealth_no" class="form-control" value="<?= htmlspecialchars($patient['philhealth_no']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="religion" class="form-label">Religion</label>
                                <input type="text" name="religion" id="religion" class="form-control" value="<?= htmlspecialchars($patient['religion']) ?>">
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
                                    <option value="Single" <?= $patient['civil_status'] === 'Single' ? 'selected' : '' ?>>Single</option>
                                    <option value="Married" <?= $patient['civil_status'] === 'Married' ? 'selected' : '' ?>>Married</option>
                                    <option value="Widowed" <?= $patient['civil_status'] === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                                    <option value="Separated" <?= $patient['civil_status'] === 'Separated' ? 'selected' : '' ?>>Separated</option>
                                    <option value="Divorced" <?= $patient['civil_status'] === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="nationality" class="form-label">Nationality</label>
                                <input type="text" name="nationality" id="nationality" class="form-control" value="<?= htmlspecialchars($patient['nationality']) ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Section: Occupation and Address -->
                    <div class="section-container">
                        <h5 class="mb-3">Occupation and Address</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="occupation" class="form-label">Occupation</label>
                                <input type="text" name="occupation" id="occupation" class="form-control" value="<?= htmlspecialchars($patient['occupation']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="address" class="form-label">Address</label>
                                <textarea name="address" id="address" class="form-control"><?= htmlspecialchars($patient['address']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Submit and Back Buttons -->
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Update Patient</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
