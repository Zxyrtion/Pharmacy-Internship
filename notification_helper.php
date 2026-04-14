<?php
require_once 'config.php';

/**
 * Create a notification for a user
 */
function createNotification($userId, $message, $type = 'info', $relatedType = '', $relatedId = 0) {
    global $conn;
    
    if (!isset($conn)) return false;
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, related_type, related_id) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isssi", $userId, $message, $type, $relatedType, $relatedId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

/**
 * Get unread notifications for a user
 */
function getUnreadNotifications($userId, $limit = 10) {
    global $conn;
    
    if (!isset($conn)) return [];
    
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = FALSE ORDER BY created_at DESC LIMIT ?");
    if ($stmt) {
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();
        return $notifications;
    }
    return [];
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notificationId, $userId) {
    global $conn;
    
    if (!isset($conn)) return false;
    
    $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $notificationId, $userId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

/**
 * Get user ID by name
 */
function getUserIdByName($fullName) {
    global $conn;
    
    if (!isset($conn)) return null;
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE CONCAT(first_name, ' ', last_name) = ?");
    if ($stmt) {
        $stmt->bind_param("s", $fullName);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['id'] ?? null;
    }
    return null;
}

/**
 * Get unread notification count for a user
 */
function getUnreadNotificationCount($userId) {
    global $conn;
    
    if (!isset($conn)) return 0;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['count'] ?? 0;
    }
    return 0;
}
?>
