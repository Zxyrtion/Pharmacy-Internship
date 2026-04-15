<?php
require_once '../../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../views/auth/login.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'HR Personnel') {
    header('Location: ../../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Get notifications
require_once '../../models/notification.php';
require_once '../../core/Database.php';

$database = new Database();
$db = $database->getConnection();
$notification = new Notification($db);

$notifications = $notification->getByUserId($user_id, 50);
$unread_count = $notification->getUnreadCount($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - HR Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .dashboard-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .notification-item {
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s ease;
        }
        
        .notification-item:hover {
            background: #f8f9fa;
        }
        
        .notification-item.unread {
            background: #e8f4fd;
            border-left: 4px solid #667eea;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .btn-logout {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">
                <i class="bi bi-hospital"></i> MediCare Pharmacy
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($full_name); ?>
                </span>
                <a href="../logout.php" class="btn btn-logout">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="container">
            <div class="mb-4">
                <a href="dashboard.php" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3><i class="bi bi-bell"></i> All Notifications 
                        <?php if ($unread_count > 0): ?>
                            <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </h3>
                    <?php if ($unread_count > 0): ?>
                        <button onclick="markAllAsRead()" class="btn btn-primary">
                            <i class="bi bi-check-all"></i> Mark All as Read
                        </button>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($notifications)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem; color: #999;"></i>
                        <h4 class="mt-3 text-muted">No notifications yet</h4>
                        <p class="text-muted">You'll see notifications here when there are updates</p>
                    </div>
                <?php else: ?>
                    <div class="notifications-list">
                        <?php foreach ($notifications as $n): 
                            // Determine link based on notification type
                            $link = '#';
                            if ($n['type'] === 'schedule_accepted') {
                                $link = 'view_ready_interns.php';
                            } elseif ($n['type'] === 'schedule_rejected') {
                                $link = 'edit_work_schedule.php?schedule_id=' . $n['related_id'];
                            } elseif ($n['type'] === 'task_completed') {
                                $link = 'employees.php';
                            }
                        ?>
                            <div class="notification-item <?php echo $n['is_read'] == 0 ? 'unread' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-2">
                                            <?php if ($n['type'] === 'schedule_accepted'): ?>
                                                <i class="bi bi-check-circle-fill text-success me-2" style="font-size: 1.5rem;"></i>
                                            <?php elseif ($n['type'] === 'schedule_rejected'): ?>
                                                <i class="bi bi-x-circle-fill text-danger me-2" style="font-size: 1.5rem;"></i>
                                            <?php elseif ($n['type'] === 'task_completed'): ?>
                                                <i class="bi bi-clipboard-check-fill text-success me-2" style="font-size: 1.5rem;"></i>
                                            <?php else: ?>
                                                <i class="bi bi-info-circle-fill text-primary me-2" style="font-size: 1.5rem;"></i>
                                            <?php endif; ?>
                                            <h5 class="mb-0">
                                                <?php echo htmlspecialchars($n['title']); ?>
                                                <?php if ($n['is_read'] == 0): ?>
                                                    <span class="badge bg-danger ms-2">New</span>
                                                <?php endif; ?>
                                            </h5>
                                        </div>
                                        <p class="mb-2"><?php echo htmlspecialchars($n['message']); ?></p>
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> <?php echo date('F d, Y h:i A', strtotime($n['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="ms-3">
                                        <?php if ($link !== '#'): ?>
                                            <a href="<?php echo htmlspecialchars($link); ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> <?php echo $n['type'] === 'schedule_rejected' ? 'Edit' : 'View'; ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
function markAllAsRead() {
    if (!confirm('Mark all notifications as read?')) {
        return;
    }
    
    fetch('mark_all_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to mark notifications as read: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while marking notifications as read');
    });
}
    </script>
</body>
</html>
