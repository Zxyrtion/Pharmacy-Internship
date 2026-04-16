<?php
require_once '../../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../views/auth/login.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'Intern') {
    header('Location: ../../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];

// Fetch internship application status
$application_status = null;
$application_data = null;
$app_stmt = $conn->prepare("SELECT application_status, interview_scheduled, interview_date, interview_time, 
                                   schedule_sent, is_eligible, created_at
                            FROM internship_records 
                            WHERE user_id = ? 
                            ORDER BY created_at DESC 
                            LIMIT 1");
if ($app_stmt) {
    $app_stmt->bind_param("i", $user_id);
    $app_stmt->execute();
    $app_result = $app_stmt->get_result();
    if ($app_result && $app_result->num_rows > 0) {
        $application_data = $app_result->fetch_assoc();
        $application_status = $application_data['application_status'];
    }
}

$unread_count = 0;
$latest_notifications = [];

$cnt_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0");
if ($cnt_stmt) {
    $uid = (int)$user_id;
    $cnt_stmt->bind_param("i", $uid);
    if ($cnt_stmt->execute()) {
        $res = $cnt_stmt->get_result();
        if ($res) {
            $row = $res->fetch_assoc();
            $unread_count = (int)($row['c'] ?? 0);
        }
    }
}

$list_stmt = $conn->prepare("SELECT id, type, title, message, related_id, is_read, created_at
                             FROM notifications
                             WHERE user_id = ?
                             ORDER BY created_at DESC
                             LIMIT 5");
if ($list_stmt) {
    $uid = (int)$user_id;
    $list_stmt->bind_param("i", $uid);
    $list_stmt->execute();
    $res = $list_stmt->get_result();
    if ($res) {
        $latest_notifications = [];
        while ($row = $res->fetch_assoc()) {
            $latest_notifications[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intern Dashboard - MediCare Pharmacy</title>
    
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
            background: linear-gradient(135deg, #16a085 0%, #27ae60 100%);
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
            color: #16a085;
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
        
        .progress-tracker {
            padding: 1rem 0;
        }
        
        .progress-percentage {
            text-align: right;
        }
        
        .percentage-text {
            font-size: 2rem;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
            line-height: 1;
        }
        
        .progress-step {
            text-align: center;
            position: relative;
            opacity: 0.4;
            transition: all 0.3s ease;
        }
        
        .progress-step.active {
            opacity: 1;
        }
        
        .progress-step.current .step-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .step-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-size: 1.5rem;
            color: white;
            transition: all 0.3s ease;
        }
        
        .progress-step.active .step-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .step-label {
            font-size: 0.9rem;
            font-weight: 500;
            margin-top: 0.5rem;
        }
        
        .step-status {
            margin-top: 0.5rem;
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
                            <span class="notification-badge"><?php echo (int)$unread_count; ?></span>
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
                                <a href="tasks.php" class="btn btn-sm btn-light">View All</a>
                            </div>
                        </div>
                        <div class="notification-body">
                            <?php if (empty($latest_notifications)): ?>
                                <div class="notification-empty">
                                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                    <p class="mb-0 mt-2">No notifications yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($latest_notifications as $n): 
                                    // Determine link based on notification type
                                    $link = 'tasks.php';
                                    if ($n['type'] === 'interview_scheduled') {
                                        $link = 'view_interview.php';
                                    } elseif ($n['type'] === 'work_schedule_assigned' || $n['type'] === 'work_schedule_updated') {
                                        $link = 'work_schedule.php';
                                    } elseif ($n['type'] === 'internship_schedule') {
                                        $link = 'view_schedule.php';
                                    } elseif ($n['type'] === 'task_assigned') {
                                        $link = 'tasks.php';
                                    }
                                ?>
                                    <a href="<?php echo htmlspecialchars($link); ?>" 
                                       class="notification-item <?php echo (int)$n['is_read'] === 0 ? 'unread' : ''; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold mb-1">
                                                    <?php echo htmlspecialchars($n['title']); ?>
                                                    <?php if ((int)$n['is_read'] === 0): ?>
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
                <h1><i class="bi bi-mortarboard"></i> Intern Dashboard</h1>
                <p class="mb-0">Welcome back, <?php echo htmlspecialchars($full_name); ?>! Learn and assist in pharmacy operations.</p>
            </div>
            
            <?php if ($application_data): ?>
            <!-- Application Progress Tracker -->
            <div class="dashboard-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-0"><i class="bi bi-clipboard-check"></i> Application Progress</h3>
                    <?php
                    $status = $application_status;
                    $status_order = ['pending', 'submitted', 'under_review', 'approved'];
                    $current_index = array_search($status, $status_order);
                    if ($current_index === false) $current_index = 0;
                    $percentage = (($current_index + 1) / count($status_order)) * 100;
                    ?>
                    <div class="progress-percentage">
                        <span class="percentage-text"><?php echo round($percentage); ?>%</span>
                        <span class="text-muted small">Complete</span>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div class="progress mb-4" style="height: 8px; border-radius: 10px;">
                    <div class="progress-bar" role="progressbar" 
                         style="width: <?php echo $percentage; ?>%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"
                         aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
                
                <div class="progress-tracker">
                    <?php
                    $steps = [
                        'pending' => ['label' => 'Application Submitted', 'icon' => 'bi-file-earmark-text', 'color' => 'secondary'],
                        'submitted' => ['label' => 'Under Review', 'icon' => 'bi-hourglass-split', 'color' => 'info'],
                        'under_review' => ['label' => 'Documents Reviewed', 'icon' => 'bi-search', 'color' => 'warning'],
                        'approved' => ['label' => 'Approved', 'icon' => 'bi-check-circle', 'color' => 'success'],
                        'rejected' => ['label' => 'Rejected', 'icon' => 'bi-x-circle', 'color' => 'danger']
                    ];
                    ?>
                    
                    <div class="row">
                        <?php foreach ($status_order as $index => $step_key): 
                            $step = $steps[$step_key];
                            $is_active = $index <= $current_index;
                            $is_current = $step_key === $status;
                        ?>
                        <div class="col-md-3 mb-3">
                            <div class="progress-step <?php echo $is_active ? 'active' : ''; ?> <?php echo $is_current ? 'current' : ''; ?>">
                                <div class="step-icon">
                                    <i class="bi <?php echo $step['icon']; ?>"></i>
                                </div>
                                <div class="step-label"><?php echo $step['label']; ?></div>
                                <?php if ($is_current): ?>
                                    <div class="step-status">
                                        <span class="badge bg-<?php echo $step['color']; ?>">Current Status</span>
                                    </div>
                                <?php elseif ($is_active): ?>
                                    <div class="step-status">
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($status === 'rejected'): ?>
                        <div class="alert alert-danger mt-3">
                            <i class="bi bi-exclamation-triangle"></i> Your application has been rejected. Please contact HR for more information.
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($application_data['interview_scheduled']): ?>
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-calendar-event"></i> <strong>Interview Scheduled:</strong> 
                            <?php echo date('F d, Y', strtotime($application_data['interview_date'])); ?> 
                            at <?php echo date('h:i A', strtotime($application_data['interview_time'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($application_data['schedule_sent']): ?>
                        <div class="alert alert-success mt-3">
                            <i class="bi bi-check-circle"></i> Your work schedule has been sent! Check your notifications.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-file-earmark-text feature-icon"></i>
                        <h4>Apply Internship</h4>
                        <p>Submit your internship application</p>
                        <a href="apply_internship.php" class="btn btn-success">Apply Now</a>
                    </div>

                </div>

                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-calendar-event feature-icon"></i>
                        <h4>Interview Schedule</h4>
                        <p>View your interview details</p>
                        <a href="view_interview.php" class="btn btn-primary">View Details</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-folder-fill feature-icon"></i>
                        <h4>Policies & Guidelines</h4>
                        <p>View pharmacy policies</p>
                        <a href="policies_guidelines.php" class="btn btn-primary">View Policies</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-book feature-icon"></i>
                        <h4>Learning Modules</h4>
                        <p>Complete training modules</p>
                        <button class="btn btn-primary">Start Learning</button>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-person-plus feature-icon"></i>
                        <h4>Shadowing</h4>
                        <p>Learn from experienced staff</p>
                        <button class="btn btn-primary">View Schedule</button>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-clipboard-check feature-icon"></i>
                        <h4>Tasks</h4>
                        <p>Complete assigned tasks</p>
                        <a class="btn btn-primary" href="tasks.php">View Tasks</a>
                    </div>
                </div>
        
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-file-earmark-text feature-icon"></i>
                        <h4>Work Schedule</h4>
                        <p>View your work schedule</p>
                        <a href="work_schedule.php" class="btn btn-success">View Schedule</a>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-graph-up feature-icon"></i>
                        <h4>Progress</h4>
                        <p>Track your learning progress</p>
                        <button class="btn btn-primary">View Progress</button>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-truck feature-icon"></i>
                        <h4>Suppliers</h4>
                        <p>Manage supplier information</p>
                        <a href="supplier_management.php" class="btn btn-primary">View Suppliers</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-clock-history feature-icon"></i>
                        <h4>Stock Movements</h4>
                        <p>Track inventory changes</p>
                        <a href="inventory_movements.php" class="btn btn-primary">View History</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-list-check feature-icon"></i>
                        <h4>Product Inventory</h4>
                        <p>Simple product inventory</p>
                        <a href="product_inventory.php" class="btn btn-primary">View Products</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-file-earmark-bar-graph feature-icon"></i>
                        <h4>Inventory Report</h4>
                        <p>Generate inventory reports</p>
                        <a href="inventory_report.php" class="btn btn-primary">Create Report</a>
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
    
    if (!bell.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

// Prevent dropdown from closing when clicking inside it
document.getElementById('notificationDropdown').addEventListener('click', function(event) {
    event.stopPropagation();
});

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
                        const newBadge = document.createElement('span');
                        newBadge.className = 'notification-badge';
                        newBadge.textContent = data.unread_count;
                        bell.appendChild(newBadge);
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
