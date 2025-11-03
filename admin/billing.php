<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

require '../connections/connections.php';
$pdo = connection();

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search_by = isset($_GET['search_by']) ? $_GET['search_by'] : 'patient_name'; // Default search by patient name

// Build the WHERE clause based on search and filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    switch ($search_by) {
        case 'transaction_id':
            $where_conditions[] = "mt.transaction_id LIKE :search";
            break;
        case 'service_name':
            $where_conditions[] = "s.service_name LIKE :search";
            break;
        default: // patient_name
            $where_conditions[] = "CONCAT(p.first_name, ' ', p.last_name) LIKE :search";
    }
    $params[':search'] = "%$search%";
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(bh.billing_date) = :date";
    $params[':date'] = $date_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Query to join billing_header with patients, medical_transactions, medical_services, and billing_items to get desired columns.
$query = "SELECT 
            bh.billing_id,
            mt.transaction_id,
            CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
            s.service_name,
            bh.net_amount AS total_amount,
            bh.billing_date
          FROM billing_header bh
          JOIN patients p ON bh.case_id = p.case_id
          JOIN medical_transactions mt ON bh.transaction_id = mt.transaction_id
          JOIN medical_services s ON mt.service_id = s.service_id
          LEFT JOIN billing_items bi ON bh.billing_id = bi.billing_id
          $where_clause
          GROUP BY bh.billing_id, mt.transaction_id, p.first_name, p.last_name, s.service_name, mt.amount, bh.billing_date
          ORDER BY bh.billing_date DESC
          LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$billingRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total billing records with filters
$totalQuery = "SELECT COUNT(DISTINCT bh.billing_id) AS total 
               FROM billing_header bh
               JOIN patients p ON bh.case_id = p.case_id
               JOIN medical_transactions mt ON bh.transaction_id = mt.transaction_id
               JOIN medical_services s ON mt.service_id = s.service_id
               $where_clause";
$totalStmt = $pdo->prepare($totalQuery);
foreach ($params as $key => $value) {
    $totalStmt->bindValue($key, $value);
}
$totalStmt->execute();
$totalResult = $totalStmt->fetch();
$totalRecords = $totalResult['total'];
$totalPages = ceil($totalRecords / $limit);

// Build query string for pagination links
$queryParams = [];
if (!empty($search)) $queryParams['search'] = $search;
if (!empty($search_by)) $queryParams['search_by'] = $search_by;
if (!empty($date_filter)) $queryParams['date'] = $date_filter;

// Function to generate pagination URL
function getPageUrl($pageNum, $queryParams) {
    $queryParams['page'] = $pageNum;
    return '?' . http_build_query($queryParams);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Billing</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
      transition: all 0.4s ease;
      background-color: #f8f9fa;
    }

    /* Adjust margin when sidebar is collapsed */
    .sidebar.collapsed ~ .dashboard-main-content {
      margin-left: 85px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .dashboard-main-content {
        margin-left: 0;
        padding: 20px;
      }
      
      .sidebar.collapsed ~ .dashboard-main-content {
        margin-left: 0;
        padding-left: 85px;
      }
    }

    .page-header {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
      color: white;
      padding: 25px;
      border-radius: 15px;
      margin-bottom: 25px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .search-container {
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 25px;
    }

    .table-container {
      background: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .table thead th {
      background-color: var(--secondary-color);
      color: var(--text-color);
      font-weight: 600;
      border-bottom: 2px solid var(--primary-color);
    }

    .table tbody tr:hover {
      background-color: rgba(46, 139, 87, 0.05);
    }

    .btn-action {
      padding: 5px 10px;
      margin: 0 2px;
      border-radius: 5px;
      transition: all 0.3s ease;
    }

    .btn-action:hover {
      transform: translateY(-2px);
    }

    .amount-badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-weight: 500;
      font-size: 0.85rem;
      background-color: #e9ecef;
      color: #495057;
    }

    .pagination {
      margin-top: 20px;
    }

    .page-link {
      color: var(--primary-color);
      border-color: var(--border-color);
    }

    .page-link:hover {
      color: var(--primary-light);
      background-color: var(--secondary-color);
    }

    .page-item.active .page-link {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }

    .alert {
      border-radius: 10px;
      margin-bottom: 20px;
    }

    .form-select, .form-control {
      border-radius: 8px;
      border: 1px solid var(--border-color);
      padding: 8px 12px;
    }

    .form-select:focus, .form-control:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.25);
    }

    .btn-success {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }

    .btn-success:hover {
      background-color: var(--primary-light);
      border-color: var(--primary-light);
    }

    .btn-outline-success {
      color: var(--primary-color);
      border-color: var(--primary-color);
    }

    .btn-outline-success:hover {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }

    .btn-secondary {
      background-color: #6c757d;
      border-color: #6c757d;
    }

    .btn-secondary:hover {
      background-color: #5a6268;
      border-color: #545b62;
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <?php include '../sidebar.php'; ?>

    <main class="dashboard-main-content">
      <?php include '../admin/breadcrumb.php'; ?>
      
      <!-- Page Header -->
      <div class="page-header">
        <div class="row align-items-center">
          <div class="col-md-6">
            <h2 class="mb-0">
              <i class="fas fa-file-invoice-dollar me-2"></i>Billing Records
            </h2>
            <p class="mb-0 mt-2">
              <i class="fas fa-list me-2"></i>Total Records: <?= $totalRecords ?>
            </p>
          </div>
          <div class="col-md-6 text-end">
            <a href="add_billing.php" class="btn btn-success">
              <i class="fas fa-plus me-2"></i>Add Billing Record
            </a>
          </div>
        </div>
      </div>

      <!-- Search and Filter Section -->
      <div class="search-container">
        <form id="searchForm" method="GET" class="row g-3">
          <div class="col-md-4">
            <div class="input-group">
              <input type="text" id="searchInput" name="search" class="form-control" placeholder="Search by Case ID, Patient Name, or Service..." value="<?= htmlspecialchars($search) ?>">
              <button class="btn btn-outline-success" type="submit">
                <i class="fas fa-search"></i>
              </button>
            </div>
          </div>
          <div class="col-md-3">
            <input type="date" id="dateFilter" name="date" class="form-control" value="<?= htmlspecialchars($date_filter) ?>">
          </div>
          <div class="col-md-3">
            <select id="searchByFilter" name="search_by" class="form-select">
              <option value="patient_name" <?= $search_by === 'patient_name' ? 'selected' : '' ?>>Search by Patient Name</option>
              <option value="transaction_id" <?= $search_by === 'transaction_id' ? 'selected' : '' ?>>Search by Transaction ID</option>
              <option value="service_name" <?= $search_by === 'service_name' ? 'selected' : '' ?>>Search by Service Name</option>
            </select>
          </div>
          <div class="col-md-2">
            <a href="billing.php" class="btn btn-secondary w-100">
              <i class="fas fa-redo-alt"></i>
            </a>
          </div>
        </form>
      </div>

      <!-- Success/Error Messages -->
      <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="fas fa-check-circle me-2"></i><?= $_SESSION['message'] ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
      <?php endif; ?>

      <!-- Billing Records Table -->
      <div class="table-container">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>Transaction ID</th>
              <th>Patient Name</th>
              <th>Service Name</th>
              <th>Total Amount</th>
              <th>Billing Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($billingRecords)): ?>
              <?php foreach ($billingRecords as $index => $record): ?>
                <tr>
                  <td><?= (($page - 1) * $limit) + $index + 1 ?></td>
                  <td><?= htmlspecialchars($record['transaction_id']) ?></td>
                  <td><?= htmlspecialchars($record['patient_name']) ?></td>
                  <td><?= htmlspecialchars($record['service_name']) ?></td>
                  <td>
                    <span class="amount-badge">
                      ₱<?= number_format($record['total_amount'], 2) ?>
                    </span>
                  </td>
                  <td><?= (new DateTime($record['billing_date']))->format('F j, Y g:i a') ?></td>
                  <td>
                    <a href="view_billing.php?billing_id=<?= $record['billing_id'] ?>" 
                       class="btn btn-info btn-action" title="View Details">
                      <i class="fas fa-eye"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center py-4">
                  <i class="fas fa-info-circle me-2"></i>No billing records found.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <nav>
          <ul class="pagination justify-content-center">
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= getPageUrl(1, $queryParams) ?>" aria-label="First">
                <i class="fas fa-angle-double-left"></i>
              </a>
            </li>
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= getPageUrl(max(1, $page - 1), $queryParams) ?>" aria-label="Previous">
                <i class="fas fa-angle-left"></i>
              </a>
            </li>
            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            for ($i = $startPage; $i <= $endPage; $i++): ?>
              <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                <a class="page-link" href="<?= getPageUrl($i, $queryParams) ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= getPageUrl(min($totalPages, $page + 1), $queryParams) ?>" aria-label="Next">
                <i class="fas fa-angle-right"></i>
              </a>
            </li>
            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= getPageUrl($totalPages, $queryParams) ?>" aria-label="Last">
                <i class="fas fa-angle-double-right"></i>
              </a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const searchForm = document.getElementById('searchForm');
      const searchInput = document.getElementById('searchInput');
      const dateFilter = document.getElementById('dateFilter');
      const searchByFilter = document.getElementById('searchByFilter');
      let searchTimeout;

      function handleSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          searchForm.submit();
        }, 500); // 500ms delay for search input
      }

      searchInput.addEventListener('input', handleSearch);
      dateFilter.addEventListener('change', () => searchForm.submit());
      searchByFilter.addEventListener('change', () => searchForm.submit());
    });
  </script>
</body>
</html>
