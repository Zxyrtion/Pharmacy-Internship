<?php
require_once '../../config.php';
require_once '../../controllers/InternshipController.php';

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

// Initialize controller
$internshipController = new InternshipController($conn);

// Get all interns with acknowledged schedules (ready to work)
$ready_sql = "SELECT ws.*, 
              u.id as intern_id, u.first_name, u.last_name, u.email,
              ie.average_rating, ie.final_decision,
              moa.id as moa_id, moa.intern_signature, moa.accepted_at as moa_accepted_at, 
              moa.ip_address, moa.moa_document_path, moa.moa_document_name,
              moa.lawyer_name, moa.lawyer_license_number
              FROM work_schedules ws
              JOIN users u ON ws.user_id = u.id
              LEFT JOIN interview_evaluations ie ON ws.evaluation_id = ie.id
              INNER JOIN moa_agreements moa ON ws.id = moa.work_schedule_id
              WHERE ws.status = 'acknowledged' AND moa.status = 'active'
              ORDER BY moa.accepted_at DESC";
$ready_result = $conn->query($ready_sql);

if (!$ready_result) {
    die("Query failed: " . $conn->error);
}

$ready_interns = [];
while ($row = $ready_result->fetch_assoc()) {
    // Get task statistics for each intern
    $row['task_stats'] = $internshipController->getInternTaskStats($row['intern_id']);
    $ready_interns[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ready Interns - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        body {
            position: relative;
            overflow-x: hidden;
        }
        
        .container-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
            position: relative;
            z-index: 1;
        }
        
        .page-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
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
        
        .intern-card {
            background: #f8f9fa;
            border-left: 5px solid #28a745;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }
        
        .intern-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .moa-badge {
            background: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-block;
        }
        
        .task-progress {
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .task-stat {
            text-align: center;
            padding: 0.5rem;
        }
        
        .task-stat .number {
            font-size: 1.8rem;
            font-weight: bold;
            display: block;
            line-height: 1;
        }
        
        .task-stat .label {
            font-size: 0.75rem;
            color: #6c757d;
            display: block;
            margin-top: 0.25rem;
        }
        
        .stat-completed { color: #28a745; }
        .stat-pending { color: #ffc107; }
        .stat-late { color: #dc3545; }
        .stat-progress { color: #17a2b8; }
        
        .progress-bar-custom {
            height: 8px;
            border-radius: 10px;
            background: #e9ecef;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s ease;
        }
        
        .btn-view-tasks {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .btn-view-tasks:hover {
            background: #5568d3;
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
                        <a class="nav-link" href="evaluate_interview.php">
                            <i class="bi bi-clipboard-check"></i> Evaluate
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="view_ready_interns.php">
                            <i class="bi bi-people-fill"></i> Ready Interns
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

    <div class="container-page">
        <div class="container">
            <div class="page-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-people-fill text-success"></i> Ready to Work Interns</h2>
                        <p class="text-muted mb-0">Interns who have accepted their work schedule and signed the MOA</p>
                    </div>
                    <div class="text-end">
                        <h3 class="mb-0"><?php echo count($ready_interns); ?></h3>
                        <small class="text-muted">Total Ready</small>
                    </div>
                </div>
                
                <?php if (empty($ready_interns)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem; color: #6c757d;"></i>
                        <h4 class="mt-3">No Ready Interns Yet</h4>
                        <p class="text-muted">Interns who accept their schedules will appear here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($ready_interns as $intern): ?>
                        <div class="intern-card">
                            <!-- First Row: Basic Info -->
                            <div class="row align-items-center mb-3">
                                <div class="col-md-4">
                                    <h5 class="mb-1">
                                        <i class="bi bi-person-check-fill text-success"></i>
                                        <?php echo htmlspecialchars($intern['first_name'] . ' ' . $intern['last_name']); ?>
                                    </h5>
                                    <p class="mb-0 text-muted">
                                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($intern['email']); ?>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Department:</small>
                                    <p class="mb-0"><strong><?php echo htmlspecialchars($intern['department']); ?></strong></p>
                                    <small class="text-muted">Shift:</small>
                                    <p class="mb-0"><?php echo htmlspecialchars($intern['shift_time']); ?></p>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Start Date:</small>
                                    <p class="mb-0"><strong><?php echo date('M d, Y', strtotime($intern['start_date'])); ?></strong></p>
                                    <small class="text-muted">MOA Signed:</small>
                                    <p class="mb-0"><?php echo date('M d, Y', strtotime($intern['moa_accepted_at'])); ?></p>
                                </div>
                                <div class="col-md-2 text-end">
                                    <span class="moa-badge d-block">
                                        <i class="bi bi-check-circle-fill"></i> MOA Signed
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Second Row: Task Progress -->
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="task-progress">
                                        <div class="row g-3">
                                            <div class="col-4 task-stat">
                                                <span class="number stat-completed"><?php echo $intern['task_stats']['completed']; ?></span>
                                                <span class="label">Finished</span>
                                            </div>
                                            <div class="col-4 task-stat">
                                                <span class="number stat-pending"><?php echo $intern['task_stats']['pending']; ?></span>
                                                <span class="label">Pending</span>
                                            </div>
                                            <div class="col-4 task-stat">
                                                <span class="number stat-late"><?php echo $intern['task_stats']['late']; ?></span>
                                                <span class="label">Late</span>
                                            </div>
                                        </div>
                                        <?php 
                                        $total = $intern['task_stats']['total'];
                                        $completed = $intern['task_stats']['completed'];
                                        $progress_percent = $total > 0 ? ($completed / $total) * 100 : 0;
                                        ?>
                                        <div class="progress-bar-custom">
                                            <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%"></div>
                                        </div>
                                        <small class="text-muted d-block text-center mt-1">
                                            <?php echo round($progress_percent); ?>% Complete (<?php echo $completed; ?>/<?php echo $total; ?> tasks)
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <a href="view_intern_tasks.php?intern_id=<?php echo $intern['intern_id']; ?>" 
                                       class="btn btn-view-tasks w-100 mb-2">
                                        <i class="bi bi-list-task"></i> View All Tasks
                                    </a>
                                    <?php if ($intern['moa_document_path']): ?>
                                        <a href="<?php echo $intern['moa_document_path']; ?>" target="_blank" 
                                           class="btn btn-sm btn-success w-100">
                                            <i class="bi bi-file-earmark-pdf"></i> View MOA Document
                                        </a>
                                    <?php else: ?>
                                        <a href="upload_moa_document.php?moa_id=<?php echo $intern['moa_id']; ?>" 
                                           class="btn btn-sm btn-warning w-100">
                                            <i class="bi bi-upload"></i> Upload MOA
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-person-check"></i> Intern Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
