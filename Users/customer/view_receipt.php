<?php
require_once '../../config.php';

if (!isLoggedIn()) { 
    header('Location: ../../views/auth/login.php'); 
    exit(); 
}

if ($_SESSION['role_name'] !== 'Customer') { 
    header('Location: ../../index.php'); 
    exit(); 
}

$customer_id = (int)$_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Get the most recent uploaded receipt for this customer
$stmt = $conn->prepare("SELECT * FROM prescription_receipts 
    WHERE customer_id = ? 
    ORDER BY uploaded_at DESC 
    LIMIT 1");
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$receipt = $stmt->get_result()->fetch_assoc();

if (!$receipt) {
    header('Location: prescription_submit.php?error=no_receipt');
    exit();
}
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
        .receipt-card { 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.08); 
            padding: 2rem; 
            margin-top: 1.5rem; 
        }
        .receipt-header {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
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
    <div class="mt-3 mb-2">
        <a href="prescription_submit.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="receipt-card">
        <div class="receipt-header">
            <h3 class="mb-0"><i class="bi bi-file-earmark-image"></i> Your Uploaded Receipt</h3>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Uploaded:</strong> <?= date('M j, Y g:i A', strtotime($receipt['uploaded_at'])) ?></p>
                <p><strong>File:</strong> <?= htmlspecialchars($receipt['original_filename']) ?></p>
                <p><strong>Size:</strong> <?= number_format($receipt['file_size'] / 1024, 2) ?> KB</p>
                <?php if (!empty($receipt['notes'])): ?>
                <p><strong>Notes:</strong> <?= htmlspecialchars($receipt['notes']) ?></p>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-end">
                <p><strong>Status:</strong></p>
                <?php
                $badge_class = match($receipt['status']) {
                    'Pending' => 'bg-warning text-dark',
                    'Reviewed' => 'bg-info text-dark',
                    'Processed' => 'bg-success',
                    'Rejected' => 'bg-danger',
                    default => 'bg-secondary'
                };
                ?>
                <span class="badge <?= $badge_class ?> status-badge">
                    <?= htmlspecialchars($receipt['status']) ?>
                </span>
            </div>
        </div>

        <?php 
        $mime_type = $receipt['mime_type'];
        $file_path = $receipt['file_path'];
        ?>

        <?php if (strpos($mime_type, 'image') !== false): ?>
        <!-- Image Preview -->
        <div class="text-center">
            <img src="<?= htmlspecialchars($file_path) ?>" 
                 alt="Receipt" 
                 class="img-fluid border rounded shadow-sm" 
                 style="max-height: 600px; cursor: pointer;"
                 onclick="window.open('<?= htmlspecialchars($file_path) ?>', '_blank')">
            <p class="text-muted small mt-3">
                <i class="bi bi-info-circle"></i> Click image to view full size in new tab
            </p>
        </div>
        <?php elseif (strpos($mime_type, 'pdf') !== false): ?>
        <!-- PDF Preview -->
        <div class="text-center">
            <div class="alert alert-info">
                <i class="bi bi-file-pdf"></i> PDF Document
            </div>
            <a href="<?= htmlspecialchars($file_path) ?>" target="_blank" class="btn btn-primary btn-lg">
                <i class="bi bi-file-pdf"></i> Open PDF Receipt
            </a>
        </div>
        <?php endif; ?>

        <div class="mt-4 text-center">
            <a href="prescription_submit.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to Prescription Form
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
