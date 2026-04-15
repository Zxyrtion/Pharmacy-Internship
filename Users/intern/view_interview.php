<?php
require_once '../../config.php';
require_once '../../core/Database.php';
require_once '../../controllers/InternshipController.php';

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

// Get internship record
$db = new Database();
$conn = $db->getConnection();
$controller = new InternshipController($conn);

// Get user's internship record using PDO
$sql = "SELECT * FROM internship_records WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$internship_record = $stmt->fetch(PDO::FETCH_ASSOC);

// Get interview details if scheduled
$interview_details = null;
if ($internship_record && $internship_record['interview_scheduled']) {
    $interview_details = [
        'interview_date' => $internship_record['interview_date'],
        'interview_time' => $internship_record['interview_time'],
        'interview_type' => $internship_record['interview_type'],
        'interview_location' => $internship_record['interview_location'],
        'interview_meeting_link' => $internship_record['interview_meeting_link'],
        'interview_notes' => $internship_record['interview_notes']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Details - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .interview-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .interview-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            animation: scaleIn 0.5s ease-out;
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
        
        .detail-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
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
        
        .interview-type-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
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
            <a class="navbar-brand" href="dashboard.php">
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

    <div class="interview-container">
        <div class="container">
            <?php if ($interview_details): ?>
                <div class="interview-card">
                    <div class="text-center mb-4">
                        <i class="bi bi-check-circle-fill success-icon"></i>
                        <h1 class="mt-3">Interview Scheduled!</h1>
                        <p class="text-muted">Your interview has been scheduled. Here are the details:</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-card">
                                <div class="detail-label">
                                    <i class="bi bi-calendar-event"></i> Interview Date
                                </div>
                                <div class="detail-value">
                                    <?php echo date('F d, Y', strtotime($interview_details['interview_date'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="detail-card">
                                <div class="detail-label">
                                    <i class="bi bi-clock"></i> Interview Time
                                </div>
                                <div class="detail-value">
                                    <?php echo date('g:i A', strtotime($interview_details['interview_time'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-label">
                            <i class="bi bi-type"></i> Interview Type
                        </div>
                        <div class="detail-value">
                            <?php if ($interview_details['interview_type'] === 'personal'): ?>
                                <span class="interview-type-badge badge bg-primary">
                                    <i class="bi bi-person-fill"></i> Personal Interview
                                </span>
                            <?php else: ?>
                                <span class="interview-type-badge badge bg-success">
                                    <i class="bi bi-camera-video-fill"></i> Online Interview
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($interview_details['interview_type'] === 'personal' && !empty($interview_details['interview_location'])): ?>
                        <div class="detail-card">
                            <div class="detail-label">
                                <i class="bi bi-geo-alt-fill"></i> Location
                            </div>
                            <div class="detail-value">
                                <?php echo nl2br(htmlspecialchars($interview_details['interview_location'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($interview_details['interview_type'] === 'online' && !empty($interview_details['interview_meeting_link'])): ?>
                        <div class="detail-card">
                            <div class="detail-label">
                                <i class="bi bi-link-45deg"></i> Meeting Link
                            </div>
                            <div class="detail-value">
                                <a href="<?php echo htmlspecialchars($interview_details['interview_meeting_link']); ?>" 
                                   target="_blank" class="btn btn-success">
                                    <i class="bi bi-box-arrow-up-right"></i> Join Meeting
                                </a>
                                <p class="mt-2 small text-muted">
                                    <?php echo htmlspecialchars($interview_details['interview_meeting_link']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($interview_details['interview_notes'])): ?>
                        <div class="detail-card">
                            <div class="detail-label">
                                <i class="bi bi-sticky"></i> Additional Notes
                            </div>
                            <div class="detail-value">
                                <?php echo nl2br(htmlspecialchars($interview_details['interview_notes'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info mt-4">
                        <h6><i class="bi bi-info-circle"></i> Important Reminders:</h6>
                        <ul class="mb-0">
                            <li>Please arrive 10 minutes early for personal interviews</li>
                            <li>Bring a valid ID and your application documents</li>
                            <li>Dress professionally</li>
                            <?php if ($interview_details['interview_type'] === 'online'): ?>
                                <li>Test your internet connection and camera before the interview</li>
                                <li>Find a quiet place with good lighting</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="dashboard.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="interview-card text-center">
                    <i class="bi bi-calendar-x" style="font-size: 5rem; color: #6c757d;"></i>
                    <h2 class="mt-3">No Interview Scheduled</h2>
                    <p class="text-muted">You don't have any scheduled interviews at the moment.</p>
                    <a href="dashboard.php" class="btn btn-primary mt-3">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
