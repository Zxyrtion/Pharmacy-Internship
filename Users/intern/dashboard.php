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

$user_id   = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$email     = $_SESSION['email'];

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

$list_stmt = $conn->prepare("SELECT id, title, message, link, is_read, created_at
                             FROM notifications
                             WHERE user_id = ?
                             ORDER BY created_at DESC
                             LIMIT 5");
if ($list_stmt) {
    $uid = (int)$user_id;
    $list_stmt->bind_param("i", $uid);
    if ($list_stmt->execute()) {
        $res = $list_stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $latest_notifications[] = $row;
            }
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
            <div class="welcome-header">
                <h1><i class="bi bi-mortarboard"></i> Intern Dashboard</h1>
                <p class="mb-0">Welcome back, <?php echo htmlspecialchars($full_name); ?>! Learn and assist in pharmacy operations.</p>
            </div>
            
            <div class="row">
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
                        <i class="bi bi-graph-up feature-icon"></i>
                        <h4>Progress</h4>
                        <p>Track your learning progress</p>
                        <button class="btn btn-primary">View Progress</button>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h3 class="mb-0"><i class="bi bi-bell"></i> Notifications</h3>
                    <a href="tasks.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-clipboard-check"></i> View Tasks
                        <?php if ($unread_count > 0): ?>
                            <span class="badge bg-danger ms-1"><?php echo (int)$unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>

                <?php if (empty($latest_notifications)): ?>
                    <p class="text-muted mb-0">No notifications yet.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($latest_notifications as $n): ?>
                            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                               href="<?php echo htmlspecialchars($n['link'] ?: 'tasks.php'); ?>">
                                <div class="me-3">
                                    <div class="fw-semibold">
                                        <?php echo htmlspecialchars($n['title']); ?>
                                        <?php if ((int)$n['is_read'] === 0): ?>
                                            <span class="badge bg-danger ms-1">New</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($n['message']); ?></div>
                                </div>
                                <small class="text-muted"><?php echo htmlspecialchars($n['created_at']); ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-card">
                <h3><i class="bi bi-clock-history feature-icon"></i> Recent Activities</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Activity</th>
                                <th>Supervisor</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Medication Dispensing</td>
                                <td>Dr. Smith</td>
                                <td>2024-04-14</td>
                                <td><span class="badge bg-success">Completed</span></td>
                            </tr>
                            <tr>
                                <td>Inventory Management</td>
                                <td>Ms. Johnson</td>
                                <td>2024-04-13</td>
                                <td><span class="badge bg-warning">In Progress</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
