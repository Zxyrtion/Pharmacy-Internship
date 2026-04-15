<?php
require_once '../../config.php';

if (!isLoggedIn()) {
    header('Location: ../../views/auth/login.php');
    exit();
}

if (($_SESSION['role_name'] ?? '') !== 'HR Personnel') {
    header('Location: ../../index.php');
    exit();
}

$hr_user_id = (int)($_SESSION['user_id'] ?? 0);
$full_name = $_SESSION['full_name'] ?? 'HR';

$employee_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($employee_id <= 0) {
    header('Location: employees.php');
    exit();
}

$routine_table = 'internship_routine';

// Load employee info
$emp = null;
$emp_stmt = $conn->prepare("SELECT u.id, u.first_name, u.middle_name, u.last_name, u.email, r.role_name
                            FROM users u
                            JOIN roles r ON r.id = u.role_id
                            WHERE u.id = ?
                            LIMIT 1");
if ($emp_stmt) {
    $emp_stmt->bind_param("i", $employee_id);
    if ($emp_stmt->execute()) {
        $res = $emp_stmt->get_result();
        if ($res && $res->num_rows === 1) {
            $emp = $res->fetch_assoc();
        }
    }
}
if (!$emp) {
    header('Location: employees.php');
    exit();
}

$success = '';
$errors = [];

// Flash success across redirect (prevents form re-submit)
if (isset($_SESSION['flash_success'])) {
    $success = (string)$_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

$allowed_file_ext = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'rtf', 'odt', 'ods', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'zip'];
$max_upload_bytes = 15 * 1024 * 1024;

// Recent tasks for this employee (right-side panel)
$recent_tasks = [];
$rt_stmt = $conn->prepare("SELECT id, title, date_to AS due_date, file_path, created_at
                           FROM `{$routine_table}`
                           WHERE assigned_to = ?
                           ORDER BY created_at DESC
                           LIMIT 10");
if ($rt_stmt) {
    $rt_stmt->bind_param("i", $employee_id);
    if ($rt_stmt->execute()) {
        $res = $rt_stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $recent_tasks[] = $row;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $date_from_input = isset($_POST['date_from']) ? trim($_POST['date_from']) : '';
    $date_to_input = isset($_POST['date_to']) ? trim($_POST['date_to']) : '';

    if ($title === '') $errors[] = 'Task title is required.';
    if ($description === '') $errors[] = 'Task description/duties is required.';
    if ($date_from_input === '' || $date_to_input === '') {
        $errors[] = 'Please choose both Date From and Date To.';
    }
    if ($date_from_input !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from_input)) {
        $errors[] = 'Date From format is invalid.';
    }
    if ($date_to_input !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to_input)) {
        $errors[] = 'Date To format is invalid.';
    }
    if ($date_from_input !== '' && $date_to_input !== '' && $date_from_input > $date_to_input) {
        $errors[] = 'Date From cannot be after Date To.';
    }

    $attachment = [
        'original_name' => null,
        'stored_name' => null,
        'size_bytes' => null,
        'mime_type' => null,
    ];

    if (empty($errors) && !empty($_FILES['task_file']) && is_array($_FILES['task_file'])) {
        $f = $_FILES['task_file'];
        if ($f['error'] === UPLOAD_ERR_NO_FILE) {
            // optional
        } elseif ($f['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed. Please try again.';
        } elseif ($f['size'] > $max_upload_bytes) {
            $errors[] = 'Attached file is too large (max 15 MB).';
        } else {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if ($ext === '' || !in_array($ext, $allowed_file_ext, true)) {
                $errors[] = 'File type not allowed.';
            } else {
                $project_root = realpath(__DIR__ . '/../..');
                if ($project_root === false) {
                    $errors[] = 'Upload storage is unavailable.';
                } else {
                    $upload_dir = $project_root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'tasks';
                    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                        $errors[] = 'Could not create upload folder.';
                    } else {
                        $safe_base = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($f['name'], PATHINFO_FILENAME));
                        if ($safe_base === '') $safe_base = 'file';
                        $stored_name = $hr_user_id . '_' . uniqid('', true) . '_' . $safe_base . '.' . $ext;
                        $dest = $upload_dir . DIRECTORY_SEPARATOR . $stored_name;
                        if (!move_uploaded_file($f['tmp_name'], $dest)) {
                            $errors[] = 'Could not save the uploaded file.';
                        } else {
                            $mime_type = null;
                            if (function_exists('finfo_open')) {
                                $fi = finfo_open(FILEINFO_MIME_TYPE);
                                if ($fi) {
                                    $mime_type = finfo_file($fi, $dest);
                                    finfo_close($fi);
                                }
                            }
                            $attachment = [
                                'original_name' => $f['name'],
                                'stored_name' => $stored_name,
                                'size_bytes' => (int)$f['size'],
                                'mime_type' => $mime_type ? (string)$mime_type : null,
                            ];
                        }
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        $date_from = $date_from_input;
        $date_to   = $date_to_input;

        $sql  = "INSERT INTO `{$routine_table}` (assigned_to, title, duties, date_from, date_to, file_path)
                 VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $errors[] = 'Database error: could not prepare statement.';
        } else {
            $file_path = $attachment['stored_name'];
            $stmt->bind_param(
                "isssss",
                $employee_id,
                $title,
                $description,
                $date_from,
                $date_to,
                $file_path
            );
            if ($stmt->execute()) {
                // notify employee
                $notif_title   = 'New task assigned';
                $notif_message = "HR assigned you a task: {$title} ({$date_from} to {$date_to}).";
                $notif_type    = 'task_assigned';

                $nstmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_id, is_read, created_at) VALUES (?, ?, ?, ?, NULL, 0, NOW())");
                if ($nstmt) {
                    $nstmt->bind_param("isss", $employee_id, $notif_type, $notif_title, $notif_message);
                    $nstmt->execute();
                }

                $_SESSION['task_assigned_success'] = true;
                $_SESSION['task_assigned_title'] = $title;
                $_SESSION['task_assigned_to'] = $emp_name;
                header('Location: assign_task.php?user_id=' . (int)$employee_id);
                exit();
            } else {
                $errors[] = 'Database error: could not assign task.';
            }
        }
    }
}

$emp_name = trim(($emp['first_name'] ?? '') . ' ' . ($emp['middle_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
$emp_name = preg_replace('/\s+/', ' ', $emp_name);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Task - HR - MediCare Pharmacy</title>
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
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
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
        .meta {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 1rem;
        }
        .hint {
            font-size: .9rem;
            color: #6c757d;
        }
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
                    <h1 class="h3 mb-1"><i class="bi bi-clipboard-plus"></i> Assign Task / Duties</h1>
                    <div class="meta mt-2">
                        <div class="fw-semibold"><i class="bi bi-person-badge"></i> <?php echo htmlspecialchars($emp_name); ?></div>
                        <div class="small opacity-75"><?php echo htmlspecialchars($emp['role_name'] ?? ''); ?> · <?php echo htmlspecialchars($emp['email'] ?? ''); ?></div>
                    </div>
                </div>
                <a href="employees.php" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Back to Employees
                </a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?>
                            <li><?php echo htmlspecialchars($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-7 mb-4">
                    <div class="panel">
                        <h2 class="h5 mb-3"><i class="bi bi-plus-circle"></i> Create a task</h2>
                        <form method="post" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label" for="title">Task title</label>
                                <input type="text" class="form-control" id="title" name="title" required
                                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                                       placeholder="e.g. Submit internship weekly report">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="description">Task / duties</label>
                                <textarea class="form-control" id="description" name="description" rows="6" required
                                          placeholder="Write the duties/instructions..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="date_from">Date From</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" required
                                           value="<?php echo isset($_POST['date_from']) ? htmlspecialchars($_POST['date_from']) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="date_to">Date To</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" required
                                           value="<?php echo isset($_POST['date_to']) ? htmlspecialchars($_POST['date_to']) : ''; ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="task_file">Attach file (optional)</label>
                                    <input type="file" class="form-control" id="task_file" name="task_file"
                                           accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.rtf,.odt,.ods,.png,.jpg,.jpeg,.gif,.webp,.zip">
                                    <div class="form-text">Documents/images/ZIP. Max 15 MB.</div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Assign task
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-5 mb-4">
                    <div class="panel">
                        <h2 class="h5 mb-3"><i class="bi bi-clock-history"></i> Recent tasks for this employee</h2>
                        <?php if (empty($recent_tasks)): ?>
                            <p class="text-muted mb-0">No tasks assigned yet.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_tasks as $rt): ?>
                                    <div class="list-group-item px-0 border-bottom">
                                        <div class="fw-semibold"><?php echo htmlspecialchars($rt['title']); ?></div>
                                        <small class="text-muted">
                                            Due: <?php echo htmlspecialchars($rt['due_date'] ?: '—'); ?>
                                        </small>
                                        <?php if (!empty($rt['file_path'])): ?>
                                            <div class="small mt-1">
                                                <i class="bi bi-paperclip"></i>
                                                <a href="<?php echo htmlspecialchars(BASE_URL . 'uploads/tasks/' . rawurlencode($rt['file_path'])); ?>" target="_blank" rel="noopener">
                                                    <?php echo htmlspecialchars($rt['file_path']); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <div class="small text-muted mt-1"><?php echo htmlspecialchars($rt['created_at']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="panel">
                        <h2 class="h5 mb-2"><i class="bi bi-info-circle"></i> Next steps</h2>
                        <p class="hint mb-0">Interns can view these tasks in their dashboard and update the status to Pending/Finished.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="taskSuccessModal" tabindex="-1" aria-labelledby="taskSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="taskSuccessModalLabel">
                        <i class="bi bi-check-circle"></i> Task Assigned Successfully!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="bi bi-clipboard-check text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="mb-3">Task has been assigned!</h5>
                        <p class="mb-2"><strong>Task Title:</strong> <?php echo htmlspecialchars($_SESSION['task_assigned_title'] ?? ''); ?></p>
                        <p class="mb-2"><strong>Assigned to:</strong> <?php echo htmlspecialchars($_SESSION['task_assigned_to'] ?? ''); ?></p>
                        <p class="text-muted mb-0">The intern has been notified and can view this task in their dashboard.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="dashboard.php" class="btn btn-primary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show success modal if task was assigned
        <?php if (isset($_SESSION['task_assigned_success']) && $_SESSION['task_assigned_success']): ?>
            const successModal = new bootstrap.Modal(document.getElementById('taskSuccessModal'));
            successModal.show();
            
            // Clear the session variable after showing modal
            setTimeout(() => {
                <?php unset($_SESSION['task_assigned_success']); ?>
                <?php unset($_SESSION['task_assigned_title']); ?>
                <?php unset($_SESSION['task_assigned_to']); ?>
            }, 500);
        <?php endif; ?>
    </script>
</body>
</html>

