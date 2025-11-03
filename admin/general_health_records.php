<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

require '../connections/connections.php';
$con = connection();
$con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Get patient ID from URL
$case_id = isset($_GET['case_id']) ? $_GET['case_id'] : null;

if (!$case_id) {
    $_SESSION['error'] = "No patient selected";
    header("Location: patients.php");
    exit();
}

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
    $_SESSION['error'] = "Patient not found";
    header("Location: patients.php");
    exit();
}

// Fetch patient's health records
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

// Fetch patient's transactions
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
$totalAmount = array_sum(array_column($transactions, 'amount'));
$pendingTransactions = array_filter($transactions, function($t) {
    return $t['payment_status'] === 'Pending';
});
$totalPending = count($pendingTransactions);
$totalPendingAmount = array_sum(array_column($pendingTransactions, 'amount'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Health Records - <?= htmlspecialchars($patient['full_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .patient-header {
            background-color: #f8f9fa;
            padding: 2rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }
        .stats-card {
            background-color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            height: 100%;
        }
        .chart-container {
            background-color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 2rem;
        }
        .timeline {
            position: relative;
            padding: 1rem 0;
        }
        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 1.5rem;
            height: 100%;
            width: 2px;
            background-color: #e9ecef;
        }
        .timeline-item {
            position: relative;
            padding-left: 3rem;
            margin-bottom: 1.5rem;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 1.25rem;
            top: 0.5rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background-color: #0d6efd;
        }
    </style>
</head>
<body style="font-family: 'Poppins', sans-serif;">
    <div class="dashboard-container">
        <?php include '../sidebar.php'; ?>

        <main class="dashboard-main-content">
            <div class="container mt-5">
                <?php 
                    if (isset($_SESSION['success'])) {
                        echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                        unset($_SESSION['success']);
                    }
                    if (isset($_SESSION['error'])) {
                        echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                        unset($_SESSION['error']);
                    }
                ?>
                
                <!-- Breadcrumb Navigation -->
                <?php include '../admin/breadcrumb.php'; ?>

                <!-- Patient Header -->
                <div class="patient-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2"><?= htmlspecialchars($patient['full_name']) ?></h2>
                            <p class="mb-1"><strong>Case ID:</strong> <?= htmlspecialchars($patient['case_id']) ?></p>
                            <p class="mb-1"><strong>Gender:</strong> <?= htmlspecialchars($patient['gender']) ?></p>
                            <p class="mb-0"><strong>Contact:</strong> <?= htmlspecialchars($patient['contact_number']) ?></p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="patient_health_records.php?case_id=<?= $case_id ?>" class="btn btn-primary me-2">
                                <i class="fas fa-file-medical"></i> Health Records
                            </a>
                            <a href="transactions.php?case_id=<?= $case_id ?>" class="btn btn-info">
                                <i class="fas fa-receipt"></i> Transactions
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h5 class="text-muted">Total Transactions</h5>
                            <h3><?= $totalTransactions ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h5 class="text-muted">Total Amount</h5>
                            <h3>₱<?= number_format($totalAmount, 2) ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h5 class="text-muted">Pending Transactions</h5>
                            <h3><?= $totalPending ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h5 class="text-muted">Pending Amount</h5>
                            <h3>₱<?= number_format($totalPendingAmount, 2) ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h4>Transaction History</h4>
                            <canvas id="transactionsChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h4>Payment Status</h4>
                            <canvas id="paymentStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Health Records -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">Recent Health Records</h4>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php foreach ($healthRecords as $record): ?>
                                <div class="timeline-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-1"><?= htmlspecialchars($record['name']) ?></h5>
                                            <p class="text-muted mb-1"><?= (new DateTime($record['created_at']))->format('F j, Y g:i a') ?></p>
                                            <p class="mb-0"><?= htmlspecialchars($record['diagnosis']) ?></p>
                                        </div>
                                        <span class="badge bg-primary"><?= htmlspecialchars($record['name']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Recent Transactions</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Service</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td><?= (new DateTime($transaction['transaction_date']))->format('F j, Y g:i a') ?></td>
                                            <td><?= htmlspecialchars($transaction['service_name']) ?></td>
                                            <td>₱<?= number_format($transaction['amount'], 2) ?></td>
                                            <td>
                                                <span class="badge <?= $transaction['payment_status'] === 'Paid' ? 'bg-success' : 'bg-warning' ?>">
                                                    <?= htmlspecialchars($transaction['payment_status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prepare data for charts
        const transactionDates = <?= json_encode(array_column($transactions, 'transaction_date')) ?>;
        const transactionAmounts = <?= json_encode(array_column($transactions, 'amount')) ?>;
        
        const paidCount = <?= count(array_filter($transactions, function($t) { return $t['payment_status'] === 'Paid'; })) ?>;
        const pendingCount = <?= count(array_filter($transactions, function($t) { return $t['payment_status'] === 'Pending'; })) ?>;

        // Transaction History Chart
        new Chart(document.getElementById('transactionsChart'), {
            type: 'line',
            data: {
                labels: transactionDates.map(date => new Date(date).toLocaleDateString()),
                datasets: [{
                    label: 'Transaction Amount',
                    data: transactionAmounts,
                    borderColor: '#0d6efd',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        // Payment Status Chart
        new Chart(document.getElementById('paymentStatusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Paid', 'Pending'],
                datasets: [{
                    data: [paidCount, pendingCount],
                    backgroundColor: ['#198754', '#ffc107']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    </script>
</body>
</html> 