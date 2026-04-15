<?php
require_once '../../config.php';
?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="../../index.php">
            <i class="bi bi-hospital"></i> MediCare Pharmacy
        </a>
        
        <div class="navbar-nav ms-auto">
            <span class="navbar-text me-3">
                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($full_name ?? $_SESSION['full_name'] ?? 'User'); ?>
            </span>
            <a href="../logout.php" class="btn btn-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
</nav>
