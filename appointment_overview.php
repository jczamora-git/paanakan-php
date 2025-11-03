<?php
// Start session or handle any required initialization
session_start();

// Support POST as primary, fallback to GET for backward compatibility
$patient_id = $_POST['patient_id'] ?? $_GET['patient_id'] ?? '';
$appointment_date_time = $_POST['appointment_date_time'] ?? $_GET['appointment_date_time'] ?? '';

if (empty($patient_id) || empty($appointment_date_time)) {
    echo "Error: Missing patient_id or appointment_date_time.";
    exit();
}

// Convert the 12-hour format date to 24-hour format
$appointment_date_time_24hr = date("Y-m-d H:i:s", strtotime($appointment_date_time));

require 'connections/connections.php';
$pdo = connection(); // Use the correct database connection function

// Query to fetch the appointment details
$query = "SELECT * FROM appointments WHERE patient_id = :patient_id AND scheduled_date = :scheduled_date";
$stmt = $pdo->prepare($query);
$stmt->execute([
    ':patient_id' => $patient_id,
    ':scheduled_date' => $appointment_date_time_24hr
]);

$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

// If no data is found, display an error
if (!$appointment) {
    echo "No appointment found for the provided details.";
    exit();
}

// Fetch patient details (first_name and last_name instead of name)
$patientQuery = "SELECT first_name, last_name FROM patients WHERE patient_id = :patient_id";
$patientStmt = $pdo->prepare($patientQuery);
$patientStmt->execute([':patient_id' => $patient_id]);
$patient = $patientStmt->fetch(PDO::FETCH_ASSOC);

$patient_name = $patient['first_name'] . ' ' . $patient['last_name'] ?? 'Unknown Patient';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Overview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            color: #2c3e50;
        }

        .content-container {
            margin: 30px auto;
            padding: 40px;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            max-width: 800px;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background-color: #2E8B57;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }

        .success-icon i {
            font-size: 40px;
            color: white;
        }

        h2 {
            font-size: 2rem;
            color: #2E8B57;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
        }

        .details-section {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .detail-item:last-child {
            margin-bottom: 0;
        }

        .detail-icon {
            width: 40px;
            height: 40px;
            background-color: #E6F9F1;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .detail-icon i {
            color: #2E8B57;
            font-size: 20px;
        }

        .detail-content {
            flex-grow: 1;
        }

        .detail-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 2px;
        }

        .detail-value {
            font-size: 1.1rem;
            color: #2c3e50;
            font-weight: 500;
        }

        .notice-container {
            background-color: #E6F9F1;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #D3F2E1;
            margin-bottom: 30px;
        }

        .notice-container h3 {
            color: #2E8B57;
            font-size: 1.3rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .notice-container h3 i {
            margin-right: 10px;
        }

        .notice-container p {
            color: #555;
            font-size: 1rem;
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
        }

        .notice-container p i {
            color: #2E8B57;
            margin-right: 10px;
            margin-top: 4px;
        }

        .btn-container {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #2E8B57;
            border-color: #2E8B57;
        }

        .btn-primary:hover {
            background-color: #23845E;
            border-color: #23845E;
            transform: translateY(-2px);
        }

        .btn-outline {
            background-color: transparent;
            border: 2px solid #2E8B57;
            color: #2E8B57;
        }

        .btn-outline:hover {
            background-color: #2E8B57;
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="content-container">
    <div class="success-icon">
        <i class="fas fa-check"></i>
    </div>
    
    <h2>Appointment Scheduled Successfully!</h2>

    <div class="details-section">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Patient Name</div>
                        <div class="detail-value"><?php echo htmlspecialchars($patient_name); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Appointment Type</div>
                        <div class="detail-value"><?php echo htmlspecialchars($appointment['appointment_type']); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Date</div>
                        <div class="detail-value"><?php echo htmlspecialchars(date('F j, Y', strtotime($appointment['scheduled_date']))); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Time</div>
                        <div class="detail-value"><?php echo htmlspecialchars(date('g:i A', strtotime($appointment['scheduled_date']))); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="notice-container">
        <h3><i class="fas fa-info-circle"></i> Important Reminders</h3>
        <p><i class="fas fa-clock"></i> Please arrive on time for your appointment.</p>
        <p><i class="fas fa-exclamation-circle"></i> If you are late by up to 5 minutes, a walk-in patient may be served before you.</p>
        <p><i class="fas fa-exclamation-triangle"></i> If you are more than 5 minutes late, you will have to wait until the walk-in patient is finished.</p>
        <p><i class="fas fa-hand-point-right"></i> To avoid any inconvenience, please arrive on time. Thank you for your understanding!</p>
    </div>

    <div class="btn-container justify-content-center" style="justify-content: center;">
        <a href="login.php" class="btn btn-success">
            <i class="fas fa-sign-in-alt"></i>
            Login to View Appointments
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
