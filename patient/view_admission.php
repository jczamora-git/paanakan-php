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

// Get admission_id from GET
$admission_id = isset($_GET['admission_id']) ? intval($_GET['admission_id']) : null;
if (!$admission_id) {
    $_SESSION['error'] = "No admission selected.";
    header("Location: dashboard.php");
    exit();
}

// Fetch admission record
$stmt = $pdo->prepare('SELECT * FROM admissions WHERE admission_id = :admission_id AND patient_id = :patient_id');
$stmt->execute([':admission_id' => $admission_id, ':patient_id' => $patient_id]);
$admission = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Details</title>
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
        .admission-section { padding: 0 !important; margin: 0 !important; }
        .admission-card {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            border-radius: 22px;
            background: #f6fbf7;
            box-shadow: 0 6px 32px rgba(46,139,87,0.10), 0 1.5px 4px rgba(46,139,87,0.04);
            padding-bottom: 32px;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .admission-card:hover {
            box-shadow: 0 12px 48px rgba(46,139,87,0.16), 0 2px 8px rgba(46,139,87,0.08);
            transform: translateY(-2px) scale(1.01);
        }
        .admission-card .admission-date {
            font-size: 1rem;
            color: #2E8B57;
            margin-bottom: 10px;
            padding-left: 32px;
            padding-top: 32px;
            font-weight: 500;
        }
        .admission-card .admission-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2E8B57;
            margin-bottom: 18px;
            padding-left: 32px;
        }
        .details-grid {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 32px 0 32px !important;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
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
        @media (max-width: 991px) { .details-grid { grid-template-columns: 1fr; padding: 0 16px 0 16px !important; } }
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
        <div class="admission-section">
            <h4 style="margin-left:0; color:#2E8B57;"><i class="fas fa-hospital-user me-2"></i>Admission Details</h4>
            <?php if ($admission): ?>
                <div class="admission-card">
                    <div class="admission-date">
                        <?php
                        $dateField = $admission['admission_date'] ?? null;
                        echo $dateField ? 'Admission Date: ' . date('F j, Y', strtotime($dateField)) : 'Admission Date: N/A';
                        ?>
                    </div>
                    <div class="details-grid">
                    <?php
                    $hideFields = ['admission_id','patient_id'];
                    foreach ($admission as $key => $val):
                        if (in_array($key, $hideFields)) continue;
                        ?>
                        <div class="detail-item">
                            <strong><?= ucwords(str_replace('_', ' ', $key)) ?>:</strong>
                            <span><?= ($val !== null && $val !== '') ? htmlspecialchars($val) : '<span class=\"text-muted\">N/A</span>' ?></span>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger mt-4">Admission record not found or you do not have access to this record.</div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 