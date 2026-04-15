<?php
require_once '../../config.php';

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

// Get evaluation ID
$evaluation_id = $_GET['id'] ?? null;
if (!$evaluation_id) {
    header('Location: evaluate_interview.php');
    exit();
}

// Get evaluation details
$sql = "SELECT ie.*, ir.first_name, ir.last_name, ir.higher_ed_institution,
        u.email, isch.batch_number, isch.interview_date,
        hr.first_name as hr_first_name, hr.last_name as hr_last_name
        FROM interview_evaluations ie
        JOIN internship_records ir ON ie.user_id = ir.user_id
        JOIN users u ON ie.user_id = u.id
        JOIN interview_assignments ia ON ie.interview_assignment_id = ia.id
        JOIN interview_schedule isch ON ia.schedule_id = isch.id
        JOIN users hr ON ie.evaluated_by = hr.id
        WHERE ie.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $evaluation_id);
$stmt->execute();
$evaluation = $stmt->get_result()->fetch_assoc();

if (!$evaluation) {
    header('Location: evaluate_interview.php');
    exit();
}

$categories = [
    ['name' => 'education', 'label' => 'Education'],
    ['name' => 'training', 'label' => 'Training'],
    ['name' => 'work_experience', 'label' => 'Work Experience'],
    ['name' => 'company_knowledge', 'label' => 'Company Knowledge'],
    ['name' => 'technical_skills', 'label' => 'Technical Skills'],
    ['name' => 'multitasking_skills', 'label' => 'Multitasking Skills'],
    ['name' => 'communication_skills', 'label' => 'Communication Skills'],
    ['name' => 'teamwork', 'label' => 'Teamwork'],
    ['name' => 'stress_tolerance', 'label' => 'Stress Tolerance'],
    ['name' => 'culture_fit', 'label' => 'Culture Fit']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Evaluation - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .evaluation-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .evaluation-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .candidate-info {
            background: linear-gradient(135deg, #16a085 0%, #27ae60 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .rating-display {
            display: flex;
            gap: 0.25rem;
            margin-top: 0.5rem;
        }
        
        .rating-star {
            color: #ffc107;
            font-size: 1.5rem;
        }
        
        .category-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
        }
        
        .decision-badge {
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .decision-accepted { background: #d4edda; color: #155724; }
        .decision-rejected { background: #f8d7da; color: #721c24; }
        .decision-pending { background: #fff3cd; color: #856404; }
        
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
        
        @media print {
            .no-print {
                display: none;
            }
            .evaluation-container {
                background: white;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm no-print">
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

    <div class="evaluation-container">
        <div class="container">
            <div class="evaluation-card">
                <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                    <div>
                        <h2><i class="bi bi-clipboard-data"></i> Interview Evaluation Details</h2>
                        <p class="text-muted mb-0">Evaluation ID: #<?php echo str_pad($evaluation['id'], 6, '0', STR_PAD_LEFT); ?></p>
                    </div>
                    <div>
                        <button onclick="window.print()" class="btn btn-secondary me-2">
                            <i class="bi bi-printer"></i> Print
                        </button>
                        <a href="evaluate_interview.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Back to Evaluations
                        </a>
                    </div>
                </div>
                
                <!-- Candidate Information -->
                <div class="candidate-info">
                    <h4><i class="bi bi-person-circle"></i> Candidate Information</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Name:</strong> <?php echo htmlspecialchars($evaluation['first_name'] . ' ' . $evaluation['last_name']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($evaluation['email']); ?><br>
                            <strong>Institution:</strong> <?php echo htmlspecialchars($evaluation['higher_ed_institution'] ?? 'N/A'); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Batch Number:</strong> #<?php echo $evaluation['batch_number']; ?><br>
                            <strong>Interview Date:</strong> <?php echo date('F d, Y', strtotime($evaluation['interview_date'])); ?><br>
                            <strong>Evaluated By:</strong> <?php echo htmlspecialchars($evaluation['hr_first_name'] . ' ' . $evaluation['hr_last_name']); ?><br>
                            <strong>Evaluation Date:</strong> <?php echo date('F d, Y', strtotime($evaluation['created_at'])); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Final Decision -->
                <div class="text-center mb-4">
                    <h4>Final Decision</h4>
                    <span class="decision-badge decision-<?php echo $evaluation['final_decision']; ?>">
                        <?php echo strtoupper($evaluation['final_decision']); ?>
                    </span>
                    <h5 class="mt-3">Average Rating: <strong><?php echo number_format($evaluation['average_rating'], 2); ?></strong> / 5.00</h5>
                </div>
                
                <!-- Rating Categories -->
                <h4 class="mt-4 mb-3"><i class="bi bi-star"></i> Rating Categories</h4>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Category</th>
                                <th width="150">Rating</th>
                                <th>Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): 
                                $rating_field = $category['name'] . '_rating';
                                $comments_field = $category['name'] . '_comments';
                                $rating = $evaluation[$rating_field];
                            ?>
                                <tr>
                                    <td><strong><?php echo $category['label']; ?></strong></td>
                                    <td>
                                        <div class="rating-display">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star<?php echo $i <= $rating ? '-fill' : ''; ?> rating-star"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <small class="text-muted"><?php echo $rating; ?> / 5</small>
                                    </td>
                                    <td><?php echo htmlspecialchars($evaluation[$comments_field] ?: 'No comments'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Overall Evaluation -->
                <div class="category-section mt-4">
                    <h5><i class="bi bi-chat-left-text"></i> Overall Evaluation</h5>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($evaluation['overall_evaluation'])); ?></p>
                </div>
                
                <!-- Work Schedule (if accepted) -->
                <?php if ($evaluation['final_decision'] === 'accepted'): ?>
                    <div class="category-section">
                        <h5><i class="bi bi-calendar-check"></i> Work Schedule</h5>
                        <p class="mb-2">
                            <strong>Start Date:</strong> 
                            <?php echo $evaluation['work_start_date'] ? date('F d, Y', strtotime($evaluation['work_start_date'])) : 'Not specified'; ?>
                        </p>
                        <p class="mb-0">
                            <strong>Schedule Details:</strong><br>
                            <?php echo nl2br(htmlspecialchars($evaluation['work_schedule_details'] ?: 'No details provided')); ?>
                        </p>
                        <div class="alert alert-success mt-3 mb-0">
                            <i class="bi bi-check-circle"></i> 
                            Work schedule has been <?php echo $evaluation['work_schedule_sent'] ? 'sent' : 'not sent yet'; ?> to the candidate.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
