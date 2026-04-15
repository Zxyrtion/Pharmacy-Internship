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

// Handle form submissions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_schedule') {
            // Create new interview schedule
            $batch_number = $_POST['batch_number'];
            $interview_date = $_POST['interview_date'];
            $interview_time = $_POST['interview_time'];
            $interview_type = $_POST['interview_type'];
            $location = $_POST['location'] ?? null;
            $online_meeting_link = $_POST['online_meeting_link'] ?? null;
            $online_meeting_id = $_POST['online_meeting_id'] ?? null;
            $online_meeting_password = $_POST['online_meeting_password'] ?? null;
            $max_slots = $_POST['max_slots'] ?? 15;
            $notes = $_POST['notes'] ?? '';
            
            $sql = "INSERT INTO interview_schedule 
                    (batch_number, interview_date, interview_time, interview_type, location, 
                     online_meeting_link, online_meeting_id, online_meeting_password, max_slots, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            if ($stmt === false) {
                $error = "Database error: " . $conn->error;
            } else {
                $stmt->bind_param("isssssssisi", 
                    $batch_number, $interview_date, $interview_time, $interview_type, $location,
                    $online_meeting_link, $online_meeting_id, $online_meeting_password, 
                    $max_slots, $notes, $user_id
                );
                
                if ($stmt->execute()) {
                    $success = "Interview schedule created successfully!";
                } else {
                    $error = "Failed to create interview schedule: " . $stmt->error;
                }
            }
        } elseif ($_POST['action'] === 'assign_applicants') {
            // Assign applicants to interview schedule
            $schedule_id = $_POST['schedule_id'];
            $selected_applicants = $_POST['applicants'] ?? [];
            
            if (!empty($selected_applicants)) {
                // Get interview schedule details for notification
                $sched_sql = "SELECT interview_date, interview_time, interview_type, location, online_meeting_link, batch_number 
                              FROM interview_schedule WHERE id = ?";
                $sched_stmt = $conn->prepare($sched_sql);
                $sched_stmt->bind_param("i", $schedule_id);
                $sched_stmt->execute();
                $schedule_info = $sched_stmt->get_result()->fetch_assoc();
                
                // Load notification model
                require_once '../../models/notification.php';
                require_once '../../core/Database.php';
                $db = new Database();
                $pdo_conn = $db->getConnection();
                $notification = new Notification($pdo_conn);
                
                $assigned_count = 0;
                foreach ($selected_applicants as $applicant_id) {
                    // Get internship record id
                    $record_sql = "SELECT id FROM internship_records WHERE user_id = ?";
                    $record_stmt = $conn->prepare($record_sql);
                    
                    if ($record_stmt === false) {
                        continue; // Skip this applicant if prepare fails
                    }
                    
                    $record_stmt->bind_param("i", $applicant_id);
                    $record_stmt->execute();
                    $record_result = $record_stmt->get_result();
                    $record = $record_result->fetch_assoc();
                    
                    if ($record) {
                        $sql = "INSERT INTO interview_assignments (schedule_id, user_id, internship_record_id) 
                                VALUES (?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        
                        if ($stmt !== false) {
                            $stmt->bind_param("iii", $schedule_id, $applicant_id, $record['id']);
                            
                            if ($stmt->execute()) {
                                $assigned_count++;
                                
                                // Create notification for intern
                                $interview_date_formatted = date('F d, Y', strtotime($schedule_info['interview_date']));
                                $interview_time_formatted = date('h:i A', strtotime($schedule_info['interview_time']));
                                
                                $notification_message = "You have been scheduled for an interview on {$interview_date_formatted} at {$interview_time_formatted}. ";
                                if ($schedule_info['interview_type'] === 'online') {
                                    $notification_message .= "This is an online interview. Check your dashboard for meeting details.";
                                } else {
                                    $notification_message .= "Location: {$schedule_info['location']}";
                                }
                                
                                $notification->create(
                                    $applicant_id,
                                    'interview_scheduled',
                                    $notification_message,
                                    $schedule_id
                                );
                            }
                        }
                    }
                }
                
                // Update filled slots
                $update_sql = "UPDATE interview_schedule SET filled_slots = filled_slots + ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ii", $assigned_count, $schedule_id);
                $update_stmt->execute();
                
                $success = "$assigned_count applicant(s) assigned to interview schedule and notified!";
                
                // Set session variables for modal
                $_SESSION['interview_assigned_success'] = true;
                $_SESSION['interview_assigned_count'] = $assigned_count;
                $_SESSION['interview_date'] = $schedule_info['interview_date'];
                $_SESSION['interview_time'] = $schedule_info['interview_time'];
                $_SESSION['last_schedule_id'] = $schedule_id;
            } else {
                $error = "Please select at least one applicant.";
            }
        }
    }
}

// Get all interview schedules
$schedules_sql = "SELECT s.*, u.first_name, u.last_name,
                  (SELECT COUNT(*) FROM interview_assignments WHERE schedule_id = s.id) as actual_filled
                  FROM interview_schedule s
                  LEFT JOIN users u ON s.created_by = u.id
                  ORDER BY s.interview_date DESC, s.interview_time DESC";
$schedules_result = $conn->query($schedules_sql);

if (!$schedules_result) {
    die("Error in schedules query: " . $conn->error);
}

$schedules = [];
while ($row = $schedules_result->fetch_assoc()) {
    $schedules[] = $row;
}

// Get approved applicants without interview assignment
$unassigned_sql = "SELECT ir.*, u.email 
                   FROM internship_records ir
                   LEFT JOIN users u ON ir.user_id = u.id
                   LEFT JOIN interview_assignments ia ON ir.user_id = ia.user_id
                   WHERE ir.application_status = 'approved' AND ia.id IS NULL
                   ORDER BY ir.updated_at ASC";
$unassigned_result = $conn->query($unassigned_sql);

if (!$unassigned_result) {
    die("Error in unassigned query: " . $conn->error);
}

$unassigned_applicants = [];
while ($row = $unassigned_result->fetch_assoc()) {
    $unassigned_applicants[] = $row;
}

// Get next batch number
$batch_sql = "SELECT COALESCE(MAX(batch_number), 0) + 1 as next_batch FROM interview_schedule";
$batch_result = $conn->query($batch_sql);
$next_batch = $batch_result->fetch_assoc()['next_batch'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Schedule - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .schedule-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .schedule-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .batch-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 5px solid #667eea;
        }
        
        .batch-card.full {
            border-left-color: #e74c3c;
        }
        
        .batch-card.completed {
            border-left-color: #27ae60;
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
            padding: 0.375rem 0.75rem;
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
                        <a class="nav-link active" href="interview_schedule.php">
                            <i class="bi bi-calendar-check"></i> Interview Schedule
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

    <div class="schedule-container">
        <div class="container">
            <div class="schedule-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-calendar-check"></i> Interview Schedule Management</h2>
                        <p class="text-muted mb-0">Create and manage interview batches (15 persons per batch)</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createScheduleModal">
                        <i class="bi bi-plus-circle"></i> Create New Schedule
                    </button>
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
                
                <!-- Unassigned Applicants Alert -->
                <?php if (count($unassigned_applicants) > 0): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i> 
                        <strong><?php echo count($unassigned_applicants); ?> approved applicant(s)</strong> waiting for interview assignment.
                    </div>
                <?php endif; ?>
                
                <!-- Interview Schedules -->
                <h4 class="mt-4 mb-3"><i class="bi bi-list-check"></i> Interview Batches</h4>
                
                <?php if (empty($schedules)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x" style="font-size: 4rem; color: #6c757d;"></i>
                        <h4 class="mt-3">No Interview Schedules Yet</h4>
                        <p class="text-muted">Create your first interview schedule to get started.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($schedules as $schedule): ?>
                        <?php 
                        $is_full = $schedule['actual_filled'] >= $schedule['max_slots'];
                        $is_completed = $schedule['status'] === 'completed';
                        $card_class = $is_completed ? 'completed' : ($is_full ? 'full' : '');
                        ?>
                        <div class="batch-card <?php echo $card_class; ?>">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5>
                                        <i class="bi bi-calendar-event"></i> Batch #<?php echo $schedule['batch_number']; ?>
                                        <?php if ($schedule['interview_type'] === 'online'): ?>
                                            <span class="badge bg-info ms-2">
                                                <i class="bi bi-camera-video"></i> Online
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary ms-2">
                                                <i class="bi bi-person"></i> Personal
                                            </span>
                                        <?php endif; ?>
                                    </h5>
                                    <p class="mb-1">
                                        <strong>Date:</strong> <?php echo date('F d, Y', strtotime($schedule['interview_date'])); ?><br>
                                        <strong>Time:</strong> <?php echo date('h:i A', strtotime($schedule['interview_time'])); ?><br>
                                        <?php if ($schedule['interview_type'] === 'online'): ?>
                                            <strong>Meeting Link:</strong> 
                                            <?php if ($schedule['online_meeting_link']): ?>
                                                <a href="<?php echo htmlspecialchars($schedule['online_meeting_link']); ?>" target="_blank" class="text-primary">
                                                    <i class="bi bi-link-45deg"></i> Join Meeting
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Not set</span>
                                            <?php endif; ?>
                                            <br>
                                            <?php if ($schedule['online_meeting_id']): ?>
                                                <strong>Meeting ID:</strong> <?php echo htmlspecialchars($schedule['online_meeting_id']); ?><br>
                                            <?php endif; ?>
                                            <?php if ($schedule['online_meeting_password']): ?>
                                                <strong>Password:</strong> <?php echo htmlspecialchars($schedule['online_meeting_password']); ?><br>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <strong>Location:</strong> <?php echo htmlspecialchars($schedule['location']); ?>
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($schedule['notes']): ?>
                                        <p class="mb-0 small text-muted">
                                            <i class="bi bi-info-circle"></i> <?php echo htmlspecialchars($schedule['notes']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3 text-center">
                                    <h3 class="mb-0"><?php echo $schedule['actual_filled']; ?>/<?php echo $schedule['max_slots']; ?></h3>
                                    <small class="text-muted">Slots Filled</small>
                                    <div class="progress mt-2" style="height: 10px;">
                                        <div class="progress-bar <?php echo $is_full ? 'bg-danger' : 'bg-success'; ?>" 
                                             style="width: <?php echo ($schedule['actual_filled'] / $schedule['max_slots']) * 100; ?>%"></div>
                                    </div>
                                </div>
                                <div class="col-md-3 text-end">
                                    <span class="badge bg-<?php 
                                        echo $schedule['status'] === 'completed' ? 'success' : 
                                             ($schedule['status'] === 'cancelled' ? 'danger' : 
                                             ($schedule['status'] === 'ongoing' ? 'warning' : 'info')); 
                                    ?> mb-2">
                                        <?php echo ucfirst($schedule['status']); ?>
                                    </span><br>
                                    
                                    <?php if (!$is_full && $schedule['status'] === 'scheduled'): ?>
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="openAssignModal(<?php echo $schedule['id']; ?>, <?php echo $schedule['batch_number']; ?>, <?php echo $schedule['max_slots'] - $schedule['actual_filled']; ?>)">
                                            <i class="bi bi-person-plus"></i> Assign Applicants
                                        </button>
                                    <?php endif; ?>
                                    
                                    <a href="view_interview_batch.php?id=<?php echo $schedule['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Create Schedule Modal -->
    <div class="modal fade" id="createScheduleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-calendar-plus"></i> Create Interview Schedule
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_schedule">
                        
                        <div class="mb-3">
                            <label class="form-label">Batch Number</label>
                            <input type="number" class="form-control" name="batch_number" 
                                   value="<?php echo $next_batch; ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Interview Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="interview_type" id="interviewType" required onchange="toggleInterviewFields()">
                                <option value="personal">Personal Interview</option>
                                <option value="online">Online Interview</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Interview Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="interview_date" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Interview Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="interview_time" required>
                        </div>
                        
                        <!-- Personal Interview Fields -->
                        <div id="personalFields">
                            <div class="mb-3">
                                <label class="form-label">Location <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="location" id="locationField"
                                       placeholder="e.g., HR Office, Room 201">
                            </div>
                        </div>
                        
                        <!-- Online Interview Fields -->
                        <div id="onlineFields" style="display: none;">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Enter your online meeting details (Zoom, Google Meet, Teams, etc.)
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Meeting Link <span class="text-danger">*</span></label>
                                <input type="url" class="form-control" name="online_meeting_link" id="meetingLinkField"
                                       placeholder="e.g., https://zoom.us/j/123456789">
                                <small class="text-muted">Full URL to join the meeting</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Meeting ID</label>
                                    <input type="text" class="form-control" name="online_meeting_id"
                                           placeholder="e.g., 123 456 789">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Meeting Password</label>
                                    <input type="text" class="form-control" name="online_meeting_password"
                                           placeholder="e.g., abc123">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Maximum Slots</label>
                            <input type="number" class="form-control" name="max_slots" 
                                   value="15" min="1" max="30">
                            <small class="text-muted">Default: 15 persons per batch</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3" 
                                      placeholder="Additional instructions or requirements"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Create Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Assign Applicants Modal -->
    <div class="modal fade" id="assignApplicantsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus"></i> Assign Applicants to Batch <span id="modalBatchNumber"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="assign_applicants">
                        <input type="hidden" name="schedule_id" id="assignScheduleId">
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            Available slots: <strong id="availableSlots"></strong>
                        </div>
                        
                        <?php if (empty($unassigned_applicants)): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> No unassigned applicants available.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="50">
                                                <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)">
                                            </th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Institution</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($unassigned_applicants as $applicant): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="applicants[]" 
                                                           value="<?php echo $applicant['user_id']; ?>" 
                                                           class="applicant-checkbox">
                                                </td>
                                                <td><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($applicant['email']); ?></td>
                                                <td><?php echo htmlspecialchars($applicant['higher_ed_institution'] ?? 'N/A'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" <?php echo empty($unassigned_applicants) ? 'disabled' : ''; ?>>
                            <i class="bi bi-check-circle"></i> Assign Selected
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function toggleInterviewFields() {
            const interviewType = document.getElementById('interviewType').value;
            const personalFields = document.getElementById('personalFields');
            const onlineFields = document.getElementById('onlineFields');
            const locationField = document.getElementById('locationField');
            const meetingLinkField = document.getElementById('meetingLinkField');
            
            if (interviewType === 'online') {
                personalFields.style.display = 'none';
                onlineFields.style.display = 'block';
                locationField.removeAttribute('required');
                meetingLinkField.setAttribute('required', 'required');
            } else {
                personalFields.style.display = 'block';
                onlineFields.style.display = 'none';
                locationField.setAttribute('required', 'required');
                meetingLinkField.removeAttribute('required');
            }
        }
        
        function openAssignModal(scheduleId, batchNumber, availableSlots) {
            document.getElementById('assignScheduleId').value = scheduleId;
            document.getElementById('modalBatchNumber').textContent = '#' + batchNumber;
            document.getElementById('availableSlots').textContent = availableSlots;
            
            const modal = new bootstrap.Modal(document.getElementById('assignApplicantsModal'));
            modal.show();
        }
        
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.applicant-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }
    </script>

    <!-- Interview Success Modal -->
    <div class="modal fade" id="interviewSuccessModal" tabindex="-1" aria-labelledby="interviewSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="interviewSuccessModalLabel">
                        <i class="bi bi-calendar-check-fill"></i> Interview Schedule Sent Successfully!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <i class="bi bi-calendar-check-fill text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="mb-3">Interview Schedules Sent to Interns!</h5>
                    
                    <div class="alert alert-success text-start">
                        <h6><i class="bi bi-info-circle"></i> Summary:</h6>
                        <ul class="mb-0">
                            <li><strong><?php echo $_SESSION['interview_assigned_count'] ?? 0; ?></strong> applicant(s) assigned</li>
                            <li><strong>Date:</strong> <?php echo isset($_SESSION['interview_date']) ? date('F d, Y', strtotime($_SESSION['interview_date'])) : ''; ?></li>
                            <li><strong>Time:</strong> <?php echo isset($_SESSION['interview_time']) ? date('h:i A', strtotime($_SESSION['interview_time'])) : ''; ?></li>
                        </ul>
                    </div>
                    
                    <p class="text-muted mb-0">
                        <i class="bi bi-bell-fill"></i> All selected interns have been notified via their dashboard notifications. 
                        They can now view their interview schedule details.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Close
                    </button>
                    <a href="view_interview_batch.php?id=<?php echo $_SESSION['last_schedule_id'] ?? ''; ?>" class="btn btn-primary">
                        <i class="bi bi-eye"></i> View Batch Details
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show interview success modal if interviews were assigned
        <?php if (isset($_SESSION['interview_assigned_success']) && $_SESSION['interview_assigned_success']): ?>
            const interviewModal = new bootstrap.Modal(document.getElementById('interviewSuccessModal'));
            interviewModal.show();
            
            // Clear the session variable after showing modal
            setTimeout(() => {
                <?php 
                unset($_SESSION['interview_assigned_success']); 
                unset($_SESSION['interview_assigned_count']); 
                unset($_SESSION['interview_date']); 
                unset($_SESSION['interview_time']);
                unset($_SESSION['last_schedule_id']); 
                ?>
            }, 500);
        <?php endif; ?>
    </script>
</body>
</html>
