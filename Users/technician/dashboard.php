<?php
require_once '../../config.php';
require_once '../../models/purchase_order.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../views/auth/login.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'Pharmacy Technician') {
    header('Location: /Pharmacy-Internship/index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];

// Get unread notifications count and latest notifications
$unread_count = 0;
$latest_notifications = [];

$cnt_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0");
if ($cnt_stmt) {
    $cnt_stmt->bind_param("i", $user_id);
    if ($cnt_stmt->execute()) {
        $res = $cnt_stmt->get_result();
        if ($res) {
            $row = $res->fetch_assoc();
            $unread_count = (int)($row['c'] ?? 0);
        }
    }
    $cnt_stmt->close();
}

// Fetch latest 5 notifications
$notif_stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
if ($notif_stmt) {
    $notif_stmt->bind_param("i", $user_id);
    $notif_stmt->execute();
    $notif_result = $notif_stmt->get_result();
    while ($row = $notif_result->fetch_assoc()) {
        $latest_notifications[] = $row;
    }
    $notif_stmt->close();
}

// Get requisition stats
$purchaseOrder = new PurchaseOrder($conn);
$req_stats = $purchaseOrder->getUserRequisitionStats($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Technician Dashboard - MediCare Pharmacy</title>
    
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
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
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
            color: #9b59b6;
            margin-bottom: 1rem;
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
        
        .stat-box {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 0.5rem 0;
        }
        
        .stat-box h2 {
            margin: 0;
            font-weight: bold;
        }
        
        .stat-box p {
            margin: 0.5rem 0 0 0;
        }
        
        .notification-bell {
            position: relative;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 1.3rem;
            color: #667eea;
        }
        
        .notification-bell:hover {
            background: rgba(102, 126, 234, 0.1);
            transform: scale(1.1);
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
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
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
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="/Pharmacy-Internship/index.php">
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
                                <a href="notifications.php" class="btn btn-sm btn-light">View All</a>
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
                                    // Determine link based on notification related_type
                                    $link = 'notifications.php';
                                    if (isset($n['related_type']) && $n['related_type'] === 'inventory_report') {
                                        $link = 'review_reports.php';
                                    }
                                ?>
                                    <a href="<?php echo htmlspecialchars($link); ?>" 
                                       class="notification-item <?php echo (int)$n['is_read'] === 0 ? 'unread' : ''; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold mb-1">
                                                    <?php 
                                                    $icon = '';
                                                    if ($n['type'] === 'info') $icon = '<i class="bi bi-info-circle text-info"></i>';
                                                    elseif ($n['type'] === 'success') $icon = '<i class="bi bi-check-circle text-success"></i>';
                                                    elseif ($n['type'] === 'error') $icon = '<i class="bi bi-exclamation-circle text-danger"></i>';
                                                    elseif ($n['type'] === 'warning') $icon = '<i class="bi bi-exclamation-triangle text-warning"></i>';
                                                    else $icon = '<i class="bi bi-bell"></i>';
                                                    echo $icon;
                                                    ?>
                                                    Notification
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
                <h1><i class="bi bi-gear"></i> Pharmacy Technician Dashboard</h1>
                <p class="mb-0">Welcome back, <?php echo htmlspecialchars($full_name); ?>! Prepare medications and manage inventory.</p>
            </div>
            
            <!-- Requisition Stats -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="dashboard-card">
                        <h3><i class="bi bi-graph-up"></i> My Requisition Statistics</h3>
                        <div class="row text-center mt-3">
                            <div class="col-md-2">
                                <div class="stat-box">
                                    <h2 class="text-primary"><?php echo $req_stats['total']; ?></h2>
                                    <p class="text-muted">Total</p>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-box">
                                    <h2 class="text-secondary"><?php echo $req_stats['submitted']; ?></h2>
                                    <p class="text-muted">Submitted</p>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-box">
                                    <h2 class="text-success"><?php echo $req_stats['approved']; ?></h2>
                                    <p class="text-muted">Approved</p>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-box">
                                    <h2 class="text-danger"><?php echo $req_stats['rejected']; ?></h2>
                                    <p class="text-muted">Rejected</p>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-box">
                                    <h2 class="text-info"><?php echo $req_stats['processed']; ?></h2>
                                    <p class="text-muted">Processed</p>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <a href="my_requisitions.php" class="btn btn-primary mt-3">
                                    <i class="bi bi-eye"></i> View All
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Process 11: Check inventory report -->
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-capsule feature-icon"></i>
                        <h4>Medicine Prep</h4>
                        <p>Prepare prescription medications</p>
                        <button class="btn btn-primary">View Queue</button>
                    </div>
                </div>

                <!-- Process 12: Request additional stocks -->
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-box-seam feature-icon"></i>
                        <h4>Request Stocks</h4>
                        <p>Request additional stock supplies</p>
                        <a href="create_requisition.php" class="btn btn-primary">Request</a>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-file-text feature-icon"></i>
                        <h4>My Requisitions</h4>
                        <p>View submitted requisitions</p>
                        <a href="my_requisitions.php" class="btn btn-primary">View</a>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-graph-up feature-icon"></i>
                        <h4>Inventory Reports</h4>
                        <p>View inventory reports</p>
                        <a href="review_reports.php" class="btn btn-primary">View</a>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-card">
                <h3><i class="bi bi-clock-history"></i> Medication Preparation Queue</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Patient</th>
                                <th>Medication</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#MED001</td>
                                <td>Juan Dela Cruz</td>
                                <td>Paracetamol 500mg</td>
                                <td><span class="badge bg-danger">High</span></td>
                                <td><span class="badge bg-warning">Preparing</span></td>
                                <td><button class="btn btn-sm btn-success">Complete</button></td>
                            </tr>
                            <tr>
                                <td>#MED002</td>
                                <td>Maria Santos</td>
                                <td>Amoxicillin 250mg</td>
                                <td><span class="badge bg-warning">Medium</span></td>
                                <td><span class="badge bg-info">Pending</span></td>
                                <td><button class="btn btn-sm btn-primary">Start</button></td>
                            </tr>
                        </tbody>
                    </table>
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
        
        if (dropdown && !dropdown.contains(event.target) && !bell.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });
    </script>
</body>
</html>
