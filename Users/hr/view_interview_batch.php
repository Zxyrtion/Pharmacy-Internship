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

// Get schedule ID
$schedule_id = $_GET['id'] ?? null;
if (!$schedule_id) {
    header('Location: interview_schedule.php');
    exit();
}

// Handle status updates
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_status') {
            $new_status = $_POST['status'];
            $sql = "UPDATE interview_schedule SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $new_status, $schedule_id);
            
            if ($stmt->execute()) {
                $success = "Interview status updated to: " . ucfirst($new_status);
            } else {
                $error = "Failed to update status.";
            }
        } elseif ($_POST['action'] === 'update_assignment_status') {
            $assignment_id = $_POST['assignment_id'];
            $assignment_status = $_POST['assignment_status'];
            
            $sql = "UPDATE interview_assignments SET assignment_status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $assignment_status, $assignment_id);
            
            if ($stmt->execute()) {
                $success = "Assignment status updated successfully.";
            } else {
                $error = "Failed to update assignment status.";
            }
        }
    }
}

// Get schedule details
$schedule_sql = "SELECT s.*, u.first_name, u.last_name
                 FROM interview_schedule s
                 LEFT JOIN users u ON s.created_by = u.id
                 WHERE s.id = ?";
$schedule_stmt = $conn->prepare($schedule_sql);
$schedule_stmt->bind_param("i", $schedule_id);
$schedule_stmt->execute();
$schedule = $schedule_stmt->get_result()->fetch_assoc();

if (!$schedule) {
    header('Location: interview_schedule.php');
    exit();
}

// Get assigned applicants
$assignments_sql = "SELECT ia.*, ir.first_name, ir.last_name, ir.higher_ed_institution,
                    u.email, ie.id as evaluation_id, ie.average_rating, ie.final_decision
                    FROM interview_assignments ia
                    JOIN internship_records ir ON ia.internship_record_id = ir.id
                    JOIN users u ON ia.user_id = u.id
                    LEFT JOIN interview_evaluations ie ON ia.id = ie.interview_assignment_id
                    WHERE ia.schedule_id = ?
                    ORDER BY ir.last_name, ir.first_name";
$assignments_stmt = $conn->prepare($assignments_sql);
$assignments_stmt->bind_param("i", $schedule_id);
$assignments_stmt->execute();
$assignments_result = $assignments_stmt->get_result();
$assignments = [];
while ($row = $assignments_result->fetch_assoc()) {
    $assignments[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Batch Details - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .batch-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .batch-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .schedule-info {
            background: linear-gradient(135deg, #16a085 0%, #27ae60 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
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
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
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

    <div class="batch-container">
        <div class="container">
            <div class="batch-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-calendar-event"></i> Interview Batch #<?php echo $schedule['batch_number']; ?></h2>
                        <p class="text-muted mb-0">Manage interview batch and assignments</p>
                    </div>
                    <a href="interview_schedule.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Schedule
                    </a>
                </div>
                
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
                
                <!-- Schedule Information -->
                <div class="schedule-info">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="bi bi-info-circle"></i> Schedule Details</h5>
                            <p class="mb-1">
                                <strong>Date:</strong> <?php echo date('F d, Y', strtotime($schedule['interview_date'])); ?><br>
                                <strong>Time:</strong> <?php echo date('h:i A', strtotime($schedule['interview_time'])); ?><br>
                                <strong>Type:</strong> <?php echo ucfirst($schedule['interview_type']); ?>
                            </p>
                            <?php if ($schedule['interview_type'] === 'online'): ?>
                                <?php if ($schedule['online_meeting_link']): ?>
                                    <p class="mb-1">
                                        <strong>Meeting Link:</strong> 
                                        <a href="<?php echo htmlspecialchars($schedule['online_meeting_link']); ?>" 
                                           target="_blank" class="text-white text-decoration-underline">
                                            Join Meeting
                                        </a>
                                    </p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="mb-1">
                                    <strong>Location:</strong> <?php echo htmlspecialchars($schedule['location']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="bi bi-people"></i> Capacity</h5>
                            <h3><?php echo count($assignments); ?> / <?php echo $schedule['max_slots']; ?></h3>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-light" 
                                     style="width: <?php echo (count($assignments) / $schedule['max_slots']) * 100; ?>%">
                                </div>
                            </div>
                            <p class="mt-2 mb-0">
                                <strong>Status:</strong> 
                                <span class="badge bg-light text-dark"><?php echo ucfirst($schedule['status']); ?></span>
                            </p>
                        </div>
                    </div>
                    <?php if ($schedule['notes']): ?>
                        <hr class="bg-light">
                        <p class="mb-0">
                            <strong><i class="bi bi-sticky"></i> Notes:</strong> 
                            <?php echo htmlspecialchars($schedule['notes']); ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Update Status -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-gear"></i> Update Interview Status</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="action" value="update_status">
                            <div class="col-md-8">
                                <select class="form-select" name="status" required>
                                    <option value="scheduled" <?php echo $schedule['status'] === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="ongoing" <?php echo $schedule['status'] === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                    <option value="completed" <?php echo $schedule['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $schedule['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-check-circle"></i> Update Status
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Assigned Applicants -->
                <h4 class="mt-4 mb-3"><i class="bi bi-people-fill"></i> Assigned Applicants</h4>
                
                <?php if (empty($assignments)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No applicants assigned to this batch yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Institution</th>
                                    <th>Status</th>
                                    <th>Evaluation</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $index => $assignment): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['email']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['higher_ed_institution'] ?? 'N/A'); ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="update_assignment_status">
                                                <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                                <select class="form-select form-select-sm" 
                                                        name="assignment_status" 
                                                        onchange="this.form.submit()"
                                                        style="width: auto;">
                                                    <option value="assigned" <?php echo $assignment['assignment_status'] === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                                    <option value="confirmed" <?php echo $assignment['assignment_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                    <option value="completed" <?php echo $assignment['assignment_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="no_show" <?php echo $assignment['assignment_status'] === 'no_show' ? 'selected' : ''; ?>>No Show</option>
                                                    <option value="cancelled" <?php echo $assignment['assignment_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <?php if ($assignment['evaluation_id']): ?>
                                                <span class="badge bg-<?php 
                                                    echo $assignment['final_decision'] === 'accepted' ? 'success' : 
                                                         ($assignment['final_decision'] === 'rejected' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo ucfirst($assignment['final_decision']); ?>
                                                    (<?php echo number_format($assignment['average_rating'], 2); ?>)
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Not Evaluated</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($assignment['evaluation_id']): ?>
                                                <a href="view_evaluation.php?id=<?php echo $assignment['evaluation_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                            <?php elseif ($assignment['assignment_status'] === 'completed'): ?>
                                                <a href="evaluate_interview.php" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-star"></i> Evaluate
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
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
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
