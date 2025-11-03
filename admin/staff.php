<?php
// Start session and check if the user is logged in as admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

require '../connections/connections.php';
$pdo = connection();

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch staff with pagination
$query = "SELECT * FROM staff ORDER BY date_hired DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch total staff count
$totalQuery = "SELECT COUNT(*) FROM staff";
$totalStmt = $pdo->query($totalQuery);
$totalCount = $totalStmt->fetchColumn();
$totalPages = ceil($totalCount / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/components.css">
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
    <?php include '../sidebar.php'; ?>
    <main class="dashboard-main-content">
        <?php include '../admin/breadcrumb.php'; ?>
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-0">
                        <i class="fas fa-user-md me-2"></i>Staff
                    </h2>
                    <p class="mb-0 mt-2">
                        <i class="fas fa-users me-2"></i>Total Staff: <?= $totalCount ?>
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-success" onclick="openAddStaffModal()">
                        <i class="fas fa-user-plus me-2"></i>Add Staff
                    </button>
                </div>
            </div>
        </div>
        <!-- Search and Filter Section -->
        <div class="search-container">
            <form class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search staff by name, email...">
                        <button class="btn btn-outline-success" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <select id="roleFilter" class="form-select">
                        <option value="">All Roles</option>
                        <option value="Doctor">Doctor</option>
                        <option value="Midwife">Midwife</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="attendanceFilter" class="form-select">
                        <option value="">All Attendance</option>
                        <option value="Present">Present</option>
                        <option value="Absent">Absent</option>
                        <option value="Not Set">Not Set</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="statusFilter" class="form-select">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button id="resetFilters" class="btn btn-secondary w-100" type="button">
                        <i class="fas fa-redo-alt me-2"></i>Reset
                    </button>
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
        <!-- Staff Table -->
        <div class="table-container">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Date Hired</th>
                        <th>Status</th>
                        <th>Attendance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($staff)): ?>
                        <?php foreach ($staff as $index => $s): ?>
                            <tr>
                                <td><?= (($page - 1) * $limit) + $index + 1 ?></td>
                                <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                                <td><?= htmlspecialchars($s['role']) ?></td>
                                <td><?= htmlspecialchars($s['email']) ?></td>
                                <td><?= htmlspecialchars($s['contact_number']) ?></td>
                                <td><?= $s['date_hired'] ? (new DateTime($s['date_hired']))->format('F j, Y') : '' ?></td>
                                <td><?= htmlspecialchars($s['status']) ?></td>
                                <td>
                                    <?php if ($s['attendance_status'] === 'Present'): ?>
                                        <span class="badge bg-success">Present</span>
                                    <?php elseif ($s['attendance_status'] === 'Absent'): ?>
                                        <span class="badge bg-danger">Absent</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Not Set</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-info btn-action" title="Edit" onclick="openEditStaffModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-info-circle me-2"></i>No staff found.
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
                        <a class="page-link" href="?page=1" aria-label="First">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    </li>
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= max(1, $page - 1) ?>" aria-label="Previous">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    </li>
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>" aria-label="Next">
                            <i class="fas fa-angle-right"></i>
                        </a>
                    </li>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $totalPages ?>" aria-label="Last">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </main>
</div>
<!-- Add/Edit Staff Modal -->
<div class="modal fade" id="staffModal" tabindex="-1" aria-labelledby="staffModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="staffForm" method="POST" action="process_staff.php">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="staffModalLabel">Add Staff</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="staff_id" id="staff_id">
        <div class="row g-3">
          <div class="col-md-6">
            <label for="first_name" class="form-label">First Name</label>
            <input type="text" class="form-control" id="first_name" name="first_name" required>
          </div>
          <div class="col-md-6">
            <label for="last_name" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="last_name" name="last_name" required>
          </div>
        </div>
        <div class="row g-3 mt-2">
          <div class="col-md-6">
            <label for="role" class="form-label">Role</label>
            <select class="form-select" id="role" name="role" required>
              <option value="">Select Role</option>
              <option value="Doctor">Doctor</option>
              <option value="Midwife">Midwife</option>
              <option value="Admin">Admin</option>
            </select>
          </div>
          <div class="col-md-6">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
          </div>
        </div>
        <div class="row g-3 mt-2">
          <div class="col-md-6">
            <label for="contact_number" class="form-label">Contact Number</label>
            <input type="text" class="form-control" id="contact_number" name="contact_number">
          </div>
          <div class="col-md-6">
            <label for="date_hired" class="form-label">Date Hired</label>
            <input type="date" class="form-control" id="date_hired" name="date_hired">
          </div>
        </div>
        <div class="row g-3 mt-2">
          <div class="col-md-6">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <div class="col-md-6">
            <label for="attendance_status" class="form-label">Attendance</label>
            <select class="form-select" id="attendance_status" name="attendance_status" required>
              <option value="Not Set">Not Set</option>
              <option value="Present">Present</option>
              <option value="Absent">Absent</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success d-flex align-items-center" id="saveStaffBtn">
          <span id="saveStaffBtnText">Save</span>
          <span class="spinner-border spinner-border-sm ms-2 d-none" id="saveSpinner" role="status" aria-hidden="true"></span>
        </button>
      </div>
    </form>
  </div>
</div>
<!-- Toast for feedback -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
  <div id="staffToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="staffToastBody"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const roleFilter = document.getElementById('roleFilter');
    const attendanceFilter = document.getElementById('attendanceFilter');
    const statusFilter = document.getElementById('statusFilter');
    const resetButton = document.getElementById('resetFilters');
    const table = document.querySelector('.table');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedRole = roleFilter.value.toLowerCase();
        const selectedAttendance = attendanceFilter.value.toLowerCase();
        const selectedStatus = statusFilter.value.toLowerCase();

        Array.from(rows).forEach(row => {
            const name = row.cells[1].textContent.toLowerCase();
            const role = row.cells[2].textContent.toLowerCase();
            const attendance = row.cells[7].textContent.toLowerCase();
            const status = row.cells[6].textContent.toLowerCase();
            const email = row.cells[3].textContent.toLowerCase();

            const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
            const matchesRole = !selectedRole || role === selectedRole;
            const matchesAttendance = !selectedAttendance || attendance === selectedAttendance;
            const matchesStatus = !selectedStatus || status === selectedStatus;

            row.style.display = (matchesSearch && matchesRole && matchesAttendance && matchesStatus) ? '' : 'none';
        });
    }

    searchInput.addEventListener('input', filterTable);
    roleFilter.addEventListener('change', filterTable);
    attendanceFilter.addEventListener('change', filterTable);
    statusFilter.addEventListener('change', filterTable);

    resetButton.addEventListener('click', function() {
        searchInput.value = '';
        roleFilter.value = '';
        attendanceFilter.value = '';
        statusFilter.value = '';
        Array.from(rows).forEach(row => row.style.display = '');
    });
});

function openAddStaffModal() {
    document.getElementById('staffForm').reset();
    document.getElementById('staffModalLabel').textContent = 'Add Staff';
    document.getElementById('saveStaffBtnText').textContent = 'Save';
    document.getElementById('saveStaffBtn').classList.remove('btn-warning');
    document.getElementById('saveStaffBtn').classList.add('btn-success');
    document.getElementById('staff_id').value = '';
    var staffModal = new bootstrap.Modal(document.getElementById('staffModal'));
    staffModal.show();
}

function openEditStaffModal(staff) {
    document.getElementById('staffModalLabel').textContent = 'Edit Staff';
    document.getElementById('saveStaffBtnText').textContent = 'Update';
    document.getElementById('saveStaffBtn').classList.remove('btn-success');
    document.getElementById('saveStaffBtn').classList.add('btn-warning');
    document.getElementById('staff_id').value = staff.staff_id;
    document.getElementById('first_name').value = staff.first_name;
    document.getElementById('last_name').value = staff.last_name;
    document.getElementById('role').value = staff.role;
    document.getElementById('email').value = staff.email;
    document.getElementById('contact_number').value = staff.contact_number;
    document.getElementById('date_hired').value = staff.date_hired;
    document.getElementById('status').value = staff.status;
    document.getElementById('attendance_status').value = staff.attendance_status;
    var staffModal = new bootstrap.Modal(document.getElementById('staffModal'));
    staffModal.show();
}

// Enhanced form submission handler
const staffForm = document.getElementById('staffForm');
staffForm.onsubmit = function(e) {
    e.preventDefault();
    const saveBtn = document.getElementById('saveStaffBtn');
    const saveSpinner = document.getElementById('saveSpinner');
    saveBtn.disabled = true;
    saveSpinner.classList.remove('d-none');
    const formData = new FormData(this);
    fetch('process_staff.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        saveBtn.disabled = false;
        saveSpinner.classList.add('d-none');
        showStaffToast(data.message, data.success);
        if (data.success) {
            setTimeout(() => window.location.reload(), 1200);
        }
    })
    .catch(error => {
        saveBtn.disabled = false;
        saveSpinner.classList.add('d-none');
        showStaffToast('An error occurred while processing your request', false);
    });
};

function showStaffToast(message, success) {
    const toastEl = document.getElementById('staffToast');
    const toastBody = document.getElementById('staffToastBody');
    toastBody.textContent = message;
    toastEl.classList.remove('text-bg-success', 'text-bg-danger');
    toastEl.classList.add(success ? 'text-bg-success' : 'text-bg-danger');
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
}
</script>
</body>
</html> 