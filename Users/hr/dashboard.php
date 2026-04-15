<?php
require_once '../../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
<<<<<<< HEAD
    header('Location: ../views/auth/login.php');
=======
    header('Location: ../../views/auth/login.php');
>>>>>>> recovery-restore
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'HR Personnel') {
<<<<<<< HEAD
    header('Location: ../index.php');
=======
    header('Location: ../../index.php');
>>>>>>> recovery-restore
    exit();
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
    <title>HR Dashboard - MediCare Pharmacy</title>
    
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
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
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
            color: #34495e;
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
<<<<<<< HEAD
            <a class="navbar-brand" href="../index.php">
=======
            <a class="navbar-brand" href="../../index.php">
>>>>>>> recovery-restore
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
                <h1><i class="bi bi-people"></i> HR Dashboard</h1>
                <p class="mb-0">Welcome back, <?php echo htmlspecialchars($full_name); ?>! Manage staff and human resources.</p>
            </div>
            
            <div class="row">
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
<<<<<<< HEAD
                        <i class="bi bi-file-earmark-text feature-icon"></i>
                        <h4>Intern Applicants</h4>
                        <p>Review internship applications</p>
                        <a href="internship_applications.php" class="btn btn-success">Review Applications</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-folder-fill feature-icon"></i>
                        <h4>Pharmacy Policies</h4>
                        <p>Organize policies and guidelines</p>
                        <a href="pharmacy_policies.php" class="btn btn-primary">Manage Documents</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-calendar-check feature-icon"></i>
                        <h4>Interview Schedule</h4>
                        <p>Manage interview batches and schedules</p>
                        <a href="interview_schedule.php" class="btn btn-info">View Schedule</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-clipboard-check feature-icon"></i>
                        <h4>Evaluate Interviews</h4>
                        <p>Rate and evaluate completed interviews</p>
                        <a href="evaluate_interview.php" class="btn btn-success">Evaluate Now</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-people-fill feature-icon text-success"></i>
                        <h4>Active Interns</h4>
                        <p>View interns who signed MOA</p>
                        <a href="view_ready_interns.php" class="btn btn-success">View Interns</a>
=======
                        <i class="bi bi-person-plus feature-icon"></i>
                        <h4>Employees</h4>
                        <p>Manage employee records</p>
                        <a href="employees.php" class="btn btn-primary">View All</a>
>>>>>>> recovery-restore
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-calendar-check feature-icon"></i>
                        <h4>Attendance</h4>
                        <p>Track employee attendance</p>
<<<<<<< HEAD
                        <a href="attendance.php" class="btn btn-primary">View Report</a>
=======
                        <button class="btn btn-primary">View Report</button>
>>>>>>> recovery-restore
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-cash-stack feature-icon"></i>
                        <h4>Payroll</h4>
                        <p>Manage salary and benefits</p>
                        <button class="btn btn-primary">Process</button>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-clipboard-data feature-icon"></i>
                        <h4>Reports</h4>
<<<<<<< HEAD
                        <p>View HR analytics</p>
                        <button class="btn btn-primary">Analytics</button>
=======
                        <p>Assign duties and monitor tasks</p>
                        <button class="btn btn-primary" type="button" disabled>Analytics</button>
>>>>>>> recovery-restore
                    </div>
                </div>
            </div>
            
            <div class="dashboard-card">
                <h3><i class="bi bi-clock-history"></i> Recent Activities</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Activity</th>
                                <th>Employee</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>New Hire</td>
                                <td>John Smith</td>
                                <td>2024-04-14</td>
                                <td><span class="badge bg-success">Completed</span></td>
                            </tr>
                            <tr>
                                <td>Leave Request</td>
                                <td>Jane Doe</td>
                                <td>2024-04-13</td>
                                <td><span class="badge bg-warning">Pending</span></td>
                            </tr>
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
