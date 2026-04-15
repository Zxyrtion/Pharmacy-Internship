<?php
require_once '../../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'HR Personnel') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle different actions
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        // Get notifications using PDO
        require_once '../../core/Database.php';
        require_once '../../models/notification.php';
        
        $db = new Database();
        $pdo_conn = $db->getConnection();
        $notification = new Notification($pdo_conn);
        
        $notifications = $notification->getByUserId($user_id, 20);
        $unread_count = $notification->getUnreadCount($user_id);
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unread_count
        ]);
        break;
        
    case 'mark_read':
        require_once '../../core/Database.php';
        require_once '../../models/notification.php';
        
        $db = new Database();
        $pdo_conn = $db->getConnection();
        $notification = new Notification($pdo_conn);
        
        $notification_id = $_POST['notification_id'] ?? null;
        
        if ($notification_id) {
            $result = $notification->markAsRead($notification_id);
            echo json_encode(['success' => $result]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Missing notification_id']);
        }
        break;
        
    case 'mark_all_read':
        require_once '../../core/Database.php';
        require_once '../../models/notification.php';
        
        $db = new Database();
        $pdo_conn = $db->getConnection();
        $notification = new Notification($pdo_conn);
        
        $result = $notification->markAllAsRead($user_id);
        echo json_encode(['success' => $result]);
        break;
        
    case 'count':
        require_once '../../core/Database.php';
        require_once '../../models/notification.php';
        
        $db = new Database();
        $pdo_conn = $db->getConnection();
        $notification = new Notification($pdo_conn);
        
        $count = $notification->getUnreadCount($user_id);
        echo json_encode(['success' => true, 'count' => $count]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
