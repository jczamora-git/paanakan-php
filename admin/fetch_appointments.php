<?php
require '../connections/connections.php';
date_default_timezone_set('Asia/Manila');

$pdo = connection();

// Handle POST request for updating appointment status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (isset($_POST['appointment_id']) && isset($_POST['status'])) {
        try {
            $stmt = $pdo->prepare("UPDATE Appointments SET status = :status WHERE appointment_id = :appointment_id");
            $stmt->execute([
                ':status' => $_POST['status'],
                ':appointment_id' => $_POST['appointment_id']
            ]);
            
            // Redirect back with success message
            header("Location: manage_appointments.php?success=1");
            exit;
        } catch (PDOException $e) {
            // Redirect back with error message
            header("Location: manage_appointments.php?error=1");
            exit;
        }
    }
}

if (isset($_GET['date'])) {
    $selectedDate = date("Y-m-d", strtotime($_GET['date'])); // Ensure format YYYY-MM-DD

    // Fetch appointments for the selected date
    $appointmentsQuery = "
        SELECT a.appointment_id, a.scheduled_date, a.appointment_type, 
               p.first_name, p.last_name, p.contact_number
        FROM Appointments a
        JOIN Patients p ON a.patient_id = p.patient_id
        WHERE DATE(a.scheduled_date) = :selectedDate AND a.status = 'Approved'
        ORDER BY a.scheduled_date ASC";

    $stmt = $pdo->prepare($appointmentsQuery);
    $stmt->bindParam(':selectedDate', $selectedDate, PDO::PARAM_STR);
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add simple JavaScript for confirmation
    echo '<script>
    function confirmMarkAsMissed(form) {
        if (confirm("Are you sure you want to mark this appointment as missed? This action cannot be undone.")) {
            form.submit();
        }
        return false;
    }
    </script>';

    // Generate table rows dynamically
    if (!empty($appointments)) {
        foreach ($appointments as $appointment) {
            $type = strtolower($appointment['appointment_type']);
            $viewLink = '';
            $viewTitle = '';
            if ($type === 'regular checkup') {
                $viewLink = "/paanakan/appointments_records/regular_checkup.php?appointment_id=" . $appointment['appointment_id'];
                $viewTitle = "Regular Checkup Record";
            } elseif ($type === 'under observation') {
                $viewLink = "/paanakan/appointments_records/under_observation.php?appointment_id=" . $appointment['appointment_id'];
                $viewTitle = "Under Observation Record";
            } elseif ($type === 'pre-natal checkup') {
                $viewLink = "/paanakan/appointments_records/prenatal_checkup.php?appointment_id=" . $appointment['appointment_id'];
                $viewTitle = "Pre-Natal Records";
            } elseif ($type === 'post-natal checkup') {
                $viewLink = "/paanakan/appointments_records/postnatal_checkup.php?appointment_id=" . $appointment['appointment_id'];
                $viewTitle = "Post-Natal Records";
            } elseif ($type === 'medical consultation') {
                $viewLink = "/paanakan/appointments_records/medical_consultation.php?appointment_id=" . $appointment['appointment_id'];
                $viewTitle = "Medical Consultation Record";
            } elseif ($type === 'vaccination') {
                $viewLink = "/paanakan/appointments_records/vaccination.php?appointment_id=" . $appointment['appointment_id'];
                $viewTitle = "Vaccination Record";
            } elseif ($type === 'follow-up') {
                $viewLink = "/paanakan/appointments_records/follow_up.php?appointment_id=" . $appointment['appointment_id'];
                $viewTitle = "Follow-up Record";
            }
            echo "<tr>
                    <td>" . date("g:i A", strtotime($appointment['scheduled_date'])) . "</td>
                    <td>" . htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) . "</td>
                    <td>" . htmlspecialchars($appointment['contact_number']) . "</td>
                    <td>" . htmlspecialchars($appointment['appointment_type']) . "</td>
                    <td>
                        <div class='btn-group' role='group'>
                            <a href='" . $viewLink . "' class='action-icon me-2' data-bs-toggle='tooltip' data-bs-placement='top' title='" . $viewTitle . "'>
                                <i class='fas fa-file-medical' style='color: #2E8B57;'></i>
                            </a>
                            <form method='POST' action='manage_appointments.php' style='display:inline;' onsubmit='return confirmMarkAsMissed(this);'>
                                <input type='hidden' name='action' value='update_status'>
                                <input type='hidden' name='appointment_id' value='" . $appointment['appointment_id'] . "'>
                                <input type='hidden' name='status' value='Missed'>
                                <button type='submit' class='action-icon' data-bs-toggle='tooltip' data-bs-placement='top' title='Mark as Missed'>
                                    <i class='fas fa-times' style='color: #dc3545;'></i>
                                </button>
                            </form>
                        </div>
                    </td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='5' class='text-center'>No appointments for this day.</td></tr>";
    }
}
?>
