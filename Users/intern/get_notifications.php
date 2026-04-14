<?php
require_once '../../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
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
    
    case 'get_schedule':
        try {
            require_once '../../controllers/InternshipController.php';
            
            // Use mysqli connection from config.php
            $controller = new InternshipController($conn);
            
            $application_id = $_GET['application_id'] ?? null;
            
            if ($application_id) {
                $schedule = $controller->getScheduleDetails($application_id);
                
                if ($schedule) {
                    echo json_encode([
                        'success' => true,
                        'schedule' => $schedule
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Schedule not found or not yet set'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing application_id'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error loading schedule: ' . $e->getMessage()
            ]);
        }
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
