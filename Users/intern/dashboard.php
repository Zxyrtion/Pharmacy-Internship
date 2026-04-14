<?php
require_once '../../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../views/auth/login.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'Intern') {
    header('Location: ../../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];

// Get internship status
require_once '../../core/Database.php';
require_once '../../models/internship.php';

$db = new Database();
$pdo_conn = $db->getConnection();
$internship = new Internship($pdo_conn);
$internship_record = $internship->getByUserId($user_id);

// Create record if doesn't exist
if (!$internship_record) {
    $internship->create($user_id);
    $internship_record = $internship->getByUserId($user_id);
}

// Get notifications
require_once '../../models/notification.php';
$notification = new Notification($pdo_conn);
$unread_count = $notification->getUnreadCount($user_id);
$notifications = $notification->getByUserId($user_id, 5);

// Get work schedule (check if any schedule exists for this user)
$work_schedule = null;
$schedule_sql = "SELECT ws.*, ie.average_rating, ie.final_decision 
                 FROM work_schedules ws
                 LEFT JOIN interview_evaluations ie ON ws.evaluation_id = ie.id
                 WHERE ws.user_id = ? 
                 ORDER BY ws.created_at DESC 
                 LIMIT 1";
$schedule_stmt = $conn->prepare($schedule_sql);
$schedule_stmt->bind_param("i", $user_id);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();
$work_schedule = $schedule_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intern Dashboard - MediCare Pharmacy</title>
    
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
            background: linear-gradient(135deg, #16a085 0%, #27ae60 100%);
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
            color: #16a085;
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
        
        .notification-bell {
            position: relative;
            font-size: 1.5rem;
            color: #667eea;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 0.5rem;
            display: inline-block;
        }
        
        .notification-bell:hover {
            color: #764ba2;
            transform: scale(1.1);
        }
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .notification-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            width: 400px;
            max-height: 500px;
            overflow-y: auto;
            z-index: 9999;
            display: none;
            margin-top: 5px;
        }
        
        .notification-dropdown.show {
            display: block;
        }
        
        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        
        .notification-item:hover {
            background: #f8f9fa;
        }
        
        .notification-item.unread {
            background: #e8f4fd;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .detail-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            border-left: 4px solid #667eea;
        }

        .detail-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.875rem;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .detail-value {
            font-size: 1.125rem;
            color: #212529;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm" style="position: relative; z-index: 1000;">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">
                <i class="bi bi-hospital"></i> MediCare Pharmacy
            </a>
            
            <div class="navbar-nav ms-auto">
                <!-- Notification Bell -->
                <div class="position-relative me-3" style="display: inline-block; z-index: 10000;">
                    <div class="notification-bell" id="notificationBell">
                        <i class="bi bi-bell-fill"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-badge" id="notificationBadge"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Notification Dropdown -->
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="background: #f8f9fa; border-radius: 15px 15px 0 0;">
                            <h6 class="mb-0"><i class="bi bi-bell"></i> Notifications</h6>
                            <?php if ($unread_count > 0): ?>
                                <button class="btn btn-sm btn-link text-decoration-none" id="markAllRead">
                                    Mark all as read
                                </button>
                            <?php endif; ?>
                        </div>
                        <div id="notificationList">
                            <?php if (empty($notifications)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                    <p class="mb-0 mt-2">No notifications yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" 
                                         data-id="<?php echo $notif['id']; ?>"
                                         data-type="<?php echo $notif['type']; ?>"
                                         data-related-id="<?php echo $notif['related_id']; ?>">
                                        <div class="d-flex">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($notif['title']); ?></h6>
                                                <p class="mb-1 small text-muted"><?php echo htmlspecialchars($notif['message']); ?></p>
                                                <small class="text-muted">
                                                    <i class="bi bi-clock"></i> 
                                                    <?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?>
                                                </small>
                                            </div>
                                            <?php if (!$notif['is_read']): ?>
                                                <div class="ms-2">
                                                    <span class="badge bg-primary">New</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($full_name); ?>
                </span>
                <a href="../logout.php" class="btn btn-logout">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <?php if ($internship_record['application_status'] === 'approved'): ?>
    <?php elseif ($internship_record['application_status'] === 'rejected'): ?>
    <!-- Notification Bar for Rejected Application -->
    <div class="alert alert-danger alert-dismissible fade show m-0 rounded-0 border-0" role="alert" id="rejectionNotification" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border-bottom: 3px solid #bd2130;">
        <div class="container">
            <div class="d-flex align-items-center">
                <i class="bi bi-x-circle-fill me-3" style="font-size: 2rem;"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-1 text-white">
                        Application Status Update
                    </h5>
                    <p class="mb-0 text-white">
                        Unfortunately, your application was not approved at this time. Please contact HR for more information or to reapply.
                    </p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php elseif ($internship_record['application_status'] === 'under_review'): ?>
    <!-- Notification Bar for Under Review Application -->
    <div class="alert alert-info alert-dismissible fade show m-0 rounded-0 border-0" role="alert" id="reviewNotification" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); border-bottom: 3px solid #117a8b;">
        <div class="container">
            <div class="d-flex align-items-center">
                <i class="bi bi-hourglass-split me-3" style="font-size: 2rem;"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-1 text-white">
                        <i class="bi bi-clock-history"></i> Application Under Review
                    </h5>
                    <p class="mb-0 text-white">
                        Your internship application is currently being reviewed by our HR team. We'll notify you once a decision has been made.
                    </p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="dashboard-container">
        <div class="container">
            <div class="welcome-header">
                <h1><i class="bi bi-mortarboard"></i> Intern Dashboard</h1>
                <p class="mb-0">Welcome back, <?php echo htmlspecialchars($full_name); ?>! Learn and assist in pharmacy operations.</p>
            </div>
            
            <!-- Internship Status Card -->
            <div class="col-12 mb-4">
                <div class="dashboard-card">
                    <h3><i class="bi bi-clipboard-check"></i> Internship Application Status</h3>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="progress mb-3" style="height: 25px;">
                                <?php
                                $completed_requirements = 0;
                                $total_requirements = 6;
                                
                                if ($internship_record['is_enrolled_higher_ed']) $completed_requirements++;
                                if ($internship_record['is_enrolled_internship_subject']) $completed_requirements++;
                                if ($internship_record['is_at_least_18']) $completed_requirements++;
                                if ($internship_record['has_passed_pre_internship']) $completed_requirements++;
                                if ($internship_record['medical_certificate_submitted']) $completed_requirements++;
                                if ($internship_record['parental_consent_submitted']) $completed_requirements++;
                                
                                $progress_percentage = ($completed_requirements / $total_requirements) * 100;
                                ?>
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $progress_percentage; ?>%">
                                    <?php echo $completed_requirements; ?>/<?php echo $total_requirements; ?> Requirements Complete
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">Application Status:</small>
                                    <p>
                                        <?php
                                        $status_class = 'secondary';
                                        $status_text = 'Not Started';
                                        
                                        switch($internship_record['application_status']) {
                                            case 'submitted':
                                                $status_class = 'info';
                                                $status_text = 'Submitted';
                                                break;
                                            case 'under_review':
                                                $status_class = 'warning';
                                                $status_text = 'Under Review';
                                                break;
                                            case 'approved':
                                                $status_class = 'success';
                                                $status_text = 'Approved';
                                                break;
                                            case 'rejected':
                                                $status_class = 'danger';
                                                $status_text = 'Rejected';
                                                break;
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        <?php if ($internship_record['is_eligible']): ?>
                                            <span class="badge bg-success">Eligible to Submit</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Requirements Incomplete</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <?php if ($internship_record['application_status'] === 'pending'): ?>
                                        <a href="apply_internship.php" class="btn btn-primary">
                                            <i class="bi bi-pencil-square"></i> Complete Application
                                        </a>
                                    <?php elseif ($internship_record['is_eligible'] && $internship_record['application_status'] === 'pending'): ?>
                                        <a href="apply_internship.php" class="btn btn-success">
                                            <i class="bi bi-send"></i> Submit Application
                                        </a>
                                    <?php else: ?>
                                        <a href="apply_internship.php" class="btn btn-outline-primary">
                                            <i class="bi bi-eye"></i> View Application
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <i class="bi bi-award" style="font-size: 3rem; color: <?php echo $internship_record['is_eligible'] ? '#28a745' : '#ffc107'; ?>;"></i>
                                <h5 class="mt-2">
                                    <?php echo $internship_record['is_eligible'] ? 'Ready to Apply!' : 'Complete Requirements'; ?>
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            
            <div class="row">
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-file-earmark-text feature-icon"></i>
                        <h4>Apply Internship</h4>
                        <p>Submit your internship application</p>
                        <a href="apply_internship.php" class="btn btn-success">Apply Now</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-calendar-week feature-icon"></i>
                        <h4>Work Schedule</h4>
                        <p>View your work schedule</p>
                        <?php if ($work_schedule): ?>
                            <span class="badge bg-success mb-2">
                                <i class="bi bi-check-circle"></i> Schedule Available
                            </span><br>
                        <?php endif; ?>
                        <a href="work_schedule.php" class="btn btn-primary">View Schedule</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-book feature-icon"></i>
                        <h4>Learning Modules</h4>
                        <p>Complete training modules</p>
                        <button class="btn btn-primary">Start Learning</button>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-person-plus feature-icon"></i>
                        <h4>Shadowing</h4>
                        <p>Learn from experienced staff</p>
                        <button class="btn btn-primary">View Schedule</button>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-clipboard-check feature-icon"></i>
                        <h4>Tasks</h4>
                        <p>Complete assigned tasks</p>
                        <button class="btn btn-primary">View Tasks</button>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-graph-up feature-icon"></i>
                        <h4>Progress</h4>
                        <p>Track your learning progress</p>
                        <button class="btn btn-primary">View Progress</button>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-folder-fill feature-icon"></i>
                        <h4>Policies & Guidelines</h4>
                        <p>View pharmacy policies and guidelines</p>
                        <a href="policies_guidelines.php" class="btn btn-primary">View Policies</a>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-card">
                <h3><i class="bi bi-clock-history feature-icon"></i> Recent Activities</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Activity</th>
                                <th>Supervisor</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Medication Dispensing</td>
                                <td>Dr. Smith</td>
                                <td>2024-04-14</td>
                                <td><span class="badge bg-success">Completed</span></td>
                            </tr>
                            <tr>
                                <td>Inventory Management</td>
                                <td>Ms. Johnson</td>
                                <td>2024-04-13</td>
                                <td><span class="badge bg-warning">In Progress</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Internship Details Modal -->
    <div class="modal fade" id="internshipDetailsModal" tabindex="-1" aria-labelledby="internshipDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border-radius: 20px 20px 0 0;">
                    <h5 class="modal-title" id="internshipDetailsModalLabel">
                        <i class="bi bi-calendar-check"></i> Internship Schedule & Location Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <!-- Schedule Section -->
                        <div class="col-md-6 mb-4">
                            <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                                <div class="card-body">
                                    <h5 class="card-title text-success mb-3">
                                        <i class="bi bi-clock-history"></i> Internship Schedule
                                    </h5>
                                    <div class="schedule-info">
                                        <div class="mb-3">
                                            <label class="text-muted small">Start Date:</label>
                                            <p class="mb-0 fw-bold">
                                                <i class="bi bi-calendar-event text-success"></i> 
                                                <?php 
                                                // You can add start_date field to database or use a default
                                                echo date('F d, Y', strtotime('+7 days')); 
                                                ?>
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="text-muted small">Duration:</label>
                                            <p class="mb-0 fw-bold">
                                                <i class="bi bi-hourglass-split text-success"></i> 
                                                6 Months (480 hours)
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="text-muted small">Working Days:</label>
                                            <p class="mb-0 fw-bold">
                                                <i class="bi bi-calendar-week text-success"></i> 
                                                Monday - Friday
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="text-muted small">Working Hours:</label>
                                            <p class="mb-0 fw-bold">
                                                <i class="bi bi-alarm text-success"></i> 
                                                8:00 AM - 5:00 PM
                                            </p>
                                        </div>
                                        <div class="alert alert-info mb-0" style="border-radius: 10px;">
                                            <small>
                                                <i class="bi bi-info-circle"></i> 
                                                Please report on your first day at 8:00 AM sharp
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Location Section -->
                        <div class="col-md-6 mb-4">
                            <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                                <div class="card-body">
                                    <h5 class="card-title text-primary mb-3">
                                        <i class="bi bi-geo-alt-fill"></i> Location Details
                                    </h5>
                                    <div class="location-info">
                                        <div class="mb-3">
                                            <label class="text-muted small">Pharmacy Name:</label>
                                            <p class="mb-0 fw-bold">
                                                <i class="bi bi-hospital text-primary"></i> 
                                                MediCare Pharmacy - Main Branch
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="text-muted small">Address:</label>
                                            <p class="mb-0 fw-bold">
                                                <i class="bi bi-pin-map text-primary"></i> 
                                                123 Health Street, Medical District<br>
                                                <span class="ms-4">Cebu City, Cebu 6000</span>
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="text-muted small">Contact Person:</label>
                                            <p class="mb-0 fw-bold">
                                                <i class="bi bi-person-badge text-primary"></i> 
                                                Ms. Maria Santos - HR Manager
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="text-muted small">Contact Number:</label>
                                            <p class="mb-0 fw-bold">
                                                <i class="bi bi-telephone text-primary"></i> 
                                                (032) 123-4567
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="text-muted small">Email:</label>
                                            <p class="mb-0 fw-bold">
                                                <i class="bi bi-envelope text-primary"></i> 
                                                hr@medicarepharmacy.com
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Important Notes -->
                    <div class="card border-0 shadow-sm" style="border-radius: 15px; background: #fff3cd;">
                        <div class="card-body">
                            <h6 class="text-warning mb-3">
                                <i class="bi bi-exclamation-triangle-fill"></i> Important Reminders
                            </h6>
                            <ul class="mb-0 small">
                                <li>Bring a valid ID and your approval letter on your first day</li>
                                <li>Dress code: Business casual or scrubs (will be provided)</li>
                                <li>Arrive 15 minutes early for orientation</li>
                                <li>Bring a notebook and pen for training sessions</li>
                                <li>Contact HR if you have any questions or concerns</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #e9ecef; border-radius: 0 0 20px 20px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Close
                    </button>
                    <button type="button" class="btn btn-success" onclick="window.print();">
                        <i class="bi bi-printer"></i> Print Details
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Notification Bell Toggle
        const notificationBell = document.getElementById('notificationBell');
        const notificationDropdown = document.getElementById('notificationDropdown');
        
        notificationBell.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationDropdown.contains(e.target) && !notificationBell.contains(e.target)) {
                notificationDropdown.classList.remove('show');
            }
        });
        
        // Handle notification click
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.dataset.id;
                const notificationType = this.dataset.type;
                const relatedId = this.dataset.relatedId;
                
                // Mark as read
                fetch('get_notifications.php?action=mark_read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'notification_id=' + notificationId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove unread styling
                        this.classList.remove('unread');
                        
                        // Update badge count
                        updateNotificationBadge();
                        
                        // Handle notification type
                        if (notificationType === 'internship_approved') {
                            // Open the internship details modal
                            const modal = new bootstrap.Modal(document.getElementById('internshipDetailsModal'));
                            modal.show();
                            notificationDropdown.classList.remove('show');
                        } else if (notificationType === 'internship_schedule') {
                            // Redirect to schedule details page
                            window.location.href = 'view_schedule.php?application_id=' + relatedId;
                        } else if (notificationType === 'interview_scheduled') {
                            // Load and show interview details
                            loadInterviewDetails(relatedId);
                            notificationDropdown.classList.remove('show');
                        }
                    }
                });
            });
        });
        
        // Mark all as read
        const markAllReadBtn = document.getElementById('markAllRead');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                
                fetch('get_notifications.php?action=mark_all_read', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove all unread styling
                        document.querySelectorAll('.notification-item.unread').forEach(item => {
                            item.classList.remove('unread');
                        });
                        
                        // Remove "New" badges
                        document.querySelectorAll('.notification-item .badge').forEach(badge => {
                            badge.remove();
                        });
                        
                        // Update badge count
                        updateNotificationBadge();
                        
                        // Hide the mark all read button
                        this.style.display = 'none';
                    }
                });
            });
        }
        
        // Update notification badge count
        function updateNotificationBadge() {
            fetch('get_notifications.php?action=count')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.getElementById('notificationBadge');
                        if (data.count > 0) {
                            if (badge) {
                                badge.textContent = data.count;
                            } else {
                                // Create badge if it doesn't exist
                                const newBadge = document.createElement('span');
                                newBadge.className = 'notification-badge';
                                newBadge.id = 'notificationBadge';
                                newBadge.textContent = data.count;
                                notificationBell.appendChild(newBadge);
                            }
                        } else {
                            if (badge) {
                                badge.remove();
                            }
                        }
                    }
                });
        }
        
        // Load interview details
        function loadInterviewDetails(applicationId) {
            fetch('get_interview_details.php?application_id=' + applicationId)
                .then(response => {
                    // Check if response is ok
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text(); // Get as text first to debug
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success && data.interview) {
                            showInterviewModal(data.interview);
                        } else {
                            alert('Unable to load interview details: ' + (data.message || 'Unknown error'));
                        }
                    } catch (e) {
                        console.error('JSON Parse Error:', e);
                        console.error('Response text:', text);
                        alert('Error loading interview details. Please check the console for details.');
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    alert('An error occurred while loading interview details.');
                });
        }
        
        // Show interview modal
        function showInterviewModal(interview) {
            document.getElementById('modal_interview_date').textContent = interview.date_formatted;
            document.getElementById('modal_interview_time').textContent = interview.time_formatted;
            
            const typeContainer = document.getElementById('modal_interview_type');
            if (interview.type === 'personal') {
                typeContainer.innerHTML = '<span class="badge bg-primary"><i class="bi bi-person-fill"></i> Personal Interview</span>';
                document.getElementById('modal_location_section').style.display = 'block';
                document.getElementById('modal_online_section').style.display = 'none';
                document.getElementById('modal_interview_location').textContent = interview.location || 'N/A';
            } else {
                typeContainer.innerHTML = '<span class="badge bg-success"><i class="bi bi-camera-video-fill"></i> Online Interview</span>';
                document.getElementById('modal_location_section').style.display = 'none';
                document.getElementById('modal_online_section').style.display = 'block';
                document.getElementById('modal_meeting_link').href = interview.meeting_link;
                document.getElementById('modal_meeting_link_text').textContent = interview.meeting_link;
            }
            
            const notesSection = document.getElementById('modal_notes_section');
            if (interview.notes) {
                notesSection.style.display = 'block';
                document.getElementById('modal_interview_notes').textContent = interview.notes;
            } else {
                notesSection.style.display = 'none';
            }
            
            const modal = new bootstrap.Modal(document.getElementById('interviewModal'));
            modal.show();
        }
        
        // Auto-refresh notification count every 30 seconds
        setInterval(updateNotificationBadge, 30000);
        
        // Acknowledge work schedule
        function acknowledgeSchedule(scheduleId) {
            if (confirm('Confirm that you have reviewed and acknowledged your work schedule?')) {
                fetch('acknowledge_schedule.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'schedule_id=' + scheduleId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Schedule acknowledged successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to acknowledge schedule'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        }
    </script>
    
    <!-- Interview Details Modal -->
    <div class="modal fade" id="interviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 20px 20px 0 0;">
                    <h5 class="modal-title">
                        <i class="bi bi-calendar-event"></i> Interview Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-check-circle-fill" style="font-size: 4rem; color: #28a745;"></i>
                        <h4 class="mt-3">Your Interview is Scheduled!</h4>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="detail-card">
                                <div class="detail-label">
                                    <i class="bi bi-calendar-event"></i> Interview Date
                                </div>
                                <div class="detail-value" id="modal_interview_date">Loading...</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="detail-card">
                                <div class="detail-label">
                                    <i class="bi bi-clock"></i> Interview Time
                                </div>
                                <div class="detail-value" id="modal_interview_time">Loading...</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-card mb-3">
                        <div class="detail-label">
                            <i class="bi bi-type"></i> Interview Type
                        </div>
                        <div class="detail-value" id="modal_interview_type">Loading...</div>
                    </div>
                    
                    <div id="modal_location_section" class="detail-card mb-3" style="display: none;">
                        <div class="detail-label">
                            <i class="bi bi-geo-alt-fill"></i> Location
                        </div>
                        <div class="detail-value" id="modal_interview_location">Loading...</div>
                    </div>
                    
                    <div id="modal_online_section" class="detail-card mb-3" style="display: none;">
                        <div class="detail-label">
                            <i class="bi bi-link-45deg"></i> Meeting Link
                        </div>
                        <div class="detail-value">
                            <a href="#" id="modal_meeting_link" target="_blank" class="btn btn-success">
                                <i class="bi bi-box-arrow-up-right"></i> Join Meeting
                            </a>
                            <p class="mt-2 small text-muted" id="modal_meeting_link_text">Loading...</p>
                        </div>
                    </div>
                    
                    <div id="modal_notes_section" class="detail-card mb-3" style="display: none;">
                        <div class="detail-label">
                            <i class="bi bi-sticky"></i> Additional Notes
                        </div>
                        <div class="detail-value" id="modal_interview_notes">Loading...</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle"></i> Important Reminders:</h6>
                        <ul class="mb-0 small">
                            <li>Please arrive 10 minutes early for personal interviews</li>
                            <li>Bring a valid ID and your application documents</li>
                            <li>Dress professionally</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="view_interview.php" class="btn btn-primary">
                        <i class="bi bi-eye"></i> View Full Details
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
