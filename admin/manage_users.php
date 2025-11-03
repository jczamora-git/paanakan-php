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

// Handle adding, editing, and deleting users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        // Add User
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $contact_number = $_POST['contact_number'];
        $role = $_POST['role'];

        $query = "INSERT INTO users (username, password, first_name, last_name, email, contact_number, role) 
                  VALUES (:username, :password, :first_name, :last_name, :email, :contact_number, :role)";
        $stmt = $pdo->prepare($query);

        try {
            $stmt->execute([
                ':username' => $username,
                ':password' => $password,
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':email' => $email,
                ':contact_number' => $contact_number,
                ':role' => $role,
            ]);
            $_SESSION['message'] = "User added successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to add user: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'edit') {
        // Edit User
        $user_id = intval($_POST['user_id']);
        $username = $_POST['username'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $contact_number = $_POST['contact_number'];
        $role = $_POST['role'];
        $new_password = $_POST['new_password'];
    
        // Base query for updating user details
        $query = "UPDATE users SET username = :username, first_name = :first_name, last_name = :last_name, 
                  email = :email, contact_number = :contact_number, role = :role";
    
        // Check if new password is provided
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query .= ", password = :password";
        }
    
        $query .= " WHERE user_id = :user_id";
        $stmt = $pdo->prepare($query);
    
        // Bind parameters
        $params = [
            ':username' => $username,
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':email' => $email,
            ':contact_number' => $contact_number,
            ':role' => $role,
            ':user_id' => $user_id,
        ];
    
        if (!empty($new_password)) {
            $params[':password'] = $hashed_password;
        }
    
        try {
            $stmt->execute($params);
            $_SESSION['message'] = "User updated successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to update user: " . $e->getMessage();
        }
    
        header("Location: manage_users.php");
        exit();
    } elseif ($_POST['action'] === 'delete') {
        // Delete User
        $user_id = intval($_POST['user_id']);
        $query = "DELETE FROM users WHERE user_id = :user_id";
        $stmt = $pdo->prepare($query);

        try {
            $stmt->execute([':user_id' => $user_id]);
            $_SESSION['message'] = "User deleted successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to delete user: " . $e->getMessage();
        }
    }

    header("Location: manage_users.php");
    exit();
}

// Fetch all users
$query = "SELECT user_id, username, first_name, last_name, email, contact_number, role, created_at FROM users ORDER BY created_at ASC";
$stmt = $pdo->query($query);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                            <i class="fas fa-users-cog me-2"></i>Manage Users
                        </h2>
                        <p class="mb-0 mt-2">
                            <i class="fas fa-user-friends me-2"></i>Total Users: <?= count($users) ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-end">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-user-plus me-2"></i>Add User
                        </button>
                    </div>
                </div>
            </div>
            <!-- Search and Filter Section -->
            <div class="search-container">
                <form class="row g-3" method="GET">
                    <div class="col-md-5">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search by username or email..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                            <button class="btn btn-outline-success" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select name="role" class="form-select">
                            <option value="">All Roles</option>
                            <option value="Admin" <?= (isset($_GET['role']) && $_GET['role'] === 'Admin') ? 'selected' : '' ?>>Admin</option>
                            <option value="MidWife" <?= (isset($_GET['role']) && $_GET['role'] === 'MidWife') ? 'selected' : '' ?>>Midwife</option>
                            <option value="Patient" <?= (isset($_GET['role']) && $_GET['role'] === 'Patient') ? 'selected' : '' ?>>Patient</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <a href="manage_users.php" class="btn btn-secondary w-100">
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
            <!-- Users Table -->
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Contact Number</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Filtering logic for search and role
                        $filteredUsers = $users;
                        if (isset($_GET['search']) && $_GET['search'] !== '') {
                            $search = strtolower(trim($_GET['search']));
                            $filteredUsers = array_filter($filteredUsers, function($user) use ($search) {
                                return strpos(strtolower($user['username']), $search) !== false || strpos(strtolower($user['email']), $search) !== false;
                            });
                        }
                        if (isset($_GET['role']) && $_GET['role'] !== '') {
                            $role = $_GET['role'];
                            $filteredUsers = array_filter($filteredUsers, function($user) use ($role) {
                                return $user['role'] === $role;
                            });
                        }
                        ?>
                        <?php if (!empty($filteredUsers)): ?>
                            <?php foreach ($filteredUsers as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['user_id']) ?></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['contact_number']) ?></td>
                                    <td><?= htmlspecialchars($user['role']) ?></td>
                                    <td><?= (new DateTime($user['created_at']))->format('F j, Y g:i a') ?></td>
                                    <td>
                                        <button class="btn btn-info btn-action" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $user['user_id'] ?>" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="" method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-action" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Deactivate">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <!-- Edit User Modal -->
                                <div class="modal fade" id="editUserModal<?= $user['user_id'] ?>" tabindex="-1" aria-labelledby="editUserModalLabel<?= $user['user_id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="" method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editUserModalLabel<?= $user['user_id'] ?>">Edit User</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="edit">
                                                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                    <div class="mb-3">
                                                        <label for="username" class="form-label">Username</label>
                                                        <input type="text" name="username" id="username" value="<?= htmlspecialchars($user['username']) ?>" class="form-control" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="first_name" class="form-label">First Name</label>
                                                        <input type="text" name="first_name" id="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" class="form-control" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="last_name" class="form-label">Last Name</label>
                                                        <input type="text" name="last_name" id="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" class="form-control" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="email" class="form-label">Email</label>
                                                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="contact_number" class="form-label">Contact Number</label>
                                                        <input type="text" name="contact_number" id="contact_number" value="<?= htmlspecialchars($user['contact_number']) ?>" class="form-control" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="role" class="form-label">Role</label>
                                                        <select name="role" id="role" class="form-select" required>
                                                            <option value="Admin" <?= $user['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                                                            <option value="MidWife" <?= $user['role'] === 'MidWife' ? 'selected' : '' ?>>Midwife</option>
                                                            <option value="Patient" <?= $user['role'] === 'Patient' ? 'selected' : '' ?>>Patient</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="new_password" class="form-label">New Password</label>
                                                        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Leave blank to keep current password">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" class="btn btn-primary">Update User</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-info-circle me-2"></i>No users found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUserModalLabel">Add User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" name="first_name" id="first_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="contact_number" class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" id="contact_number" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select name="role" id="role" class="form-select" required>
                                <option value="Admin">Admin</option>
                                <option value="MidWife">Midwife</option>
                                <option value="Patient">Patient</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get the search form and all select elements
            const searchForm = document.querySelector('.search-container form');
            const selectElements = searchForm.querySelectorAll('select');
            // Add change event listener to each select element
            selectElements.forEach(select => {
                select.addEventListener('change', function() {
                    searchForm.submit();
                });
            });
        });
        document.addEventListener("DOMContentLoaded", function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>

</html>
