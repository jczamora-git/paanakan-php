<?php
$error = "";
$patient = null;
$caseIdVerified = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $hasCaseId = $_POST["hasCaseId"] ?? "";
    $caseId = $_POST["caseId"] ?? "";

    // If "No" is selected, redirect to add_patient.php
    if ($hasCaseId === "no") {
        header("Location: add_patient.php");
        exit();
    }

    // Validate "Yes" option
    if ($hasCaseId === "yes") {
        if (empty($caseId)) {
            $error = "Please enter your Case ID.";
        } else {
            require 'connections/connections.php';
            $pdo = connection();

            $query = "SELECT * FROM patients WHERE case_id = :case_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':case_id' => $caseId]);
            $patient = $stmt->fetch();

            if ($patient) {
                $caseIdVerified = true;
            } else {
                $error = "Case ID not found in the system. Please verify and try again.";
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
        function toggleCaseIdField() {
            const hasCaseId = document.getElementById("hasCaseId").value;
            const caseIdDiv = document.getElementById("caseIdField");
            const continueBtn = document.getElementById("continueBtn");

            if (hasCaseId === "yes") {
                caseIdDiv.style.display = "block";
                continueBtn.disabled = !document.getElementById("caseId").value;
            } else if (hasCaseId === "no") {
                window.location.href = "add_patient.php";
            } else {
                caseIdDiv.style.display = "none";
                continueBtn.disabled = true;
            }
        }

        function enableContinue() {
            const caseId = document.getElementById("caseId").value;
            document.getElementById("continueBtn").disabled = caseId === "";
        }
    </script>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="appointment-container">
        
        <?php if (!empty($error)): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>

        <form id="verifyForm" action="step1.php" method="POST">
            <div class="dropdown-section">
                <label for="hasCaseId">Do you already have Case ID?</label>
                <select id="hasCaseId" name="hasCaseId" class="dropdown-select" onchange="toggleCaseIdField()" required>
                    <option value="">-- Select --</option>
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                </select>
            </div>

            <div id="caseIdField" class="dropdown-section" style="display: none;">
                <label for="caseId">Enter Case ID</label>
                <input type="text" id="caseId" name="caseId" class="dropdown-select" onchange="enableContinue()" required>
            </div>

            <div class="button-group">
                <a href="appointment.php" class="btn btn-success">Back</a>
                <button type="submit" name="submit" id="continueBtn" class="btn continue-btn" disabled>Next</button>
            </div>
        </form>
    </div>

    <?php if ($caseIdVerified && $patient): ?>
    <div class="modal fade" id="patientDetailsModal" tabindex="-1" aria-labelledby="patientDetailsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="patientDetailsModalLabel">Welcome, <?php echo htmlspecialchars($patient['first_name']) . ' ' . htmlspecialchars($patient['last_name']); ?>!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>You are about to make an Appointment with Paanakan sa Calapan.</p>
                </div>
                <div class="modal-footer">
                    <form action="step2.php" method="POST">
                        <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($patient['patient_id']); ?>">
                        <button type="submit" class="btn btn-success">Continue</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript to Trigger Modal on Page Load -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var myModal = new bootstrap.Modal(document.getElementById('patientDetailsModal'));
            myModal.show();
        });
    </script>
<?php endif; ?>

<!-- Ensure Bootstrap JS is Loaded -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
