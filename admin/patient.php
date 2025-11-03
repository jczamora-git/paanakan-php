<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require '../connections/connections.php';

// Get the database connection
$pdo = connection();

// Pagination settings
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$offset = ($page - 1) * $limit; // Calculate the offset for SQL query

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$gender = isset($_GET['gender']) ? trim($_GET['gender']) : '';
$ageRange = isset($_GET['age_range']) ? trim($_GET['age_range']) : '';

// Build the WHERE clause for search and filters
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(first_name LIKE :search OR last_name LIKE :search OR case_id LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($gender)) {
    $whereConditions[] = "gender = :gender";
    $params[':gender'] = $gender;
}

if (!empty($ageRange)) {
    $currentDate = new DateTime();
    switch($ageRange) {
        case '0-18':
            $whereConditions[] = "TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18";
            break;
        case '19-30':
            $whereConditions[] = "TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 19 AND 30";
            break;
        case '31-50':
            $whereConditions[] = "TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 31 AND 50";
            break;
        case '51+':
            $whereConditions[] = "TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= 51";
            break;
    }
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Fetch patients with pagination and search
$query = "SELECT * FROM patients $whereClause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);

// Bind all parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch total patient count with search conditions
$totalQuery = "SELECT COUNT(*) FROM patients $whereClause";
$totalStmt = $pdo->prepare($totalQuery);
foreach ($params as $key => $value) {
    $totalStmt->bindValue($key, $value);
}
$totalStmt->execute();
$totalCount = $totalStmt->fetchColumn();
$totalPages = ceil($totalCount / $limit); // Total pages

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Patients</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css"><!-- Sidebar styles -->
    <link rel="stylesheet" href="../css/components.css"><!-- Table styles -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
        .sidebar.collapsed ~ .dashboard-main-content {
            margin-left: 85px;
        }
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
        .action-icon {
            background: none;
            border: none;
            padding: 0 4px;
            font-size: 1.1rem;
            line-height: 1;
            vertical-align: middle;
            transition: color 0.2s, box-shadow 0.2s;
            box-shadow: none;
            outline: none;
        }
        .action-icon:focus, .action-icon:hover {
            color: #17633c !important;
            background: none;
            box-shadow: none;
            outline: none;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include '../sidebar.php'; ?>

        <!-- Main Content -->
        <main class="dashboard-main-content">
            <?php include '../admin/breadcrumb.php'; ?>
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h2 class="mb-0">
                            <i class="fas fa-users me-2"></i>Patients
                        </h2>
                        <p class="mb-0 mt-2">
                            <i class="fas fa-user-friends me-2"></i>Total Patients: <?= $totalCount ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="add_patient.php" class="btn btn-success">
                            <i class="fas fa-user-plus me-2"></i>Add Patient
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="search-container">
                <form id="searchForm" class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" id="searchInput" name="search" class="form-control" placeholder="Search patients by name, case ID..." value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-outline-success" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select id="genderFilter" name="gender" class="form-select">
                            <option value="">All Genders</option>
                            <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="ageFilter" name="age_range" class="form-select">
                            <option value="">All Ages</option>
                            <option value="0-18" <?= $ageRange === '0-18' ? 'selected' : '' ?>>Under 18</option>
                            <option value="19-30" <?= $ageRange === '19-30' ? 'selected' : '' ?>>19-30</option>
                            <option value="31-50" <?= $ageRange === '31-50' ? 'selected' : '' ?>>31-50</option>
                            <option value="51+" <?= $ageRange === '51+' ? 'selected' : '' ?>>51 and above</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <a href="patient.php" class="btn btn-secondary w-100">
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
                            <th>Case ID</th>
                            <th>Full Name</th>
                            <th>Gender</th>
                            <th>DOB</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($patients)): ?>
                            <?php foreach ($patients as $index => $patient): ?>
                                <tr>
                                    <td><?= (($page - 1) * $limit) + $index + 1 ?></td>
                                    <td><?= htmlspecialchars($patient['case_id']) ?></td>
                                    <td><?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?></td>
                                    <td><?= htmlspecialchars($patient['gender']) ?></td>
                                    <td><?= (new DateTime($patient['date_of_birth']))->format('F j, Y') ?></td>
                                    <td><?= htmlspecialchars($patient['contact_number']) ?></td>
                                    <td><?= htmlspecialchars($patient['address']) ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="edit_patient.php?patient_id=<?= $patient['patient_id'] ?>" class="btn btn-info btn-action" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="../admin/patient_health_records.php?patient_id=<?= $patient['patient_id'] ?>" class="btn btn-primary btn-action" title="View Records">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
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
                        <?php
                        // Build query string for pagination links
                        $queryParams = [];
                        if (!empty($search)) $queryParams['search'] = $search;
                        if (!empty($gender)) $queryParams['gender'] = $gender;
                        if (!empty($ageRange)) $queryParams['age_range'] = $ageRange;
                        
                        // Function to generate pagination URL
                        function getPageUrl($pageNum, $queryParams) {
                            $queryParams['page'] = $pageNum;
                            return '?' . http_build_query($queryParams);
                        }
                        ?>
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
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');
        const genderFilter = document.getElementById('genderFilter');
        const ageFilter = document.getElementById('ageFilter');
        
        let searchTimeout;

        function handleSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchForm.submit();
            }, 500); // 500ms delay for search input
        }

        // Add event listeners
        searchInput.addEventListener('input', handleSearch);
        genderFilter.addEventListener('change', () => searchForm.submit());
        ageFilter.addEventListener('change', () => searchForm.submit());
    });
    </script>
</body>

</html>
