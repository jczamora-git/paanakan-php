<?php
// Start session and check if the user is logged in as Patient
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Patient') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require '../connections/connections.php';
require '../activity_log.php';

// Get database connection
$pdo = connection();

// Create an instance of the ActivityLog class
$activityLog = new ActivityLog($pdo);

// Get the logged-in user's ID (Patient)
$user_id = $_SESSION['user_id'];

// Fetch the patient_id linked to this user_id
$stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    $_SESSION['error'] = "You are not registered as a patient.";
    header("Location: ../dashboard.php");
    exit();
}

$patient_id = $patient['patient_id']; // Get the corresponding patient_id

// Set default date (today)
date_default_timezone_set('Asia/Manila');
$selectedDate = date('Y-m-d');

// Display stored error from JavaScript
if (isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']); // Clear the error after displaying
} elseif (isset($_SESSION['message'])) {
    $successMessage = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the message after displaying
} else {
    $errorMessage = "";
    $successMessage = "";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">  
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/calendar.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/toast-alert.css">

    <style>
        .container-wrapper {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin: 40px auto;
            max-width: 1100px;
        }
        .slots-container {
            flex: 1;
            max-width: 45%;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
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
        }
        .back-btn, .next-btn {
            background-color: #2E8B57 !important;
            color: white !important;
        }
        .next-btn:disabled {
            background-color: #ccc !important;
            cursor: not-allowed;
        }
    </style>
</head>

<body style="font-family: 'Poppins', sans-serif;">
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>

        <main class="dashboard-main-content">
            <div class="container mt-4">  
                
                <!-- Display Success/Error Messages -->
                <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $successMessage ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif (!empty($errorMessage)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $errorMessage ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>


                <!-- Breadcrumb Navigation -->
                <?php include '../admin/breadcrumb.php'; ?>
                <!-- Calendar and Slot Selection -->
                <div class="container-wrapper">
                    <!-- Left: Calendar -->
                    <div class="col-lg-6">
                        <div class="calendar-wrapper">
                            <div id="calendar"></div> <!-- Calendar will populate this -->
                        </div>
                    </div>

                    <!-- Right: Available Slots -->
                    <div class="slots-container">
                        <h5>Appointment Type</h5>
                        <select class="form-select mb-3" id="appointmentType" required>
                            <option value="">-- Select Purpose --</option>
                            <option value="Regular Checkup">Regular Checkup</option>
                            <option value="Follow-up Checkup">Follow-up Checkup</option>
                            <option value="Under Observation">Under Observation</option>
                            <option value="Pre-Natal Checkup">Pre-Natal Checkup</option>
                            <option value="Post-Natal Checkup">Post-Natal Checkup</option>
                            <option value="Medical Consultation">Medical Consultation</option>
                            <option value="Vaccination">Vaccination</option>
                        </select>

                        <div class="table-container">
                            <!-- The table will be populated here dynamically with AJAX -->
                        </div>

                        <div class="button-group">
                            <button id="scheduleBtn" class="btn next-btn" disabled>Schedule Appointment</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to schedule this appointment?</p>
                    <p><strong id="appointment-info"></strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="cancel-appointment">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirm-appointment">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Approval Modal -->
    <div class="modal fade" id="pendingModal" tabindex="-1" aria-labelledby="pendingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center" style="padding: 40px;">
                    <h3 class="mb-3" style="color: #2E8B57; font-weight: 700;">Appointment Submitted</h3>
                    <p style="font-size: 18px; color: #333;">Your appointment has been submitted and is <strong>pending approval</strong> by the clinic administrator.</p>
                    <p style="font-size: 16px; color: #666; margin-top: 12px;">You will receive a notification once your appointment is approved or if additional information is needed.</p>
                    <button type="button" class="btn btn-primary mt-4" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../calendar.js"></script>
    <script src="../js/toast-alert.js"></script>
    <script>
   $(document).ready(function () {
    let selectedDate = "<?= $selectedDate ?>";
    let patient_id = "<?= $patient_id ?>";
    let selectedTime = null;

    function loadAppointments(date) {
        selectedTime = null; // Reset selected time when changing date
        $("#scheduleBtn").prop("disabled", true); // Disable schedule button

        $.ajax({
            url: '../fetch_appointments.php',
            method: 'GET',
            data: { date: date, _: new Date().getTime() }, // Prevent caching
            success: function (response) {
                $(".table-container").html(response);
            },
            error: function () {
                console.error("Error fetching appointments.");
                $(".table-container").html("<tr><td colspan='3' class='text-center'>Error loading appointments.</td></tr>");
            }
        });
    }

    $(document).on("click", ".selectable-row", function () {
        if ($(this).hasClass("disabled")) return;

        $(".selectable-row").removeClass("selected-row");
        $(this).addClass("selected-row");

        selectedTime = $(this).data("time");
        $("#scheduleBtn").prop("disabled", false); // Enable schedule button
    });

    $("#scheduleBtn").click(function () {
        let appointmentType = $("#appointmentType").val();

        if (!appointmentType) {
            showAlert("error", "Please select an appointment type before scheduling.");
            return;
        }

        $("#appointment-info").text(`Date: ${selectedDate}, Time: ${selectedTime}, Type: ${appointmentType}`);
        $("#confirmationModal").modal("show");
    });

    $("#confirm-appointment").click(function () {
        let appointmentType = $("#appointmentType").val();

        $.ajax({
            url: "../process_appointment.php",
            method: 'POST',
            dataType: 'json',
            data: {
                patient_id: patient_id,
                appointment_type: appointmentType,
                dateTime: selectedDate + " " + selectedTime
            },
            success: function (response) {
                $("#confirmationModal").modal("hide");

                if (response && response.success) {
                    // If server reports pending status, show pending modal and toast
                    if (response.status === 'pending') {
                        $("#pendingModal").modal("show");
                        if (typeof Toast !== 'undefined' && Toast.info) {
                            Toast.info('Appointment submitted and is pending approval by the clinic administrator.');
                        } else {
                            showAlert("success", "Appointment submitted and is pending approval by the clinic administrator.");
                        }
                    } else {
                        // Generic success (non-pending)
                        if (typeof Toast !== 'undefined' && Toast.success) {
                            Toast.success(response.message || 'Appointment scheduled successfully.');
                        } else {
                            showAlert("success", response.message || 'Appointment scheduled successfully.');
                        }
                    }

                    // Optionally log email send result to console for debugging
                    if (response.email) console.info('Email result:', response.email);

                    // Refresh available slots
                    loadAppointments(selectedDate);
                } else {
                    var msg = (response && response.message) ? response.message : 'Failed to schedule appointment. Please try again.';
                    if (typeof Toast !== 'undefined' && Toast.error) {
                        Toast.error(msg);
                    } else {
                        showAlert("error", msg);
                    }
                }
            },
            error: function () {
                // Use toast for failures if available
                if (typeof Toast !== 'undefined' && Toast.error) {
                    Toast.error('Failed to schedule appointment. Please try again.');
                } else {
                    showAlert("error", "Failed to schedule appointment. Please try again.");
                }
            }
        });
    });

    $("#cancel-appointment").click(function () {
        $("#confirmationModal").modal("hide"); // Ensure modal closes when canceled
        showAlert("error", "Appointment scheduling was canceled.");
    });

    function showAlert(type, message) {
        let alertClass = type === "success" ? "alert-success" : "alert-danger";
        $(".container.mt-4").prepend(`
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
    }

    // When the user selects a different date, reload appointments
    $(document).on("click", ".calendar-day:not(.disabled)", function () {
        selectedDate = $(this).data("date");
        loadAppointments(selectedDate);
    });

    // Load appointments on page load
    loadAppointments(selectedDate);
});


    </script>
</body>
</html>
