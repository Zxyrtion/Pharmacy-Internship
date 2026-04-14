<?php
require_once '../../config.php';

if (!isLoggedIn()) {
    header('Location: ../../views/auth/login.php');
    exit();
}

if (($_SESSION['role_name'] ?? '') !== 'Intern') {
    header('Location: ../../index.php');
    exit();
}

$user_id   = (int)($_SESSION['user_id'] ?? 0);
$full_name = $_SESSION['full_name'] ?? 'Intern';

$routine_table = 'internship_routine';

// Update task status (Intern can only update their own tasks)
$flash_success = '';
$flash_error   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id    = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
    $new_status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $allowed_status = ['pending', 'finished'];

    if ($task_id <= 0 || !in_array($new_status, $allowed_status, true)) {
        $flash_error = 'Invalid status update.';
    } else {
        $upd = $conn->prepare("UPDATE `{$routine_table}` SET status = ? WHERE id = ? AND assigned_to = ?");
        if ($upd) {
            $upd->bind_param("sii", $new_status, $task_id, $user_id);
            if ($upd->execute() && $upd->affected_rows >= 0) {
                $flash_success = 'Task status updated.';
                header('Location: tasks.php');
                exit();
            } else {
                $flash_error = 'Could not update task status.';
            }
        } else {
            $flash_error = 'Database error.';
        }
    }
}

// Mark task notifications as read when intern opens this page
$tasks_link = BASE_URL . 'Users/intern/tasks.php';
$mark_stmt  = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND link = ?");
if ($mark_stmt) {
    $mark_stmt->bind_param("is", $user_id, $tasks_link);
    $mark_stmt->execute();
}

$tasks = [];
$stmt  = $conn->prepare("SELECT id, title, duties AS description, date_from, date_to AS due_date, file_path, created_at
                         FROM `{$routine_table}`
                         WHERE assigned_to = ?
                         ORDER BY created_at DESC
                         LIMIT 50");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $tasks[] = $row;
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
    <title>My Tasks - Intern - MediCare Pharmacy</title>
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
        .task-title { font-weight: 600; }
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
                    <h1 class="h3 mb-1"><i class="bi bi-clipboard-check"></i> My Tasks</h1>
                    <p class="mb-0 opacity-75">Tasks assigned by HR will appear here.</p>
                </div>
                <a href="dashboard.php" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Back to Intern dashboard
                </a>
            </div>

            <div class="panel">
                <?php if ($flash_success): ?>
                    <div class="alert alert-success mb-3"><i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($flash_success); ?></div>
                <?php endif; ?>
                <?php if ($flash_error): ?>
                    <div class="alert alert-danger mb-3"><i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($flash_error); ?></div>
                <?php endif; ?>
                <?php if (empty($tasks)): ?>
                    <p class="text-muted mb-0">No tasks assigned yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Date Range</th>
                                    <th>Attachment</th>
                                    <th>Assigned</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $t): ?>
                                    <tr>
                                        <td>
                                            <div class="task-title"><?php echo htmlspecialchars($t['title']); ?></div>
                                            <div class="small text-muted"><?php echo nl2br(htmlspecialchars($t['description'])); ?></div>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars(($t['date_from'] ?? '—') . ' → ' . ($t['due_date'] ?: '—')); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if (!empty($t['file_path'])): ?>
                                                <a href="<?php echo htmlspecialchars(BASE_URL . 'uploads/tasks/' . rawurlencode($t['file_path'])); ?>" target="_blank" rel="noopener">
                                                    <i class="bi bi-paperclip"></i> View file
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($t['created_at']); ?></small></td>
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

