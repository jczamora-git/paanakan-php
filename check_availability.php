<?php
require 'connections/connections.php';
$con = connection();
$con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$response = ['status' => 'available', 'field' => ''];

if (isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $stmt = $con->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $response['status'] = 'taken';
        $response['field'] = 'username';
    }
}

if (isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $stmt = $con->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $response['status'] = 'taken';
        $response['field'] = 'email';
    }
}

echo json_encode($response);
exit();
?>
