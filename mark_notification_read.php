<?php
require_once 'config.php';
require_once 'notification_helper.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$notificationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user_id'];

if ($notificationId > 0) {
    $result = markNotificationAsRead($notificationId, $userId);
    echo json_encode(['success' => $result]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
}
?>
