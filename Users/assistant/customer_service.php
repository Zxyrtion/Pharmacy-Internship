<?php
require_once '../../config.php';
if (!isLoggedIn()) { header('Location: ../../views/auth/login.php'); exit(); }
if ($_SESSION['role_name'] !== 'Pharmacy Assistant') { header('Location: ../../index.php'); exit(); }

$full_name = $_SESSION['full_name'];

// Ensure table exists
$conn->query("CREATE TABLE IF NOT EXISTS customer_inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(200) NOT NULL,
    contact VARCHAR(100),
    inquiry_type ENUM('General','Prescription','Stock','Complaint','Other') DEFAULT 'General',
    subject VARCHAR(300) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('Open','In Progress','Resolved') DEFAULT 'Open',
    handled_by INT NULL,
    response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$success = ''; $error = '';

// Handle new inquiry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_inquiry'])) {
    $name    = trim($_POST['customer_name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $type    = $_POST['inquiry_type'] ?? 'General';
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($name && $subject && $message) {
        $stmt = $conn->prepare("INSERT INTO customer_inquiries (customer_name, contact, inquiry_type, subject, message, handled_by) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('sssssi', $name, $contact, $type, $subject, $message, $_SESSION['user_id']);
        $stmt->execute();
        $success = 'Inquiry logged successfully.';
    } else { $error = 'Please fill all required fields.'; }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id       = (int)$_POST['inquiry_id'];
    $status   = $_POST['status'];
    $response = trim($_POST['response'] ?? '');
    $stmt = $conn->prepare("UPDATE customer_inquiries SET status=?, response=?, handled_by=? WHERE id=?");
    $stmt->bind_param('ssii', $status, $response, $_SESSION['user_id'], $id);
    $stmt->execute();
    $success = 'Inquiry updated.';
}

$filter = $_GET['status'] ?? 'Open';
if ($filter === 'All') {
    $res = $conn->query("SELECT * FROM customer_inquiries ORDER BY created_at DESC");
} else {
    $s = $conn->prepare("SELECT * FROM customer_inquiries WHERE status=? ORDER BY created_at DESC");
    $s->bind_param('s', $filter); $s->execute();
    $res = $s->get_result();
}
$inquiries = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Service - MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; }
        .page-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 2rem; margin-top: 1.5rem; }
        .badge-open       { background:#ffc107; color:#000; }
        .badge-in.progress{ background:#0dcaf0; color:#000; }
        .badge-resolved   { background:#198754; }
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
        <h5 class="mb-0"><i class="bi bi-people"></i> Customer Service — Help Desk</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle"></i> Log Inquiry
        </button>
    </div>

    <?php if ($success): ?><div class="alert alert-success mt-2"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger mt-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="page-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Customer Inquiries</h5>
            <div class="btn-group">
                <?php foreach (['Open','In Progress','Resolved','All'] as $s): ?>
                <a href="?status=<?= $s ?>" class="btn btn-sm btn-outline-primary <?= $filter === $s ? 'active fw-bold' : '' ?>"><?= $s ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php if (empty($inquiries)): ?>
            <div class="text-center text-muted py-4"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-2">No <?= strtolower($filter) ?> inquiries.</p></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr><th>#</th><th>Customer</th><th>Contact</th><th>Type</th><th>Subject</th><th>Status</th><th>Date</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($inquiries as $inq): ?>
                    <tr>
                        <td>#<?= $inq['id'] ?></td>
                        <td><?= htmlspecialchars($inq['customer_name']) ?></td>
                        <td><?= htmlspecialchars($inq['contact'] ?? '-') ?></td>
                        <td><span class="badge bg-secondary"><?= $inq['inquiry_type'] ?></span></td>
                        <td><?= htmlspecialchars($inq['subject']) ?></td>
                        <td><span class="badge badge-<?= strtolower(str_replace(' ','',$inq['status'])) ?>"><?= $inq['status'] ?></span></td>
                        <td><?= date('M j, Y', strtotime($inq['created_at'])) ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateModal"
                                data-id="<?= $inq['id'] ?>"
                                data-subject="<?= htmlspecialchars($inq['subject']) ?>"
                                data-message="<?= htmlspecialchars($inq['message']) ?>"
                                data-status="<?= $inq['status'] ?>"
                                data-response="<?= htmlspecialchars($inq['response'] ?? '') ?>">
                                <i class="bi bi-pencil"></i> Update
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Log Customer Inquiry</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label fw-semibold">Customer Name *</label><input type="text" name="customer_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Contact</label><input type="text" name="contact" class="form-control" placeholder="Phone or email"></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Type</label>
                        <select name="inquiry_type" class="form-select">
                            <option>General</option><option>Prescription</option><option>Stock</option><option>Complaint</option><option>Other</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label fw-semibold">Subject *</label><input type="text" name="subject" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Message *</label><textarea name="message" class="form-control" rows="3" required></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_inquiry" value="1" class="btn btn-primary">Log Inquiry</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Modal -->
<div class="modal fade" id="updateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Update Inquiry</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="inquiry_id" id="updateId">
                    <div class="mb-2"><strong>Subject:</strong> <span id="updateSubject"></span></div>
                    <div class="mb-3"><strong>Message:</strong><p id="updateMessage" class="text-muted small"></p></div>
                    <div class="mb-3"><label class="form-label fw-semibold">Status</label>
                        <select name="status" id="updateStatus" class="form-select">
                            <option>Open</option><option>In Progress</option><option>Resolved</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label fw-semibold">Response</label><textarea name="response" id="updateResponse" class="form-control" rows="3"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="update_status" value="1" class="btn btn-primary">Update</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('updateModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('updateId').value       = btn.dataset.id;
    document.getElementById('updateSubject').textContent = btn.dataset.subject;
    document.getElementById('updateMessage').textContent = btn.dataset.message;
    document.getElementById('updateStatus').value   = btn.dataset.status;
    document.getElementById('updateResponse').value = btn.dataset.response;
});
</script>
</body>
</html>
