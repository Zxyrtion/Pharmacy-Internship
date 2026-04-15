<?php
require_once '../../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../views/auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Get user's evaluation
$sql = "SELECT ie.*, isch.batch_number, isch.interview_date,
        hr.first_name as hr_first_name, hr.last_name as hr_last_name
        FROM interview_evaluations ie
        JOIN interview_assignments ia ON ie.interview_assignment_id = ia.id
        JOIN interview_schedule isch ON ia.schedule_id = isch.id
        JOIN users hr ON ie.evaluated_by = hr.id
        WHERE ie.user_id = ?
        ORDER BY ie.created_at DESC
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$evaluation = $stmt->get_result()->fetch_assoc();

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
    <title>My Evaluation - MediCare Pharmacy</title>
    
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
        
        .result-header {
            background: linear-gradient(135deg, #16a085 0%, #27ae60 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .result-header.rejected {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
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
    <?php include 'header.php'; ?>

    <div class="evaluation-container">
        <div class="container">
            <div class="evaluation-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-clipboard-data"></i> My Interview Evaluation</h2>
                        <p class="text-muted mb-0">View your interview evaluation results</p>
                    </div>
                    <a href="dashboard.php" class="btn btn-primary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
                
                <?php if (!$evaluation): ?>
                    <div class="alert alert-info text-center py-5">
                        <i class="bi bi-info-circle" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">No Evaluation Yet</h4>
                        <p>Your interview evaluation is not available yet. Please check back later.</p>
                    </div>
                <?php else: ?>
                    <!-- Result Header -->
                    <div class="result-header <?php echo $evaluation['final_decision'] === 'rejected' ? 'rejected' : ''; ?>">
                        <h3>
                            <?php if ($evaluation['final_decision'] === 'accepted'): ?>
                                <i class="bi bi-check-circle"></i> Congratulations!
                            <?php else: ?>
                                <i class="bi bi-x-circle"></i> Interview Result
                            <?php endif; ?>
                        </h3>
                        <h4 class="mt-3">
                            <?php if ($evaluation['final_decision'] === 'accepted'): ?>
                                You have been ACCEPTED for the internship position!
                            <?php else: ?>
                                Thank you for your interest in our internship program.
                            <?php endif; ?>
                        </h4>
                        <h2 class="mt-4">Average Rating: <?php echo number_format($evaluation['average_rating'], 2); ?> / 5.00</h2>
                        <p class="mb-0">
                            <i class="bi bi-calendar"></i> Evaluated on <?php echo date('F d, Y', strtotime($evaluation['created_at'])); ?>
                        </p>
                    </div>
                    
                    <!-- Work Schedule (if accepted) -->
                    <?php if ($evaluation['final_decision'] === 'accepted' && $evaluation['work_start_date']): ?>
                        <div class="alert alert-success">
                            <h5><i class="bi bi-calendar-check"></i> Your Work Schedule</h5>
                            <p class="mb-2">
                                <strong>Start Date:</strong> <?php echo date('F d, Y', strtotime($evaluation['work_start_date'])); ?>
                            </p>
                            <?php if ($evaluation['work_schedule_details']): ?>
                                <p class="mb-0">
                                    <strong>Schedule Details:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($evaluation['work_schedule_details'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Rating Categories -->
                    <h4 class="mt-4 mb-3"><i class="bi bi-star"></i> Evaluation Breakdown</h4>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Category</th>
                                    <th width="200">Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): 
                                    $rating_field = $category['name'] . '_rating';
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
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Overall Evaluation -->
                    <div class="category-section mt-4">
                        <h5><i class="bi bi-chat-left-text"></i> Overall Feedback</h5>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($evaluation['overall_evaluation'])); ?></p>
                    </div>
                    
                    <!-- Evaluator Info -->
                    <div class="text-muted mt-3">
                        <small>
                            <i class="bi bi-person"></i> Evaluated by: <?php echo htmlspecialchars($evaluation['hr_first_name'] . ' ' . $evaluation['hr_last_name']); ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
