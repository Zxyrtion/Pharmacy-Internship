<?php
require_once '../../config.php';
require_once '../../models/prescription.php';
require_once '../../models/purchase_order.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../views/auth/login.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'Pharmacist') {
    header('Location: /Pharmacy-Internship/index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];

// Initialize models
$prescription = new Prescription($conn);
$purchaseOrder = new PurchaseOrder($conn);

// Get recent prescriptions
$recent_prescriptions = $prescription->getAllPrescriptions();

// Get prescription statistics
$prescription_stats = $prescription->getPrescriptionStats();

// Get approved requisitions for purchase order generation
$approved_requisitions = $purchaseOrder->getApprovedRequisitions();

// Get purchase order statistics
$po_stats = $purchaseOrder->getPurchaseOrderStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacist Dashboard - MediCare Pharmacy</title>
    
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
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .feature-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: #e74c3c;
            margin-bottom: 1rem;
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
                <h1><i class="bi bi-hospital"></i> Pharmacist Dashboard</h1>
                <p class="mb-0">Welcome back, <?php echo htmlspecialchars($full_name); ?>! Manage prescriptions and patient consultations.</p>
            </div>
            
            <div class="row">
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-file-medical feature-icon"></i>
                        <h4>Prescriptions</h4>
                        <p>Review and process prescriptions</p>
                        <a href="prescriptions.php" class="btn btn-primary">View All</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-people feature-icon"></i>
                        <h4>Consultations</h4>
                        <p>Manage patient consultations</p>
                        <button class="btn btn-primary">View Schedule</button>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-capsule feature-icon"></i>
                        <h4>Medicine Inventory</h4>
                        <p>Check stock levels and orders</p>
                        <a href="manage_inventory.php" class="btn btn-primary">
                            <i class="bi bi-gear"></i> Manage
                        </a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-download feature-icon"></i>
                        <h4>Dispense Product</h4>
                        <p>Check availability and dispense medications</p>
                        <a href="dispense_product.php" class="btn btn-primary">
                            <i class="bi bi-download"></i> Dispense
                        </a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-file-plus feature-icon"></i>
                        <h4>Create Requisition</h4>
                        <p>Generate new requisition</p>
                        <a href="create_requisition.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Create
                        </a>
                    </div>
                </div>
                
                <!-- Process 13: Check stock requisition -->
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-clipboard-check feature-icon"></i>
                        <h4>Manage Requisitions</h4>
                        <p>View, approve, and manage requisitions</p>
                        <a href="manage_requisitions.php" class="btn btn-primary">
                            <i class="bi bi-gear"></i> Manage
                        </a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-graph-up feature-icon"></i>
                        <h4>Reports</h4>
                        <p>View sales and inventory reports</p>
                        <button class="btn btn-primary">View Reports</button>
                    </div>
                </div>
            </div>
            

            
            <div class="dashboard-card">
                <h3><i class="bi bi-clock-history"></i> Recent Prescriptions</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_prescriptions)): ?>
                                <?php foreach ($recent_prescriptions as $presc): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($presc['id']); ?></td>
                                        <td><?php echo htmlspecialchars($presc['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($presc['doctor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($presc['prescription_date'] ?? $presc['created_at']); ?></td>
                                        <td>
                                            <?php
                                            $badge_class = match($presc['status']) {
                                                'Pending'    => 'bg-warning',
                                                'Processing' => 'bg-info',
                                                'Ready'      => 'bg-success',
                                                'Dispensed'  => 'bg-secondary',
                                                'Cancelled'  => 'bg-danger',
                                                default      => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($presc['status']); ?></span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No prescriptions found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="dashboard-card">
                <h3><i class="bi bi-cart-plus"></i> Purchase Orders - Process 14</h3>
                <p class="text-muted">Generate Purchase Orders from Approved Requisitions</p>
                
                <?php if (!empty($approved_requisitions)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Requisition ID</th>
                                    <th>Requested By</th>
                                    <th>Date</th>
                                    <th>Urgency</th>
                                    <th>Total Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($approved_requisitions as $req): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($req['requisition_id']); ?></td>
                                        <td><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($req['requisition_date']); ?></td>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                echo match($req['urgency']) {
                                                    'Normal' => 'bg-success',
                                                    'Urgent' => 'bg-warning',
                                                    'Critical' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                }; 
                                                ?>">
                                                <?php echo htmlspecialchars($req['urgency']); ?>
                                            </span>
                                        </td>
                                        <td>₱<?php echo number_format($req['total_amount'], 2); ?></td>
                                        <td>
                                            <a href="generate_purchase_order.php?requisition_id=<?php echo urlencode($req['id']); ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="bi bi-cart-plus"></i> Generate PO
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No approved requisitions available for purchase order generation.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function viewPrescription(prescriptionId) {
            // Redirect to process order page with view mode
            window.location.href = 'process_order.php?prescription_id=' + prescriptionId + '&view=1';
        }
        
        // Auto-refresh dashboard every 30 seconds to check for new prescriptions
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
