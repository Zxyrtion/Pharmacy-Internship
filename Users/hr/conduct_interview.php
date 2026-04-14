<?php
require_once '../../config.php';
require_once '../../controllers/InternshipController.php';
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

// Get application ID from URL
$application_id = $_GET['application_id'] ?? null;
if (!$application_id) {
    header('Location: internship_applications.php');
    exit();
}

// Initialize controllers
$internshipController = new InternshipController($conn);
$interviewController = new InterviewController($conn);

// Get application details
$application = $internshipController->getApplicationDetails($application_id);
if (!$application) {
    header('Location: internship_applications.php');
    exit();
}

// Check if application is approved
if ($application['status'] !== 'approved') {
    header('Location: internship_applications.php');
    exit();
}

// Get existing interviews
$existing_interviews = $interviewController->getInterviewsByApplication($application_id);

// Default interview questions for pharmacy internship
$default_questions = [
    "Why do you want to pursue an internship in pharmacy?",
    "What relevant experience or education do you have in pharmacy or healthcare?",
    "How do you handle stressful situations or high-pressure environments?",
    "What are your career goals in the pharmacy field?",
    "How do you ensure accuracy and attention to detail in your work?",
    "Describe your experience with customer service or patient care.",
    "What do you know about medication safety and proper handling?",
    "How do you stay updated with new pharmacy regulations and practices?",
    "Describe a time when you had to work as part of a team.",
    "What questions do you have about our pharmacy internship program?"
];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = [
        'application_id' => $application_id,
        'hr_id' => $user_id,
        'interview_type' => $_POST['interview_type'],
        'questions' => json_encode($_POST['questions']),
        'answers' => json_encode($_POST['answers']),
        'notes' => $_POST['notes'],
        'rating' => $_POST['rating'],
        'outcome' => $_POST['outcome'],
        'feedback' => $_POST['feedback']
    ];
    
    $result = $interviewController->createInterview($data);
    
    if ($result['success']) {
        $success = "Interview completed successfully!";
        
        // If outcome is 'Hired', create employee profile
        if ($_POST['outcome'] === 'Hired') {
            $profile_data = [
                'position' => 'Intern',
                'department' => 'Pharmacy',
                'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
                'supervisor_id' => $user_id,
                'salary' => $_POST['salary'] ?? 0
            ];
            
            $profile_result = $interviewController->createEmployeeProfile($result['interview_id'], $profile_data);
            
            if ($profile_result['success']) {
                $success .= " Employee profile created with ID: " . $profile_result['employee_id'];
            } else {
                $errors = $profile_result['errors'];
            }
        }
        
        // Refresh interviews list
        $existing_interviews = $interviewController->getInterviewsByApplication($application_id);
    } else {
        $errors = $result['errors'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conduct Interview - MediCare Pharmacy</title>
    
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
        
        .applicant-info {
            background: linear-gradient(135deg, #16a085 0%, #27ae60 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .question-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
        }
        
        .rating-stars {
            font-size: 1.5rem;
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
        
        .interview-history {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .outcome-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .outcome-pending { background: #fff3cd; color: #856404; }
        .outcome-recommended { background: #d1ecf1; color: #0c5460; }
        .outcome-not-recommended { background: #f8d7da; color: #721c24; }
        .outcome-hired { background: #d4edda; color: #155724; }
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
            <div class="interview-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-chat-dots"></i> Conduct Interview</h2>
                        <p class="text-muted mb-0">Interview with approved internship applicant</p>
                    </div>
                    <a href="internship_applications.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Applications
                    </a>
                </div>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle"></i>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Applicant Information -->
                <div class="applicant-info">
                    <h4><i class="bi bi-person-circle"></i> Applicant Information</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Name:</strong> <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($application['email']); ?><br>
                            <strong>Application Date:</strong> <?php echo date('F d, Y', strtotime($application['application_date'])); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong> <span class="badge bg-success">Approved</span><br>
                            <strong>Application ID:</strong> #<?php echo str_pad($application['id'], 6, '0', STR_PAD_LEFT); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Interview Form -->
                <form method="POST" id="interviewForm">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="interview_type" class="form-label">Interview Type</label>
                            <select class="form-select" id="interview_type" name="interview_type" required>
                                <option value="Initial Interview">Initial Interview</option>
                                <option value="Technical Interview">Technical Interview</option>
                                <option value="Final Interview">Final Interview</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="rating" class="form-label">Overall Rating (1-10)</label>
                            <input type="number" class="form-control" id="rating" name="rating" min="1" max="10" required>
                        </div>
                    </div>
                    
                    <h4><i class="bi bi-question-circle"></i> Interview Questions</h4>
                    <div id="questionsContainer">
                        <?php foreach ($default_questions as $index => $question): ?>
                            <div class="question-card">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Question <?php echo $index + 1; ?>:</strong></label>
                                    <input type="text" class="form-control" name="questions[]" value="<?php echo htmlspecialchars($question); ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Answer:</label>
                                    <textarea class="form-control" name="answers[]" rows="3" placeholder="Enter applicant's answer..."></textarea>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="outcome" class="form-label">Interview Outcome</label>
                            <select class="form-select" id="outcome" name="outcome" required onchange="toggleEmployeeFields()">
                                <option value="">Select Outcome</option>
                                <option value="Pending">Pending</option>
                                <option value="Recommended">Recommended</option>
                                <option value="Not Recommended">Not Recommended</option>
                                <option value="Hired">Hired</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="employeeFields" style="display: none;">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="feedback" class="form-label">Interview Feedback</label>
                        <textarea class="form-control" id="feedback" name="feedback" rows="4" placeholder="Provide detailed feedback about the interview..."></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any additional notes or observations..."></textarea>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle"></i> Complete Interview
                        </button>
                    </div>
                </form>
                
                <!-- Interview History -->
                <?php if (!empty($existing_interviews)): ?>
                    <div class="interview-history">
                        <h4><i class="bi bi-clock-history"></i> Previous Interviews</h4>
                        <?php foreach ($existing_interviews as $interview): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6><?php echo htmlspecialchars($interview['interview_type']); ?></h6>
                                            <p class="text-muted mb-2">
                                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($interview['hr_first_name'] . ' ' . $interview['hr_last_name']); ?>
                                                <span class="ms-3">
                                                    <i class="bi bi-calendar"></i> <?php echo date('M d, Y H:i', strtotime($interview['interview_date'])); ?>
                                                </span>
                                            </p>
                                            <?php if ($interview['feedback']): ?>
                                                <p class="mb-2"><strong>Feedback:</strong> <?php echo htmlspecialchars($interview['feedback']); ?></p>
                                            <?php endif; ?>
                                            <?php if ($interview['rating']): ?>
                                                <div class="rating-stars mb-2">
                                                    Rating: <?php echo $interview['rating']; ?>/10
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="bi bi-star<?php echo $i <= ($interview['rating'] / 2) ? '-fill' : ''; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <span class="outcome-badge outcome-<?php echo strtolower(str_replace(' ', '-', $interview['outcome'])); ?>">
                                                <?php echo htmlspecialchars($interview['outcome']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function toggleEmployeeFields() {
            const outcome = document.getElementById('outcome').value;
            const employeeFields = document.getElementById('employeeFields');
            
            if (outcome === 'Hired') {
                employeeFields.style.display = 'block';
                document.getElementById('start_date').required = true;
            } else {
                employeeFields.style.display = 'none';
                document.getElementById('start_date').required = false;
            }
        }
        
        // Set default start date to today
        document.getElementById('start_date').valueAsDate = new Date();
        
        // Form validation
        document.getElementById('interviewForm').addEventListener('submit', function(e) {
            const answers = document.querySelectorAll('textarea[name="answers[]"]');
            let hasAnswer = false;
            
            answers.forEach(function(textarea) {
                if (textarea.value.trim() !== '') {
                    hasAnswer = true;
                }
            });
            
            if (!hasAnswer) {
                e.preventDefault();
                alert('Please provide at least one answer for the interview questions.');
            }
        });
    </script>
</body>
</html>
