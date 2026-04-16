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

// Get intern_id from URL
$intern_id = isset($_GET['intern_id']) ? intval($_GET['intern_id']) : 0;

if (!$intern_id) {
    header('Location: view_ready_interns.php');
    exit();
}

// Initialize controller
$internshipController = new InternshipController($conn);

// Get intern details
$intern_sql = "SELECT u.*, ws.department, ws.shift_time, ws.start_date 
               FROM users u
               LEFT JOIN work_schedules ws ON u.id = ws.user_id
               WHERE u.id = ? AND u.role_id = 1";
$stmt = $conn->prepare($intern_sql);
$stmt->bind_param("i", $intern_id);
$stmt->execute();
$intern = $stmt->get_result()->fetch_assoc();

if (!$intern) {
    header('Location: view_ready_interns.php');
    exit();
}

// Get task statistics
$task_stats = $internshipController->getInternTaskStats($intern_id);

// Get all tasks
$all_tasks = $internshipController->getInternTasks($intern_id);

// Filter by status if requested
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intern Tasks - <?php echo htmlspecialchars($intern['first_name'] . ' ' . $intern['last_name']); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .container-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
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
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .stat-card.completed { background: linear-gradient(135deg, #28a745, #20c997); }
        .stat-card.pending { background: linear-gradient(135deg, #ffc107, #ff9800); }
        .stat-card.late { background: linear-gradient(135deg, #dc3545, #c0392b); }
        .stat-card.progress { background: linear-gradient(135deg, #17a2b8, #138496); }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .task-card {
            background: #f8f9fa;
            border-left: 5px solid #6c757d;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        
        .task-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .task-card.completed { border-left-color: #28a745; background: #d4edda; }
        .task-card.pending { border-left-color: #ffc107; background: #fff3cd; }
        .task-card.late { border-left-color: #dc3545; background: #f8d7da; }
        .task-card.in-progress { border-left-color: #17a2b8; background: #d1ecf1; }
        
        .priority-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .priority-low { background: #d4edda; color: #155724; }
        .priority-medium { background: #fff3cd; color: #856404; }
        .priority-high { background: #f8d7da; color: #721c24; }
        .priority-urgent { background: #dc3545; color: white; }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .status-completed { background: #28a745; color: white; }
        .status-pending { background: #ffc107; color: #000; }
        .status-late { background: #dc3545; color: white; }
        .status-progress { background: #17a2b8; color: white; }
        
        .filter-btn {
            margin: 0 0.25rem;
            border-radius: 20px;
            padding: 0.5rem 1.5rem;
        }
        
        .filter-btn.active {
            background: #667eea;
            color: white;
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
                        <a class="nav-link" href="view_ready_interns.php">
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
            <!-- Intern Info Card -->
            <div class="page-card mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3>
                            <i class="bi bi-person-badge text-primary"></i>
                            <?php echo htmlspecialchars($intern['first_name'] . ' ' . $intern['last_name']); ?>
                        </h3>
                        <p class="text-muted mb-0">
                            <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($intern['email']); ?> |
                            <i class="bi bi-building"></i> <?php echo htmlspecialchars($intern['department'] ?? 'N/A'); ?> |
                            <i class="bi bi-clock"></i> <?php echo htmlspecialchars($intern['shift_time'] ?? 'N/A'); ?>
                        </p>
                    </div>
                    <a href="view_ready_interns.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Interns
                    </a>
                </div>
            </div>
            
            <!-- Task Statistics -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card completed">
                        <span class="stat-number"><?php echo $task_stats['completed']; ?></span>
                        <span class="stat-label">
                            <i class="bi bi-check-circle-fill"></i> Finished Tasks
                        </span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card pending">
                        <span class="stat-number"><?php echo $task_stats['pending']; ?></span>
                        <span class="stat-label">
                            <i class="bi bi-clock-fill"></i> Pending Tasks
                        </span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card late">
                        <span class="stat-number"><?php echo $task_stats['late']; ?></span>
                        <span class="stat-label">
                            <i class="bi bi-exclamation-triangle-fill"></i> Late Tasks
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Tasks List -->
            <div class="page-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><i class="bi bi-list-task"></i> Task List</h4>
                    <div>
                        <a href="?intern_id=<?php echo $intern_id; ?>&status=all" 
                           class="btn btn-sm filter-btn <?php echo $filter_status === 'all' ? 'active' : 'btn-outline-secondary'; ?>">
                            All
                        </a>
                        <a href="?intern_id=<?php echo $intern_id; ?>&status=Pending" 
                           class="btn btn-sm filter-btn <?php echo $filter_status === 'Pending' ? 'active' : 'btn-outline-secondary'; ?>">
                            Pending
                        </a>
                        <a href="?intern_id=<?php echo $intern_id; ?>&status=In Progress" 
                           class="btn btn-sm filter-btn <?php echo $filter_status === 'In Progress' ? 'active' : 'btn-outline-secondary'; ?>">
                            In Progress
                        </a>
                        <a href="?intern_id=<?php echo $intern_id; ?>&status=Completed" 
                           class="btn btn-sm filter-btn <?php echo $filter_status === 'Completed' ? 'active' : 'btn-outline-secondary'; ?>">
                            Completed
                        </a>
                    </div>
                </div>
                
                <?php if (empty($all_tasks)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem; color: #6c757d;"></i>
                        <h4 class="mt-3">No Tasks Yet</h4>
                        <p class="text-muted">Tasks assigned to this intern will appear here.</p>
                    </div>
                <?php else: ?>
                    <?php 
                    $filtered_tasks = $filter_status === 'all' ? $all_tasks : array_filter($all_tasks, function($task) use ($filter_status) {
                        return $task['status'] === $filter_status;
                    });
                    ?>
                    
                    <?php if (empty($filtered_tasks)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-filter-circle" style="font-size: 4rem; color: #6c757d;"></i>
                            <h4 class="mt-3">No <?php echo $filter_status; ?> Tasks</h4>
                        </div>
                    <?php else: ?>
                        <?php foreach ($filtered_tasks as $task): ?>
                            <?php 
                            $status_class = strtolower(str_replace(' ', '-', $task['status']));
                            if ($task['is_late'] && $task['status'] !== 'Completed') {
                                $status_class = 'late';
                            }
                            ?>
                            <div class="task-card <?php echo $status_class; ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h5 class="mb-2">
                                            <?php echo htmlspecialchars($task['task_title']); ?>
                                            <?php if ($task['is_late'] && $task['status'] !== 'Completed'): ?>
                                                <span class="badge bg-danger ms-2">
                                                    <i class="bi bi-exclamation-triangle"></i> LATE
                                                </span>
                                            <?php endif; ?>
                                        </h5>
                                        <p class="mb-2 text-muted">
                                            <?php echo htmlspecialchars($task['task_description'] ?? 'No description'); ?>
                                        </p>
                                        <small class="text-muted">
                                            <i class="bi bi-person"></i> Assigned by: 
                                            <?php echo htmlspecialchars($task['first_name'] . ' ' . $task['last_name']); ?>
                                        </small>
                                    </div>
                                    <div class="col-md-2">
                                        <small class="text-muted d-block">Category:</small>
                                        <strong><?php echo htmlspecialchars($task['category'] ?? 'General'); ?></strong>
                                        <br>
                                        <span class="priority-badge priority-<?php echo strtolower($task['priority']); ?> mt-2 d-inline-block">
                                            <?php echo $task['priority']; ?>
                                        </span>
                                    </div>
                                    <div class="col-md-2">
                                        <small class="text-muted d-block">Due Date:</small>
                                        <strong><?php echo date('M d, Y', strtotime($task['due_date'])); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo date('h:i A', strtotime($task['due_date'])); ?></small>
                                        <?php if ($task['completed_date']): ?>
                                            <br>
                                            <small class="text-success">
                                                <i class="bi bi-check-circle"></i> 
                                                Completed: <?php echo date('M d, Y', strtotime($task['completed_date'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <?php 
                                        $status_badge_class = 'status-' . strtolower(str_replace(' ', '-', $task['status']));
                                        if ($task['is_late'] && $task['status'] !== 'Completed') {
                                            $status_badge_class = 'status-late';
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_badge_class; ?>">
                                            <?php echo $task['status']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
