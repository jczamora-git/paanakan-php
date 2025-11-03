<?php
session_start();
require_once '../connections/connections.php';
require_once '../activity_log.php';

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    $pdo = connection();
    $activityLog = new ActivityLog($pdo);

    // Get user's full name for logging
    $userQuery = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS user_name FROM users WHERE user_id = :user_id");
    $userQuery->execute([':user_id' => $_SESSION['user_id']]);
    $userRow = $userQuery->fetch();
    $user_name = $userRow ? $userRow['user_name'] : 'Unknown User';

    // Validate required fields
    $required_fields = ['first_name', 'last_name', 'role', 'email', 'status', 'attendance_status'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Please fill in all required fields");
        }
    }

    // Validate email format
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Check if staff_id exists (for update) or not (for insert)
    $staff_id = !empty($_POST['staff_id']) ? $_POST['staff_id'] : null;

    if ($staff_id) {
        // Update existing staff
        // First check if email is unique (excluding current staff)
        $checkEmail = $pdo->prepare("SELECT staff_id FROM staff WHERE email = ? AND staff_id != ?");
        $checkEmail->execute([$_POST['email'], $staff_id]);
        if ($checkEmail->rowCount() > 0) {
            throw new Exception("Email already exists");
        }

        // Get old staff details for comparison
        $oldStaffQuery = $pdo->prepare("SELECT first_name, last_name, role, email, contact_number, date_hired, status, attendance_status FROM staff WHERE staff_id = ?");
        $oldStaffQuery->execute([$staff_id]);
        $oldStaff = $oldStaffQuery->fetch(PDO::FETCH_ASSOC);

        // Update staff
        $stmt = $pdo->prepare("UPDATE staff SET first_name = ?, last_name = ?, role = ?, email = ?, contact_number = ?, date_hired = ?, status = ?, attendance_status = ? WHERE staff_id = ?");
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['role'],
            $_POST['email'],
            $_POST['contact_number'],
            $_POST['date_hired'],
            $_POST['status'],
            $_POST['attendance_status'],
            $staff_id
        ]);

        // Build changes log
        $changes = [];
        if ($oldStaff['first_name'] !== $_POST['first_name']) $changes[] = "first name from '{$oldStaff['first_name']}' to '{$_POST['first_name']}'";
        if ($oldStaff['last_name'] !== $_POST['last_name']) $changes[] = "last name from '{$oldStaff['last_name']}' to '{$_POST['last_name']}'";
        if ($oldStaff['role'] !== $_POST['role']) $changes[] = "role from '{$oldStaff['role']}' to '{$_POST['role']}'";
        if ($oldStaff['email'] !== $_POST['email']) $changes[] = "email from '{$oldStaff['email']}' to '{$_POST['email']}'";
        if ($oldStaff['contact_number'] !== $_POST['contact_number']) {
            $oldContact = $oldStaff['contact_number'] ?: 'N/A';
            $newContact = $_POST['contact_number'] ?: 'N/A';
            $changes[] = "contact number from '$oldContact' to '$newContact'";
        }
        if ($oldStaff['date_hired'] !== $_POST['date_hired']) {
            $oldDate = $oldStaff['date_hired'] ?: 'N/A';
            $newDate = $_POST['date_hired'] ?: 'N/A';
            $changes[] = "date hired from '$oldDate' to '$newDate'";
        }
        if ($oldStaff['status'] !== $_POST['status']) $changes[] = "status from '{$oldStaff['status']}' to '{$_POST['status']}'";
        if ($oldStaff['attendance_status'] !== $_POST['attendance_status']) $changes[] = "attendance status from '{$oldStaff['attendance_status']}' to '{$_POST['attendance_status']}'";

        // Log the activity with changes
        if (!empty($changes)) {
            $action_desc = $user_name . " updated staff member " . $_POST['first_name'] . " " . $_POST['last_name'] . ": " . implode(", ", $changes);
            $activityLog->logActivity($_SESSION['user_id'], $action_desc);
        }

        $response['success'] = true;
        $response['message'] = "Staff member updated successfully";
    } else {
        // Insert new staff
        // Check if email already exists
        $checkEmail = $pdo->prepare("SELECT staff_id FROM staff WHERE email = ?");
        $checkEmail->execute([$_POST['email']]);
        if ($checkEmail->rowCount() > 0) {
            throw new Exception("Email already exists");
        }

        $stmt = $pdo->prepare("INSERT INTO staff (first_name, last_name, role, email, contact_number, date_hired, status, attendance_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['role'],
            $_POST['email'],
            $_POST['contact_number'],
            $_POST['date_hired'],
            $_POST['status'],
            $_POST['attendance_status']
        ]);

        // Log the activity
        $action_desc = $user_name . " added new staff member: " . $_POST['first_name'] . " " . $_POST['last_name'] . " (Role: " . $_POST['role'] . ")";
        $activityLog->logActivity($_SESSION['user_id'], $action_desc);

        $response['success'] = true;
        $response['message'] = "New staff member added successfully";
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 