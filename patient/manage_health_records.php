<?php
// Start session and include database connection
session_start();
require '../connections/connections.php';

// Check if the user is logged in as Patient
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Patient') {
    header("Location: ../login.php");
    exit();
}

// Get the database connection
$pdo = connection();

// Retrieve the logged-in user's ID (Patient)
$user_id = $_SESSION['user_id'];

// Fetch the patient_id using user_id
$query = "SELECT patient_id, first_name, last_name, date_of_birth, gender, address, contact_number, philhealth_no, patient_status, religion, nationality, occupation, case_id FROM patients WHERE user_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    $_SESSION['error'] = "No patient record found for this user.";
    header("Location: ../dashboard.php");
    exit();
}

$patient_id = $patient['patient_id'];
$case_id = $patient['case_id'];

// Calculate Age from Birthday
$birthdate = new DateTime($patient['date_of_birth']);
$today = new DateTime();
$age = $birthdate->diff($today)->y;

// Build a more specific health records list: only include a record type if the patient has at least one record in that table
$health_records = [];

// Admission
$stmt = $pdo->prepare('SELECT * FROM admissions WHERE patient_id = :patient_id');
$stmt->execute([':patient_id' => $patient_id]);
$admissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($admissions) {
    $health_records[] = [
        'record_type' => 'Admission',
        'record_id' => $admissions[0]['admission_id'],
        'record_date' => $admissions[0]['admission_date'],
        'description' => 'Admitted for ' . $admissions[0]['admitting_diagnosis'],
    ];
}

// Helper: get all appointment_ids and transaction_ids for this patient
$stmt = $pdo->prepare('SELECT appointment_id FROM appointments WHERE patient_id = :patient_id');
$stmt->execute([':patient_id' => $patient_id]);
$appointment_ids = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'appointment_id');

$stmt = $pdo->prepare("SELECT t.transaction_id FROM medical_transactions t JOIN patients p ON t.case_id = p.case_id WHERE p.patient_id = :patient_id");
$stmt->execute([':patient_id' => $patient_id]);
$transaction_ids = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'transaction_id');

// Helper for IN clause
function placeholders($array) {
    return implode(',', array_fill(0, count($array), '?'));
}

// Table checks (appointment_id based)
$appt_tables = [
    'Follow Up' => 'follow_up_records',
    'Medical Consultation' => 'medical_consultation_records',
    'Regular Checkup' => 'regular_checkup_records',
    'Under Observation' => 'under_observation_records',
];
foreach ($appt_tables as $label => $table) {
    if ($appointment_ids) {
        $sql = "SELECT * FROM $table WHERE appointment_id IN (" . placeholders($appointment_ids) . ") LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($appointment_ids);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $health_records[] = [
                'record_type' => $label,
                'record_id' => $row['appointment_id'],
                'record_date' => $row['created_at'] ?? $row['visit_date'] ?? $row['record_date'] ?? null,
                'description' => $label . ' Record',
            ];
        }
    }
}

// Table checks (transaction_id based)
$trans_tables = [
    'Hemoglobin' => 'hemoglobin',
    'OB Ultrasound' => 'ob_ultrasound',
    'Pap Smear' => 'pap_smear',
    'TV Ultrasound' => 'tv_ultrasound',
    'Urinalysis' => 'urinalysis',
    'Circumcision Consent' => 'circumcision_consent',
];
foreach ($trans_tables as $label => $table) {
    if ($transaction_ids) {
        $sql = "SELECT * FROM $table WHERE transaction_id IN (" . placeholders($transaction_ids) . ") LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($transaction_ids);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $health_records[] = [
                'record_type' => $label,
                'record_id' => $row['transaction_id'],
                'record_date' => $row['created_at'] ?? $row['report_date'] ?? $row['record_date'] ?? null,
                'description' => $label . ' Record',
            ];
        }
    }
}

// Table checks (either appointment_id or transaction_id)
$either_tables = [
    'Postnatal' => 'postnatal_records',
    'Prenatal' => 'prenatal_records',
    'Vaccination' => 'vaccination_records',
];
foreach ($either_tables as $label => $table) {
    $rows = [];
    if ($appointment_ids) {
        $sql = "SELECT * FROM $table WHERE appointment_id IN (" . placeholders($appointment_ids) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($appointment_ids);
        $rows = array_merge($rows, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    if ($transaction_ids) {
        $sql = "SELECT * FROM $table WHERE transaction_id IN (" . placeholders($transaction_ids) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($transaction_ids);
        $rows = array_merge($rows, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    foreach ($rows as $row) {
        $is_appointment = !empty($row['appointment_id']);
        $health_records[] = [
            'record_type' => $label,
            'record_id' => $is_appointment ? $row['appointment_id'] : $row['transaction_id'],
            'record_date' => $row['created_at'] ?? $row['visit_date'] ?? $row['report_date'] ?? $row['record_date'] ?? null,
            'description' => $label . ' Record',
            'link_type' => $is_appointment ? 'appointment' : 'transaction',
        ];
    }
}

// Sort by record_date DESC
usort($health_records, function($a, $b) {
    return strtotime($b['record_date']) <=> strtotime($a['record_date']);
});
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Health Records</title>
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
        .bg-purple {
            background-color: #8e44ad !important;
            color: #fff !important;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>

        <main class="dashboard-main-content">
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

            <!-- Add Compare Records button above the table -->
            <div class="d-flex justify-content-end mb-3">
                <a href="compare_records.php" class="btn btn-primary">
                    <i class="fas fa-balance-scale me-2"></i>Compare Records
                </a>
            </div>

            <!-- Health Records Table -->
            <div class="records-table">
                <div class="card-header">
                    <h5><i class="fas fa-file-medical me-2"></i>Health Records</h5>
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
                                            <?php
                                            $appt_types = ['Follow Up', 'Medical Consultation', 'Regular Checkup', 'Under Observation'];
                                            $trans_types = ['Hemoglobin', 'OB Ultrasound', 'Pap Smear', 'TV Ultrasound', 'Urinalysis', 'Circumcision Consent'];
                                            $either_types = ['Postnatal', 'Prenatal', 'Vaccination'];
                                            $badge_class = '';
                                            if (in_array($record['record_type'], $appt_types)) {
                                                $badge_class = 'bg-purple';
                                            } elseif (in_array($record['record_type'], $trans_types)) {
                                                $badge_class = 'bg-primary';
                                            } elseif (in_array($record['record_type'], $either_types)) {
                                                if (isset($record['link_type']) && $record['link_type'] === 'transaction') {
                                                    $badge_class = 'bg-primary';
                                                } else {
                                                    $badge_class = 'bg-purple';
                                                }
                                            } elseif ($record['record_type'] == 'Admission') {
                                                $badge_class = 'bg-success';
                                            } else {
                                                $badge_class = 'bg-secondary';
                                            }
                                            ?>
                                            <span class="badge <?= $badge_class ?>">
                                                <?= htmlspecialchars($record['record_type']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($record['description'] ?: 'N/A') ?></td>
                                        <td><?= date("F j, Y g:i A", strtotime($record['record_date'])) ?></td>
                                        <td class="action-buttons">
                                            <?php
                                            $either_types = ['Postnatal', 'Prenatal', 'Vaccination'];
                                            $recordType = $record['record_type'];
                                            $viewLink = '';
                                            if (in_array($recordType, $either_types)) {
                                                if (isset($record['link_type']) && $record['link_type'] === 'transaction') {
                                                    $viewLink = 'view_healthrecords.php?transaction_id=' . $record['record_id'];
                                                } else {
                                                    $viewLink = 'view_appointments.php?appointment_id=' . $record['record_id'];
                                                }
                                            } else {
                                                $viewLinks = [
                                                    'Admission' => 'view_admission.php?admission_id=',
                                                    'Follow Up' => 'view_appointments.php?appointment_id=',
                                                    'Medical Consultation' => 'view_appointments.php?appointment_id=',
                                                    'Regular Checkup' => 'view_appointments.php?appointment_id=',
                                                    'Under Observation' => 'view_appointments.php?appointment_id=',
                                                    'Hemoglobin' => 'view_healthrecords.php?transaction_id=',
                                                    'OB Ultrasound' => 'view_healthrecords.php?transaction_id=',
                                                    'Pap Smear' => 'view_healthrecords.php?transaction_id=',
                                                    'TV Ultrasound' => 'view_healthrecords.php?transaction_id=',
                                                    'Urinalysis' => 'view_healthrecords.php?transaction_id=',
                                                    'Circumcision Consent' => 'view_healthrecords.php?transaction_id=',
                                                ];
                                                $viewLink = isset($viewLinks[$recordType]) ? $viewLinks[$recordType] . $record['record_id'] : '';
                                            }
                                            if (!empty($viewLink)) {
                                                echo '<a href="' . $viewLink . '" class="btn btn-sm btn-view" title="View Record"><i class="fas fa-eye"></i></a>';
                                            } else {
                                                echo '<button class="btn btn-sm btn-secondary" title="View not available" disabled><i class="fas fa-eye-slash"></i></button>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="no-records">
                                        <i class="fas fa-file-medical"></i>
                                        <p class="mb-0">No health records found.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
