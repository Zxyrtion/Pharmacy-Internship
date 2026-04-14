<?php
require_once '../../config.php';

if (!isLoggedIn()) {
    header('Location: ../../views/auth/login.php');
    exit();
}

if ($_SESSION['role_name'] !== 'HR Personnel') {
    header('Location: ../../index.php');
    exit();
}

$full_name = $_SESSION['full_name'];
$success = '';
$errors = [];

$allowed_file_ext = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'rtf', 'odt', 'ods', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'zip'];
$max_upload_bytes = 15 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    $report_type = isset($_POST['report_type']) ? trim($_POST['report_type']) : '';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $date_from = isset($_POST['date_from']) ? trim($_POST['date_from']) : '';
    $date_to = isset($_POST['date_to']) ? trim($_POST['date_to']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

    $allowed_types = [
        'attendance_summary' => 'Attendance summary',
        'payroll_summary' => 'Payroll summary',
        'hiring' => 'Hiring & onboarding',
        'staff_performance' => 'Staff performance',
        'leave' => 'Leave & absences',
        'custom' => 'Custom report',
    ];

    if ($report_type === '' || !isset($allowed_types[$report_type])) {
        $errors[] = 'Please select a valid report type.';
    }
    if ($title === '') {
        $errors[] = 'Report title is required.';
    }
    if ($date_from === '' || $date_to === '') {
        $errors[] = 'Please choose both start and end dates.';
    }
    if ($date_from !== '' && $date_to !== '' && $date_from > $date_to) {
        $errors[] = 'Start date cannot be after end date.';
    }

    $attachment_meta = null;
    $attachment_db = [
        'original_name' => null,
        'stored_name' => null,
        'size_bytes' => null,
        'mime_type' => null,
    ];
    if (empty($errors) && !empty($_FILES['report_file']) && is_array($_FILES['report_file'])) {
        $f = $_FILES['report_file'];
        if ($f['error'] === UPLOAD_ERR_NO_FILE) {
            // optional attachment
        } elseif ($f['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed. Please try again or choose a smaller file.';
        } elseif ($f['size'] > $max_upload_bytes) {
            $errors[] = 'Attached file is too large (max 15 MB).';
        } else {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if ($ext === '' || !in_array($ext, $allowed_file_ext, true)) {
                $errors[] = 'File type not allowed. Use documents or common office formats (e.g. PDF, Word, Excel, images, ZIP).';
            } else {
                $project_root = realpath(__DIR__ . '/../..');
                if ($project_root === false) {
                    $errors[] = 'Upload storage is unavailable.';
                } else {
                    $upload_dir = $project_root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'hr_reports';
                    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                        $errors[] = 'Could not create upload folder.';
                    } else {
                        $safe_base = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($f['name'], PATHINFO_FILENAME));
                        if ($safe_base === '') {
                            $safe_base = 'file';
                        }
                        $stored_name = $_SESSION['user_id'] . '_' . uniqid('', true) . '_' . $safe_base . '.' . $ext;
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
                            $attachment_meta = [
                                'original_name' => $f['name'],
                                'stored_name' => $stored_name,
                            ];
                            $attachment_db = [
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
        $sql = "INSERT INTO internship_routine
                (user_id, report_type, title, date_from, date_to, notes, file_original_name, file_stored_name, file_size_bytes, file_mime_type)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $errors[] = 'Database error: could not prepare statement.';
        } else {
            $notes_param = ($notes !== '') ? $notes : null;
            $size_param = $attachment_db['size_bytes'];
            $stmt->bind_param(
                "isssssssis",
                $user_id,
                $report_type,
                $title,
                $date_from,
                $date_to,
                $notes_param,
                $attachment_db['original_name'],
                $attachment_db['stored_name'],
                $size_param,
                $attachment_db['mime_type']
            );

            if ($stmt->execute()) {
                $new_report_id = (int)$stmt->insert_id;

                // Ensure notifications table exists (simple in-app notifications)
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

                // Notify Intern users about the new report
                $intern_ids = [];
                $intern_stmt = $conn->prepare("SELECT u.id
                                               FROM users u
                                               JOIN roles r ON r.id = u.role_id
                                               WHERE r.role_name = 'Intern' AND u.is_active = 1");
                if ($intern_stmt && $intern_stmt->execute()) {
                    $intern_res = $intern_stmt->get_result();
                    if ($intern_res) {
                        while ($ir = $intern_res->fetch_assoc()) {
                            $intern_ids[] = (int)$ir['id'];
                        }
                    }
                }

                if (!empty($intern_ids)) {
                    $notif_title = 'New HR report';
                    $notif_message = "HR created a new report: {$title} ({$date_from} to {$date_to}).";
                    $notif_link = BASE_URL . 'Users/intern/reports.php?report_id=' . $new_report_id;
                    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
                    if ($notif_stmt) {
                        foreach ($intern_ids as $iid) {
                            $notif_stmt->bind_param("isss", $iid, $notif_title, $notif_message, $notif_link);
                            $notif_stmt->execute();
                        }
                    }
                }

                $success = 'Your report request has been recorded.';
                if ($attachment_meta !== null) {
                    $success .= ' Your attachment was saved.';
                }
                $_POST = [];
                $_FILES = [];
            } else {
                $errors[] = 'Database error: could not save report request.';
            }
        }
    }
}

$drafts = [];
$drafts_stmt = $conn->prepare("SELECT id, user_id, report_type, title, date_from, date_to, notes, file_original_name, file_stored_name, created_at
                               FROM internship_routine
                               ORDER BY created_at DESC
                               LIMIT 10");
if ($drafts_stmt && $drafts_stmt->execute()) {
    $res = $drafts_stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $drafts[] = [
                'type' => $row['report_type'],
                'title' => $row['title'],
                'from' => $row['date_from'],
                'to' => $row['date_to'],
                'notes' => $row['notes'],
                'created_at' => $row['created_at'],
                'attachment' => ($row['file_stored_name'] ? [
                    'original_name' => $row['file_original_name'] ?: $row['file_stored_name'],
                    'stored_name' => $row['file_stored_name'],
                ] : null),
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Reports & Analytics - MediCare Pharmacy</title>
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
                    <h1 class="h3 mb-1"><i class="bi bi-clipboard-data"></i> Reports & Analytics</h1>
                    <p class="mb-0 opacity-75">Create HR reports, define periods, and track recent requests.</p>
                </div>
                <a href="dashboard.php" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Back to HR dashboard
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
                        <h2 class="h5 mb-3"><i class="bi bi-plus-circle"></i> Create a report</h2>
                        <form method="post" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label" for="report_type">Report type</label>
                                <select class="form-select" id="report_type" name="report_type" required>
                                    <option value="">— Select —</option>
                                    <option value="attendance_summary">Attendance summary</option>
                                    <option value="payroll_summary">Payroll summary</option>
                                    <option value="hiring">Hiring & onboarding</option>
                                    <option value="staff_performance">Staff performance</option>
                                    <option value="leave">Leave & absences</option>
                                    <option value="custom">Custom report</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="title">Title</label>
                                <input type="text" class="form-control" id="title" name="title" required
                                       placeholder="e.g. Q2 attendance overview"
                                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="date_from">Period start</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" required
                                           value="<?php echo isset($_POST['date_from']) ? htmlspecialchars($_POST['date_from']) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="date_to">Period end</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" required
                                           value="<?php echo isset($_POST['date_to']) ? htmlspecialchars($_POST['date_to']) : ''; ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="notes">Notes / filters (optional)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4"
                                          placeholder="Departments, employees, or other criteria…"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="report_file">Attach file (optional)</label>
                                <input type="file" class="form-control" id="report_file" name="report_file"
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.rtf,.odt,.ods,.png,.jpg,.jpeg,.gif,.webp,.zip,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                                <div class="form-text">Documents, spreadsheets, PDFs, images, or ZIP. Max 15 MB. Executable files are not accepted.</div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-file-earmark-arrow-up"></i> Submit report request
                            </button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-5 mb-4">
                    <div class="panel">
                        <h2 class="h5 mb-3"><i class="bi bi-clock-history"></i> Recent requests (this session)</h2>
                        <?php if (empty($drafts)): ?>
                            <p class="text-muted mb-0">No report requests yet. Submit a form on the left to see them listed here.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($drafts as $d): ?>
                                    <div class="list-group-item px-0 border-bottom">
                                        <div class="fw-semibold"><?php echo htmlspecialchars($d['title']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($d['type']); ?> ·
                                            <?php echo htmlspecialchars($d['from']); ?> → <?php echo htmlspecialchars($d['to']); ?></small>
                                        <?php if (!empty($d['notes'])): ?>
                                            <div class="small mt-1"><?php echo nl2br(htmlspecialchars($d['notes'])); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($d['attachment']) && is_array($d['attachment'])): ?>
                                            <div class="small mt-1">
                                                <i class="bi bi-paperclip"></i>
                                                <a href="<?php echo htmlspecialchars(BASE_URL . 'uploads/hr_reports/' . rawurlencode($d['attachment']['stored_name'])); ?>" target="_blank" rel="noopener">
                                                    <?php echo htmlspecialchars($d['attachment']['original_name']); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <div class="small text-muted mt-1"><?php echo htmlspecialchars($d['created_at']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="panel">
                        <h2 class="h5 mb-2"><i class="bi bi-info-circle"></i> Next steps</h2>
                        <p class="text-muted small mb-0">You can later connect this form to a database table, PDF export, or email notifications. For now, submissions are stored in your session so you can demo the flow.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
