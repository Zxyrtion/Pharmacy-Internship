<?php
require_once '../../config.php';
require_once '../../notification_helper.php';

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

// Mark notification as read if requested
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notif_id = (int)$_GET['mark_read'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $notif_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: notifications.php");
    exit();
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: notifications.php");
    exit();
}

// Fetch notifications
$notifications = [];
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
}

// Count unread
$unread_count = 0;
foreach ($notifications as $notif) {
    if ($notif['is_read'] == 0) $unread_count++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Pharmacy Technician</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        .notification-item {
            background: white;
            border-left: 4px solid #dee2e6;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .notification-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .notification-item.unread {
            background: #e3f2fd;
            border-left-color: #2196F3;
        }
        .notification-item.info {
            border-left-color: #17a2b8;
        }
        .notification-item.success {
            border-left-color: #28a745;
        }
        .notification-item.error {
            border-left-color: #dc3545;
        }
        .notification-item.warning {
            border-left-color: #ffc107;
        }
        .notification-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
        }
        .notification-time {
            font-size: 0.85rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($full_name); ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-bell"></i> Notifications</h2>
            <?php if ($unread_count > 0): ?>
                <a href="?mark_all_read=1" class="btn btn-sm btn-primary">
                    <i class="bi bi-check-all"></i> Mark All as Read
                </a>
            <?php endif; ?>
        </div>

        <?php if ($unread_count > 0): ?>
            <div class="alert alert-info">
                You have <?= $unread_count ?> unread notification<?= $unread_count > 1 ? 's' : '' ?>
            </div>
        <?php endif; ?>

        <div class="notifications-list">
            <?php if (!empty($notifications)): ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?= $notif['is_read'] == 0 ? 'unread' : '' ?> <?= htmlspecialchars($notif['type']) ?>">
                        <div class="d-flex align-items-start">
                            <div class="notification-icon">
                                <?php if ($notif['type'] === 'info'): ?>
                                    <i class="bi bi-info-circle text-info"></i>
                                <?php elseif ($notif['type'] === 'success'): ?>
                                    <i class="bi bi-check-circle text-success"></i>
                                <?php elseif ($notif['type'] === 'error'): ?>
                                    <i class="bi bi-exclamation-circle text-danger"></i>
                                <?php elseif ($notif['type'] === 'warning'): ?>
                                    <i class="bi bi-exclamation-triangle text-warning"></i>
                                <?php else: ?>
                                    <i class="bi bi-bell"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1"><?= htmlspecialchars($notif['message']) ?></p>
                                <small class="notification-time">
                                    <i class="bi bi-clock"></i> <?= date('M d, Y h:i A', strtotime($notif['created_at'])) ?>
                                </small>
                            </div>
                            <?php if ($notif['is_read'] == 0): ?>
                                <a href="?mark_read=<?= $notif['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-check"></i> Mark Read
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-secondary text-center">
                    <i class="bi bi-inbox"></i> No notifications yet
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
