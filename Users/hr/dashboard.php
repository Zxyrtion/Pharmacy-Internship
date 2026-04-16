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
$email = $_SESSION['email'];

// Get notifications
require_once '../../models/notification.php';
require_once '../../core/Database.php';

$database = new Database();
$db = $database->getConnection();
$notification = new Notification($db);

$notifications = $notification->getByUserId($user_id, 10);
$unread_count = $notification->getUnreadCount($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Dashboard - MediCare Pharmacy</title>
    
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
        
        .welcome-header {
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .feature-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: #34495e;
            margin-bottom: 1rem;
        }
        
        .notification-bell {
            position: relative;
            font-size: 1.5rem;
            color: #667eea;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 0.5rem;
            display: inline-block;
            z-index: 1000;
            pointer-events: auto;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            border: 2px solid transparent;
        }
        
        .notification-bell:hover {
            color: #764ba2;
            transform: scale(1.1);
            background: rgba(255, 255, 255, 0.2);
            border-color: #667eea;
        }
        
        .notification-bell:active {
            transform: scale(0.95);
            background: rgba(255, 255, 255, 0.3);
        }
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            width: 400px;
            max-height: 500px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            display: none;
            z-index: 1001;
            overflow: hidden;
        }
        
        .notification-dropdown.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .notification-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-body {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notification-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .notification-item:hover {
            background: #f8f9fa;
        }
        
        .notification-item.unread {
            background: #e8f4fd;
        }
        
        .notification-item.unread:hover {
            background: #d4ebf9;
        }
        
        .notification-empty {
            padding: 3rem 1.5rem;
            text-align: center;
            color: #999;
        }
        
        .notification-wrapper {
            position: relative;
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
            
            <div class="navbar-nav ms-auto align-items-center">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($full_name); ?>
                </span>
                
                <div class="notification-wrapper">
                    <div class="notification-bell" onclick="toggleNotifications(event)">
                        <i class="bi bi-bell"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-badge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Notification Dropdown -->
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <h6 class="mb-0"><i class="bi bi-bell-fill"></i> Notifications</h6>
                            <div>
                                <?php if ($unread_count > 0): ?>
                                    <button onclick="markAllAsRead(event)" class="btn btn-sm btn-light me-2" title="Mark all as read">
                                        <i class="bi bi-check-all"></i>
                                    </button>
                                <?php endif; ?>
                                <a href="notifications.php" class="btn btn-sm btn-light">View All</a>
                            </div>
                        </div>
                        <div class="notification-body">
                            <?php if (empty($notifications)): ?>
                                <div class="notification-empty">
                                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                    <p class="mb-0 mt-2">No notifications yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach (array_slice($notifications, 0, 5) as $n): 
                                    // Determine link based on notification type
                                    $link = 'notifications.php';
                                    if ($n['type'] === 'schedule_accepted') {
                                        $link = 'view_ready_interns.php';
                                    } elseif ($n['type'] === 'schedule_rejected') {
                                        $link = 'edit_work_schedule.php?schedule_id=' . $n['related_id'];
                                    } elseif ($n['type'] === 'task_completed') {
                                        $link = 'view_ready_interns.php';
                                    } elseif ($n['type'] === 'new_application') {
                                        $link = 'internship_applications.php';
                                    }
                                ?>
                                    <a href="<?php echo htmlspecialchars($link); ?>" 
                                       class="notification-item <?php echo $n['is_read'] == 0 ? 'unread' : ''; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold mb-1">
                                                    <?php if ($n['type'] === 'schedule_accepted'): ?>
                                                        <i class="bi bi-check-circle-fill text-success"></i>
                                                    <?php elseif ($n['type'] === 'schedule_rejected'): ?>
                                                        <i class="bi bi-x-circle-fill text-danger"></i>
                                                    <?php elseif ($n['type'] === 'task_completed'): ?>
                                                        <i class="bi bi-clipboard-check-fill text-success"></i>
                                                    <?php elseif ($n['type'] === 'new_application'): ?>
                                                        <i class="bi bi-file-earmark-text-fill text-info"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-info-circle-fill text-primary"></i>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($n['title']); ?>
                                                    <?php if ($n['is_read'] == 0): ?>
                                                        <span class="badge bg-danger ms-1">New</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="small text-muted">
                                                    <?php echo htmlspecialchars($n['message']); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            <i class="bi bi-clock"></i> <?php echo date('M d, Y h:i A', strtotime($n['created_at'])); ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <a href="../logout.php" class="btn btn-logout ms-2">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="container">
            <div class="welcome-header">
                <h1><i class="bi bi-people"></i> HR Dashboard</h1>
                <p class="mb-0">Welcome back, <?php echo htmlspecialchars($full_name); ?>! Manage staff and human resources.</p>
            </div>
            
            <?php
            // Get rejected schedules that need attention
            $rejected_sql = "SELECT ws.*, u.first_name, u.last_name, u.email
                            FROM work_schedules ws
                            JOIN users u ON ws.user_id = u.id
                            WHERE ws.status = 'rejected' AND ws.created_by = ?
                            ORDER BY ws.sent_at DESC";
            $rejected_stmt = $conn->prepare($rejected_sql);
            $rejected_stmt->bind_param("i", $user_id);
            $rejected_stmt->execute();
            $rejected_result = $rejected_stmt->get_result();
            $rejected_schedules = [];
            while ($row = $rejected_result->fetch_assoc()) {
                $rejected_schedules[] = $row;
            }
            ?>
            
            <?php if (!empty($rejected_schedules)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h5 class="alert-heading">
                    <i class="bi bi-exclamation-triangle-fill"></i> Rejected Work Schedules Require Attention
                </h5>
                <p class="mb-3">The following interns have rejected their work schedules. Please review and update them:</p>
                <div class="list-group">
                    <?php foreach ($rejected_schedules as $rs): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($rs['first_name'] . ' ' . $rs['last_name']); ?></strong>
                                <br>
                                <small class="text-muted">
                                    Department: <?php echo htmlspecialchars($rs['department']); ?> | 
                                    Rejected: <?php echo date('M d, Y', strtotime($rs['sent_at'])); ?>
                                </small>
                            </div>
                            <a href="edit_work_schedule.php?schedule_id=<?php echo $rs['id']; ?>" class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil-square"></i> Edit Schedule
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-file-earmark-text feature-icon"></i>
                        <h4>Intern Applicants</h4>
                        <p>Review internship applications</p>
                        <a href="internship_applications.php" class="btn btn-success">Review Applications</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-folder-fill feature-icon"></i>
                        <h4>Pharmacy Policies</h4>
                        <p>Organize policies and guidelines</p>
                        <a href="pharmacy_policies.php" class="btn btn-primary">Manage Documents</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-calendar-check feature-icon"></i>
                        <h4>Interview Schedule</h4>
                        <p>Manage interview batches and schedules</p>
                        <a href="interview_schedule.php" class="btn btn-info">View Schedule</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-clipboard-check feature-icon"></i>
                        <h4>Evaluate Interviews</h4>
                        <p>Rate and evaluate completed interviews</p>
                        <a href="evaluate_interview.php" class="btn btn-success">Evaluate Now</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-person-plus feature-icon"></i>
                        <h4>Employees Task</h4>
                        <p>Manage employee records</p>
                        <a href="employees.php" class="btn btn-primary">View All</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-person-badge feature-icon"></i>
                        <h4>Employee Profiles</h4>
                        <p>View detailed employee profiles and documents</p>
                        <a href="view_ready_interns.php" class="btn btn-primary">View Profiles</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-cash-stack feature-icon"></i>
                        <h4>Payroll</h4>
                        <p>Manage salary and benefits</p>
                        <button class="btn btn-primary">Process</button>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-clipboard-data feature-icon"></i>
                        <h4>Reports</h4>
                        <p>Assign duties and monitor tasks</p>
                        <button class="btn btn-primary" type="button" disabled>Analytics</button>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
function toggleNotifications(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('show');
}

function markAllAsRead(event) {
    event.stopPropagation();
    event.preventDefault();
    
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
            // Remove all unread styling
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
            
            // Remove all "New" badges
            document.querySelectorAll('.notification-item .badge.bg-danger').forEach(badge => {
                badge.remove();
            });
            
            // Remove notification badge from bell icon
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                badge.remove();
            }
            
            // Show success message
            const header = document.querySelector('.notification-header h6');
            const originalText = header.innerHTML;
            header.innerHTML = '<i class="bi bi-check-circle-fill"></i> All marked as read!';
            
            setTimeout(() => {
                header.innerHTML = originalText;
            }, 2000);
        } else {
            alert('Failed to mark notifications as read: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while marking notifications as read');
    });
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('notificationDropdown');
    const bell = document.querySelector('.notification-bell');
    
    if (bell && !bell.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

// Prevent dropdown from closing when clicking inside it
const dropdown = document.getElementById('notificationDropdown');
if (dropdown) {
    dropdown.addEventListener('click', function(event) {
        event.stopPropagation();
    });
}

// Auto-refresh notifications every 30 seconds
setInterval(() => {
    fetch('get_notifications.php?action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update notification count
                const badge = document.querySelector('.notification-badge');
                if (data.unread_count > 0) {
                    if (badge) {
                        badge.textContent = data.unread_count;
                    } else {
                        const bell = document.querySelector('.notification-bell');
                        if (bell) {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'notification-badge';
                            newBadge.textContent = data.unread_count;
                            bell.appendChild(newBadge);
                        }
                    }
                } else if (badge) {
                    badge.remove();
                }
            }
        })
        .catch(error => console.log('Error refreshing notifications:', error));
}, 30000);
    </script>
</body>
</html>
