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
$email = $_SESSION['email'];

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'mark_done') {
        // Mark interview as done/completed
        $assignment_id = $_POST['assignment_id'];
        
        $update_sql = "UPDATE interview_assignments SET assignment_status = 'completed' WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $assignment_id);
        
        if ($update_stmt->execute()) {
            $success = "Interview marked as completed! You can now evaluate it.";
        } else {
            $error = "Failed to mark interview as completed.";
        }
    } elseif ($_POST['action'] === 'submit_evaluation') {
        $assignment_id = $_POST['assignment_id'];
        $intern_user_id = $_POST['intern_user_id'];
        
        // Calculate average rating
        $ratings = [
            $_POST['education_rating'],
            $_POST['training_rating'],
            $_POST['work_experience_rating'],
            $_POST['company_knowledge_rating'],
            $_POST['technical_skills_rating'],
            $_POST['multitasking_skills_rating'],
            $_POST['communication_skills_rating'],
            $_POST['teamwork_rating'],
            $_POST['stress_tolerance_rating'],
            $_POST['culture_fit_rating']
        ];
        
        $average_rating = array_sum($ratings) / count($ratings);
        
        // Determine final decision based on average
        if ($average_rating >= 4.0) {
            $final_decision = 'accepted';
        } elseif ($average_rating <= 3.0) {
            $final_decision = 'rejected';
        } else {
            $final_decision = 'pending';
        }
        
        // Insert evaluation
        $sql = "INSERT INTO interview_evaluations (
            interview_assignment_id, user_id, evaluated_by,
            education_rating, education_comments,
            training_rating, training_comments,
            work_experience_rating, work_experience_comments,
            company_knowledge_rating, company_knowledge_comments,
            technical_skills_rating, technical_skills_comments,
            multitasking_skills_rating, multitasking_skills_comments,
            communication_skills_rating, communication_skills_comments,
            teamwork_rating, teamwork_comments,
            stress_tolerance_rating, stress_tolerance_comments,
            culture_fit_rating, culture_fit_comments,
            average_rating, overall_evaluation, final_decision,
            work_start_date, work_schedule_details, work_schedule_sent
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            $error = "Database error: " . $conn->error;
        } else {
            $work_schedule_sent = 0; // Will be updated later when schedule is created
            $work_start_date = null;
            $work_schedule_details = null;
            
            // Type string: i=integer, s=string, d=double
            // 3 ints (assignment_id, user_id, evaluated_by)
            // 10 categories: each has int (rating) + string (comments) = isis isis isis isis isis isis isis isis isis isis (20 params)
            // 1 double (average_rating)
            // 2 strings (overall_evaluation, final_decision)
            // 2 strings (work_start_date, work_schedule_details) - can be null
            // 1 int (work_schedule_sent)
            // Total: 3 + 20 + 1 + 2 + 2 + 1 = 29 parameters
            // Type string: iii + isis*10 + d + ss + ss + i = "iiiisisisisisisisisisisidsssi"
            
            $stmt->bind_param("iiiisisisisisisisisisisidsssi",
                $assignment_id, $intern_user_id, $user_id,
                $_POST['education_rating'], $_POST['education_comments'],
                $_POST['training_rating'], $_POST['training_comments'],
                $_POST['work_experience_rating'], $_POST['work_experience_comments'],
                $_POST['company_knowledge_rating'], $_POST['company_knowledge_comments'],
                $_POST['technical_skills_rating'], $_POST['technical_skills_comments'],
                $_POST['multitasking_skills_rating'], $_POST['multitasking_skills_comments'],
                $_POST['communication_skills_rating'], $_POST['communication_comments'],
                $_POST['teamwork_rating'], $_POST['teamwork_comments'],
                $_POST['stress_tolerance_rating'], $_POST['stress_tolerance_comments'],
                $_POST['culture_fit_rating'], $_POST['culture_fit_comments'],
                $average_rating, $_POST['overall_evaluation'], $final_decision,
                $work_start_date, $work_schedule_details, $work_schedule_sent
            );
            
            if ($stmt->execute()) {
                $evaluation_id = $conn->insert_id;
                
                // Update interview assignment status
                $update_sql = "UPDATE interview_assignments SET assignment_status = 'completed' WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $assignment_id);
                $update_stmt->execute();
                
                // Set session variables for modal
                $_SESSION['evaluation_success'] = true;
                $_SESSION['evaluation_decision'] = $final_decision;
                $_SESSION['evaluation_rating'] = $average_rating;
                
                // If accepted (rating 4-5), store evaluation ID for work schedule
                if ($final_decision === 'accepted') {
                    $_SESSION['redirect_to_schedule'] = $evaluation_id;
                } else {
                    // If rejected, update status immediately
                    $status_sql = "UPDATE internship_records SET application_status = 'rejected' WHERE user_id = ?";
                    $status_stmt = $conn->prepare($status_sql);
                    $status_stmt->bind_param("i", $intern_user_id);
                    $status_stmt->execute();
                }
                
                // Redirect back to same page to show modal
                header("Location: evaluate_interview.php");
                exit();
                
            } else {
                $error = "Failed to submit evaluation: " . $stmt->error;
            }
        }
    }
}

// Get scheduled/confirmed interviews (not yet marked as done)
$scheduled_sql = "SELECT ia.*, ir.first_name, ir.last_name, ir.higher_ed_institution,
                  isch.interview_date, isch.interview_time, isch.batch_number,
                  u.email
                  FROM interview_assignments ia
                  JOIN internship_records ir ON ia.internship_record_id = ir.id
                  JOIN interview_schedule isch ON ia.schedule_id = isch.id
                  JOIN users u ON ia.user_id = u.id
                  WHERE ia.assignment_status IN ('assigned', 'confirmed')
                  ORDER BY isch.interview_date ASC, isch.interview_time ASC";
$scheduled_result = $conn->query($scheduled_sql);
$scheduled_interviews = [];
while ($row = $scheduled_result->fetch_assoc()) {
    $scheduled_interviews[] = $row;
}

// Get completed interviews that haven't been evaluated yet
$interviews_sql = "SELECT ia.*, ir.first_name, ir.last_name, ir.higher_ed_institution,
                   isch.interview_date, isch.interview_time, isch.batch_number,
                   u.email
                   FROM interview_assignments ia
                   JOIN internship_records ir ON ia.internship_record_id = ir.id
                   JOIN interview_schedule isch ON ia.schedule_id = isch.id
                   JOIN users u ON ia.user_id = u.id
                   LEFT JOIN interview_evaluations ie ON ia.id = ie.interview_assignment_id
                   WHERE ia.assignment_status = 'completed' 
                   AND ie.id IS NULL
                   ORDER BY isch.interview_date DESC";
$interviews_result = $conn->query($interviews_sql);
$completed_interviews = [];
while ($row = $interviews_result->fetch_assoc()) {
    $completed_interviews[] = $row;
}

// Get evaluated interviews
$evaluated_sql = "SELECT ie.*, ir.first_name, ir.last_name, u.email,
                  isch.batch_number, isch.interview_date
                  FROM interview_evaluations ie
                  JOIN internship_records ir ON ie.user_id = ir.user_id
                  JOIN users u ON ie.user_id = u.id
                  JOIN interview_assignments ia ON ie.interview_assignment_id = ia.id
                  JOIN interview_schedule isch ON ia.schedule_id = isch.id
                  ORDER BY ie.created_at DESC";
$evaluated_result = $conn->query($evaluated_sql);
$evaluated_interviews = [];
while ($row = $evaluated_result->fetch_assoc()) {
    $evaluated_interviews[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Evaluation - MediCare Pharmacy</title>
    
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
        
        .rating-scale {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .rating-scale input[type="radio"] {
            display: none;
        }
        
        .rating-scale label {
            padding: 0.5rem 1rem;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            min-width: 60px;
        }
        
        .rating-scale input[type="radio"]:checked + label {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .rating-scale label:hover {
            border-color: #667eea;
        }
        
        .category-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
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
        
        .decision-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .decision-accepted { background: #d4edda; color: #155724; }
        .decision-rejected { background: #f8d7da; color: #721c24; }
        .decision-pending { background: #fff3cd; color: #856404; }
        
        /* Make modal body scrollable */
        #evaluationModal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        
        /* Ensure modal footer is always visible */
        #evaluationModal .modal-footer {
            position: sticky;
            bottom: 0;
            background: white;
            z-index: 1000;
            border-top: 2px solid #dee2e6;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-hospital"></i> MediCare Pharmacy
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="internship_applications.php">
                            <i class="bi bi-file-earmark-text"></i> Applications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="interview_schedule.php">
                            <i class="bi bi-calendar-check"></i> Interview Schedule
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="evaluate_interview.php">
                            <i class="bi bi-clipboard-check"></i> Evaluate Interviews
                        </a>
                    </li>
                </ul>
                
                <div class="navbar-nav ms-auto">
                    <span class="navbar-text me-3">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($full_name); ?>
                    </span>
                    <a href="../logout.php" class="btn btn-logout">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="evaluation-container">
        <div class="container">
            <div class="evaluation-card">
                <h2><i class="bi bi-clipboard-check"></i> Interview Evaluation</h2>
                <p class="text-muted mb-4">Rate completed interviews and make hiring decisions</p>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Scheduled Interviews (Not Yet Done) -->
                <h4 class="mt-4 mb-3"><i class="bi bi-calendar-check"></i> Scheduled Interviews</h4>
                
                <?php if (empty($scheduled_interviews)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No scheduled interviews at the moment.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Batch</th>
                                    <th>Intern Name</th>
                                    <th>Email</th>
                                    <th>Institution</th>
                                    <th>Interview Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($scheduled_interviews as $interview): ?>
                                    <tr>
                                        <td>#<?php echo $interview['batch_number']; ?></td>
                                        <td><?php echo htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($interview['email']); ?></td>
                                        <td><?php echo htmlspecialchars($interview['higher_ed_institution'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($interview['interview_date'] . ' ' . $interview['interview_time'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $interview['assignment_status'] === 'confirmed' ? 'success' : 'info'; ?>">
                                                <?php echo ucfirst($interview['assignment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="mark_done">
                                                <input type="hidden" name="assignment_id" value="<?php echo $interview['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success" 
                                                        onclick="return confirm('Mark this interview as completed?')">
                                                    <i class="bi bi-check-circle"></i> Done Interview
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <!-- Pending Evaluations -->
                <h4 class="mt-4 mb-3"><i class="bi bi-hourglass-split"></i> Pending Evaluations</h4>
                
                <?php if (empty($completed_interviews)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No completed interviews pending evaluation.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Batch</th>
                                    <th>Intern Name</th>
                                    <th>Email</th>
                                    <th>Institution</th>
                                    <th>Interview Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completed_interviews as $interview): ?>
                                    <tr>
                                        <td>#<?php echo $interview['batch_number']; ?></td>
                                        <td><?php echo htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($interview['email']); ?></td>
                                        <td><?php echo htmlspecialchars($interview['higher_ed_institution'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($interview['interview_date'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" 
                                                    onclick="openEvaluationModal(<?php echo htmlspecialchars(json_encode($interview)); ?>)">
                                                <i class="bi bi-star"></i> Evaluate
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <!-- Evaluated Interviews -->
                <h4 class="mt-5 mb-3"><i class="bi bi-check-circle"></i> Evaluated Interviews</h4>
                
                <?php if (empty($evaluated_interviews)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No evaluated interviews yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Batch</th>
                                    <th>Intern Name</th>
                                    <th>Email</th>
                                    <th>Average Rating</th>
                                    <th>Decision</th>
                                    <th>Evaluated On</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($evaluated_interviews as $eval): ?>
                                    <tr>
                                        <td>#<?php echo $eval['batch_number']; ?></td>
                                        <td><?php echo htmlspecialchars($eval['first_name'] . ' ' . $eval['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($eval['email']); ?></td>
                                        <td>
                                            <strong><?php echo number_format($eval['average_rating'], 2); ?></strong>/5.00
                                        </td>
                                        <td>
                                            <span class="decision-badge decision-<?php echo $eval['final_decision']; ?>">
                                                <?php echo ucfirst($eval['final_decision']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($eval['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewEvaluation(<?php echo $eval['id']; ?>)">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                            <?php if ($eval['final_decision'] === 'accepted' && $eval['work_schedule_sent'] == 0): ?>
                                                <a href="create_work_schedule.php?evaluation_id=<?php echo $eval['id']; ?>" 
                                                   class="btn btn-sm btn-success ms-1">
                                                    <i class="bi bi-calendar-week"></i> Work Schedule
                                                </a>
                                            <?php elseif ($eval['final_decision'] === 'accepted' && $eval['work_schedule_sent'] == 1): ?>
                                                <?php
                                                // Check if schedule was rejected
                                                $check_sql = "SELECT id, status FROM work_schedules WHERE evaluation_id = ? ORDER BY created_at DESC LIMIT 1";
                                                $check_stmt = $conn->prepare($check_sql);
                                                $check_stmt->bind_param("i", $eval['id']);
                                                $check_stmt->execute();
                                                $schedule_result = $check_stmt->get_result();
                                                $schedule_status = $schedule_result->fetch_assoc();
                                                ?>
                                                <?php if ($schedule_status && $schedule_status['status'] === 'rejected'): ?>
                                                    <a href="edit_work_schedule.php?schedule_id=<?php echo $schedule_status['id']; ?>" 
                                                       class="btn btn-sm btn-danger ms-1">
                                                        <i class="bi bi-exclamation-triangle"></i> Rejected - Edit
                                                    </a>
                                                <?php elseif ($schedule_status && $schedule_status['status'] === 'acknowledged'): ?>
                                                    <span class="badge bg-success ms-1">
                                                        <i class="bi bi-check-circle"></i> Acknowledged
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning ms-1">
                                                        <i class="bi bi-clock"></i> Sent
                                                    </span>
                                                    <?php if ($schedule_status): ?>
                                                        <a href="edit_work_schedule.php?schedule_id=<?php echo $schedule_status['id']; ?>" 
                                                           class="btn btn-sm btn-outline-secondary ms-1">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Evaluation Modal -->
    <div class="modal fade" id="evaluationModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-clipboard-check"></i> Interview Evaluation Form
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="evaluationForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="submit_evaluation">
                        <input type="hidden" name="assignment_id" id="assignment_id">
                        <input type="hidden" name="intern_user_id" id="intern_user_id">
                        
                        <!-- Candidate Info -->
                        <div class="alert alert-info">
                            <h6><i class="bi bi-person-circle"></i> Candidate Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Name:</strong> <span id="candidate_name"></span><br>
                                    <strong>Email:</strong> <span id="candidate_email"></span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Institution:</strong> <span id="candidate_institution"></span><br>
                                    <strong>Interview Date:</strong> <span id="interview_date"></span>
                                </div>
                            </div>
                        </div>
                        
                        <h6 class="mb-3">A/ Rate the candidate (1 = Poor, 2 = Fair, 3 = Proficient, 4 = Very Good, 5 = Excellent)</h6>
                        
                        <!-- Rating Categories -->
                        <?php 
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
                        
                        foreach ($categories as $category): ?>
                            <div class="category-card">
                                <label class="form-label"><strong><?php echo $category['label']; ?></strong></label>
                                <div class="rating-scale">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <input type="radio" 
                                               name="<?php echo $category['name']; ?>_rating" 
                                               id="<?php echo $category['name']; ?>_<?php echo $i; ?>" 
                                               value="<?php echo $i; ?>" 
                                               required>
                                        <label for="<?php echo $category['name']; ?>_<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                                <textarea class="form-control mt-2" 
                                          name="<?php echo $category['name']; ?>_comments" 
                                          rows="2" 
                                          placeholder="Comments about <?php echo strtolower($category['label']); ?>..."></textarea>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Overall Evaluation -->
                        <div class="category-card">
                            <label class="form-label"><strong>B/ Overall Evaluation</strong></label>
                            <textarea class="form-control" 
                                      name="overall_evaluation" 
                                      rows="4" 
                                      required
                                      placeholder="Provide a comprehensive evaluation of the candidate's performance during the interview..."></textarea>
                            <small class="text-muted mt-2 d-block">
                                <i class="bi bi-info-circle"></i> 
                                <strong>Note:</strong> Average rating 4.0-5.0 = Accepted (you'll create work schedule next), 
                                1.0-3.0 = Rejected
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Submit Evaluation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Evaluation Success Modal -->
    <div class="modal fade" id="evaluationSuccessModal" tabindex="-1" aria-labelledby="evaluationSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="evaluationSuccessModalLabel">
                        <i class="bi bi-check-circle-fill"></i> Evaluation Completed!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <i class="bi bi-clipboard-check-fill text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="mb-3">Interview Evaluation Submitted Successfully!</h5>
                    
                    <div class="alert alert-info text-start">
                        <h6><i class="bi bi-info-circle"></i> Evaluation Summary:</h6>
                        <ul class="mb-0">
                            <li><strong>Average Rating:</strong> <?php echo isset($_SESSION['evaluation_rating']) ? number_format($_SESSION['evaluation_rating'], 2) : ''; ?> / 5.0</li>
                            <li><strong>Decision:</strong> 
                                <?php if (isset($_SESSION['evaluation_decision'])): ?>
                                    <span class="badge bg-<?php echo $_SESSION['evaluation_decision'] === 'accepted' ? 'success' : 'danger'; ?>">
                                        <?php echo strtoupper($_SESSION['evaluation_decision']); ?>
                                    </span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                    
                    <?php if (isset($_SESSION['evaluation_decision']) && $_SESSION['evaluation_decision'] === 'accepted'): ?>
                        <p class="text-muted mb-0">
                            <i class="bi bi-calendar-week"></i> Click the button below to create the work schedule for this intern.
                        </p>
                    <?php else: ?>
                        <p class="text-muted mb-0">
                            <i class="bi bi-x-circle"></i> The intern has been notified of the decision.
                        </p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Close
                    </button>
                    <?php if (isset($_SESSION['evaluation_decision']) && $_SESSION['evaluation_decision'] === 'accepted'): ?>
                        <a href="create_work_schedule.php?evaluation_id=<?php echo $_SESSION['redirect_to_schedule'] ?? ''; ?>" class="btn btn-primary">
                            <i class="bi bi-calendar-week"></i> Create Work Schedule
                        </a>
                        <?php unset($_SESSION['redirect_to_schedule']); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function openEvaluationModal(interview) {
            document.getElementById('assignment_id').value = interview.id;
            document.getElementById('intern_user_id').value = interview.user_id;
            document.getElementById('candidate_name').textContent = interview.first_name + ' ' + interview.last_name;
            document.getElementById('candidate_email').textContent = interview.email;
            document.getElementById('candidate_institution').textContent = interview.higher_ed_institution || 'N/A';
            document.getElementById('interview_date').textContent = new Date(interview.interview_date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            // Reset form
            document.getElementById('evaluationForm').reset();
            
            // Show modal
            new bootstrap.Modal(document.getElementById('evaluationModal')).show();
        }
        
        function viewEvaluation(evaluationId) {
            // Redirect to view evaluation details page
            window.location.href = 'view_evaluation.php?id=' + evaluationId;
        }
        
        // Show evaluation success modal if evaluation was just submitted
        <?php if (isset($_SESSION['evaluation_success']) && $_SESSION['evaluation_success']): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var successModal = new bootstrap.Modal(document.getElementById('evaluationSuccessModal'));
                successModal.show();
            });
            
            // Clear session variables after showing modal
            <?php 
            unset($_SESSION['evaluation_success']); 
            unset($_SESSION['evaluation_decision']); 
            unset($_SESSION['evaluation_rating']);
            // Don't unset redirect_to_schedule yet, needed for the button
            ?>
        <?php endif; ?>
    </script>
</body>
</html>
