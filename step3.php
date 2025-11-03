<?php
session_start();
date_default_timezone_set('Asia/Manila'); // Set timezone to Manila
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date("Y-m-d");
// Check if 'appointment_type' and 'patient_id' are set in POST
if (isset($_POST['appointment_type']) && isset($_POST['patient_id'])) {
    $appointment_type = $_POST['appointment_type'];
    $patient_id = $_POST['patient_id'];
   
} else {
    // Redirect to appointment.php if appointment_type or patient_id is not set
    header("Location: appointment.php");
    exit(); // Make sure to call exit after header to stop further script execution
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 3: Select Appointment Slot</title>
    <link rel="stylesheet" href="calendar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* General Layout */
        .container-wrapper {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin: 40px auto;
            max-width: 1100px;
        }

        /* Slot Table Section */
        .slots-container {
            flex: 1;
            max-width: 45%;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
        }

        /* Back and Next Buttons */
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

        .back-btn {
            background-color: #2E8B57 !important;
            color: white !important;
            text-decoration: none;
        }

        .next-btn {
            background-color: #2E8B57 !important;
            color: white !important;
        }

        .next-btn:disabled {
            background-color: #ccc !important;
            cursor: not-allowed;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container-wrapper {
                flex-direction: column;
                align-items: center;
            }

            .calendar-container, .slots-container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container-wrapper">
    <!-- Left: Calendar -->
    <div class="col-lg-6">
        <div class="calendar-wrapper">
            <div id="calendar"></div> <!-- Calendar will populate this -->
        </div>
    </div>

    <!-- Right: Available Slots -->
    <div class="slots-container">
        <!-- Selected Date Display (Formatted) -->
        <div class="date-left">
            <!-- Large Day Number -->
            <span class="day-number" id="selected-date"><?= date('d', strtotime($selectedDate)) ?></span>
            <div class="date-text">
                <!-- Month / Year -->
                <span class="month-year" id="selected-month"><?= date('m/Y', strtotime($selectedDate)) ?></span>
                <!-- Weekday -->
                <span class="weekday" id="selected-day"><?= date('l', strtotime($selectedDate)) ?></span>
            </div>
        </div>
        <div class="table-container">
            <!-- The table will be populated here dynamically with AJAX -->
        </div>

        <!-- Back and Next Buttons -->
        <div class="button-group">
            <!-- Back Button (Passes patient_id via POST) -->
            <form action="step2.php" method="POST">
                <input type="hidden" name="patient_id" value="<?= htmlspecialchars($patient_id) ?>">
                <button type="submit" class="btn back-btn">Back</button>
            </form>

            <!-- Next Button (Disabled by default) -->
            <button id="nextBtn" class="btn next-btn" disabled>Next</button>
        </div>

    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmationModalLabel">Confirm Appointment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to make an appointment for this date and time?</p>
                <p><strong id="appointment-info"></strong></p> <!-- Display the selected date and time here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancel-appointment">Cancel</button>
                <button type="button" class="btn btn-success" id="confirm-appointment">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="calendar.js"></script>
<script>
    $(document).ready(function () {
        let selectedTime = null;
        let selectedDate = "<?= $selectedDate ?>";
        let patient_id = "<?= $patient_id ?>";
        let appointment_type = "<?= $appointment_type ?>";

        // Function to format date in Manila timezone
        function formatDateInManilaTime(date) {
            return new Date(date).toLocaleString('en-US', { timeZone: 'Asia/Manila' });
        }

        function getSelectedDateTime() {
            if (selectedDate && selectedTime) {
                // Create date object in Manila timezone
                let dateTimeString = selectedDate + " " + selectedTime;
                let manilaDateTime = new Date(dateTimeString).toLocaleString('en-US', { 
                    timeZone: 'Asia/Manila',
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
                $('#appointment-info').text(manilaDateTime);
            }
        }

        $(document).on("click", ".selectable-row", function () {
            if ($(this).hasClass("disabled")) return;

            $(".selectable-row").removeClass("selected-row");
            $(this).addClass("selected-row");

            selectedTime = $(this).data("time");

            getSelectedDateTime();
            $("#nextBtn").prop("disabled", false);
        });

        $('#nextBtn').on('click', function () {
            getSelectedDateTime();
            $('#confirmationModal').modal('show');
        });

        $('#confirm-appointment').on('click', function () {
            let dateTimeString = $('#appointment-info').text();

            $.ajax({
                url: 'process_appointment.php',
                method: 'POST',
                data: {
                    dateTime: dateTimeString,
                    patient_id: patient_id,
                    appointment_type: appointment_type
                },
                success: function(response) {
                    console.log("Server Response: " + response);

                    // Show success message inside the modal
                    $('#confirmationModal .modal-body').html(`
                        <p class="text-success"><strong>Appointment successfully created!</strong></p>
                        <p>Appointment Date & Time: <strong>${dateTimeString}</strong></p>
                    `);

                    // Change buttons inside the modal
                    $('#confirm-appointment').hide();
                    $('#cancel-appointment').text('Close').removeClass('btn-secondary').addClass('btn-success');

                    // Redirect after modal is closed
                    $('#confirmationModal').on('hidden.bs.modal', function () {
                        // Create a form and submit via POST
                        var form = $('<form>', {
                            action: 'appointment_overview.php',
                            method: 'POST',
                            style: 'display:none;'
                        });
                        form.append($('<input>', {type: 'hidden', name: 'patient_id', value: patient_id}));
                        form.append($('<input>', {type: 'hidden', name: 'appointment_date_time', value: dateTimeString}));
                        $('body').append(form);
                        form.submit();
                    });
                },
                error: function() {
                    console.error("Error sending data to the server.");
                    $('#confirmationModal .modal-body').html(`<p class="text-danger">Error creating appointment. Please try again.</p>`);
                }
            });
        });

        $('#cancel-appointment').on('click', function () {
            $('#confirmationModal').modal('hide');
        });

        // Function to load available slots and update the selected date display when the date changes
        function loadAppointments(date) {
            $.ajax({
                url: 'fetch_appointments.php',
                method: 'GET',
                data: { 
                    date: date,
                    timezone: 'Asia/Manila' // Pass timezone to fetch_appointments.php
                },
                success: function (response) {
                    $(".table-container").html(response);

                    // Format date in Manila timezone
                    let selectedDateObj = new Date(date + 'T00:00:00+08:00'); // Explicitly set to Manila timezone
                    $("#selected-date").text(selectedDateObj.getDate().toString().padStart(2, "0"));
                    $("#selected-month").text((selectedDateObj.getMonth() + 1).toString().padStart(2, "0") + "/" + selectedDateObj.getFullYear());
                    $("#selected-day").text(selectedDateObj.toLocaleDateString('en-US', { 
                        weekday: 'long',
                        timeZone: 'Asia/Manila'
                    }));

                    $("#nextBtn").prop("disabled", true);
                },
                error: function () {
                    console.error("Error fetching appointments.");
                    $(".table-container").html("<tr><td colspan='3' class='text-center'>Error loading appointments.</td></tr>");
                }
            });
        }

        // Load appointments when the date is changed from the calendar
        $(document).on("click", ".calendar-day", function () {
            selectedDate = $(this).data("date");

            // Load new appointments for the selected date
            loadAppointments(selectedDate);
        });

        // Load default appointments on page load
        loadAppointments(selectedDate);
    });
</script>

</body>
</html>