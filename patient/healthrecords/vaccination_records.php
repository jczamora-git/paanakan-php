<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

require '../connections/connections.php';
$pdo = connection();

$view_mode = false;
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : null;
$transaction_id = isset($_GET['transaction_id']) ? intval($_GET['transaction_id']) : null;
$patient_name = '';

if ($patient_id) {
    $view_mode = true;
    // Fetch patient name
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $patient_name = $row['first_name'] . ' ' . $row['last_name'];
    }
}

// Fetch existing record for this transaction (if any)
$existingRecord = null;
if ($transaction_id && !$view_mode) {
    // First get the case_id from the transaction
    $stmt = $pdo->prepare("SELECT case_id FROM medical_transactions WHERE transaction_id = ?");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($transaction) {
        $case_id = $transaction['case_id'];
    }

    // Then fetch the vaccination record
    $stmt = $pdo->prepare("SELECT * FROM vaccination_records WHERE transaction_id = ? LIMIT 1");
    $stmt->execute([$transaction_id]);
    $existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle form submission (Create or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$view_mode) {
    $fields = [
        'vaccine_name' => $_POST['vaccine_name'],
        'dose_number' => $_POST['dose_number'],
        'batch_number' => $_POST['batch_number'],
        'expiry_date' => $_POST['expiry_date'],
        'site_of_injection' => $_POST['site_of_injection'],
        'adverse_reactions' => $_POST['adverse_reactions'],
        'remarks' => $_POST['remarks'],
    ];

    if (isset($_POST['record_id']) && $_POST['record_id']) {
        // Update
        $sql = "UPDATE vaccination_records SET 
                vaccine_name=?, dose_number=?, batch_number=?, expiry_date=?, 
                site_of_injection=?, adverse_reactions=?, remarks=? 
                WHERE record_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge(array_values($fields), [$_POST['record_id']]));
    } else {
        // Insert
        $sql = "INSERT INTO vaccination_records (
                    transaction_id, case_id, vaccine_name, dose_number, batch_number, 
                    expiry_date, site_of_injection, adverse_reactions, remarks
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([$transaction_id, $case_id], array_values($fields)));
    }

    $_SESSION['message'] = "Vaccination record " . (isset($_POST['record_id']) ? "updated" : "added") . " successfully!";
    header("Location: vaccination_records.php?transaction_id=$transaction_id");
    exit();
}

// Fetch records
$records = [];
if ($view_mode && $patient_id) {
    // Get all vaccination records for this patient_id (across all transactions)
    $stmt = $pdo->prepare("
        SELECT r.*, t.transaction_date 
        FROM vaccination_records r 
        JOIN medical_transactions t ON r.transaction_id = t.transaction_id 
        WHERE t.case_id = (SELECT case_id FROM patients WHERE patient_id = ?) 
        ORDER BY t.transaction_date DESC
    ");
    $stmt->execute([$patient_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($transaction_id) {
    // First get the case_id from the transaction
    $stmt = $pdo->prepare("SELECT case_id FROM medical_transactions WHERE transaction_id = ?");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($transaction) {
        $case_id = $transaction['case_id'];
        // Get all vaccination records for this case_id
        $stmt = $pdo->prepare("
            SELECT r.*, t.transaction_date 
            FROM vaccination_records r 
            JOIN medical_transactions t ON r.transaction_id = t.transaction_id 
            WHERE t.case_id = ? 
            ORDER BY t.transaction_date DESC
        ");
        $stmt->execute([$case_id]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$breadcrumb = array(
    'Health Records' => array('link' => 'manage_health_records.php', 'icon' => 'fas fa-hospital'),
    'Vaccination Records' => array('link' => '#', 'icon' => 'fas fa-syringe')
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vaccination Records</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-container { 
            display: flex; 
            min-height: 100vh; 
        }
        .dashboard-main-content { 
            flex-grow: 1; 
            padding: 30px 20px; 
            margin-left: 270px; 
            transition: margin-left 0.4s ease; 
        }
        .sidebar.collapsed ~ .dashboard-main-content { 
            margin-left: 85px; 
        }
        .header-section {
            background: linear-gradient(90deg, #2E8B57 0%, #4CAF50 100%);
            color: #fff;
            padding: 2rem 2.5rem 1.5rem 2.5rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-section h2 { 
            margin: 0; 
            font-weight: 700; 
            letter-spacing: 1px; 
        }
        .card { 
            border-radius: 1rem; 
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.08);
            border: none;
        }
        .section-title { 
            color: #2E8B57; 
            font-weight: 600; 
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-label { 
            font-weight: 500; 
            color: #333;
            margin-bottom: 0.5rem;
        }
        .form-control {
            border-radius: 0.5rem;
            border: 1px solid #e0e0e0;
            padding: 0.6rem 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #2E8B57;
            box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.15);
        }
        .btn-primary { 
            background: #2E8B57; 
            border: none;
            border-radius: 0.5rem;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover { 
            background: #256d47;
            transform: translateY(-1px);
        }
        .table {
            margin-bottom: 0;
        }
        .table thead th { 
            background: #e8f5e9; 
            color: #2E8B57;
            font-weight: 600;
            padding: 1rem;
            border-bottom: 2px solid #2E8B57;
        }
        .table td {
            padding: 1rem;
            vertical-align: middle;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        .form-grid .full-width {
            grid-column: 1 / -1;
        }
        .card-body {
            padding: 2rem;
        }
        .table-container {
            padding: 1.5rem;
        }
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }
        .btn-light {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 0.5rem;
            padding: 0.6rem 1.5rem;
            transition: all 0.3s ease;
        }
        .btn-light:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include('sidebar.php'); ?>
    <main class="dashboard-main-content">
        <?php include('../admin/breadcrumb.php'); ?>

        <div class="header-section mb-4">
            <div>
                <h2><i class="fas fa-syringe me-2"></i>Vaccination Records</h2>
                <?php if ($view_mode): ?>
                    <div class="fs-6">Patient ID: <span class="fw-bold">#<?= htmlspecialchars($patient_id) ?></span> &mdash; <span class="fw-bold"><?= htmlspecialchars($patient_name) ?></span></div>
                <?php else: ?>
                    <div class="fs-6">Transaction ID: <span class="fw-bold">#<?= htmlspecialchars($transaction_id) ?></span></div>
                <?php endif; ?>
            </div>
            <a href="javascript:history.back()" class="btn btn-light"><i class="fas fa-arrow-left me-2"></i>Back</a>
        </div>

        <?php if (!$view_mode): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="section-title">
                    <i class="fas fa-notes-medical"></i>
                    Add / Update Record
                </h5>
                <form method="post">
                    <input type="hidden" name="transaction_id" value="<?= htmlspecialchars($transaction_id) ?>">
                    <?php if ($existingRecord): ?>
                        <input type="hidden" name="record_id" value="<?= $existingRecord['record_id'] ?>">
                    <?php endif; ?>
                    <div class="form-grid">
                        <div class="mb-3">
                            <label class="form-label">Vaccine Name</label>
                            <input type="text" name="vaccine_name" class="form-control" placeholder="Enter vaccine name..." 
                                   value="<?= $existingRecord ? htmlspecialchars($existingRecord['vaccine_name']) : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dose Number</label>
                            <input type="number" name="dose_number" class="form-control" placeholder="Enter dose number..." 
                                   value="<?= $existingRecord ? htmlspecialchars($existingRecord['dose_number']) : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Batch Number</label>
                            <input type="text" name="batch_number" class="form-control" placeholder="Enter batch number..." 
                                   value="<?= $existingRecord ? htmlspecialchars($existingRecord['batch_number']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control" 
                                   value="<?= $existingRecord ? htmlspecialchars($existingRecord['expiry_date']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Site of Injection</label>
                            <input type="text" name="site_of_injection" class="form-control" placeholder="Enter site of injection..." 
                                   value="<?= $existingRecord ? htmlspecialchars($existingRecord['site_of_injection']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Created Date</label>
                            <input type="text" class="form-control" value="<?= date('F j, Y g:i A') ?>" readonly>
                        </div>
                        <div class="mb-3 full-width">
                            <label class="form-label">Adverse Reactions</label>
                            <textarea name="adverse_reactions" class="form-control" rows="2" placeholder="Enter adverse reactions..."><?= $existingRecord ? htmlspecialchars($existingRecord['adverse_reactions']) : '' ?></textarea>
                        </div>
                        <div class="mb-3 full-width">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Enter remarks..."><?= $existingRecord ? htmlspecialchars($existingRecord['remarks']) : '' ?></textarea>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <h5 class="section-title">
                    <i class="fas fa-list"></i>
                    Existing Records
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Vaccine Name</th>
                                <th>Dose #</th>
                                <th>Batch #</th>
                                <th>Expiry Date</th>
                                <th>Site</th>
                                <th>Reactions</th>
                                <th>Remarks</th>
                                <th>Created</th>
                                <?php if ($view_mode) echo '<th>Transaction Date</th>'; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($records as $rec): ?>
                            <tr>
                                <td><?= $rec['record_id'] ?></td>
                                <td><?= htmlspecialchars($rec['vaccine_name']) ?></td>
                                <td><?= htmlspecialchars($rec['dose_number']) ?></td>
                                <td><?= htmlspecialchars($rec['batch_number']) ?></td>
                                <td><?= htmlspecialchars($rec['expiry_date']) ?></td>
                                <td><?= htmlspecialchars($rec['site_of_injection']) ?></td>
                                <td><?= htmlspecialchars($rec['adverse_reactions']) ?></td>
                                <td><?= htmlspecialchars($rec['remarks']) ?></td>
                                <td><?= date('F j, Y g:i A', strtotime($rec['created_at'])) ?></td>
                                <?php if ($view_mode): ?>
                                    <td><?= isset($rec['transaction_date']) ? date('F j, Y g:i A', strtotime($rec['transaction_date'])) : '' ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 