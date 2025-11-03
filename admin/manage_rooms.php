<?php
// Start session and include database connection
session_start();
require '../connections/connections.php';

// Check if the user is logged in as Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Get the database connection
$pdo = connection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Get user details for logging
        $userId = $_SESSION['user_id'];
        $userQuery = "SELECT first_name, last_name FROM users WHERE user_id = :user_id";
        $userStmt = $pdo->prepare($userQuery);
        $userStmt->execute([':user_id' => $userId]);
        $userDetails = $userStmt->fetch(PDO::FETCH_ASSOC);
        $userFullName = $userDetails['first_name'] . ' ' . $userDetails['last_name'];

        switch ($_POST['action']) {
            case 'add':
                $room_number = $_POST['room_number'];
                $status = $_POST['status'];
                
                $stmt = $pdo->prepare("INSERT INTO rooms (room_number, status) VALUES (?, ?)");
                $stmt->execute([$room_number, $status]);

                // Log the activity
                $logQuery = "INSERT INTO activity_log (user_id, action, timestamp) VALUES (:user_id, :action, NOW())";
                $logStmt = $pdo->prepare($logQuery);
                $logStmt->execute([
                    ':user_id' => $userId,
                    ':action' => "$userFullName added new room: Room $room_number (Status: $status)"
                ]);

                $_SESSION['message'] = "Room added successfully!";
                break;

            case 'edit':
                $room_id = $_POST['room_id'];
                $room_number = $_POST['room_number'];
                $status = $_POST['status'];
                $case_id = $_POST['case_id'] ?: null;

                // Get old room details for comparison
                $oldRoomQuery = "SELECT room_number, status, case_id FROM rooms WHERE room_id = :room_id";
                $oldRoomStmt = $pdo->prepare($oldRoomQuery);
                $oldRoomStmt->execute([':room_id' => $room_id]);
                $oldRoom = $oldRoomStmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $pdo->prepare("UPDATE rooms SET room_number = ?, status = ?, case_id = ? WHERE room_id = ?");
                $stmt->execute([$room_number, $status, $case_id, $room_id]);

                // Build changes log
                $changes = [];
                if ($oldRoom['room_number'] !== $room_number) {
                    $changes[] = "room number from '{$oldRoom['room_number']}' to '$room_number'";
                }
                if ($oldRoom['status'] !== $status) {
                    $changes[] = "status from '{$oldRoom['status']}' to '$status'";
                }
                if ($oldRoom['case_id'] !== $case_id) {
                    $oldCaseId = $oldRoom['case_id'] ?: 'N/A';
                    $newCaseId = $case_id ?: 'N/A';
                    $changes[] = "case ID from '$oldCaseId' to '$newCaseId'";
                }

                // Log the activity with changes
                if (!empty($changes)) {
                    $logQuery = "INSERT INTO activity_log (user_id, action, timestamp) VALUES (:user_id, :action, NOW())";
                    $logStmt = $pdo->prepare($logQuery);
                    $logStmt->execute([
                        ':user_id' => $userId,
                        ':action' => "$userFullName updated Room $room_number: " . implode(", ", $changes)
                    ]);
                }

                $_SESSION['message'] = "Room updated successfully!";
                break;

            case 'delete':
                $room_id = $_POST['room_id'];

                // Get room details before deletion for logging
                $roomQuery = "SELECT room_number, status, case_id FROM rooms WHERE room_id = :room_id";
                $roomStmt = $pdo->prepare($roomQuery);
                $roomStmt->execute([':room_id' => $room_id]);
                $roomDetails = $roomStmt->fetch(PDO::FETCH_ASSOC);
                // Instead of deleting the room, mark it as 'Under Maintenance' and clear case assignment
                $stmt = $pdo->prepare("UPDATE rooms SET status = 'Under Maintenance', case_id = NULL WHERE room_id = ?");
                $stmt->execute([$room_id]);

                // Log the activity for marking under maintenance
                $logQuery = "INSERT INTO activity_log (user_id, action, timestamp) VALUES (:user_id, :action, NOW())";
                $logStmt = $pdo->prepare($logQuery);
                $logStmt->execute([
                    ':user_id' => $userId,
                    ':action' => "$userFullName marked Room {$roomDetails['room_number']} as Under Maintenance" . 
                                ($roomDetails['case_id'] ? " (previous Case ID: {$roomDetails['case_id']})" : "")
                ]);

                $_SESSION['message'] = "Room marked as Under Maintenance.";
                break;
        }
        header("Location: manage_rooms.php");
        exit();
    }
}

// Fetch all rooms
$stmt = $pdo->query("SELECT * FROM rooms ORDER BY room_number");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Toast Alert Component -->
    <link rel="stylesheet" href="../css/toast-alert.css">
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
        .status-available {
            background-color: #d4edda;
            color: #155724;
        }
        .status-occupied {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-maintenance {
            background-color: #fff3cd;
            color: #856404;
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
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Global Notification Dropdown Component -->
        <?php include '../components/notification-dropdown.php'; ?>
        
        <?php include '../sidebar.php'; ?>
        <main class="dashboard-main-content">
            <?php include 'breadcrumb.php'; ?>
            <!-- Toast Alert Trigger -->
            <?php if (isset($_SESSION['message'])): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Toast.success('<?= htmlspecialchars(addslashes($_SESSION['message'])) ?>');
                    });
                </script>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h2 class="mb-0">
                            <i class="fas fa-door-open me-2"></i>Room Management
                        </h2>
                        <p class="mb-0 mt-2">
                            <i class="fas fa-hotel me-2"></i>Total Rooms: <?= count($rooms) ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-end">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                            <i class="fas fa-plus me-2"></i>Add New Room
                        </button>
                    </div>
                </div>
            </div>
            <!-- Main Table -->
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Room Number</th>
                            <th>Status</th>
                            <th>Case ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rooms)): ?>
                            <?php foreach ($rooms as $index => $room): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($room['room_number']) ?></td>
                                    <td>
                                        <?php
                                            $status = htmlspecialchars($room['status']);
                                            $badgeClass = '';
                                            if ($status === 'Available') {
                                                $badgeClass = 'status-badge status-available';
                                            } elseif ($status === 'Occupied') {
                                                $badgeClass = 'status-badge status-occupied';
                                            } else {
                                                $badgeClass = 'status-badge status-maintenance';
                                            }
                                        ?>
                                        <span class="<?= $badgeClass ?>"><?= $status ?></span>
                                    </td>
                                    <td><?= $room['case_id'] ? htmlspecialchars($room['case_id']) : 'N/A' ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-info btn-action" data-bs-toggle="modal" data-bs-target="#editRoomModal<?= $room['room_id'] ?>" title="Edit Room">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-action" data-bs-toggle="modal" data-bs-target="#deleteRoomModal<?= $room['room_id'] ?>" title="Delete Room">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Edit Room Modal -->
                                <div class="modal fade" id="editRoomModal<?= $room['room_id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Room</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="" method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="edit">
                                                    <input type="hidden" name="room_id" value="<?= $room['room_id'] ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Room Number</label>
                                                        <input type="text" class="form-control" name="room_number" value="<?= htmlspecialchars($room['room_number']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Status</label>
                                                        <select class="form-select" name="status" required>
                                                            <option value="Available" <?= $room['status'] === 'Available' ? 'selected' : '' ?>>Available</option>
                                                            <option value="Occupied" <?= $room['status'] === 'Occupied' ? 'selected' : '' ?>>Occupied</option>
                                                            <option value="Under Maintenance" <?= $room['status'] === 'Under Maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Case ID (if occupied)</label>
                                                        <input type="text" class="form-control" name="case_id" value="<?= htmlspecialchars($room['case_id'] ?? '') ?>">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <!-- Delete Room Modal -->
                                <div class="modal fade" id="deleteRoomModal<?= $room['room_id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Delete Room</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to delete Room <?= htmlspecialchars($room['room_number']) ?>?</p>
                                                <p class="text-danger">This action cannot be undone.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <form action="" method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="room_id" value="<?= $room['room_id'] ?>">
                                                    <button type="submit" class="btn btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-info-circle me-2"></i>No rooms found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <!-- Add Room Modal -->
    <div class="modal fade" id="addRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Room Number</label>
                            <input type="text" class="form-control" name="room_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="Available">Available</option>
                                <option value="Occupied">Occupied</option>
                                <option value="Under Maintenance">Under Maintenance</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Toast Alert Component -->
    <script src="../js/toast-alert.js"></script>
    <script src="../js/toast-integration.js"></script>
</body>
</html> 