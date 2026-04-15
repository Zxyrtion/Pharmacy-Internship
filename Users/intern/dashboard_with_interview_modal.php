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
$conn = $db->getConnection();
$internship = new Internship($conn);
$internship_record = $internship->getByUserId($user_id);

// Create record if doesn't exist
if (!$internship_record) {
    $internship->create($user_id);
    $internship_record = $internship->getByUserId($user_id);
}

// Get notifications
require_once '../../models/notification.php';
$notification = new Notification($conn);
$unread_count = $notification->getUnreadCount($user_id);
$notifications = $notification->getByUserId($user_id, 5);

// Handle AJAX request for interview details
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_interview') {
    require_once '../../controllers/InternshipController.php';
    $application_id = $_GET['application_id'] ?? null;
    
    if ($application_id) {
        // Verify this application belongs to the user
        $sql = "SELECT * FROM internship_records WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $application_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $application = $result->fetch_assoc();
        
        if ($application) {
            $controller = new InternshipController($conn);
            $interview_details = $controller->getInterviewDetails($application_id);
            
            if ($interview_details) {
                $response = [
                    'success' => true,
                    'interview' => [
                        'date_formatted' => date('F d, Y', strtotime($interview_details['interview_date'])),
                        'time_formatted' => date('g:i A', strtotime($interview_details['interview_time'])),
                        'type' => $interview_details['interview_type'],
                        'location' => $interview_details['interview_location'] ?? '',
                        'meeting_link' => $interview_details['interview_meeting_link'] ?? '',
                        'notes' => $interview_details['interview_notes'] ?? ''
                    ]
                ];
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Interview not found']);
    exit();
}

// Handle AJAX request to mark notification as read
if (isset($_POST['ajax']) && $_POST['ajax'] === 'mark_read') {
    $notification_id = $_POST['notification_id'] ?? null;
    
    if ($notification_id) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $notification_id, $user_id);
        
        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit();
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false]);
    exit();
}
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

    <div class="dashboard-container">
        <div class="container">
            <div class="welcome-header">
                <h1><i class="bi bi-mortarboard"></i> Intern Dashboard</h1>
                <p class="mb-0">Welcome back, <?php echo htmlspecialchars($full_name); ?>!</p>
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
                        <i class="bi bi-calendar-event feature-icon"></i>
                        <h4>Interview Schedule</h4>
                        <p>View your interview details</p>
                        <a href="view_interview.php" class="btn btn-primary">View Details</a>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="feature-card">
                        <i class="bi bi-folder-fill feature-icon"></i>
                        <h4>Policies & Guidelines</h4>
                        <p>View pharmacy policies</p>
                        <a href="policies_guidelines.php" class="btn btn-primary">View Policies</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Interview Details Modal -->
    <div class="modal fade" id="interviewDetailsModal" tabindex="-1">
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
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Notification bell toggle
        const notificationBell = document.getElementById('notificationBell');
        const notificationDropdown = document.getElementById('notificationDropdown');
        
        notificationBell.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationBell.contains(e.target) && !notificationDropdown.contains(e.target)) {
                notificationDropdown.classList.remove('show');
            }
        });
        
        // Handle notification click
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.dataset.id;
                const notificationType = this.dataset.type;
                const relatedId = this.dataset.relatedId;
                
                // Mark notification as read
                fetch('?ajax=mark_read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'ajax=mark_read&notification_id=' + notificationId
                });
                
                // Handle different notification types
                if (notificationType === 'interview_scheduled') {
                    loadInterviewDetails(relatedId);
                } else if (notificationType === 'internship_schedule' || notificationType === 'internship_approved') {
                    window.location.href = 'view_schedule.php';
                }
                
                // Close notification dropdown
                notificationDropdown.classList.remove('show');
            });
        });
        
        // Load interview details
        function loadInterviewDetails(applicationId) {
            fetch('?ajax=get_interview&application_id=' + applicationId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.interview) {
                        showInterviewModal(data.interview);
                    } else {
                        alert('Unable to load interview details.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
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
            
            const modal = new bootstrap.Modal(document.getElementById('interviewDetailsModal'));
            modal.show();
        }
    </script>
</body>
</html>
