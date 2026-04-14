<?php
require_once '../../config.php';
require_once '../../models/prescription.php';

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

$prescription = new Prescription($conn);
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['prescription_id'])) {
    $prescription_id = $_POST['prescription_id'];
    $pharmacist_id = $_SESSION['user_id'];
    
    // Generate order
    $result = $prescription->generateOrder($prescription_id, $pharmacist_id);
    
    if ($result['success']) {
        $success_message = "Order generated successfully! Order ID: " . $result['order_id'] . 
                           " Total Amount: ₱" . number_format($result['total_amount'], 2);
    } else {
        $error_message = "Failed to generate order: " . $result['error'];
    }
}

// Get prescription details if prescription_id is provided
$prescription_details = null;
if (isset($_GET['prescription_id'])) {
    $prescription_details = $prescription->getPrescriptionByPrescriptionId($_GET['prescription_id']);
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Order - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .process-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .process-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .prescription-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .order-summary {
            background: #e8f5e8;
            border: 2px solid #28a745;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .btn-process {
            background: #28a745;
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-process:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-hospital"></i> MediCare Pharmacy
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($full_name); ?>
                </span>
                <a href="../logout.php" class="btn btn-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="process-container">
        <div class="container">
            <div class="process-card">
                <h2><i class="bi bi-cart-plus"></i> Process Prescription Order</h2>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($prescription_details): ?>
                    <form method="POST" action="">
                        <input type="hidden" name="prescription_id" value="<?php echo htmlspecialchars($prescription_details['prescription_id']); ?>">
                        
                        <div class="prescription-details">
                            <h4><i class="bi bi-file-medical"></i> Prescription Details</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Prescription ID:</strong> <?php echo htmlspecialchars($prescription_details['prescription_id']); ?></p>
                                    <p><strong>Patient Name:</strong> <?php echo htmlspecialchars($prescription_details['patient_name']); ?></p>
                                    <p><strong>Medicine:</strong> <?php echo htmlspecialchars($prescription_details['medicine_name']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Dosage:</strong> <?php echo htmlspecialchars($prescription_details['dosage']); ?></p>
                                    <p><strong>Quantity:</strong> <?php echo htmlspecialchars($prescription_details['quantity']); ?></p>
                                    <p><strong>Date Prescribed:</strong> <?php echo htmlspecialchars($prescription_details['date_prescribed']); ?></p>
                                </div>
                            </div>
                            <?php if ($prescription_details['instructions']): ?>
                                <div class="mt-3">
                                    <p><strong>Instructions:</strong></p>
                                    <p class="text-muted"><?php echo htmlspecialchars($prescription_details['instructions']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="order-summary">
                            <h4><i class="bi bi-receipt"></i> Order Summary</h4>
                            <div class="detail-row">
                                <span>Medicine:</span>
                                <span><?php echo htmlspecialchars($prescription_details['medicine_name']); ?> (<?php echo htmlspecialchars($prescription_details['dosage']); ?>)</span>
                            </div>
                            <div class="detail-row">
                                <span>Quantity:</span>
                                <span><?php echo htmlspecialchars($prescription_details['quantity']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span>Unit Price:</span>
                                <span>₱<?php echo number_format(15.50, 2); ?></span>
                            </div>
                            <div class="detail-row">
                                <span>Total Amount:</span>
                                <span>₱<?php echo number_format(15.50 * $prescription_details['quantity'], 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-process">
                                <i class="bi bi-check-circle"></i> Generate Order
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> No prescription found. Please select a prescription from the dashboard.
                    </div>
                    <div class="text-center">
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
