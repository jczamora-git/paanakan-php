<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

require '../connections/connections.php';
$con = connection();
$con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build the query
$whereClause = "WHERE 1=1";
if ($status !== 'all') {
    $whereClause .= " AND t.payment_status = :status";
}
if ($start_date) {
    $whereClause .= " AND DATE(t.transaction_date) >= :start_date";
}
if ($end_date) {
    $whereClause .= " AND DATE(t.transaction_date) <= :end_date";
}

// Get total count
$countQuery = "SELECT COUNT(*) FROM medical_transactions t $whereClause";
$countStmt = $con->prepare($countQuery);
if ($status !== 'all') {
    $countStmt->bindParam(':status', $status);
}
if ($start_date) {
    $countStmt->bindParam(':start_date', $start_date);
}
if ($end_date) {
    $countStmt->bindParam(':end_date', $end_date);
}
$countStmt->execute();
$totalCount = $countStmt->fetchColumn();
$totalPages = ceil($totalCount / $limit);

// Get transactions
$query = "
    SELECT t.transaction_id, t.case_id, t.transaction_date, s.service_name, t.amount, t.payment_status,
           CONCAT(p.first_name, ' ', p.last_name) as patient_name
    FROM medical_transactions t
    LEFT JOIN medical_services s ON t.service_id = s.service_id
    LEFT JOIN patients p ON t.case_id = p.case_id
    $whereClause
    ORDER BY t.transaction_date DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $con->prepare($query);
if ($status !== 'all') {
    $stmt->bindParam(':status', $status);
}
if ($start_date) {
    $stmt->bindParam(':start_date', $start_date);
}
if ($end_date) {
    $stmt->bindParam(':end_date', $end_date);
}
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
    <style>
        .filter-container {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
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

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Transaction History</h2>
                    <a href="transactions.php" class="btn btn-success">
                        <i class="fas fa-arrow-left"></i> Back to Transactions
                    </a>
                </div>

                <!-- Filters -->
                <div class="filter-container">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Payment Status</label>
                            <select class="form-select" name="status" id="status">
                                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Status</option>
                                <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Paid" <?= $status === 'Paid' ? 'selected' : '' ?>>Paid</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" id="start_date" value="<?= $start_date ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" id="end_date" value="<?= $end_date ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">Filter</button>
                        </div>
                    </form>
                </div>

                <!-- Transactions Table -->
                <div class="table-container shadow rounded bg-white p-3">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Case ID</th>
                                <th>Patient Name</th>
                                <th>Transaction Date</th>
                                <th>Service Name</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No transactions found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $index => $transaction): ?>
                                    <tr>
                                        <td><?= (($page - 1) * $limit) + $index + 1 ?></td>
                                        <td><?= htmlspecialchars($transaction['case_id']) ?></td>
                                        <td><?= htmlspecialchars($transaction['patient_name']) ?></td>
                                        <td><?= (new DateTime($transaction['transaction_date']))->format('F j, Y g:i a') ?></td>
                                        <td><?= htmlspecialchars($transaction['service_name']) ?></td>
                                        
                                        <td>₱<?= number_format($transaction['amount'], 2) ?></td>
                                        <td>
                                            <span class="badge <?= $transaction['payment_status'] === 'Paid' ? 'bg-success' : 'bg-warning' ?> status-badge">
                                                <?= htmlspecialchars($transaction['payment_status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($transaction['service_name'] === 'Transvaginal Ultrasound'): ?>
                                                <a href="../healthrecords/ultrasound_report.php?case_id=<?= $transaction['case_id'] ?>&transaction_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View Ultrasound Report">
                                                    <i class="fas fa-wave-square"></i>
                                                </a>
                                            <?php elseif ($transaction['service_name'] === 'OB Ultrasound'): ?>
                                                <a href="../healthrecords/ob_ultrasound_report.php?case_id=<?= $transaction['case_id'] ?>&transaction_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View OB Ultrasound Report">
                                                    <i class="fas fa-baby"></i>
                                                </a>
                                            <?php elseif ($transaction['service_name'] === 'Pap Smear'): ?>
                                                <a href="../healthrecords/pap_smear_report.php?case_id=<?= $transaction['case_id'] ?>&transaction_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View Pap Smear Report">
                                                    <i class="fas fa-microscope"></i>
                                                </a>
                                            <?php elseif ($transaction['service_name'] === 'Hemoglobin Test'): ?>
                                                <a href="../healthrecords/hemoglobin_report.php?case_id=<?= $transaction['case_id'] ?>&transaction_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View Hemoglobin Report">
                                                    <i class="fas fa-tint"></i>
                                                </a>
                                            <?php elseif ($transaction['service_name'] === 'Urinalysis'): ?>
                                                <a href="../healthrecords/urinalysis_report.php?case_id=<?= $transaction['case_id'] ?>&transaction_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View Urinalysis Report">
                                                    <i class="fas fa-flask"></i>
                                                </a>
                                            <?php elseif ($transaction['service_name'] === 'Circumcision'): ?>
                                                <a href="../healthrecords/circumcision_consent.php?case_id=<?= $transaction['case_id'] ?>&transaction_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View Circumcision Consent">
                                                    <i class="fas fa-scissors"></i>
                                                </a>
                                            <?php elseif ($transaction['service_name'] === 'Prenatal Checkup'): ?>
                                                <a href="../healthrecords/prenatal_records.php?case_id=<?= $transaction['case_id'] ?>&transaction_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View Prenatal Record">
                                                    <i class="fas fa-baby-carriage"></i>
                                                </a>
                                            <?php elseif ($transaction['service_name'] === 'Postnatal Checkup'): ?>
                                                <a href="../healthrecords/postnatal_records.php?case_id=<?= $transaction['case_id'] ?>&transaction_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View Postnatal Record">
                                                    <i class="fas fa-child"></i>
                                                </a>
                                            <?php elseif ($transaction['service_name'] === 'Vaccination for Newborn'): ?>
                                                <a href="../healthrecords/vaccination_records.php?transaction_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View Vaccination Record">
                                                    <i class="fas fa-syringe"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="records.php?case_id=<?= $transaction['case_id'] ?>&record_id=<?= $transaction['transaction_id'] ?>" class="btn btn-sm btn me-1" title="View Records">
                                                    <i class="fas fa-file-medical"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=1<?= $status !== 'all' ? '&status=' . $status : '' ?><?= $start_date ? '&start_date=' . $start_date : '' ?><?= $end_date ? '&end_date=' . $end_date : '' ?>" aria-label="First">
                                <span class="material-icons">first_page</span>
                            </a>
                        </li>
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?><?= $status !== 'all' ? '&status=' . $status : '' ?><?= $start_date ? '&start_date=' . $start_date : '' ?><?= $end_date ? '&end_date=' . $end_date : '' ?>" aria-label="Previous">
                                <span class="material-icons">chevron_left</span>
                            </a>
                        </li>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= $status !== 'all' ? '&status=' . $status : '' ?><?= $start_date ? '&start_date=' . $start_date : '' ?><?= $end_date ? '&end_date=' . $end_date : '' ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?><?= $status !== 'all' ? '&status=' . $status : '' ?><?= $start_date ? '&start_date=' . $start_date : '' ?><?= $end_date ? '&end_date=' . $end_date : '' ?>" aria-label="Next">
                                <span class="material-icons">chevron_right</span>
                            </a>
                        </li>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $totalPages ?><?= $status !== 'all' ? '&status=' . $status : '' ?><?= $start_date ? '&start_date=' . $start_date : '' ?><?= $end_date ? '&end_date=' . $end_date : '' ?>" aria-label="Last">
                                <span class="material-icons">last_page</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 