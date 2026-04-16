<?php
require_once '../../config.php';

if (!isLoggedIn()) { header('Location: ../../views/auth/login.php'); exit(); }
if ($_SESSION['role_name'] !== 'Pharmacy Assistant') { header('Location: ../../index.php'); exit(); }

$receipt_id = (int)($_GET['id'] ?? 0);

if (!$receipt_id) {
    header('Location: dashboard.php');
    exit();
}

$stmt = $conn->prepare("SELECT pr.*, CONCAT(u.first_name, ' ', u.last_name) as customer_name 
    FROM prescription_receipts pr 
    LEFT JOIN users u ON u.id = pr.customer_id 
    WHERE pr.id = ?");
$stmt->bind_param('i', $receipt_id);
$stmt->execute();
$receipt = $stmt->get_result()->fetch_assoc();

if (!$receipt) {
    header('Location: dashboard.php');
    exit();
}

$file_path = '../../' . $receipt['file_path'];
$mime_type = $receipt['mime_type'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Receipt - MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f0f2f5; }
        .receipt-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 2rem; margin-top: 1.5rem; }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php"><i class="bi bi-hospital"></i> MediCare Pharmacy</a>
        <div class="navbar-nav ms-auto">
            <a href="../logout.php" class="btn btn-danger btn-sm rounded-pill"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <div class="mt-3 mb-2">
        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="receipt-card">
        <h4 class="mb-4"><i class="bi bi-file-earmark-image"></i> Prescription Receipt</h4>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Customer:</strong> <?= htmlspecialchars($receipt['customer_name']) ?></p>
                <p><strong>Uploaded:</strong> <?= date('F j, Y g:i A', strtotime($receipt['uploaded_at'])) ?></p>
                <p><strong>Status:</strong> 
                    <span class="badge bg-<?= $receipt['status'] === 'Pending' ? 'warning' : 'success' ?>">
                        <?= htmlspecialchars($receipt['status']) ?>
                    </span>
                </p>
            </div>
            <div class="col-md-6">
                <p><strong>Original Filename:</strong> <?= htmlspecialchars($receipt['original_filename']) ?></p>
                <p><strong>File Size:</strong> <?= number_format($receipt['file_size'] / 1024, 2) ?> KB</p>
                <?php if (!empty($receipt['notes'])): ?>
                <p><strong>Notes:</strong> <?= htmlspecialchars($receipt['notes']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <hr>

        <div class="text-center mt-4">
            <?php if (strpos($mime_type, 'image') !== false): ?>
                <img src="<?= htmlspecialchars($file_path) ?>" 
                     alt="Receipt" 
                     class="img-fluid border rounded shadow-sm" 
                     style="max-width: 100%; max-height: 800px;">
            <?php elseif (strpos($mime_type, 'pdf') !== false): ?>
                <embed src="<?= htmlspecialchars($file_path) ?>" 
                       type="application/pdf" 
                       width="100%" 
                       height="800px" 
                       class="border rounded shadow-sm">
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> Unable to preview this file type.
                    <a href="<?= htmlspecialchars($file_path) ?>" download class="btn btn-primary btn-sm ms-2">
                        <i class="bi bi-download"></i> Download File
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
