<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require '../connections/connections.php';
$pdo = connection();

// Set the number of records per page
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build the WHERE clause based on search and filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.case_id LIKE :search OR p.first_name LIKE :search OR p.last_name LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($status_filter)) {
    switch ($status_filter) {
        case 'Admitted':
            $where_conditions[] = "a.admission_date IS NOT NULL AND a.discharge_date IS NULL";
            break;
        case 'Discharged':
            $where_conditions[] = "a.discharge_date IS NOT NULL";
            break;
        case 'Outpatient':
            $where_conditions[] = "a.admission_date IS NULL";
            break;
    }
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Fetch patients with search and filters
$query = "
    WITH patient_visits AS (
        SELECT patient_id, visit_date
        FROM (
            SELECT patient_id, scheduled_date as visit_date 
            FROM appointments
            WHERE status != 'Cancelled'

            UNION ALL

            SELECT p.patient_id, mt.transaction_date as visit_date 
            FROM medical_transactions mt 
            INNER JOIN patients p ON mt.case_id = p.case_id

            UNION ALL

            SELECT 
                patient_id,
                CASE 
                    WHEN discharge_date IS NULL THEN admission_date
                    ELSE discharge_date
                END as visit_date
            FROM admissions
        ) AS all_visits
    )
    SELECT 
        p.patient_id, 
        p.case_id, 
        CONCAT(p.first_name, ' ', p.last_name) AS fullname, 
        COALESCE(a.admission_id, NULL) AS admission_record_id, 
        COALESCE(a.admission_date, NULL) AS admission_date, 
        COALESCE(a.discharge_date, NULL) AS discharge_date,
        pv.visit_date AS last_visit
    FROM patients p
    LEFT JOIN admissions a ON p.patient_id = a.patient_id 
        AND a.discharge_date IS NULL
    LEFT JOIN (
        SELECT patient_id, MAX(visit_date) as visit_date
        FROM patient_visits
        GROUP BY patient_id
    ) pv ON p.patient_id = pv.patient_id
    $where_clause
    ORDER BY p.case_id DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total filtered patients
$totalQuery = "
    SELECT COUNT(*) AS total 
    FROM patients p
    LEFT JOIN admissions a ON p.patient_id = a.patient_id 
        AND a.discharge_date IS NULL
    $where_clause
";
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
if (!empty($status_filter)) $queryParams['status'] = $status_filter;

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
    <title>Manage Health Records</title>
    <!-- Bootstrap CSS -->
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

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .status-admitted {
            background-color: #d4edda;
            color: #155724;
        }

        .status-discharged {
            background-color: #e2e3e5;
            color: #383d41;
        }

        .status-outpatient {
            background-color: #cce5ff;
            color: #004085;
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
                            <i class="fas fa-notes-medical me-2"></i>Health Records
                        </h2>
                        <p class="mb-0 mt-2">
                            <i class="fas fa-users me-2"></i>Total Patients: <?= $totalRecords ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="search-container">
                <form id="searchForm" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" id="searchInput" name="search" class="form-control" placeholder="Search patients by name, case ID..." value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-outline-success" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select id="statusFilter" name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Admitted" <?= $status_filter === 'Admitted' ? 'selected' : '' ?>>Admitted</option>
                            <option value="Discharged" <?= $status_filter === 'Discharged' ? 'selected' : '' ?>>Discharged</option>
                            <option value="Outpatient" <?= $status_filter === 'Outpatient' ? 'selected' : '' ?>>Outpatient</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <a href="manage_health_records.php" class="btn btn-secondary w-100">
                            <i class="fas fa-redo-alt me-2"></i>Reset
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
            <?php elseif (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Patients Table -->
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th>Case ID</th>
                            <th>Patient Status</th>
                            <th>Last Visit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($patients)): ?>
                            <?php foreach ($patients as $index => $patient): ?>
                                <?php 
                                    $status = 'Outpatient';
                                    $status_class = 'status-outpatient';
                                    $admission_record_id = $patient['admission_record_id']; 

                                    if ($patient['admission_date'] && !$patient['discharge_date']) {
                                        $status = 'Admitted';
                                        $status_class = 'status-admitted';
                                    } elseif ($patient['discharge_date']) {
                                        $status = 'Discharged';
                                        $status_class = 'status-discharged';
                                    }

                                    // Format last visit date
                                    $last_visit = $patient['last_visit'] ? date('M d, Y', strtotime($patient['last_visit'])) : 'No visits';
                                ?>
                                <tr>
                                    <td><?= $offset + $index + 1 ?></td>
                                    <td><?= htmlspecialchars($patient['fullname']) ?></td>
                                    <td><?= htmlspecialchars($patient['case_id']) ?></td>
                                    <td>
                                        <span class="status-badge <?= $status_class ?>">
                                            <?= $status ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?= $last_visit ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="patient_health_records.php?patient_id=<?= $patient['patient_id'] ?>" 
                                               class="btn btn-info btn-action" title="View Records">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <?php if ($status === 'Admitted' && $admission_record_id): ?>
                                                <a href="discharge.php?patient_id=<?= $patient['patient_id'] ?>&record_id=<?= $admission_record_id ?>" 
                                                   class="btn btn-danger btn-action" title="Discharge">
                                                    <i class="fas fa-sign-out-alt"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="admission.php?patient_id=<?= $patient['patient_id'] ?>" 
                                                   class="btn btn-success btn-action" title="Admit">
                                                    <i class="fas fa-hospital-user"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-info-circle me-2"></i>No patients found.
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        let searchTimeout;

        function handleSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchForm.submit();
            }, 500); // 500ms delay for search input
        }

        searchInput.addEventListener('input', handleSearch);
        statusFilter.addEventListener('change', () => searchForm.submit());
    });
    </script>
</body>
</html>
