<?php
require_once '../../config.php';
if (!isLoggedIn()) { header('Location: ../../views/auth/login.php'); exit(); }
if ($_SESSION['role_name'] !== 'Pharmacy Assistant') { header('Location: ../../index.php'); exit(); }

$full_name = $_SESSION['full_name'];

$conn->query("CREATE TABLE IF NOT EXISTS call_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caller_name VARCHAR(200) NOT NULL,
    phone_number VARCHAR(20),
    call_type ENUM('Inquiry','Prescription','Complaint','Follow-up','Other') DEFAULT 'Inquiry',
    notes TEXT,
    duration_minutes INT DEFAULT 0,
    handled_by INT,
    call_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caller  = trim($_POST['caller_name'] ?? '');
    $phone   = trim($_POST['phone_number'] ?? '');
    $type    = $_POST['call_type'] ?? 'Inquiry';
    $notes   = trim($_POST['notes'] ?? '');
    $dur     = (int)($_POST['duration_minutes'] ?? 0);
    if ($caller) {
        $stmt = $conn->prepare("INSERT INTO call_logs (caller_name, phone_number, call_type, notes, duration_minutes, handled_by) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('ssssis', $caller, $phone, $type, $notes, $dur, $_SESSION['user_id']);
        $stmt->execute();
        $success = 'Call logged successfully.';
    }
}

$res = $conn->query("SELECT cl.*, u.first_name, u.last_name FROM call_logs cl
    LEFT JOIN users u ON cl.handled_by = u.id ORDER BY cl.call_date DESC LIMIT 50");
$calls = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// Stats
$today = date('Y-m-d');
$ts = $conn->prepare("SELECT COUNT(*) as cnt, SUM(duration_minutes) as total_min FROM call_logs WHERE DATE(call_date)=?");
$ts->bind_param('s', $today); $ts->execute();
$today_stats = $ts->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Call Center - MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; }
        .page-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 2rem; margin-top: 1.5rem; }
        .stat-box { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 10px; padding: 1.2rem; text-align: center; }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php"><i class="bi bi-hospital"></i> MediCare Pharmacy</a>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text me-3"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($full_name) ?></span>
            <a href="../logout.php" class="btn btn-danger btn-sm rounded-pill"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>
</nav>
<div class="container pb-5">
    <div class="mt-3 mb-2 d-flex justify-content-between align-items-center">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
        <h5 class="mb-0"><i class="bi bi-telephone"></i> Phone Support — Call Center</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#logModal">
            <i class="bi bi-plus-circle"></i> Log Call
        </button>
    </div>

    <?php if ($success): ?><div class="alert alert-success mt-2"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <!-- Stats -->
    <div class="row mt-3 mb-3">
        <div class="col-md-4">
            <div class="stat-box"><div style="font-size:2rem; font-weight:700;"><?= $today_stats['cnt'] ?? 0 ?></div><div>Calls Today</div></div>
        </div>
        <div class="col-md-4">
            <div class="stat-box"><div style="font-size:2rem; font-weight:700;"><?= $today_stats['total_min'] ?? 0 ?> min</div><div>Total Duration Today</div></div>
        </div>
        <div class="col-md-4">
            <div class="stat-box"><div style="font-size:2rem; font-weight:700;"><?= count($calls) ?></div><div>Total Logged Calls</div></div>
        </div>
    </div>

    <div class="page-card">
        <h5 class="mb-3">Recent Call Log</h5>
        <?php if (empty($calls)): ?>
            <div class="text-center text-muted py-4"><i class="bi bi-telephone-x" style="font-size:3rem;"></i><p class="mt-2">No calls logged yet.</p></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr><th>#</th><th>Caller</th><th>Phone</th><th>Type</th><th>Notes</th><th>Duration</th><th>Handled By</th><th>Date</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($calls as $call): ?>
                    <tr>
                        <td>#<?= $call['id'] ?></td>
                        <td><?= htmlspecialchars($call['caller_name']) ?></td>
                        <td><?= htmlspecialchars($call['phone_number'] ?? '-') ?></td>
                        <td><span class="badge bg-secondary"><?= $call['call_type'] ?></span></td>
                        <td><?= htmlspecialchars(substr($call['notes'] ?? '-', 0, 50)) ?><?= strlen($call['notes'] ?? '') > 50 ? '...' : '' ?></td>
                        <td><?= $call['duration_minutes'] ?> min</td>
                        <td><?= htmlspecialchars(($call['first_name'] ?? '') . ' ' . ($call['last_name'] ?? '')) ?></td>
                        <td><?= date('M j, Y H:i', strtotime($call['call_date'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Log Call Modal -->
<div class="modal fade" id="logModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-telephone"></i> Log a Call</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label fw-semibold">Caller Name *</label><input type="text" name="caller_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Phone Number</label><input type="text" name="phone_number" class="form-control" placeholder="09XXXXXXXXX"></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Call Type</label>
                        <select name="call_type" class="form-select">
                            <option>Inquiry</option><option>Prescription</option><option>Complaint</option><option>Follow-up</option><option>Other</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label fw-semibold">Duration (minutes)</label><input type="number" name="duration_minutes" class="form-control" min="0" value="0"></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Notes</label><textarea name="notes" class="form-control" rows="3" placeholder="Summary of the call..."></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Log Call</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
