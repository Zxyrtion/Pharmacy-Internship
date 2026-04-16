<?php
require_once 'config.php';
require_once 'notification_helper.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Mark all notifications as read for this user
$stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $success = $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
