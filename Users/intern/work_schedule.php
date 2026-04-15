<?php
require_once '../../config.php';

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

// Handle form submissions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $schedule_id = $_POST['schedule_id'];
        
        if ($_POST['action'] === 'accept') {
            // Accept the schedule with MOA signature
            $digital_signature = $_POST['digital_signature'] ?? '';
            $signature_method = $_POST['signature_method'] ?? 'type';
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            
            // Handle file uploads
            $moa_document_path = null;
            $moa_document_name = null;
            $signature_file_path = null;
            $signature_file_name = null;
            
            // Handle MOA document upload (optional)
            if (isset($_FILES['moa_document']) && $_FILES['moa_document']['error'] === UPLOAD_ERR_OK) {
                $moa_file = $_FILES['moa_document'];
                $allowed_moa_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
                
                if (in_array($moa_file['type'], $allowed_moa_types)) {
                    // Create upload directory if it doesn't exist
                    $moa_upload_dir = '../../uploads/internship_documents/' . $user_id . '/';
                    if (!file_exists($moa_upload_dir)) {
                        mkdir($moa_upload_dir, 0777, true);
                    }
                    
                    // Generate unique filename
                    $moa_file_extension = pathinfo($moa_file['name'], PATHINFO_EXTENSION);
                    $moa_new_filename = 'moa_' . $schedule_id . '_' . time() . '.' . $moa_file_extension;
                    $moa_upload_path = $moa_upload_dir . $moa_new_filename;
                    
                    if (move_uploaded_file($moa_file['tmp_name'], $moa_upload_path)) {
                        $moa_document_path = $moa_upload_path;
                        $moa_document_name = $moa_file['name'];
                    }
                }
            }
            
            // Handle signature file upload (if upload method is selected)
            if ($signature_method === 'upload' && isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] === UPLOAD_ERR_OK) {
                $sig_file = $_FILES['signature_file'];
                $allowed_sig_types = ['image/jpeg', 'image/png', 'application/pdf'];
                
                if (in_array($sig_file['type'], $allowed_sig_types)) {
                    // Create upload directory if it doesn't exist
                    $sig_upload_dir = '../../uploads/signatures/' . $user_id . '/';
                    if (!file_exists($sig_upload_dir)) {
                        mkdir($sig_upload_dir, 0777, true);
                    }
                    
                    // Generate unique filename
                    $sig_file_extension = pathinfo($sig_file['name'], PATHINFO_EXTENSION);
                    $sig_new_filename = 'signature_' . $schedule_id . '_' . time() . '.' . $sig_file_extension;
                    $sig_upload_path = $sig_upload_dir . $sig_new_filename;
                    
                    if (move_uploaded_file($sig_file['tmp_name'], $sig_upload_path)) {
                        $signature_file_path = $sig_upload_path;
                        $signature_file_name = $sig_file['name'];
                        // Use filename as digital signature for database
                        $digital_signature = $sig_file['name'];
                    }
                }
            }
            
            // Get work schedule details
            $ws_sql = "SELECT * FROM work_schedules WHERE id = ? AND user_id = ?";
            $ws_stmt = $conn->prepare($ws_sql);
            $ws_stmt->bind_param("ii", $schedule_id, $user_id);
            $ws_stmt->execute();
            $ws_result = $ws_stmt->get_result();
            $schedule = $ws_result->fetch_assoc();
            
            if ($schedule) {
                // Create MOA content
                $moa_content = "MEMORANDUM OF AGREEMENT\n\n";
                $moa_content .= "This agreement is entered into on " . date('F d, Y') . "\n";
                $moa_content .= "Between: MediCare Pharmacy and " . $full_name . "\n\n";
                $moa_content .= "Department: " . $schedule['department'] . "\n";
                $moa_content .= "Supervisor: " . $schedule['supervisor_name'] . "\n";
                $moa_content .= "Location: " . $schedule['location'] . "\n";
                $moa_content .= "Start Date: " . date('F d, Y', strtotime($schedule['start_date'])) . "\n\n";
                $moa_content .= "Work Schedule:\n" . $schedule['formatted_schedule'];
                
                // Insert MOA record
                $moa_sql = "INSERT INTO moa_agreements (
                    work_schedule_id, user_id, moa_content, moa_version,
                    agreement_date, start_date, department, supervisor_name, location,
                    intern_signature, intern_full_name, intern_email,
                    accepted_at, ip_address, user_agent,
                    agreed_terms, agreed_confidentiality, agreed_schedule, status,
                    moa_document_path, moa_document_name, signature_file_path, signature_file_name
                ) VALUES (?, ?, ?, '1.0', CURDATE(), ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, 1, 1, 1, 'active', ?, ?, ?, ?)";
                
                $moa_stmt = $conn->prepare($moa_sql);
                $moa_stmt->bind_param("iissssssssssssss",
                    $schedule_id, $user_id, $moa_content,
                    $schedule['start_date'], $schedule['department'], 
                    $schedule['supervisor_name'], $schedule['location'],
                    $digital_signature, $full_name, $email,
                    $ip_address, $user_agent,
                    $moa_document_path, $moa_document_name, $signature_file_path, $signature_file_name
                );
                
                if ($moa_stmt->execute()) {
                    // Update work schedule status
                    $update_sql = "UPDATE work_schedules 
                                   SET status = 'acknowledged', acknowledged_at = NOW() 
                                   WHERE id = ? AND user_id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("ii", $schedule_id, $user_id);
                    $update_stmt->execute();
                    
                    $success = "Work schedule and MOA accepted successfully! You are now ready to start your internship.";
                } else {
                    $error = "Failed to save MOA: " . $moa_stmt->error;
                }
            } else {
                $error = "Schedule not found.";
            }
        } elseif ($_POST['action'] === 'request_reset') {
            // Request schedule reset
            $reason = $_POST['reset_reason'];
            
            $update_sql = "UPDATE work_schedules 
                           SET status = 'pending', 
                               special_instructions = CONCAT(IFNULL(special_instructions, ''), '\n\n[RESET REQUESTED by Intern on ', NOW(), ']\nReason: ', ?) 
                           WHERE id = ? AND user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sii", $reason, $schedule_id, $user_id);
            
            if ($update_stmt->execute()) {
                $success = "Schedule reset request sent to HR. They will review and update your schedule.";
            } else {
                $error = "Failed to send reset request.";
            }
        }
    }
}

// Get work schedule
$schedule_sql = "SELECT ws.*, ie.average_rating, ie.final_decision,
                 u.first_name as hr_first_name, u.last_name as hr_last_name,
                 moa.id as moa_id, moa.intern_signature, moa.accepted_at as moa_accepted_at
                 FROM work_schedules ws
                 LEFT JOIN interview_evaluations ie ON ws.evaluation_id = ie.id
                 LEFT JOIN users u ON ws.created_by = u.id
                 LEFT JOIN moa_agreements moa ON ws.id = moa.work_schedule_id
                 WHERE ws.user_id = ? 
                 ORDER BY ws.created_at DESC 
                 LIMIT 1";
$schedule_stmt = $conn->prepare($schedule_sql);
$schedule_stmt->bind_param("i", $user_id);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();
$work_schedule = $schedule_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Work Schedule - MediCare Pharmacy</title>
    
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
        
        .schedule-header {
            background: linear-gradient(135deg, #16a085 0%, #27ae60 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #16a085;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        
        .info-box h5 {
            color: #16a085;
            margin-bottom: 0.5rem;
        }
        
        .schedule-detail {
            padding: 1rem;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .schedule-detail .label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .schedule-detail .value {
            font-size: 1.1rem;
            color: #212529;
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
            font-size: 1rem;
        }
        
        .weekly-schedule {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
        }
        
        .day-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .day-row:last-child {
            border-bottom: none;
        }
        
        .day-name {
            font-weight: 600;
            width: 120px;
        }
        
        .day-time {
            color: #16a085;
            font-weight: 500;
        }
        
        .day-off {
            color: #dc3545;
            font-weight: 500;
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
                        <a class="nav-link active" href="work_schedule.php">
                            <i class="bi bi-calendar-week"></i> Work Schedule
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="apply_internship.php">
                            <i class="bi bi-file-earmark-text"></i> Application
                        </a>
                    </li>
                </ul>
                
                <div class="navbar-nav ms-auto">
                    <span class="navbar-text me-3">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($full_name); ?>
                    </span>
                    <a href="../../logout.php" class="btn btn-logout">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="schedule-container">
        <div class="container">
            <div class="schedule-header">
                <h1><i class="bi bi-calendar-week"></i> My Work Schedule</h1>
                <p class="mb-0">View and manage your internship work schedule</p>
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
            
            <?php if ($work_schedule): ?>
                <div class="schedule-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3><i class="bi bi-briefcase"></i> Your Internship Schedule</h3>
                        <span class="status-badge bg-<?php 
                            echo $work_schedule['status'] === 'acknowledged' ? 'success' : 
                                 ($work_schedule['status'] === 'sent' ? 'warning' : 
                                 ($work_schedule['status'] === 'pending' ? 'secondary' : 'info')); 
                        ?>">
                            <?php echo ucfirst($work_schedule['status']); ?>
                        </span>
                    </div>
                    
                    <?php if ($work_schedule['status'] === 'sent'): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> 
                            <strong>Action Required:</strong> Please review your work schedule and accept it, or request changes if needed.
                        </div>
                    <?php elseif ($work_schedule['status'] === 'acknowledged' && $work_schedule['moa_id']): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> 
                            <strong>MOA Signed:</strong> You signed the Memorandum of Agreement on <?php echo date('F d, Y h:i A', strtotime($work_schedule['moa_accepted_at'])); ?>
                        </div>
                    <?php elseif ($work_schedule['status'] === 'pending'): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-clock-history"></i> 
                            <strong>Pending Review:</strong> Your schedule reset request is being reviewed by HR.
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Left Column: Schedule Details -->
                        <div class="col-md-8">
                            <h5 class="mb-3"><i class="bi bi-info-circle"></i> Schedule Information</h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="schedule-detail">
                                        <div class="label"><i class="bi bi-calendar-event"></i> Start Date</div>
                                        <div class="value"><?php echo date('F d, Y', strtotime($work_schedule['start_date'])); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="schedule-detail">
                                        <div class="label"><i class="bi bi-building"></i> Department</div>
                                        <div class="value"><?php echo htmlspecialchars($work_schedule['department']); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="schedule-detail">
                                        <div class="label"><i class="bi bi-clock"></i> Shift Time</div>
                                        <div class="value"><?php echo htmlspecialchars($work_schedule['shift_time']); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="schedule-detail">
                                        <div class="label"><i class="bi bi-person-badge"></i> Supervisor</div>
                                        <div class="value"><?php echo htmlspecialchars($work_schedule['supervisor_name']); ?></div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="schedule-detail">
                                        <div class="label"><i class="bi bi-geo-alt"></i> Location</div>
                                        <div class="value"><?php echo htmlspecialchars($work_schedule['location']); ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="mt-4 mb-3"><i class="bi bi-calendar3"></i> Weekly Schedule</h5>
                            <div class="weekly-schedule">
                                <?php
                                $all_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                $working_days = explode(',', $work_schedule['working_days']);
                                
                                foreach ($all_days as $day):
                                    $is_working = in_array($day, $working_days);
                                ?>
                                    <div class="day-row">
                                        <span class="day-name"><?php echo $day; ?></span>
                                        <span class="<?php echo $is_working ? 'day-time' : 'day-off'; ?>">
                                            <?php echo $is_working ? $work_schedule['shift_time'] : 'OFF'; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if ($work_schedule['special_instructions']): ?>
                                <div class="info-box mt-4">
                                    <h5><i class="bi bi-exclamation-circle"></i> Special Instructions</h5>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($work_schedule['special_instructions'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Right Column: Actions & Full Schedule -->
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="bi bi-file-text"></i> Full Schedule</h6>
                                    <pre class="mb-0" style="font-size: 0.85rem; white-space: pre-wrap; background: #f8f9fa; padding: 1rem; border-radius: 5px;"><?php echo htmlspecialchars($work_schedule['formatted_schedule']); ?></pre>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="bi bi-person"></i> Created By</h6>
                                    <p class="mb-2">
                                        <strong><?php echo htmlspecialchars($work_schedule['hr_first_name'] . ' ' . $work_schedule['hr_last_name']); ?></strong><br>
                                        <small class="text-muted">HR Personnel</small>
                                    </p>
                                    <small class="text-muted">
                                        Created: <?php echo date('M d, Y', strtotime($work_schedule['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="mt-3">
                                <?php if ($work_schedule['status'] === 'sent'): ?>
                                    <button class="btn btn-success w-100 mb-2" data-bs-toggle="modal" data-bs-target="#moaModal">
                                        <i class="bi bi-check-circle"></i> Accept Schedule & Sign MOA
                                    </button>
                                    
                                    <button class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#resetModal">
                                        <i class="bi bi-arrow-clockwise"></i> Request Schedule Reset
                                    </button>
                                <?php elseif ($work_schedule['status'] === 'acknowledged'): ?>
                                    <div class="alert alert-success">
                                        <i class="bi bi-check-circle"></i> MOA Signed
                                    </div>
                                    <button class="btn btn-outline-warning w-100" data-bs-toggle="modal" data-bs-target="#resetModal">
                                        <i class="bi bi-arrow-clockwise"></i> Request Schedule Change
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="schedule-card text-center py-5">
                    <i class="bi bi-calendar-x" style="font-size: 5rem; color: #6c757d;"></i>
                    <h3 class="mt-3">No Work Schedule Yet</h3>
                    <p class="text-muted">Your work schedule will appear here once HR assigns it to you.</p>
                    <a href="dashboard.php" class="btn btn-primary mt-3">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Reset Request Modal -->
    <div class="modal fade" id="resetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="bi bi-arrow-clockwise"></i> Request Schedule Reset
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="request_reset">
                        <input type="hidden" name="schedule_id" value="<?php echo $work_schedule['id'] ?? ''; ?>">
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            Please provide a reason for requesting a schedule change. HR will review your request and update your schedule accordingly.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Reason for Reset Request</strong> <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="reset_reason" rows="4" required
                                      placeholder="e.g., I have classes on Monday and Wednesday mornings, or I need to change my shift time..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-send"></i> Send Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- MOA Agreement Modal -->
    <div class="modal fade" id="moaModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-file-earmark-text"></i> Memorandum of Agreement (MOA)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> 
                        <strong>Important:</strong> Please read the entire Memorandum of Agreement carefully before accepting.
                    </div>
                    
                    <form method="POST" id="moaForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="accept">
                        <input type="hidden" name="schedule_id" value="<?php echo $work_schedule['id'] ?? ''; ?>">
                        
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                            <label class="form-check-label" for="agreeTerms">
                                <strong>I have read and agree to all terms and conditions stated in this Memorandum of Agreement.</strong>
                            </label>
                        </div>
                        
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="agreeConfidentiality" required>
                            <label class="form-check-label" for="agreeConfidentiality">
                                <strong>I agree to maintain confidentiality of all sensitive information.</strong>
                            </label>
                        </div>
                        
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="agreeSchedule" required>
                            <label class="form-check-label" for="agreeSchedule">
                                <strong>I accept the work schedule and commit to reporting on time.</strong>
                            </label>
                        </div>
                        
                        <!-- MOA Document Upload Section -->
                        <div class="mb-4 mt-4">
                            <label class="form-label"><strong>Upload Signed MOA Document (Optional)</strong></label>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> 
                                You can upload a pre-signed MOA document if you have one. If not, you can proceed with the digital signature below.
                            </div>
                            <div class="file-upload-area mb-2" onclick="document.getElementById('moa_document').click()" style="border: 2px dashed #667eea; border-radius: 10px; padding: 1.5rem; text-align: center; background: #f8f9fa; cursor: pointer; transition: all 0.3s;">
                                <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #667eea;"></i>
                                <h6 class="mt-2">Click to upload MOA document</h6>
                                <p class="text-muted mb-0 small">PDF, JPG, PNG or Word document (Max 10MB)</p>
                            </div>
                            <input type="file" class="form-control d-none" id="moa_document" name="moa_document" 
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" onchange="showMOAFileName()">
                            <div id="moaFileName" class="mt-2 text-success" style="display: none;">
                                <i class="bi bi-file-earmark-check"></i> <span id="moaFileNameText"></span>
                            </div>
                        </div>
                        
                        <!-- Digital Signature Section -->
                        <div class="mb-4 mt-3">
                            <label class="form-label"><strong>Digital Signature Method</strong></label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="signature_method" id="typeSignature" value="type" checked>
                                <label class="btn btn-outline-primary" for="typeSignature">
                                    <i class="bi bi-keyboard"></i> Type Signature
                                </label>
                                
                                <input type="radio" class="btn-check" name="signature_method" id="uploadSignature" value="upload">
                                <label class="btn btn-outline-primary" for="uploadSignature">
                                    <i class="bi bi-image"></i> Upload Signature
                                </label>
                            </div>
                        </div>
                        
                        <!-- Typed Signature -->
                        <div class="mb-3 mt-3" id="typedSignatureSection">
                            <label class="form-label"><strong>Digital Signature (Type your full name)</strong> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="digital_signature" id="digitalSignature" required
                                   placeholder="Type your full name as signature">
                            <small class="text-muted">By typing your name, you are electronically signing this agreement.</small>
                        </div>
                        
                        <!-- Uploaded Signature -->
                        <div class="mb-3 mt-3" id="uploadedSignatureSection" style="display: none;">
                            <label class="form-label"><strong>Upload Digital Signature File</strong> <span class="text-danger">*</span></label>
                            <div class="file-upload-area mb-2" onclick="document.getElementById('signature_file').click()" style="border: 2px dashed #667eea; border-radius: 10px; padding: 1.5rem; text-align: center; background: #f8f9fa; cursor: pointer; transition: all 0.3s;">
                                <i class="bi bi-pen" style="font-size: 2rem; color: #667eea;"></i>
                                <h6 class="mt-2">Click to upload signature</h6>
                                <p class="text-muted mb-0 small">JPG, PNG or PDF file (Max 5MB)</p>
                            </div>
                            <input type="file" class="form-control d-none" id="signature_file" name="signature_file" 
                                   accept=".jpg,.jpeg,.png,.pdf" onchange="showSignatureFileName()">
                            <div id="signatureFileName" class="mt-2 text-success" style="display: none;">
                                <i class="bi bi-file-earmark-check"></i> <span id="signatureFileNameText"></span>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-success" onclick="submitMOA()">
                        <i class="bi bi-check-circle"></i> Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function showMOAFileName() {
            const input = document.getElementById('moa_document');
            const fileName = document.getElementById('moaFileName');
            const fileNameText = document.getElementById('moaFileNameText');
            
            if (input.files.length > 0) {
                fileNameText.textContent = input.files[0].name;
                fileName.style.display = 'block';
            }
        }
        
        function showSignatureFileName() {
            const input = document.getElementById('signature_file');
            const fileName = document.getElementById('signatureFileName');
            const fileNameText = document.getElementById('signatureFileNameText');
            
            if (input.files.length > 0) {
                fileNameText.textContent = input.files[0].name;
                fileName.style.display = 'block';
            }
        }
        
        // Toggle signature method
        document.addEventListener('DOMContentLoaded', function() {
            const typeRadio = document.getElementById('typeSignature');
            const uploadRadio = document.getElementById('uploadSignature');
            const typedSection = document.getElementById('typedSignatureSection');
            const uploadedSection = document.getElementById('uploadedSignatureSection');
            const typedInput = document.getElementById('digitalSignature');
            const uploadInput = document.getElementById('signature_file');
            
            typeRadio.addEventListener('change', function() {
                typedSection.style.display = 'block';
                uploadedSection.style.display = 'none';
                typedInput.required = true;
                uploadInput.required = false;
            });
            
            uploadRadio.addEventListener('change', function() {
                typedSection.style.display = 'none';
                uploadedSection.style.display = 'block';
                typedInput.required = false;
                uploadInput.required = true;
            });
        });
        
        function submitMOA() {
            const form = document.getElementById('moaForm');
            const signatureMethod = document.querySelector('input[name="signature_method"]:checked').value;
            const typedSignature = document.getElementById('digitalSignature').value.trim();
            const uploadedSignature = document.getElementById('signature_file').files[0];
            
            // Validate all checkboxes are checked
            if (!document.getElementById('agreeTerms').checked ||
                !document.getElementById('agreeConfidentiality').checked ||
                !document.getElementById('agreeSchedule').checked) {
                alert('Please check all agreement boxes before proceeding.');
                return;
            }
            
            // Validate signature based on method
            if (signatureMethod === 'type') {
                if (!typedSignature) {
                    alert('Please provide your digital signature.');
                    return;
                }
                
                // Validate minimum length (at least 5 characters for a name)
                if (typedSignature.length < 5) {
                    alert('Please enter your complete full name.');
                    return;
                }
                
                var signatureDisplay = typedSignature;
            } else {
                if (!uploadedSignature) {
                    alert('Please upload your signature file.');
                    return;
                }
                
                var signatureDisplay = uploadedSignature.name;
            }
            
            // Confirm submission
            if (confirm('By clicking OK, you are electronically signing this Memorandum of Agreement and accepting the work schedule.\n\nSignature: ' + signatureDisplay + '\n\nThis action cannot be undone. Do you wish to proceed?')) {
                form.submit();
            }
        }
    </script>
</body>
</html>
