<?php
require_once 'notification_helper.php';

/**
 * Display notification dropdown for user
 */
function displayNotificationDropdown($userId) {
    $notifications = getUnreadNotifications($userId, 5);
    $unreadCount = getUnreadNotificationCount($userId);
    
    echo '<div class="dropdown me-3">';
    echo '<button class="btn btn-light position-relative dropdown-toggle" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">';
    echo '<i class="bi bi-bell"></i>';
    if ($unreadCount > 0) {
        echo '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">' . $unreadCount . '</span>';
    }
    echo '</button>';
    
    echo '<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown" style="min-width: 300px; max-height: 400px; overflow-y: auto;">';
    
    if (empty($notifications)) {
        echo '<li><span class="dropdown-item text-muted">No new notifications</span></li>';
    } else {
        foreach ($notifications as $notification) {
            $typeClass = '';
            $icon = '';
            
            switch ($notification['type']) {
                case 'success':
                    $typeClass = 'text-success';
                    $icon = 'bi-check-circle-fill';
                    break;
                case 'error':
                    $typeClass = 'text-danger';
                    $icon = 'bi-x-circle-fill';
                    break;
                case 'warning':
                    $typeClass = 'text-warning';
                    $icon = 'bi-exclamation-triangle-fill';
                    break;
                default:
                    $typeClass = 'text-info';
                    $icon = 'bi-info-circle-fill';
            }
            
            echo '<li>';
            echo '<a class="dropdown-item" href="#" onclick="markAsRead(' . $notification['id'] . ')">';
            echo '<div class="d-flex align-items-start">';
            echo '<i class="bi ' . $icon . ' ' . $typeClass . ' me-2 mt-1"></i>';
            echo '<div class="flex-grow-1">';
            echo '<small class="text-muted">' . date('M d, H:i', strtotime($notification['created_at'])) . '</small><br>';
            echo '<span>' . htmlspecialchars($notification['message']) . '</span>';
            echo '</div>';
            echo '</div>';
            echo '</a>';
            echo '</li>';
            echo '<li><hr class="dropdown-divider"></li>';
        }
        
        echo '<li><a class="dropdown-item text-center" href="notifications.php">View all notifications</a></li>';
    }
    
    echo '</ul>';
    echo '</div>';
    
    // Add JavaScript for marking notifications as read
    echo '<script>
    function markAsRead(notificationId) {
        fetch("mark_notification_read.php?id=" + notificationId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => console.error("Error:", error));
        return false;
    }
    </script>';
}
?>
