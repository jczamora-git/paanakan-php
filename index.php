<?php

echo "Hello World"."<br/>";
include_once("connections/connections.php");
$con = connection();
$con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
try {
    // Use the PDO connection to query the database
    $stmt = $con->query("SELECT * FROM users");

    // Fetch and display results
    while ($row = $stmt->fetch()) {
        echo $row['username']."<br/>";
    }
} catch (PDOException $e) {
    echo "Query failed: " . $e->getMessage();
}
$username = "admin";
$sql = "SELECT action,timestamp FROM user_logs where username=?";
$stmt = $con->prepare($sql);
$stmt->execute([$username]);
$users = $stmt->fetchAll();
foreach ($users as $user) {
    echo $user['action'] ."".$user['timestamp']."<br/>";
}
?>