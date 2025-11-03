<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Patient') {
    header("Location: ../login.php");
    exit();
}
require '../connections/connections.php';
$pdo = connection();

// Get patient_id and case_id for the logged-in user
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT patient_id, case_id, first_name, last_name, date_of_birth, gender FROM patients WHERE user_id = :user_id');
$stmt->execute([':user_id' => $user_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$patient) {
    $_SESSION['error'] = "Patient record not found.";
    header("Location: dashboard.php");
    exit();
}
$patient_id = $patient['patient_id'];
$case_id = $patient['case_id'];

// Calculate Age
$birthdate = new DateTime($patient['date_of_birth']);
$today = new DateTime();
$age = $birthdate->diff($today)->y;

// Get transaction_id from GET
$transaction_id = isset($_GET['transaction_id']) ? intval($_GET['transaction_id']) : null;
if (!$transaction_id) {
    $_SESSION['error'] = "No transaction selected.";
    header("Location: manage_health_records.php");
    exit();
}

// Fetch transaction and service_name
$query = "SELECT mt.*, ms.service_name FROM medical_transactions mt JOIN medical_services ms ON mt.service_id = ms.service_id WHERE mt.transaction_id = :transaction_id";
$stmt = $pdo->prepare($query);
$stmt->execute([':transaction_id' => $transaction_id]);
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$transaction) {
    $_SESSION['error'] = "Transaction not found.";
    header("Location: manage_health_records.php");
    exit();
}
$service_name = strtolower($transaction['service_name']);

// Map service_name to health records table
$table_map = [
    'transvaginal ultrasound' => 'tv_ultrasound',
    'ob ultrasound' => 'ob_ultrasound',
    'prenatal checkup' => 'prenatal_records',
    'postnatal checkup' => 'postnatal_records',
    'pap smear' => 'pap_smear',
    'urinalysis' => 'urinalysis',
    'hemoglobin test' => 'hemoglobin',
    'circumcision' => 'circumcision_consent',
    'vaccination for newborn' => 'vaccination_records',
];

// Map table to date column for ordering
$date_columns = [
    'tv_ultrasound' => 'created_at',
    'ob_ultrasound' => 'created_at',
    'prenatal_records' => 'created_at',
    'postnatal_records' => 'created_at',
    'pap_smear' => 'created_at',
    'urinalysis' => 'report_date',
    'hemoglobin' => 'created_at',
    'circumcision_consent' => 'created_at',
    'vaccination_records' => 'created_at',
    // add others as needed
];

// Helper to check if a column exists in a table
function table_has_column($pdo, $table, $column) {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return $stmt->fetch() !== false;
}

$records = [];
$records_table = isset($table_map[$service_name]) ? $table_map[$service_name] : null;
if ($records_table) {
    $date_candidates = ['created_at', 'visit_date', 'admission_date', 'report_date', 'record_date'];
    $order_by = null;
    foreach ($date_candidates as $candidate) {
        if (table_has_column($pdo, $records_table, $candidate)) {
            $order_by = $candidate;
            break;
        }
    }
    $sql = "SELECT * FROM $records_table WHERE transaction_id = :transaction_id";
    if ($order_by) {
        $sql .= " ORDER BY $order_by DESC";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':transaction_id' => $transaction_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Record Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f5f5f5; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .dashboard-main-content { flex-grow: 1; padding: 20px; margin-left: 270px; transition: margin-left 0.4s ease; }
        .sidebar.collapsed ~ .dashboard-main-content { margin-left: 85px; }
        .patient-header { background: linear-gradient(135deg, #2E8B57 0%, #3CB371 100%); color: white; padding: 25px; border-radius: 15px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .patient-header h2 { font-weight: 600; margin-bottom: 10px; }
        .patient-header p { font-size: 1.1rem; opacity: 0.9; }
        .records-section { padding: 0 !important; margin: 0 !important; }
        .records-grid { width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .record-card {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            border-radius: 22px;
            background: #f6fbf7;
            box-shadow: 0 6px 32px rgba(46,139,87,0.10), 0 1.5px 4px rgba(46,139,87,0.04);
            padding-bottom: 32px;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .record-card:hover {
            box-shadow: 0 12px 48px rgba(46,139,87,0.16), 0 2px 8px rgba(46,139,87,0.08);
            transform: translateY(-2px) scale(1.01);
        }
        .record-card .record-date {
            font-size: 1rem;
            color: #2E8B57;
            margin-bottom: 10px;
            padding-left: 32px;
            padding-top: 32px;
            font-weight: 500;
        }
        .record-card .record-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 18px;
            padding-left: 32px;
        }
        .details-grid {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 32px 0 32px !important;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }
        .detail-item {
            background: #eaf7ee;
            border-radius: 16px;
            padding: 20px 18px 16px 18px;
            border: 1.5px solid #c7e7d2;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(46,139,87,0.04);
            transition: box-shadow 0.18s, border-color 0.18s, background 0.18s;
        }
        .detail-item:hover {
            box-shadow: 0 6px 24px rgba(46,139,87,0.10);
            border-color: #3CB371;
            background: #d6f5e3;
        }
        .detail-item strong {
            color: #1e293b;
            font-size: 1.08rem;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .detail-item span {
            color: #475569;
            font-size: 1.01rem;
            font-weight: 400;
        }
        @media (max-width: 991px) { .details-grid { grid-template-columns: repeat(2, 1fr); padding: 0 16px 0 16px !important; } }
        @media (max-width: 767px) { .details-grid { grid-template-columns: 1fr; padding: 0 6px 0 6px !important; } }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include 'sidebar.php'; ?>
    <main class="dashboard-main-content">
        <div class="patient-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-1"><?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?></h2>
                    <p class="mb-0">
                        <i class="fas fa-id-card me-2"></i>Case ID: <?= $patient['case_id'] ?>
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
        <div class="records-section" style="padding:0; margin:0;">
            <h4 style="margin-left:0; color:#2E8B57;"><i class="fas fa-folder-open me-2"></i><?= htmlspecialchars($transaction['service_name']) ?> Records</h4>
                    <?php if ($records && count($records) > 0): ?>
                <div class="records-grid" style="margin:0; padding:0; width:100%;">
                            <?php foreach ($records as $rec): ?>
                        <div class="record-card" style="width:100%; max-width:none; margin:0; box-shadow:0 6px 32px rgba(46,139,87,0.10), 0 1.5px 4px rgba(46,139,87,0.04); padding-bottom:32px; border-radius:22px; background:#f6fbf7;">
                            <div class="record-date" style="padding-left:32px; padding-top:32px; color:#2E8B57; font-weight:500;">
                                                <?php
                                $dateField = $rec['created_at'] ?? $rec['visit_date'] ?? $rec['admission_date'] ?? $rec['report_date'] ?? $rec['date'] ?? $rec['record_date'] ?? null;
                                                echo $dateField ? date('F j, Y', strtotime($dateField)) : 'N/A';
                                                ?>
                                        </div>
                            <div class="details-grid" style="width:100%; margin:0; padding:0 32px 0 32px;">
                                                <?php
                            $hideFields = ['record_id','patient_id','appointment_id','transaction_id','case_id','created_at','updated_at','admission_id'];
                                                foreach ($rec as $key => $val):
                                                    if (in_array($key, $hideFields)) continue;
                                                    ?>
                                <div class="detail-item">
                                    <strong><?= ucwords(str_replace('_', ' ', $key)) ?>:</strong>
                                    <span><?= ($val !== null && $val !== '') ? htmlspecialchars($val) : '<span class=\"text-muted\">N/A</span>' ?></span>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No <?= htmlspecialchars($transaction['service_name']) ?> records found for this transaction.</div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 