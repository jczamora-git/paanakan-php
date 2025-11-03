<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $collapsed = isset($_POST['collapsed']) ? $_POST['collapsed'] === 'true' : false;
    $_SESSION['sidebar_collapsed'] = $collapsed;
    
    // Also set a cookie for persistence across sessions
    setcookie('sidebarCollapsed', $collapsed ? 'true' : 'false', time() + (86400 * 30), "/"); // 30 days
    
    echo json_encode(['success' => true]);
    exit;
}
?> 