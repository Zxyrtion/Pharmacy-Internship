<?php
require_once '../../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$notification_id = $_POST['notification_id'] ?? null;

if (!$notification_id) {
    echo json_encode(['success' => false, 'error' => 'Missing notification_id']);
    exit();
}

try {
    require_once '../../core/Database.php';
    require_once '../../models/notification.php';
    
    $db = new Database();
    $pdo_conn = $db->getConnection();
    $notification = new Notification($pdo_conn);
    
    $result = $notification->markAsRead($notification_id);
    echo json_encode(['success' => $result]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
