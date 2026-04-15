<?php
require_once '../../config.php';
require_once '../../controllers/InterviewController.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../views/auth/login.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'HR Personnel') {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];

// Initialize controller
$controller = new InterviewController($conn);

// Get specific employee profile if ID is provided
$employee_id = $_GET['id'] ?? null;
$employee_profile = null;

if ($employee_id) {
    $employee_profile = $controller->getEmployeeProfile($employee_id);
    if (!$employee_profile) {
        header('Location: employee_profiles.php');
        exit();
    }
}

// Get all employee profiles (hired employees)
$all_profiles = $controller->getAllEmployeeProfiles();

// Get all approved applicants from internship_records
$approved_query = "SELECT ir.*, u.email 
                  FROM internship_records ir
                  LEFT JOIN users u ON ir.user_id = u.id
                  WHERE ir.application_status = 'approved'
                  ORDER BY ir.updated_at DESC";
$approved_stmt = $conn->prepare($approved_query);
$approved_stmt->execute();
$approved_result = $approved_stmt->get_result();
$approved_applicants = [];
while ($row = $approved_result->fetch_assoc()) {
    $approved_applicants[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Profiles - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .profiles-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .profiles-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .employee-header {
            background: linear-gradient(135deg, #16a085 0%, #27ae60 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .interview-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .question-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .rating-stars {
            font-size: 1.2rem;
            color: #ffc107;
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
        
        .employee-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .employee-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .status-active { background: #d4edda; color: #155724; }
        .status-on-leave { background: #fff3cd; color: #856404; }
        .status-terminated { background: #f8d7da; color: #721c24; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
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

    <div class="profiles-container">
        <div class="container">
            <?php if ($employee_profile): ?>
                <!-- Single Employee Profile View -->
                <div class="profiles-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2><i class="bi bi-person-badge"></i> Employee Profile</h2>
                            <p class="text-muted mb-0">Complete employee information and interview details</p>
                        </div>
                        <a href="employee_profiles.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to All Employees
                        </a>
                    </div>
                    
                    <!-- Employee Header Information -->
                    <div class="employee-header">
                        <div class="row">
                            <div class="col-md-8">
                                <h3><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($employee_profile['first_name'] . ' ' . $employee_profile['last_name']); ?></h3>
                                <p class="mb-2">
                                    <strong>Employee ID:</strong> <?php echo htmlspecialchars($employee_profile['employee_id']); ?><br>
                                    <strong>Position:</strong> <?php echo htmlspecialchars($employee_profile['position']); ?><br>
                                    <strong>Department:</strong> <?php echo htmlspecialchars($employee_profile['department']); ?><br>
                                    <strong>Email:</strong> <?php echo htmlspecialchars($employee_profile['email']); ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $employee_profile['status'])); ?>">
                                    <?php echo htmlspecialchars($employee_profile['status']); ?>
                                </span>
                                <br><br>
                                <?php if ($employee_profile['start_date']): ?>
                                    <small><strong>Start Date:</strong> <?php echo date('F d, Y', strtotime($employee_profile['start_date'])); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Interview Details Section -->
                    <div class="interview-section">
                        <h4><i class="bi bi-chat-dots"></i> Interview Details</h4>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Interview Date:</strong> <?php echo date('F d, Y H:i', strtotime($employee_profile['interview_date'])); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Interview Rating:</strong> 
                                <span class="rating-stars">
                                    <?php echo $employee_profile['rating']; ?>/10
                                    <?php 
                                    $stars = round($employee_profile['rating'] / 2);
                                    for ($i = 1; $i <= 5; $i++): 
                                        echo '<i class="bi bi-star' . ($i <= $stars ? '-fill' : '') . '"></i>';
                                    endfor; 
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($employee_profile['feedback']): ?>
                            <div class="mb-3">
                                <strong>Interview Feedback:</strong>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($employee_profile['feedback'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Interview Questions and Answers -->
                    <div class="interview-section">
                        <h4><i class="bi bi-question-circle"></i> Interview Questions & Answers</h4>
                        
                        <?php
                        // Get interview details with questions and answers
                        $interview_id = $employee_profile['interview_id'];
                        $interview_details = $controller->getInterviewById($interview_id);
                        
                        if ($interview_details && $interview_details['questions']):
                            $questions = json_decode($interview_details['questions'], true);
                            $answers = json_decode($interview_details['answers'], true);
                            
                            if ($questions && $answers && count($questions) > 0):
                                foreach ($questions as $index => $question):
                                    $answer = $answers[$index] ?? '';
                        ?>
                                    <div class="question-card">
                                        <div class="mb-3">
                                            <h6><i class="bi bi-question-circle text-primary"></i> Question <?php echo $index + 1; ?>:</h6>
                                            <p class="mb-2"><?php echo htmlspecialchars($question); ?></p>
                                        </div>
                                        <div>
                                            <h6><i class="bi bi-chat-square-text text-success"></i> Answer:</h6>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($answer)); ?></p>
                                        </div>
                                    </div>
                        <?php 
                                endforeach;
                            else:
                        ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> No interview questions and answers recorded.
                                </div>
                        <?php 
                            endif;
                        else:
                        ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> Interview details not found.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Performance Notes Section -->
                    <?php if ($employee_profile['performance_notes']): ?>
                    <div class="interview-section">
                        <h4><i class="bi bi-clipboard-check"></i> Performance Notes</h4>
                        <p><?php echo nl2br(htmlspecialchars($employee_profile['performance_notes'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
            <?php else: ?>
                <!-- All Employees List View -->
                <div class="profiles-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2><i class="bi bi-people"></i> Approved Applicants</h2>
                            <p class="text-muted mb-0">View all approved internship applicants</p>
                        </div>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                    
                    <?php if (empty($approved_applicants)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-people" style="font-size: 4rem; color: #6c757d;"></i>
                            <h4 class="mt-3">No Approved Applicants Found</h4>
                            <p class="text-muted">Approved applicants will appear here after their applications are approved.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Institution</th>
                                        <th>Date of Birth</th>
                                        <th>Approval Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($approved_applicants as $applicant): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($applicant['email']); ?></td>
                                            <td><?php echo htmlspecialchars($applicant['higher_ed_institution'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php 
                                                if ($applicant['date_of_birth']) {
                                                    echo date('M d, Y', strtotime($applicant['date_of_birth']));
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($applicant['updated_at'])); ?></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle"></i> Approved
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Hired Employees Section -->
                <?php if (!empty($all_profiles)): ?>
                <div class="profiles-card mt-4">
                    <div class="mb-4">
                        <h2><i class="bi bi-briefcase"></i> Hired Employees</h2>
                        <p class="text-muted mb-0">View all hired employee profiles and interview details</p>
                    </div>
                    
                    <div class="row">
                        <?php foreach ($all_profiles as $employee): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="employee-card" onclick="window.location.href='employee_profiles.php?id=<?php echo $employee['user_id']; ?>'">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h5>
                                            <p class="text-muted mb-0"><?php echo htmlspecialchars($employee['employee_id']); ?></p>
                                        </div>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $employee['status'])); ?>">
                                            <?php echo htmlspecialchars($employee['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="bi bi-briefcase"></i> <?php echo htmlspecialchars($employee['position']); ?>
                                            <span class="ms-3">
                                                <i class="bi bi-building"></i> <?php echo htmlspecialchars($employee['department']); ?>
                                            </span>
                                        </small>
                                    </div>
                                    
                                    <?php if ($employee['rating']): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="bi bi-star-fill text-warning"></i> Interview Rating: <?php echo $employee['rating']; ?>/10
                                        </small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($employee['start_date']): ?>
                                    <div>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> Started: <?php echo date('M d, Y', strtotime($employee['start_date'])); ?>
                                        </small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="text-end mt-3">
                                        <small class="text-primary">
                                            <i class="bi bi-arrow-right-circle"></i> View Full Profile
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
