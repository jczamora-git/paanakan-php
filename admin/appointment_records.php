<?php
session_start();
require '../connections/connections.php';
$pdo = connection(); // Assuming you have the connection function.

if ($_SESSION['role'] !== 'Admin') {
    header('Location: ../login.php');
    exit;
}

date_default_timezone_set('Asia/Manila');

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$due_only = isset($_GET['due_only']) && $_GET['due_only'] == '1';
$today = date('Y-m-d');

$limit = 5; // Number of records per page.
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page for pagination.
$offset = ($page - 1) * $limit; // Offset for SQL query.

// Build the WHERE clause based on search and filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(CONCAT(p.first_name, ' ', p.last_name) LIKE :search OR p.contact_number LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($due_only) {
    $where_conditions[] = "DATE(a.scheduled_date) < :today";
    $params[':today'] = $today;
    // Only show Approved and Pending status when showing due appointments
    if (!in_array($status_filter, ['Approved', 'Pending'])) {
        $status_filter = '';
    }
    $where_conditions[] = "a.status IN ('Approved', 'Pending')";
} else if (!empty($date_filter)) {
    $where_conditions[] = "DATE(a.scheduled_date) = :date";
    $params[':date'] = $date_filter;
}

if (!empty($type_filter)) {
    $where_conditions[] = "a.appointment_type = :type";
    $params[':type'] = $type_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "a.status = :status";
    $params[':status'] = $status_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Fetch Appointments with filters
$query = "
   SELECT a.*, p.first_name, p.last_name, p.contact_number
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    $where_clause
    ORDER BY a.scheduled_date DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total appointments with filters
$countQuery = "
    SELECT COUNT(*) 
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    $where_clause
";
$countStmt = $pdo->prepare($countQuery);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// If an appointment_id is provided, fetch that single appointment for detailed view
$appointmentError = '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/5.11.3/main.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">  
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="../css/calendar.css">
    <link rel="stylesheet" href="../css/toast-alert.css">
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

        /* Adjust margin when sidebar is collapsed */
        .sidebar.collapsed ~ .dashboard-main-content {
            margin-left: 85px;
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

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .status-done {
            background-color: #d4edda;
            color: #155724;
        }

        .status-missed {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-approved {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
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
        <!-- Sidebar -->
        <?php include '../sidebar.php'; ?>
        <!-- Main Content -->
        <main class="dashboard-main-content">
            <div class="container mt-5">
                <!-- Breadcrumb Navigation -->
                <?php include '../admin/breadcrumb.php'; ?>

                <?php if (!empty($appointmentError)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($appointmentError) ?></div>
                <?php endif; ?>

                
               <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h2 class="mb-0">
                            <i class="fas fa-calendar-check me-2"></i>Appointment Records
                        </h2>
                        <p class="mb-0 mt-2">
                            <i class="fas fa-list me-2"></i>Total Records: <?= $totalRecords ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="search-container">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search by Name or Contact" value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-outline-success" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="type" class="form-select">
                            <option value="">All Types</option>
                            <option value="Check-up" <?= $type_filter === 'Check-up' ? 'selected' : '' ?>>Check-up</option>
                            <option value="Follow-up" <?= $type_filter === 'Follow-up' ? 'selected' : '' ?>>Follow-up</option>
                            <option value="Emergency" <?= $type_filter === 'Emergency' ? 'selected' : '' ?>>Emergency</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <?php if ($due_only): ?>
                            <option value="Approved" <?= $status_filter === 'Approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <?php else: ?>
                            <option value="Approved" <?= $status_filter === 'Approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="Disapproved" <?= $status_filter === 'Disapproved' ? 'selected' : '' ?>>Disapproved</option>
                            <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Done" <?= $status_filter === 'Done' ? 'selected' : '' ?>>Done</option>
                            <option value="Missed" <?= $status_filter === 'Missed' ? 'selected' : '' ?>>Missed</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <?php if (!$due_only): ?>
                    <div class="col-md-2">
                        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date_filter) ?>">
                    </div>
                    <?php endif; ?>
                    <div class="col-md-2">
                        <a href="?due_only=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($type_filter) ? '&type=' . urlencode($type_filter) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?>" class="btn btn-<?= $due_only ? 'success' : 'outline-success' ?> w-100 <?= $due_only ? 'active' : '' ?>" id="dueOnlyBtn">
                            <i class="fas fa-exclamation-circle me-1"></i>Show Due Only
                        </a>
                    </div>
                    <div class="col-md-1">
                        <a href="appointment_records.php" class="btn btn-secondary w-100">
                            <i class="fas fa-redo-alt"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Appointments Table -->
            <div class="table-container">
                <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Patient Name</th>
                                    <th>Contact</th>
                            <th>Type</th>
                            <th>Status</th>
                                    <th>Scheduled Date</th>
                                    <th>Completed Date</th>
                                    <?php if ($due_only): ?>
                                    <th>Action</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                    <tbody>
                        <?php if (!empty($appointments)): ?>
                            <?php foreach ($appointments as $index => $appointment): ?>
                                <tr>
                                    <td><?= (($page - 1) * $limit) + $index + 1 ?></td>
                                    <td><?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></td>
                                    <td><?= htmlspecialchars($appointment['contact_number']) ?></td>
                                    <td><?= htmlspecialchars($appointment['appointment_type']) ?></td>
                                    <td>
                                        <?php
                                            $status = htmlspecialchars($appointment['status']);
                                            $badgeClass = '';
                                            if ($status === 'Done') {
                                                $badgeClass = 'status-badge status-done';
                                            } elseif ($status === 'Missed') {
                                                $badgeClass = 'status-badge status-missed';
                                            } elseif ($status === 'Approved') {
                                                $badgeClass = 'status-badge status-approved';
                                            } else {
                                                $badgeClass = 'status-badge status-pending';
                                            }
                                        ?>
                                        <span class="<?= $badgeClass ?>"><?= $status ?></span>
                                    </td>
                                    <td><?= (new DateTime($appointment['scheduled_date']))->format('F j, Y g:i a') ?></td>
                                    <td>
                                        <?= $appointment['completed_date'] 
                                            ? (new DateTime($appointment['completed_date']))->format('F j, Y g:i a')
                                            : 'N/A' ?>
                                    </td>
                                    <?php if ($due_only): ?>
                                    <td>
                                        <?php if ($appointment['status'] === 'Pending'): ?>
                                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#disapproveModal<?= $appointment['appointment_id'] ?>">
                                                <i class="fas fa-times"></i> Disapprove
                                            </button>
                                        <?php elseif ($appointment['status'] === 'Approved'): ?>
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#missedModal<?= $appointment['appointment_id'] ?>">
                                                <i class="fas fa-clock"></i> Mark as Missed
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php if ($due_only): ?>
                                <!-- Disapprove Modal -->
                                <?php if ($appointment['status'] === 'Pending'): ?>
                                <div class="modal fade" id="disapproveModal<?= $appointment['appointment_id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Disapprove Appointment</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to disapprove this appointment for <strong><?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></strong>?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="button" class="btn btn-danger confirm-disapprove" data-id="<?= $appointment['appointment_id'] ?>">Disapprove</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <!-- Mark as Missed Modal -->
                                <?php if ($appointment['status'] === 'Approved'): ?>
                                <div class="modal fade" id="missedModal<?= $appointment['appointment_id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Mark as Missed</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to mark this appointment as missed for <strong><?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></strong>?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="button" class="btn btn-warning confirm-missed" data-id="<?= $appointment['appointment_id'] ?>">Mark as Missed</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-info-circle me-2"></i>No appointments found.
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
                            <a class="page-link" href="?page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($type_filter) ? '&type=' . urlencode($type_filter) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?><?= $due_only ? '&due_only=1' : (!empty($date_filter) ? '&date=' . urlencode($date_filter) : '') ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= max(1, $page - 1) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($type_filter) ? '&type=' . urlencode($type_filter) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?><?= $due_only ? '&due_only=1' : (!empty($date_filter) ? '&date=' . urlencode($date_filter) : '') ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($type_filter) ? '&type=' . urlencode($type_filter) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?><?= $due_only ? '&due_only=1' : (!empty($date_filter) ? '&date=' . urlencode($date_filter) : '') ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($type_filter) ? '&type=' . urlencode($type_filter) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?><?= $due_only ? '&due_only=1' : (!empty($date_filter) ? '&date=' . urlencode($date_filter) : '') ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $totalPages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($type_filter) ? '&type=' . urlencode($type_filter) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?><?= $due_only ? '&due_only=1' : (!empty($date_filter) ? '&date=' . urlencode($date_filter) : '') ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Status Message Modal -->
    <div class="modal fade" id="statusMessageModal" tabindex="-1" aria-labelledby="statusMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog mt-5">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusMessageModalLabel">Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="statusMessageModalBody">
                    <!-- Message will be injected here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Toast Alert System -->
    <script src="../js/toast-alert.js"></script>
    <script src="../js/toast-integration.js"></script>
    <script>
        // Add this script to handle automatic form submission on dropdown change
        document.addEventListener('DOMContentLoaded', function() {
            const searchForm = document.querySelector('form[method="GET"]');
            const selectElements = searchForm.querySelectorAll('select');
            selectElements.forEach(select => {
                select.addEventListener('change', function() {
                    searchForm.submit();
                });
            });
        });

        // Helper: show toast notifications
        function showToastSuccess(message) {
            if (typeof Toast !== 'undefined' && Toast.success) {
                Toast.success(message);
            } else {
                // Fallback to alert
                alert(message);
            }
        }

        function showToastError(message) {
            if (typeof Toast !== 'undefined' && Toast.error) {
                Toast.error(message);
            } else {
                // Fallback to alert
                alert(message);
            }
        }

        // Function to reload the table
        function reloadTable() {
            // Get current URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            // Reload the page with the same parameters
            window.location.href = window.location.pathname + '?' + urlParams.toString();
        }

        // Handle Disapprove action
        $(document).on('click', '.confirm-disapprove', function() {
            var appointmentId = $(this).data('id');
            $.ajax({
                url: 'update_appointment_status.php',
                method: 'POST',
                data: { appointment_id: appointmentId, status: 'Disapproved' },
                success: function(response) {
                    let jsonResponse = JSON.parse(response);
                    if (jsonResponse.success) {
                        $('.modal').modal('hide');
                        showToastSuccess("Appointment disapproved successfully!");
                        // Reload table after a short delay
                        setTimeout(reloadTable, 1000);
                    } else {
                        showToastError("Error updating appointment status.");
                    }
                },
                error: function() {
                    showToastError("Failed to update appointment status. Please try again.");
                }
            });
        });

        // Handle Mark as Missed action
        $(document).on('click', '.confirm-missed', function() {
            var appointmentId = $(this).data('id');
            $.ajax({
                url: 'update_appointment_status.php',
                method: 'POST',
                data: { appointment_id: appointmentId, status: 'Missed' },
                success: function(response) {
                    let jsonResponse = JSON.parse(response);
                    if (jsonResponse.success) {
                        $('.modal').modal('hide');
                        showToastSuccess("Appointment marked as missed!");
                        // Reload table after a short delay
                        setTimeout(reloadTable, 1000);
                    } else {
                        showToastError("Error updating appointment status.");
                    }
                },
                error: function() {
                    showToastError("Failed to update appointment status. Please try again.");
                }
            });
        });
    </script>
</body>
</html>
