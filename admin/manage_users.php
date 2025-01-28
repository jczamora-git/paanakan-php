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
        $email = $_POST['email'];
        $contact_number = $_POST['contact_number'];
        $role = $_POST['role'];

        $query = "INSERT INTO users (username, password, email, contact_number, role) 
                  VALUES (:username, :password, :email, :contact_number, :role)";
        $stmt = $pdo->prepare($query);

        try {
            $stmt->execute([
                ':username' => $username,
                ':password' => $password,
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
        if ($_POST['action'] === 'edit') {
            $user_id = intval($_POST['user_id']);
            $username = $_POST['username'];
            $email = $_POST['email'];
            $contact_number = $_POST['contact_number'];
            $role = $_POST['role'];
            $new_password = $_POST['new_password'];
        
            // Base query for updating user details
            $query = "UPDATE users SET username = :username, email = :email, contact_number = :contact_number, role = :role";
        
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
        }
        
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
$query = "SELECT user_id, username, email, contact_number, role, created_at FROM users ORDER BY created_at DESC";
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
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css"><!-- Sidebar styles -->
    <link rel="stylesheet" href="../css/components.css"><!-- Table styles -->
</head>

<body style="font-family: 'Poppins', sans-serif;">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="sidebar-header">
                <img src="../PSC_banner.png" alt="Paanakan Logo">
            </div>
            <ul>
                <li><a href="dashboard.php" class="active"><span class="material-icons">dashboard</span><span class="link-text">Dashboard</span></a></li>
                <li><a href="manage_appointments.php"><span class="material-icons">event</span><span class="link-text">Appointments</span></a></li>
                <li><a href="manage_health_records.php"><span class="material-icons">folder</span><span class="link-text">Health Records</span></a></li>
                <li><a href="transactions.php"><span class="material-icons">local_hospital</span><span class="link-text">Medical Services</span></a></li>
                <li><a href="patient.php"><span class="material-icons">person</span><span class="link-text">Patients</span></a></li>
                <li><a href="supply.php"><span class="material-icons">inventory_2</span><span class="link-text">Supplies</span></a></li>
                <li><a href="billing.php"><span class="material-icons">receipt</span><span class="link-text">Billing</span></a></li>
                <li><a href="reports.php"><span class="material-icons">assessment</span><span class="link-text">Reports</span></a></li>
                <li><a href="manage_users.php"><span class="material-icons">people</span><span class="link-text">Users</span></a></li>
                <li><a href="logs.php"><span class="material-icons">history</span><span class="link-text">Logs</span></a></li>
                <li><a href="../logout.php"><span class="material-icons">logout</span><span class="link-text">Logout</span></a></li>
            </ul>
        </aside>
        <!-- Main Content -->
        <main class="dashboard-main-content">
            <div class="container mt-5">
                <h2 class="mb-4">Manage Users</h2>

                <!-- Handle success/error messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php elseif (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <!-- Add New User -->
                <button class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addUserModal">Add User</button>

                <!-- Users Table -->
                <div class="table-container shadow rounded bg-white p-3">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Contact Number</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['user_id']) ?></td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= htmlspecialchars($user['contact_number']) ?></td>
                                        <td><?= htmlspecialchars($user['role']) ?></td>
                                        <td><?= (new DateTime($user['created_at']))->format('F j, Y g:i a') ?></td>
                                        <td>
                                            <!-- Edit Button -->
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $user['user_id'] ?>">Edit</button>

                                            <!-- Delete Form -->
                                            <form action="" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Edit User Modal -->
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

                                                        <!-- Username Field -->
                                                        <div class="mb-3">
                                                            <label for="username" class="form-label">Username</label>
                                                            <input type="text" name="username" id="username" value="<?= htmlspecialchars($user['username']) ?>" class="form-control" required>
                                                        </div>

                                                        <!-- Email Field -->
                                                        <div class="mb-3">
                                                            <label for="email" class="form-label">Email</label>
                                                            <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control" required>
                                                        </div>

                                                        <!-- Contact Number Field -->
                                                        <div class="mb-3">
                                                            <label for="contact_number" class="form-label">Contact Number</label>
                                                            <input type="text" name="contact_number" id="contact_number" value="<?= htmlspecialchars($user['contact_number']) ?>" class="form-control" required>
                                                        </div>

                                                        <!-- Role Field -->
                                                        <div class="mb-3">
                                                            <label for="role" class="form-label">Role</label>
                                                            <select name="role" id="role" class="form-select" required>
                                                                <option value="Admin" <?= $user['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                                                                <option value="MidWife" <?= $user['role'] === 'MidWife' ? 'selected' : '' ?>>Midwife</option>
                                                                <option value="Patient" <?= $user['role'] === 'Patient' ? 'selected' : '' ?>>Patient</option>
                                                            </select>
                                                        </div>

                                                        <!-- New Password Field -->
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
                                    <td colspan="7" class="text-center">No users found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
                                <option value="Midwife">Midwife</option>
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
</body>

</html>
