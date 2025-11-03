<?php
// Start session and check if the user is logged in as patient
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Patient') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require '../connections/connections.php';
$con = connection();
$con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Get patient ID from session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to access the dashboard";
    header("Location: ../login.php");
    exit();
}

// Fetch patient's case_id using user_id
$caseQuery = "SELECT case_id FROM patients WHERE user_id = :user_id";
$caseStmt = $con->prepare($caseQuery);
$caseStmt->bindParam(':user_id', $_SESSION['user_id']);
$caseStmt->execute();
$caseResult = $caseStmt->fetch();

if (!$caseResult) {
    $_SESSION['error'] = "Patient record not found";
    header("Location: ../login.php");
    exit();
}

$case_id = $caseResult['case_id'];

// Fetch patient basic information
$patientQuery = "
    SELECT p.*, 
           CONCAT(p.first_name, ' ', p.last_name) as full_name
    FROM patients p
    WHERE p.case_id = :case_id
";

$patientStmt = $con->prepare($patientQuery);
$patientStmt->bindParam(':case_id', $case_id);
$patientStmt->execute();
$patient = $patientStmt->fetch();

if (!$patient) {
    $_SESSION['error'] = "Patient information not found";
    header("Location: ../login.php");
    exit();
}

// (Previously computed age removed — we will show email in header instead)

// Fetch patient's health records (all)
$healthRecordsQuery = "
    SELECT hr.*
    FROM health_records hr
    WHERE hr.case_id = :case_id
    ORDER BY hr.created_at DESC
";

$healthRecordsStmt = $con->prepare($healthRecordsQuery);
$healthRecordsStmt->bindParam(':case_id', $case_id);
$healthRecordsStmt->execute();
$healthRecords = $healthRecordsStmt->fetchAll();

// Fetch patient's transactions (all)
$transactionsQuery = "
    SELECT t.*, s.service_name
    FROM medical_transactions t
    LEFT JOIN medical_services s ON t.service_id = s.service_id
    WHERE t.case_id = :case_id
    ORDER BY t.transaction_date DESC
";

$transactionsStmt = $con->prepare($transactionsQuery);
$transactionsStmt->bindParam(':case_id', $case_id);
$transactionsStmt->execute();
$transactions = $transactionsStmt->fetchAll();

// Calculate statistics
$totalTransactions = count($transactions);
$pendingTransactions = array_filter($transactions, function($t) {
    return $t['payment_status'] === 'Pending';
});
$totalPending = count($pendingTransactions);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - <?= htmlspecialchars($patient['full_name']) ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
    <style>
        :root {
            --primary-color: #2E8B57;
            --primary-light: #3CB371;
            --primary-dark: #228B48;
            --secondary-color: #f8f9fa;
            --accent-blue: #3498db;
            --accent-green: #27ae60;
            --accent-orange: #f39c12;
            --accent-red: #e74c3c;
            --text-color: #2c3e50;
            --text-light: #7f8c8d;
            --border-color: #ecf0f1;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.12);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.15);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f6fa;
            color: var(--text-color);
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .dashboard-main-content {
            flex-grow: 1;
            padding: 30px;
            margin-left: 270px;
            transition: all 0.4s ease;
            background-color: #f8f9fa;
        }

        .sidebar.collapsed ~ .dashboard-main-content {
            margin-left: 85px;
        }

        @media (max-width: 1024px) {
            .dashboard-main-content {
                margin-left: 0;
                padding: 20px;
            }
        }

        /* Header Section */
        .patient-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            padding: 40px 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .patient-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            opacity: 0.5;
        }

        .patient-header-content {
            position: relative;
            z-index: 1;
        }

        .patient-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .patient-header p {
            font-size: 1rem;
            opacity: 0.95;
            margin-bottom: 15px;
        }

        .patient-info-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            backdrop-filter: blur(10px);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 14px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            border-left: 5px solid var(--primary-color);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card.blue {
            border-left-color: var(--accent-blue);
        }

        .stat-card.green {
            border-left-color: var(--accent-green);
        }

        .stat-card.orange {
            border-left-color: var(--accent-orange);
        }

        .stat-card.red {
            border-left-color: var(--accent-red);
        }

        .stat-card-icon {
            font-size: 2rem;
            margin-bottom: 12px;
            opacity: 0.8;
        }

        .stat-card.blue .stat-card-icon { color: var(--accent-blue); }
        .stat-card.green .stat-card-icon { color: var(--accent-green); }
        .stat-card.orange .stat-card-icon { color: var(--accent-orange); }
        .stat-card.red .stat-card-icon { color: var(--accent-red); }

        .stat-card h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 4px;
            color: var(--text-color);
        }

        .stat-card p {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 0;
        }

        /* Sections */
        .section {
            background: white;
            border-radius: 14px;
            padding: 28px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 30px;
            transition: box-shadow 0.3s ease;
        }

        .section:hover {
            box-shadow: var(--shadow-md);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--border-color);
        }

        .section-header h2 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        /* Transaction Cards */
        .transaction-item {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            align-items: center;
            padding: 16px;
            border-radius: 10px;
            background: #f8f9fa;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .transaction-item:hover {
            background: #f0f2f5;
            box-shadow: var(--shadow-sm);
        }

        .transaction-item.status-pending {
            border-left-color: var(--accent-orange);
        }

        .transaction-item.status-paid {
            border-left-color: var(--accent-green);
        }

        .transaction-item.status-failed {
            border-left-color: var(--accent-red);
        }

        .transaction-info h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--text-color);
        }

        .transaction-info p {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-bottom: 0;
        }

        .transaction-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
            min-width: 90px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }

        /* Health Records */
        .health-record-item {
            display: flex;
            gap: 16px;
            padding: 16px;
            border-radius: 10px;
            background: #f8f9fa;
            margin-bottom: 12px;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .health-record-item:hover {
            background: #f0f2f5;
            box-shadow: var(--shadow-sm);
        }

        .health-record-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .health-record-content h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--text-color);
        }

        .health-record-content p {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-bottom: 0;
        }

        .health-record-date {
            font-size: 0.75rem;
            color: #999;
            margin-top: 4px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state-icon {
            font-size: 3rem;
            color: var(--text-light);
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-state h4 {
            color: var(--text-light);
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #bbb;
            font-size: 0.9rem;
        }

        /* Alerts */
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .patient-header {
                padding: 30px 20px;
            }

            .patient-header h1 {
                font-size: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .transaction-item,
            .health-record-item {
                flex-direction: column;
                gap: 12px;
            }

            .transaction-item {
                grid-template-columns: 1fr;
            }

            .transaction-status {
                text-align: left;
            }

            .dashboard-main-content {
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>
        <main class="dashboard-main-content">
            <!-- Alerts -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php elseif (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Patient Header (aligned with manage_health_records style) -->
            <div class="patient-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-1"><?= htmlspecialchars($patient['full_name']) ?></h2>
                        <p class="mb-0">
                            <i class="fas fa-id-card me-2"></i>Case ID: <?= htmlspecialchars($case_id) ?>
                            <span class="mx-3">|</span>
                            <i class="fas fa-envelope me-2"></i>Email: <?= htmlspecialchars($patient['email'] ?? ($patient['user_email'] ?? 'N/A')) ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <img src="../psc_whitebanner.png" alt="Hospital Logo" style="max-height:60px;">
                    </div>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-card-icon"><i class="fas fa-receipt"></i></div>
                    <h3><?= $totalTransactions ?></h3>
                    <p>Transactions</p>
                </div>
                <div class="stat-card orange">
                    <div class="stat-card-icon"><i class="fas fa-clock"></i></div>
                    <h3><?= $totalPending ?></h3>
                    <p>Pending</p>
                </div>
                <div class="stat-card red">
                    <div class="stat-card-icon"><i class="fas fa-heartbeat"></i></div>
                    <h3><?= count($healthRecords) ?></h3>
                    <p>Health Records</p>
                </div>
            </div>

            <!-- Transaction History Section -->
            <div class="section">
                <div class="section-header">
                    <h2>
                        <span class="section-header-icon"><i class="fas fa-history"></i></span>
                        Transaction History
                    </h2>
                </div>
                <?php if (!empty($transactions)): ?>
                    <div>
                        <?php foreach (array_slice($transactions, 0, 10) as $transaction): ?>
                            <?php
                                // transaction id can be named differently depending on schema
                                $tid = $transaction['transaction_id'] ?? $transaction['id'] ?? $transaction['t_id'] ?? '';
                                $statusRaw = $transaction['payment_status'] ?? '';
                                $statusClass = strtolower($statusRaw);
                                // Show 'Done' instead of 'Paid' for patient-friendly label
                                $statusLabel = ($statusRaw === 'Paid') ? 'Done' : ucfirst($statusRaw);
                            ?>
                            <div class="transaction-item status-<?= htmlspecialchars($statusClass) ?>">
                                <div class="transaction-info">
                                    <h4><?= htmlspecialchars($transaction['service_name'] ?? 'Service') ?></h4>
                                    <p>
                                        <i class="fas fa-calendar-alt"></i>
                                        <?= (new DateTime($transaction['transaction_date']))->format('F j, Y') ?>
                                    </p>
                                </div>
                                <div class="transaction-actions" style="display:flex;align-items:center;gap:10px;">
                                    <div class="transaction-status status-<?= htmlspecialchars($statusClass) ?>">
                                        <?= htmlspecialchars($statusLabel) ?>
                                    </div>
                                    <a href="transaction_details.php?id=<?= urlencode($tid) ?>" class="btn btn-sm btn-view" title="View"><i class="fas fa-eye"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($transactions) > 10): ?>
                        <div style="text-align: center; margin-top: 20px;">
                            <p style="color: var(--text-light); font-size: 0.9rem;">
                                Showing 10 of <?= count($transactions) ?> transactions
                                <a href="transactions.php" style="color: var(--primary-color); text-decoration: none;"> View All</a>
                            </p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                        <h4>No Transactions Yet</h4>
                        <p>You don't have any transactions. Once you complete a service, they will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Health Records Section -->
            <div class="section">
                <div class="section-header">
                    <h2>
                        <span class="section-header-icon"><i class="fas fa-file-medical"></i></span>
                        Health Records
                    </h2>
                </div>
                <?php if (!empty($healthRecords)): ?>
                    <div>
                        <?php foreach (array_slice($healthRecords, 0, 10) as $record): ?>
                            <div class="health-record-item">
                                <div class="health-record-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <div class="health-record-content" style="flex-grow: 1;">
                                    <h4><?= htmlspecialchars($record['record_type'] ?? 'Health Record') ?></h4>
                                    <p><?= htmlspecialchars(substr($record['notes'] ?? '', 0, 100)) ?><?= strlen($record['notes'] ?? '') > 100 ? '...' : '' ?></p>
                                    <p class="health-record-date">
                                        <i class="fas fa-calendar"></i>
                                        <?= (new DateTime($record['created_at']))->format('F j, Y \a\t g:i a') ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($healthRecords) > 10): ?>
                        <div style="text-align: center; margin-top: 20px;">
                            <p style="color: var(--text-light); font-size: 0.9rem;">
                                Showing 10 of <?= count($healthRecords) ?> records
                                <a href="manage_health_records.php" style="color: var(--primary-color); text-decoration: none;"> View All</a>
                            </p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-folder-open"></i></div>
                        <h4>No Health Records Yet</h4>
                        <p>Your health records will appear here once they are created by your healthcare provider.</p>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
