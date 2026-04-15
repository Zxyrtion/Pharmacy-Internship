<?php
require_once '../../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../views/auth/login.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'Customer') {
    header('Location: /Pharmacy-Internship/index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];

// Fetch real recent prescriptions/orders
// Note: Using existing database table structure

$stmt = $conn->prepare(
    "SELECT p.id, p.prescription_id, p.date_prescribed, p.patient_name, p.doctor_name, p.status
     FROM prescriptions p
     WHERE p.customer_id = ?
     GROUP BY p.prescription_id
     ORDER BY p.created_at DESC LIMIT 5"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$recent_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Count ready for payment
$sp = $conn->prepare("SELECT COUNT(*) as cnt FROM prescriptions WHERE customer_id=? AND status='Ready'");
$sp->bind_param('i', $user_id);
$sp->execute();
$ready_count = $sp->get_result()->fetch_assoc()['cnt'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .dashboard-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .welcome-header {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .feature-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem 1rem;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
            min-height: 220px;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            color: #3498db;
            margin-bottom: 0.75rem;
        }
        
        .feature-card h5 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .feature-card p {
            font-size: 0.85rem;
            margin-bottom: 1rem;
            color: #6c757d;
        }
        
        .btn-logout {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="/Pharmacy-Internship/index.php">
                <i class="bi bi-hospital"></i> MediCare Pharmacy
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($full_name); ?>
                </span>
                <a href="../logout.php" class="btn btn-logout">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="container">
            <div class="welcome-header">
                <h1><i class="bi bi-person"></i> Customer Dashboard</h1>
                <p class="mb-0">Welcome back, <?php echo htmlspecialchars($full_name); ?>! Manage your prescriptions and orders.</p>
            </div>
            
            <div class="row justify-content-center">
                <!-- Process 15: Present Doctor's Prescription -->
                <div class="col-md-6 col-lg-2 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-file-earmark-medical feature-icon"></i>
                        <h5>Present Prescription</h5>
                        <p class="small">Submit your doctor's prescription</p>
                        <a href="prescription_submit.php" class="btn btn-primary btn-sm">Submit Now</a>
                    </div>
                </div>

                <!-- My Prescriptions -->
                <div class="col-md-6 col-lg-2 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-file-medical feature-icon"></i>
                        <h5>My Prescriptions</h5>
                        <p class="small">View all your prescriptions</p>
                        <a href="my_prescriptions.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                </div>

                <!-- Process 16: Purchase Order (view order details) -->
                <div class="col-md-6 col-lg-2 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-cart-check feature-icon"></i>
                        <h5>Purchase Order</h5>
                        <p class="small">View your prescription orders</p>
                        <a href="purchase_order_view.php" class="btn btn-primary btn-sm">View Orders</a>
                    </div>
                </div>

                <!-- Process 17: Track dispensing status -->
                <div class="col-md-6 col-lg-2 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-bag-check feature-icon"></i>
                        <h5>Track Dispensing</h5>
                        <p class="small">Check if your medicines are ready</p>
                        <a href="track_dispensing.php" class="btn btn-primary btn-sm">Track</a>
                    </div>
                </div>

                <!-- Process 18: Pay -->
                <div class="col-md-6 col-lg-2 mb-4">
                    <div class="feature-card position-relative">
                        <i class="bi bi-cash-coin feature-icon"></i>
                        <h5>Pay for Medicine</h5>
                        <p class="small">Pay for your dispensed medicines</p>
                        <?php if ($ready_count > 0): ?>
                            <span class="position-absolute top-0 end-0 mt-2 me-2 badge bg-danger"><?= $ready_count ?></span>
                        <?php endif; ?>
                        <a href="my_prescriptions.php" class="btn btn-<?= $ready_count > 0 ? 'success' : 'primary' ?> btn-sm">
                            <?= $ready_count > 0 ? 'Pay Now' : 'View' ?>
                        </a>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <h3><i class="bi bi-clock-history"></i> Recent Prescription Orders</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_orders)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">No orders yet. <a href="prescription_submit.php">Submit a prescription</a> to get started.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($recent_orders as $ord):
                                $badge = match($ord['status']) {
                                    'Pending'    => 'warning text-dark',
                                    'Processing' => 'info text-dark',
                                    'Ready'      => 'success',
                                    'Dispensed'  => 'secondary',
                                    default      => 'secondary'
                                };
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($ord['prescription_id']) ?></td>
                                <td><?= htmlspecialchars($ord['date_prescribed']) ?></td>
                                <td><?= htmlspecialchars($ord['patient_name'] ?: 'N/A') ?></td>
                                <td><?= htmlspecialchars($ord['doctor_name']) ?></td>
                                <td>-</td>
                                <td><span class="badge bg-<?= $badge ?>"><?= $ord['status'] ?></span></td>
                                <td>
                                    <?php if ($ord['status'] === 'Ready'): ?>
                                        <a href="payment.php?rx_id=<?= $ord['prescription_id'] ?>" class="btn btn-sm btn-success">Pay</a>
                                    <?php elseif ($ord['status'] === 'Processing'): ?>
                                        <a href="purchase_order_view.php" class="btn btn-sm btn-info">View Details</a>
                                    <?php else: ?>
                                        <a href="my_prescriptions.php" class="btn btn-sm btn-info">View</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
