<?php
require_once '../../config.php';

if (!isLoggedIn()) {
    header('Location: ../../views/auth/login.php');
    exit();
}

if ($_SESSION['role_name'] !== 'Intern') {
    header('Location: ../../index.php');
    exit();
}

$full_name = $_SESSION['full_name'];
$report_id = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;

// Ensure notifications table exists (in case intern visits first)
$conn->query("CREATE TABLE IF NOT EXISTS `notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(500) DEFAULT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notifications_user_id` (`user_id`),
    KEY `idx_notifications_is_read` (`is_read`),
    CONSTRAINT `fk_notifications_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// If report_id is provided, mark matching notification(s) as read
if ($report_id > 0) {
    $link = BASE_URL . 'Users/intern/reports.php?report_id=' . $report_id;
    $mark_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND link = ?");
    if ($mark_stmt) {
        $uid = (int)$_SESSION['user_id'];
        $mark_stmt->bind_param("is", $uid, $link);
        $mark_stmt->execute();
    }
}

// Load reports (latest 20; if report_id provided, show it first)
$reports = [];
if ($report_id > 0) {
    $one_stmt = $conn->prepare("SELECT id, report_type, title, date_from, date_to, notes, file_original_name, file_stored_name, created_at
                                FROM internship_routine
                                WHERE id = ?
                                LIMIT 1");
    if ($one_stmt) {
        $one_stmt->bind_param("i", $report_id);
        if ($one_stmt->execute()) {
            $res = $one_stmt->get_result();
            if ($res && $res->num_rows === 1) {
                $reports[] = $res->fetch_assoc();
            }
        }
    }
}

$list_stmt = $conn->prepare("SELECT id, report_type, title, date_from, date_to, notes, file_original_name, file_stored_name, created_at
                             FROM internship_routine
                             ORDER BY created_at DESC
                             LIMIT 20");
if ($list_stmt && $list_stmt->execute()) {
    $res = $list_stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if ($report_id > 0 && (int)$row['id'] === $report_id) {
                continue;
            }
            $reports[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intern Reports - MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../style.css">
    <style>
        .page-wrap {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        .panel {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        .page-header {
            background: linear-gradient(135deg, #16a085 0%, #27ae60 100%);
            color: white;
            padding: 1.75rem 2rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
        }
        .btn-logout {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
        }
        .btn-logout:hover { background: #c0392b; color: white; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">
                <i class="bi bi-hospital"></i> MediCare Pharmacy
            </a>
            <div class="navbar-nav ms-auto align-items-lg-center gap-2">
                <span class="navbar-text me-lg-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($full_name); ?>
                </span>
                <a href="../logout.php" class="btn btn-logout btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="page-wrap">
        <div class="container">
            <div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h1 class="h3 mb-1"><i class="bi bi-clipboard-data"></i> HR Reports</h1>
                    <p class="mb-0 opacity-75">New reports created by HR will appear here.</p>
                </div>
                <a href="dashboard.php" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Back to Intern dashboard
                </a>
            </div>

            <div class="panel">
                <?php if (empty($reports)): ?>
                    <p class="text-muted mb-0">No reports found yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Period</th>
                                    <th>Attachment</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $r): ?>
                                    <?php $highlight = ($report_id > 0 && (int)$r['id'] === $report_id); ?>
                                    <tr class="<?php echo $highlight ? 'table-success' : ''; ?>">
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($r['title']); ?></div>
                                            <?php if (!empty($r['notes'])): ?>
                                                <div class="small text-muted"><?php echo nl2br(htmlspecialchars($r['notes'])); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($r['report_type']); ?></td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($r['date_from']); ?> → <?php echo htmlspecialchars($r['date_to']); ?></small></td>
                                        <td>
                                            <?php if (!empty($r['file_stored_name'])): ?>
                                                <a href="<?php echo htmlspecialchars(BASE_URL . 'uploads/hr_reports/' . rawurlencode($r['file_stored_name'])); ?>" target="_blank" rel="noopener">
                                                    <i class="bi bi-paperclip"></i> <?php echo htmlspecialchars($r['file_original_name'] ?: $r['file_stored_name']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($r['created_at']); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

