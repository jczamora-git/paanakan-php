<?php
// Start session and include database connection
session_start();
require '../connections/connections.php';

// Check if the user is logged in as Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Get the database connection
$pdo = connection();

// Retrieve patient_id from query parameters
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : null;

// Fetch patient information if patient_id is provided
if ($patient_id) {
    $query = "
    SELECT 
        'Appointment' AS record_type,
        a.appointment_id AS record_id,
        a.created_at AS record_date,
        CONCAT('Scheduled with ', p.first_name, ' ', p.last_name, ' for ', a.appointment_type) AS description,
        NULL AS end_date
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.patient_id = :patient_id

    UNION ALL

    SELECT 
        'Admission' AS record_type,
        ad.admission_id AS record_id,
        ad.admission_date AS record_date,
        CONCAT('Admitted for ', ad.admitting_diagnosis) AS description,
        ad.discharge_date AS end_date
    FROM admissions ad
    WHERE ad.patient_id = :patient_id

    UNION ALL

    SELECT 
        'Transaction' AS record_type,
        t.transaction_id AS record_id,
        t.transaction_date AS record_date,
        CONCAT('Service: ', ms.service_name, ' - ', ms.description) AS description,
        NULL AS end_date
    FROM medical_transactions t
    JOIN medical_services ms ON t.service_id = ms.service_id
    JOIN patients p ON t.case_id = p.case_id
    WHERE p.patient_id = :patient_id AND t.service_id != 10

    ORDER BY record_date DESC;
";


    $stmt = $pdo->prepare($query);
    $stmt->execute([':patient_id' => $patient_id]);
    $health_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $_SESSION['error'] = "No patient selected.";
    header("Location: manage_health_records.php");
    exit();
}
// Fetch patient details if `patient_id` is provided
if (isset($_GET['patient_id'])) {
    $patient_id = intval($_GET['patient_id']);
    $query = "SELECT first_name, last_name, date_of_birth, gender, address, contact_number, 
                     philhealth_no, patient_status, religion, nationality, occupation, case_id 
              FROM patients WHERE patient_id = :patient_id";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':patient_id' => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        $_SESSION['error'] = "Patient not found.";
        header("Location: manage_health_records.php");
        exit();
    }

    // Get case_id from the patient record
    $case_id = $patient['case_id'];

    // Calculate Age from Birthday
    $birthdate = new DateTime($patient['date_of_birth']);
    $today = new DateTime();
    $age = $birthdate->diff($today)->y;
} else {
    $_SESSION['error'] = "Patient ID not provided.";
    header("Location: manage_health_records.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Health Records - <?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?></title>
<!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
    <style>
        :root {
            --primary-color: #2E8B57;
            --primary-light: #3CB371;
            --secondary-color: #f8f9fa;
            --text-color: #333;
            --border-color: #eee;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .dashboard-main-content {
            flex-grow: 1;
            padding: 20px;
            margin-left: 270px;
            transition: margin-left 0.4s ease;
        }

        .sidebar.collapsed ~ .dashboard-main-content {
            margin-left: 85px;
        }

        .patient-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .patient-header h2 {
            font-weight: 600;
            margin-bottom: 10px;
        }

        .patient-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .info-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            margin-bottom: 25px;
            border: none;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .info-card .card-header {
            background: var(--secondary-color);
            border-bottom: 2px solid var(--primary-color);
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }

        .info-card .card-header h5 {
            color: var(--primary-color);
            font-weight: 600;
            margin: 0;
        }

        .info-card .card-body {
            padding: 25px;
        }

        .info-item {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
        }

        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-item i {
            width: 35px;
            height: 35px;
            background: var(--secondary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .records-table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-top: 30px;
        }

        .records-table .card-header {
            background: var(--secondary-color);
            padding: 20px;
            border-bottom: 2px solid var(--primary-color);
        }

        .records-table .card-header h5 {
            color: var(--primary-color);
            font-weight: 600;
            margin: 0;
        }

        .records-table .table {
            margin-bottom: 0;
        }

        .records-table th {
            background: var(--secondary-color);
            color: var(--text-color);
            font-weight: 600;
            padding: 15px;
            border-bottom: 2px solid var(--primary-color);
        }

        .records-table td {
            vertical-align: middle;
            padding: 15px;
        }

        .badge {
            padding: 8px 12px;
            font-weight: 500;
            border-radius: 8px;
        }

        .action-buttons .btn {
            padding: 8px 12px;
            margin: 0 3px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .action-buttons .btn:hover {
            transform: scale(1.1);
        }

        .btn-view {
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .btn-view:hover {
            background: var(--primary-color);
            color: white;
        }

        .btn-edit {
            color: #007bff;
            border: 1px solid #007bff;
        }

        .btn-edit:hover {
            background: #007bff;
            color: white;
        }

        .no-records {
            padding: 40px;
            text-align: center;
            color: #666;
        }

        .no-records i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }

        .alert {
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 25px;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
                <?php include '../sidebar.php'; ?>

        <main class="dashboard-main-content">
             <?php include '../admin/breadcrumb.php'; ?>

                <!-- Display Success/Error Messages -->
                <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= $_SESSION['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                    <?php unset($_SESSION['message']); ?>
                <?php elseif (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

            <!-- Patient Header -->
            <div class="patient-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-1"><?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?></h2>
                        <p class="mb-0">
                            <i class="fas fa-id-card me-2"></i>Case ID: <?= $case_id ?>
                            <span class="mx-3">|</span>
                            <i class="fas fa-birthday-cake me-2"></i>Age: <?= $age ?> years
                            <span class="mx-3">|</span>
                            <i class="fas fa-venus-mars me-2"></i>Gender: <?= htmlspecialchars($patient['gender']) ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <img src="../psc_whitebanner.png" alt="Hospital Logo" style="max-height: 60px;">
                    </div>
                </div>
            </div>

                        <div class="row">
                            <!-- Personal Information Card -->
                            <div class="col-md-8">
                    <div class="info-card">
                        <div class="card-header">
                            <h5><i class="fas fa-user-circle me-2"></i>Personal Information</h5>
                        </div>
                                    <div class="card-body">
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <strong>Address:</strong><br>
                                    <?= htmlspecialchars($patient['address'] ?: 'N/A') ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-phone"></i>
                                <div>
                                    <strong>Contact No.:</strong><br>
                                    <?= htmlspecialchars($patient['contact_number'] ?: 'N/A') ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-calendar"></i>
                                <div>
                                    <strong>Birthday:</strong><br>
                                    <?= date("F j, Y", strtotime($patient['date_of_birth'])) ?>
                                </div>
                            </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Other Information Card -->
                            <div class="col-md-4">
                    <div class="info-card">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle me-2"></i>Other Information</h5>
                        </div>
                                    <div class="card-body">
                            <div class="info-item">
                                <i class="fas fa-hospital-user"></i>
                                <div>
                                    <strong>PhilHealth No.:</strong><br>
                                    <?= htmlspecialchars($patient['philhealth_no'] ?: 'N/A') ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-info-circle"></i>
                                <div>
                                    <strong>Status:</strong><br>
                                    <?= htmlspecialchars($patient['patient_status'] ?: 'N/A') ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-praying-hands"></i>
                                <div>
                                    <strong>Religion:</strong><br>
                                    <?= htmlspecialchars($patient['religion'] ?: 'N/A') ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-flag"></i>
                                <div>
                                    <strong>Nationality:</strong><br>
                                    <?= htmlspecialchars($patient['nationality'] ?: 'N/A') ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-briefcase"></i>
                                <div>
                                    <strong>Occupation:</strong><br>
                                    <?= htmlspecialchars($patient['occupation'] ?: 'N/A') ?>
                                </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Health Records Table -->
            <div class="records-table">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-file-medical me-2"></i>Health Records</h5>
                    <div>
                        <a href="compare_records.php?patient_id=<?= $patient_id ?>" class="btn" style="background-color: #2E8B57; color: white;">
                            <i class="fas fa-balance-scale me-2"></i>Compare Records
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Record Type</th>
                                        <th>Description</th>
                                        <th>Record Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($health_records)): ?>
                                        <?php foreach ($health_records as $record): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-<?= $record['record_type'] == 'Appointment' ? 'primary' : 
                                                                       ($record['record_type'] == 'Admission' ? 'success' : 'warning') ?>">
                                                        <?= htmlspecialchars($record['record_type']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($record['description'] ?: 'N/A') ?></td>
                                                <td><?= date("F j, Y g:i A", strtotime($record['record_date'])) ?></td>
                                        <td class="action-buttons">
                                            <?php if ($record['record_type'] == 'Transaction'): ?>
                                                <?php
                                                // Check the service name for the transaction
                                                $checkQuery = "SELECT service_name FROM medical_services ms 
                                                             JOIN medical_transactions mt ON ms.service_id = mt.service_id 
                                                             WHERE mt.transaction_id = :transaction_id";
                                                $checkStmt = $pdo->prepare($checkQuery);
                                                $checkStmt->execute([':transaction_id' => $record['record_id']]);
                                                $service = $checkStmt->fetch(PDO::FETCH_ASSOC);
                                                
                                                if ($service && strpos(strtolower($service['service_name']), 'transvaginal') !== false): ?>
                                                    <a href="../healthrecords/ultrasound_report.php?case_id=<?= $case_id ?>&transaction_id=<?= $record['record_id'] ?>" 
                                                       class="btn btn-sm btn-view" title="View Ultrasound Report">
                                                        <i class="fas fa-file-medical"></i>
                                                    </a>
                                                <?php elseif ($service && strpos(strtolower($service['service_name']), 'ob ultrasound') !== false): ?>
                                                    <a href="../healthrecords/ob_ultrasound_report.php?case_id=<?= $case_id ?>&transaction_id=<?= $record['record_id'] ?>" 
                                                       class="btn btn-sm btn-view" title="View OB Ultrasound Report">
                                                        <i class="fas fa-baby"></i>
                                                    </a>
                                                <?php elseif ($service && strpos(strtolower($service['service_name']), 'prenatal checkup') !== false): ?>
                                                    <a href="../healthrecords/prenatal_records.php?transaction_id=<?= $record['record_id'] ?>&patient_id=<?= $patient_id ?>" 
                                                       class="btn btn-sm btn-view" title="View Prenatal Checkup">
                                                        <i class="fas fa-heart"></i>
                                                    </a>
                                                <?php elseif ($service && strpos(strtolower($service['service_name']), 'circumcision') !== false): ?>
                                                    <a href="../healthrecords/circumcision_consent.php?transaction_id=<?= $record['record_id'] ?>" 
                                                       class="btn btn-sm btn-view" title="View Circumcision Consent">
                                                        <i class="fas fa-cut"></i>
                                                    </a>
                                                <?php elseif ($service && strpos(strtolower($service['service_name']), 'pap smear') !== false): ?>
                                                    <a href="../healthrecords/pap_smear_report.php?transaction_id=<?= $record['record_id'] ?>" 
                                                       class="btn btn-sm btn-view" title="View Pap Smear Report">
                                                        <i class="fas fa-file-medical"></i>
                                                    </a>
                                                <?php elseif ($service && strpos(strtolower($service['service_name']), 'hemoglobin') !== false): ?>
                                                    <a href="../healthrecords/hemoglobin_report.php?case_id=<?= $case_id ?>&transaction_id=<?= $record['record_id'] ?>" 
                                                       class="btn btn-sm btn-view" title="View Hemoglobin Report">
                                                        <i class="fas fa-tint"></i>
                                                    </a>
                                                <?php elseif ($service && strpos(strtolower($service['service_name']), 'urinalysis') !== false): ?>
                                                    <a href="../healthrecords/urinalysis_report.php?case_id=<?= $case_id ?>&transaction_id=<?= $record['record_id'] ?>" 
                                                       class="btn btn-sm btn-view" title="View Urinalysis Report">
                                                        <i class="fas fa-vial"></i>
                                                    </a>
                                                <?php elseif ($service && strpos(strtolower($service['service_name']), 'postnatal checkup') !== false): ?>
                                                    <a href="../healthrecords/postnatal_records.php?transaction_id=<?= $record['record_id'] ?>&patient_id=<?= $patient_id ?>" 
                                                       class="btn btn-sm btn-view" title="View Postnatal Checkup">
                                                        <i class="fas fa-baby"></i>
                                                    </a>
                                                <?php elseif ($service && strpos(strtolower($service['service_name']), 'vaccination') !== false): ?>
                                                    <a href="../healthrecords/vaccination_records.php?transaction_id=<?= $record['record_id'] ?>&patient_id=<?= $patient_id ?>" 
                                                       class="btn btn-sm btn-view" title="View Vaccination Record">
                                                        <i class="fas fa-syringe"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="in_action.php?mode=view&record_id=<?= $record['record_id'] ?>&patient_id=<?= $patient_id?>" 
                                                       class="btn btn-sm btn-view" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                            <?php elseif ($record['record_type'] == 'Appointment'): ?>
                                                <?php if (strpos(strtolower($record['description']), 'pre-natal checkup') !== false): ?>
                                                    <a href="../appointments_records/prenatal_checkup.php?appointment_id=<?= $record['record_id'] ?>"
                                                       class="btn btn-sm btn-view" title="View Prenatal Checkup">
                                                        <i class="fas fa-heart"></i>
                                                    </a>
                                                <?php elseif (strpos(strtolower($record['description']), 'regular checkup') !== false): ?>
                                                    <a href="/paanakan/appointments_records/regular_checkup.php?patient_id=<?= $patient_id ?>"
                                                       class="btn btn-sm btn-view" title="View Regular Checkup Record">
                                                        <i class="fas fa-stethoscope"></i>
                                                    </a>
                                                <?php elseif (strpos(strtolower($record['description']), 'post-natal checkup') !== false): ?>
                                                    <a href="../appointments_records/postnatal_checkup.php?appointment_id=<?= $record['record_id'] ?>"
                                                       class="btn btn-sm btn-view" title="View Post-Natal Checkup">
                                                        <i class="fas fa-baby"></i>
                                                    </a>
                                                <?php elseif (strpos(strtolower($record['description']), 'medical consultation') !== false): ?>
                                                    <a href="../appointments_records/medical_consultation.php?appointment_id=<?= $record['record_id'] ?>"
                                                       class="btn btn-sm btn-view" title="View Medical Consultation">
                                                        <i class="fas fa-user-md"></i>
                                                    </a>
                                                <?php elseif (strpos(strtolower($record['description']), 'vaccination') !== false): ?>
                                                    <a href="../appointments_records/vaccination.php?appointment_id=<?= $record['record_id'] ?>"
                                                       class="btn btn-sm btn-view" title="View Vaccination Record">
                                                        <i class="fas fa-syringe"></i>
                                                    </a>
                                                <?php elseif (strpos(strtolower($record['description']), 'follow-up') !== false): ?>
                                                    <a href="../appointments_records/follow_up.php?appointment_id=<?= $record['record_id'] ?>"
                                                       class="btn btn-sm btn-view" title="View Follow-up Record">
                                                        <i class="fas fa-undo"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="in_action.php?mode=view&record_id=<?= $record['record_id'] ?>&patient_id=<?= $patient_id?>" 
                                                       class="btn btn-sm btn-view" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                            <?php elseif ($record['record_type'] == 'Admission'): ?>
                                                <a href="in_action.php?mode=edit&record_id=<?= $record['record_id'] ?>&patient_id=<?= $patient_id?>" 
                                                   class="btn btn-sm btn-view" title="View Admission Record">
                                                    <i class="fas fa-hospital-user"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="in_action.php?mode=view&record_id=<?= $record['record_id'] ?>&patient_id=<?= $patient_id?>" 
                                                   class="btn btn-sm btn-view" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                <td colspan="4" class="no-records">
                                    <i class="fas fa-file-medical"></i>
                                    <p class="mb-0">No health records found for this patient.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Remove all checkbox-related code
    });
</script>
</body>

</html>
