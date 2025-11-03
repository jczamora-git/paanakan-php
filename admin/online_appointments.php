<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require '../connections/connections.php';

// Get database connection
$pdo = connection();

// Get current date and calculate the end date (+6 days)
date_default_timezone_set('Asia/Manila'); 
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-d');
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-d', strtotime($startDate . ' +6 days'));
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$due_only = isset($_GET['due_only']) && $_GET['due_only'] == '1';
$today = date('Y-m-d');

// Appointment ID filter (optional)
$filter_appointment_id = isset($_GET['appointment_id']) && is_numeric($_GET['appointment_id']) ? (int) $_GET['appointment_id'] : 0;
// If notification passed an appointment_id and no search was provided, use it as the search value
if ($filter_appointment_id && $search === '') {
    $search = (string) $filter_appointment_id;
}
// When appointment_id is provided, ignore date range filtering
$ignoreDateRange = $filter_appointment_id > 0;

// If due_only is set, ignore date range
if ($due_only) {
    $startDate = '';
    $endDate = '';
}

$limit = 5; // Records per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build the WHERE clause based on search and filters
$where_conditions = ["a.status = 'Pending'"];
$params = [];

if (!empty($search)) {
    $searchParam = "%$search%";
    $searchConditions = [
        "CONCAT(p.first_name, ' ', p.last_name) LIKE :search",
        "p.contact_number LIKE :search",
        "p.case_id LIKE :search"
    ];
    $params[':search'] = $searchParam;

    // If search is numeric, also match appointment_id exactly
    if (ctype_digit($search)) {
        $searchConditions[] = "a.appointment_id = :search_exact";
        $params[':search_exact'] = (int) $search;
    }

    $where_conditions[] = '(' . implode(' OR ', $searchConditions) . ')';
}

if (!empty($type_filter)) {
    $where_conditions[] = "a.appointment_type = :type";
    $params[':type'] = $type_filter;
}

if ($due_only) {
    $where_conditions[] = "DATE(a.scheduled_date) < :today";
    $params[':today'] = $today;
}

// If appointment_id filter present, restrict to that appointment
if (!empty($filter_appointment_id)) {
    $where_conditions[] = "a.appointment_id = :appointment_id";
    $params[':appointment_id'] = $filter_appointment_id;
}

$where_clause = implode(" AND ", $where_conditions);

// Count total records for pagination
// When $ignoreDateRange is true (notification redirect with appointment_id), do NOT apply the DATE BETWEEN filter.
if ($ignoreDateRange) {
    // Use only the constructed where clause (it may include due_only and appointment_id)
    $totalQuery = $pdo->prepare(
        "SELECT COUNT(*) 
        FROM Appointments a
        JOIN Patients p ON a.patient_id = p.patient_id
        WHERE $where_clause"
    );
    $totalQuery->execute($params);
    $totalRecords = $totalQuery->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    // Fetch paginated appointments (no date range applied)
    $appointmentsQuery = "
    SELECT a.appointment_id, a.scheduled_date, p.first_name, p.last_name, p.contact_number, a.appointment_type
    FROM Appointments a
    JOIN Patients p ON a.patient_id = p.patient_id
    WHERE $where_clause
    ORDER BY a.scheduled_date ASC
    LIMIT :limit OFFSET :offset";

} else {
    // Normal flow: apply date range unless due_only is active (due_only is already included in where_conditions)
    if ($due_only) {
        $totalQuery = $pdo->prepare(
            "SELECT COUNT(*) 
            FROM Appointments a
            JOIN Patients p ON a.patient_id = p.patient_id
            WHERE $where_clause"
        );
        $totalQuery->execute($params);
        $totalRecords = $totalQuery->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);

        $appointmentsQuery = "
        SELECT a.appointment_id, a.scheduled_date, p.first_name, p.last_name, p.contact_number, a.appointment_type
        FROM Appointments a
        JOIN Patients p ON a.patient_id = p.patient_id
        WHERE $where_clause
        ORDER BY a.scheduled_date ASC
        LIMIT :limit OFFSET :offset";
    } else {
        // Apply date range filter
        $params[':startDate'] = $startDate;
        $params[':endDate'] = $endDate;
        $totalQuery = $pdo->prepare(
            "SELECT COUNT(*) 
            FROM Appointments a
            JOIN Patients p ON a.patient_id = p.patient_id
            WHERE $where_clause
            AND DATE(a.scheduled_date) BETWEEN :startDate AND :endDate"
        );
        $totalQuery->execute($params);
        $totalRecords = $totalQuery->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);

        // Fetch paginated appointments (with date range)
        $appointmentsQuery = "
        SELECT a.appointment_id, a.scheduled_date, p.first_name, p.last_name, p.contact_number, a.appointment_type
        FROM Appointments a
        JOIN Patients p ON a.patient_id = p.patient_id
        WHERE $where_clause
        AND DATE(a.scheduled_date) BETWEEN :startDate AND :endDate
        ORDER BY a.scheduled_date ASC
        LIMIT :limit OFFSET :offset";
    }
}

$stmt = $pdo->prepare($appointmentsQuery);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Preserve appointment_id for pagination/auto-open modal
$openAppointmentId = $filter_appointment_id;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Appointments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">  
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
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

        .form-select, .form-control {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            padding: 8px 12px;
        }

        .form-select:focus, .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.25);
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

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include '../sidebar.php'; ?>

        <!-- Main Content -->
        <main class="dashboard-main-content">
            <div class="container mt-4">  
                <!-- Breadcrumb -->
                <?php include '../admin/breadcrumb.php'; ?>

                <!-- Page Header -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h2 class="mb-0">
                                <i class="fas fa-calendar-check me-2"></i>Online Appointments
                            </h2>
                            <p class="mb-0 mt-2">
                                <i class="fas fa-list me-2"></i>Total Pending: <?= $totalRecords ?>
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
                        <?php if (!$due_only): ?>
                        <div class="col-md-2">
                            <input type="date" name="startDate" class="form-control" value="<?= $startDate ?>" required>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="endDate" class="form-control" value="<?= $endDate ?>" required>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-2">
                            <a href="?due_only=1" class="btn btn-<?= $due_only ? 'success' : 'outline-success' ?> w-100 <?= $due_only ? 'active' : '' ?>" id="dueOnlyBtn">
                                <i class="fas fa-exclamation-circle me-1"></i>Show Due Only
                            </a>
                        </div>
                        <div class="col-md-1">
                            <a href="online_appointments.php" class="btn btn-secondary w-100">
                                <i class="fas fa-redo-alt"></i>
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Appointments Table -->
                <div class="table-container">
                    <h5 class="mb-3">
                        <?php if ($due_only): ?>
                            Pending Due Appointments
                        <?php elseif ($ignoreDateRange): ?>
                            Pending Appointments (Filtered)
                        <?php else: ?>
                            Pending Appointments (<?= date('M j', strtotime($startDate)) ?> - <?= date('M j, Y', strtotime($endDate)) ?>)
                        <?php endif; ?>
                    </h5>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Patient Name</th>
                                <th>Contact</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($appointments)): ?>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr id="appointment-<?= $appointment['appointment_id'] ?>">
                                        <td><?= date("M j, Y", strtotime($appointment['scheduled_date'])) ?></td>
                                        <td><?= date("g:i A", strtotime($appointment['scheduled_date'])) ?></td>
                                        <td><?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></td>
                                        <td><?= htmlspecialchars($appointment['contact_number']) ?></td>
                                        <td><?= htmlspecialchars($appointment['appointment_type']) ?></td>
                                        <td>
                                            <span class="status-badge status-pending">Pending</span>
                                        </td>
                                        <td>
                                            <!-- Show both Approve and Disapprove buttons for admins -->
                                            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#approveModal<?= $appointment['appointment_id'] ?>" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#disapproveModal<?= $appointment['appointment_id'] ?>" title="Disapprove">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <!-- Approve Modal -->
                                    <div class="modal fade" id="approveModal<?= $appointment['appointment_id'] ?>" tabindex="-1" aria-labelledby="approveModalLabel<?= $appointment['appointment_id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="approveModalLabel<?= $appointment['appointment_id'] ?>">Approve Appointment</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to approve this appointment for <strong><?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></strong> on <strong><?= date("M j, Y g:i A", strtotime($appointment['scheduled_date'])) ?></strong>?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-success confirm-approve" data-id="<?= $appointment['appointment_id'] ?>">Approve</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Disapprove Modal -->
                                    <div class="modal fade" id="disapproveModal<?= $appointment['appointment_id'] ?>" tabindex="-1" aria-labelledby="disapproveModalLabel<?= $appointment['appointment_id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="disapproveModalLabel<?= $appointment['appointment_id'] ?>">Disapprove Appointment</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to disapprove this overdue appointment for <strong><?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']) ?></strong> scheduled on <strong><?= date("M j, Y g:i A", strtotime($appointment['scheduled_date'])) ?></strong>?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-danger confirm-disapprove" data-id="<?= $appointment['appointment_id'] ?>">Disapprove</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-info-circle me-2"></i>No pending appointments found.
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
                                <a class="page-link" href="?page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($type_filter) ? '&type=' . urlencode($type_filter) : '' ?><?= $due_only ? '&due_only=1' : ($ignoreDateRange ? '' : '&startDate=' . $startDate . '&endDate=' . $endDate) ?>">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= max(1, $page - 1) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($type_filter) ? '&type=' . urlencode($type_filter) : '' ?><?= $due_only ? '&due_only=1' : ($ignoreDateRange ? '' : '&startDate=' . $startDate . '&endDate=' . $endDate) ?>">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($type_filter) ? '&type=' . urlencode($type_filter) : '' ?><?= $due_only ? '&due_only=1' : ($ignoreDateRange ? '' : '&startDate=' . $startDate . '&endDate=' . $endDate) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($type_filter) ? '&type=' . urlencode($type_filter) : '' ?><?= $due_only ? '&due_only=1' : ($ignoreDateRange ? '' : '&startDate=' . $startDate . '&endDate=' . $endDate) ?>">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $totalPages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($type_filter) ? '&type=' . urlencode($type_filter) : '' ?><?= $due_only ? '&due_only=1' : ($ignoreDateRange ? '' : '&startDate=' . $startDate . '&endDate=' . $endDate) ?>">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </main>
    </div>

        <!-- Toast alert container is provided by toast-alert.css/js -->

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/toast-alert.js"></script>
    <script src="../js/toast-integration.js"></script>
    <script>
        // Update the script to handle both select and date inputs
        document.addEventListener('DOMContentLoaded', function() {
            const searchForm = document.querySelector('form[method="GET"]');
            
            // Handle select elements
            const selectElements = searchForm.querySelectorAll('select');
            selectElements.forEach(select => {
                select.addEventListener('change', function() {
                    searchForm.submit();
                });
            });

            // Handle date inputs
            const dateInputs = searchForm.querySelectorAll('input[type="date"]');
            dateInputs.forEach(input => {
                input.addEventListener('change', function() {
                    // Validate that both dates are selected
                    const startDate = searchForm.querySelector('input[name="startDate"]').value;
                    const endDate = searchForm.querySelector('input[name="endDate"]').value;
                    
                    if (startDate && endDate) {
                        // Validate that end date is not before start date
                        if (new Date(endDate) >= new Date(startDate)) {
                            searchForm.submit();
                        } else {
                            alert('End date cannot be before start date');
                            // Reset the end date to start date
                            searchForm.querySelector('input[name="endDate"]').value = startDate;
                        }
                    }
                });
            });

            // If due_only is active, disable date fields
            <?php if ($due_only): ?>
            document.querySelectorAll('input[type=date]').forEach(function(input) {
                input.disabled = true;
            });
            <?php endif; ?>
        });

        // Approve/Disapprove with modal confirmation
        $(document).on('click', '.confirm-approve', function() {
            var appointmentId = $(this).data('id');
            $.ajax({
                url: 'update_appointment_status.php',
                method: 'POST',
                data: { appointment_id: appointmentId, status: 'Approved' },
                success: function(response) {
                    let jsonResponse = JSON.parse(response);
                    if (jsonResponse.success) {
                        // Fade out the removed row, then refresh the table container with a slide-in
                        $('#appointment-' + appointmentId).fadeOut(300, function() {
                            $('.modal').modal('hide');
                            Toast.success("Appointment status updated successfully!");
                            // Log email result if present
                            if (jsonResponse.email) console.info('Email send result:', jsonResponse.email);
                            // Fetch refreshed table HTML and slide it in
                            $.get('fetch_online_appointments.php' + window.location.search, function(html) {
                                $('.table-container').hide().html(html).slideDown(400);
                            });
                        });
                    } else {
                        Toast.error("Error updating appointment status.");
                    }
                },
                error: function() {
                    Toast.error("Failed to update appointment status. Please try again.");
                }
            });
        });
        $(document).on('click', '.confirm-disapprove', function() {
            var appointmentId = $(this).data('id');
            $.ajax({
                url: 'update_appointment_status.php',
                method: 'POST',
                data: { appointment_id: appointmentId, status: 'Disapproved' },
                success: function(response) {
                    let jsonResponse = JSON.parse(response);
                    if (jsonResponse.success) {
                        // Fade out the removed row, then refresh the table container with a slide-in
                        $('#appointment-' + appointmentId).fadeOut(300, function() {
                            $('.modal').modal('hide');
                            Toast.success("Appointment status updated successfully!");
                            if (jsonResponse.email) console.info('Email send result:', jsonResponse.email);
                            $.get('fetch_online_appointments.php' + window.location.search, function(html) {
                                $('.table-container').hide().html(html).slideDown(400);
                            });
                        });
                    } else {
                        Toast.error("Error updating appointment status.");
                    }
                },
                error: function() {
                    Toast.error("Failed to update appointment status. Please try again.");
                }
            });
        });

        // Previously we auto-opened the disapprove modal when redirected with an appointment_id.
        // That behavior was removed so redirects only filter the list without showing any modal/alert.
    </script>
</body>
</html>
