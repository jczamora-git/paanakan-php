<?php
session_start();

// If patient_id is not passed through POST, attempt to get it from the session or a query parameter
$patientId = $_POST['patient_id'] ?? $_SESSION['patient_id'] ?? $_GET['patient_id'] ?? '';

if (empty($patientId)) {
    // Redirect to the patient verification page if no patient_id is available
    header("Location: patient_verify.php");
    exit();
}

$error = "";

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    // Get the purpose and specific purpose from the POST request
    $purpose = $_POST["purpose"] ?? "";
    $specificPurpose = $_POST["specificPurpose"] ?? "";

    // If there are no errors, proceed to the next step
    if (empty($purpose)) {
        $error = "Please select a purpose for the appointment.";
    } else {
        // If patient_id is set, pass it to the next page (step3.php) via POST
        if (!empty($patientId)) {
            // Redirect using POST (send via form submission)
            echo '<form id="redirectForm" action="step3.php" method="POST">
                    <input type="hidden" name="patient_id" value="' . htmlspecialchars($patientId) . '">
                    <input type="hidden" name="appointment_type" value="' . htmlspecialchars($purpose) . '">
                    <input type="submit" value="Redirect">
                  </form>';
            echo '<script>document.getElementById("redirectForm").submit();</script>';
        } else {
            // If patient_id is not set, redirect to the patient verification page
            header("Location: appointment.php"); 
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PSC Appointment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .appointment-container {
            text-align: center;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            max-width: 600px;
            margin: 20px auto;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
        }
        .dropdown-section {
            margin: 15px 0;
        }
        .dropdown-select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
        }
        .back-btn {
            background-color: #6c757d !important;
            color: white !important;
        }
        .continue-btn {
            background-color: #2E8B57 !important;
            color: white !important;
        }
        .continue-btn:disabled {
            background-color: #ccc !important;
            cursor: not-allowed;
        }
        .error-message {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
        }
      
    </style>
    <script>
        function updateForm() {
            let purpose = document.getElementById("purpose").value;
            let specificPurposeDiv = document.getElementById("otherOptions");
            let continueBtn = document.getElementById("continueBtn");
            let specificPurpose = document.getElementById("specificPurpose");

            if (purpose === "Others") {
                specificPurposeDiv.style.display = "block";
                continueBtn.disabled = true; // Disable until specific purpose is selected
            } else {
                specificPurposeDiv.style.display = "none";
                continueBtn.disabled = purpose === ""; // Disable if "-- Select Purpose --" is chosen
            }
        }

        function enableContinue() {
            let specificPurpose = document.getElementById("specificPurpose").value;
            let continueBtn = document.getElementById("continueBtn");
            continueBtn.disabled = specificPurpose === "";
        }
    </script>
</head>
<body>
<?php include 'header.php'; ?>
    <div class="appointment-container">
        
        <!-- Display error message if validation fails -->
        <?php if (!empty($error)): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>

        <form action="step2.php" method="POST">
            <div class="dropdown-section">
                <label for="purpose">What is the purpose of your appointment?</label>
                <select id="purpose" name="purpose" class="dropdown-select" onchange="updateForm()">
                    <option value="">-- Select Purpose --</option>
                    <option value="Regular Checkup">Regular Checkup</option>
                    <option value="Follow-up Checkup">Follow-up Checkup</option>
                    <option value="Under Observation">Under Observation</option>
                    <option value="Pre-Natal Checkup">Pre-Natal Checkup</option>
                    <option value="Post-Natal Checkup">Post-Natal Checkup</option>
                    <option value="Medical Consultation">Medical Consultation</option>
                    <option value="Vaccination">Vaccination</option>
                    <option value="Others">Others</option>
                </select>
            </div>

            <!-- Second Dropdown for "Others" (Hidden by Default) -->
            <div id="otherOptions" class="dropdown-section" style="display: none;">
                <label for="specificPurpose">Select Specific Purpose:</label>
                <select id="specificPurpose" name="specificPurpose" class="dropdown-select" onchange="enableContinue()">
                    <option value="">-- Select Specific Purpose --</option>
                    <option value="Ultrasound">Ultrasound</option>
                    <option value="Maternity Checkup">Maternity Checkup</option>
                    <option value="Laboratory">Laboratory</option>
                    <option value="Surgical Procedure">Surgical Procedure</option>
                    <option value="Vaccination">Vaccination</option>
                </select>
            </div>

            <!-- Hidden field for patient_id -->
            <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($patientId); ?>">

            <div class="button-group">
                <a href="step1.php" class="btn btn-success">Back</a>
                <button type="submit" name="submit" id="continueBtn" class="btn continue-btn" disabled>Next</button>
            </div>
        </form>
    </div>
</body>
</html>
