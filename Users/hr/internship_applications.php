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
$email = $_SESSION['email'];

// Initialize controller
$controller = new InternshipController($conn);

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $application_id = $_POST['application_id'];
    
    if ($_POST['action'] == 'update_status') {
        $status = $_POST['status'];
        $review_notes = $_POST['review_notes'] ?? null;
        
        if ($controller->updateApplicationStatus($application_id, $status, $user_id, $review_notes)) {
            $success = "Application status updated successfully!";
        } else {
            $error = "Failed to update application status.";
        }
    } elseif ($_POST['action'] == 'schedule_interview') {
        // Handle interview scheduling
        $interview_data = [
            'interview_date' => $_POST['interview_date'],
            'interview_time' => $_POST['interview_time'],
            'interview_type' => $_POST['interview_type'],
            'interview_location' => $_POST['interview_location'] ?? '',
            'interview_meeting_link' => $_POST['interview_meeting_link'] ?? '',
            'interview_notes' => $_POST['interview_notes'] ?? ''
        ];
        
        if ($controller->scheduleInterview($application_id, $interview_data, $user_id)) {
            $success = "Interview scheduled successfully! Notification sent to intern.";
        } else {
            $error = "Failed to schedule interview.";
        }
    } elseif ($_POST['action'] == 'set_schedule') {
        // Handle schedule and location setting
        $schedule_data = [
            'start_date' => $_POST['start_date'],
            'duration' => $_POST['duration'],
            'working_days' => $_POST['working_days'],
            'working_hours' => $_POST['working_hours'],
            'special_instructions' => $_POST['special_instructions'] ?? '',
            'pharmacy_name' => $_POST['pharmacy_name'],
            'pharmacy_address' => $_POST['pharmacy_address'],
            'contact_person' => $_POST['contact_person'],
            'contact_number' => $_POST['contact_number'],
            'contact_email' => $_POST['contact_email']
        ];
        
        if ($controller->setInternshipSchedule($application_id, $schedule_data, $user_id)) {
            $success = "Schedule and location details sent to intern successfully!";
        } else {
            $error = "Failed to send schedule details.";
        }
    }
}

// Get all applications
$applications = $controller->getAllApplications();

// Get applications by status filter
$status_filter = $_GET['status'] ?? 'all';
if ($status_filter !== 'all') {
    $applications = $controller->getAllApplications($status_filter);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Applications - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .applications-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .applications-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .status-badge {
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-under_review {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 20px;
        }
        
        .document-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .document-item {
            padding: 0.25rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .document-item:last-child {
            border-bottom: none;
        }
        
        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
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
        
        .filter-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
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
                            <i class="bi bi-file-earmark-text"></i> Internship Applications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pharmacy_policies.php">
                            <i class="bi bi-file-text"></i> Policies & Guidelines
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

    <div class="applications-container">
        <div class="container">
            <div class="applications-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-file-earmark-text"></i> Internship Applications</h2>
                        <p class="text-muted mb-0">Review and manage internship applications</p>
                    </div>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5><i class="bi bi-funnel"></i> Filter Applications</h5>
                        </div>
                        <div class="col-md-6">
                            <form method="GET" class="d-flex gap-2">
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Applications</option>
                                    <option value="submitted" <?php echo $status_filter === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                                    <option value="under_review" <?php echo $status_filter === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Applications Table -->
                <div class="table-responsive">
                    <table class="table">
                        <thead class="table-light">
                            <tr>
                                <th>Applicant</th>
                                <th>Email</th>
                                <th>Application Date</th>
                                <th>Status</th>
                                <th>Documents</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($applications)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <p class="mb-0">No applications found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($applications as $application): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($application['email']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($application['application_date'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $application['status']; ?>">
                                                <?php 
                                                $status_labels = [
                                                    'submitted' => 'Submitted',
                                                    'under_review' => 'Under Review',
                                                    'approved' => 'Approved',
                                                    'rejected' => 'Rejected'
                                                ];
                                                echo $status_labels[$application['status']] ?? $application['status'];
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="toggleDocuments(<?php echo $application['id']; ?>)"
                                                    id="toggle-btn-<?php echo $application['id']; ?>">
                                                <i class="bi bi-chevron-down"></i> View Documents
                                            </button>
                                            <?php if ($application['status'] === 'approved' && !$application['interview_scheduled']): ?>
                                                <button class="btn btn-sm btn-warning ms-1" 
                                                        onclick="openInterviewModal(<?php echo $application['id']; ?>, '<?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?>')">
                                                    <i class="bi bi-calendar-event"></i> Schedule Interview
                                                </button>
                                            <?php elseif ($application['interview_scheduled'] && !$application['schedule_sent']): ?>
                                                <span class="badge bg-warning ms-1">
                                                    <i class="bi bi-clock"></i> Interview Scheduled
                                                </span>
                                                <button class="btn btn-sm btn-success ms-1" 
                                                        onclick="openScheduleModal(<?php echo $application['id']; ?>, '<?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?>')">
                                                    <i class="bi bi-calendar-check"></i> Set Schedule
                                                </button>
                                            <?php elseif ($application['schedule_sent']): ?>
                                                <span class="badge bg-success ms-1">
                                                    <i class="bi bi-check-circle"></i> Schedule Sent
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <!-- Documents Row (Hidden by default) -->
                                    <tr id="documents-row-<?php echo $application['id']; ?>" style="display: none;">
                                        <td colspan="5" class="bg-light">
                                            <div class="p-4" id="documents-content-<?php echo $application['id']; ?>">
                                                <div class="text-center">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Interview Schedule Modal -->
    <div class="modal fade" id="interviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-calendar-event"></i> Schedule Interview
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="interviewForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="schedule_interview">
                        <input type="hidden" name="application_id" id="interview_application_id">
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Scheduling interview for: <strong id="interview_applicant_name"></strong>
                        </div>
                        
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="bi bi-calendar-event"></i> Interview Details
                        </h6>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Interview Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="interview_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Interview Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="interview_time" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Interview Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="interview_type" id="interview_type" required onchange="toggleInterviewFields()">
                                <option value="">-- Select Interview Type --</option>
                                <option value="personal">Personal Interview</option>
                                <option value="online">Online Interview</option>
                            </select>
                        </div>
                        
                        <!-- Personal Interview Fields -->
                        <div id="personal_interview_fields" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Interview Location <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="interview_location" rows="2" 
                                          placeholder="e.g., MediCare Pharmacy - Main Branch, 123 Health Street, Cebu City"></textarea>
                            </div>
                        </div>
                        
                        <!-- Online Interview Fields -->
                        <div id="online_interview_fields" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Meeting Link <span class="text-danger">*</span></label>
                                <input type="url" class="form-control" name="interview_meeting_link" 
                                       placeholder="e.g., https://zoom.us/j/123456789 or https://meet.google.com/abc-defg-hij">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Additional Notes</label>
                            <textarea class="form-control" name="interview_notes" rows="3" 
                                      placeholder="e.g., Please bring your original documents, Dress code: Business casual"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-send"></i> Send Interview Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Schedule & Location Modal -->
    <div class="modal fade" id="scheduleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-calendar-check"></i> Set Internship Schedule & Location
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="scheduleForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="set_schedule">
                        <input type="hidden" name="application_id" id="schedule_application_id">
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Setting schedule for: <strong id="applicant_name"></strong>
                        </div>
                        
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="bi bi-clock"></i> Internship Schedule
                        </h6>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="start_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Duration <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="duration" 
                                       placeholder="e.g., 6 Months (480 hours)" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Working Days <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="working_days" 
                                       placeholder="e.g., Monday - Friday" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Working Hours <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="working_hours" 
                                       placeholder="e.g., 8:00 AM - 5:00 PM" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Special Instructions</label>
                            <textarea class="form-control" name="special_instructions" rows="2" 
                                      placeholder="e.g., Please report on your first day at 8:00 AM sharp"></textarea>
                        </div>
                        
                        <h6 class="border-bottom pb-2 mb-3 mt-4">
                            <i class="bi bi-geo-alt"></i> Location Details
                        </h6>
                        
                        <div class="mb-3">
                            <label class="form-label">Pharmacy Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="pharmacy_name" 
                                   placeholder="e.g., MediCare Pharmacy - Main Branch" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="pharmacy_address" rows="2" 
                                      placeholder="e.g., 123 Health Street, Medical District, Cebu City, Cebu 6000" required></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Contact Person <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="contact_person" 
                                       placeholder="e.g., Ms. Maria Santos" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="contact_number" 
                                       placeholder="e.g., (032) 123-4567" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="contact_email" 
                                       placeholder="e.g., hr@pharmacy.com" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-send"></i> Send to Intern
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let openApplicationId = null;
        
        // Open interview modal
        function openInterviewModal(applicationId, applicantName) {
            document.getElementById('interview_application_id').value = applicationId;
            document.getElementById('interview_applicant_name').textContent = applicantName;
            
            // Reset form
            document.getElementById('interviewForm').reset();
            document.getElementById('interview_application_id').value = applicationId;
            document.getElementById('personal_interview_fields').style.display = 'none';
            document.getElementById('online_interview_fields').style.display = 'none';
            
            const modal = new bootstrap.Modal(document.getElementById('interviewModal'));
            modal.show();
        }
        
        // Toggle interview fields based on type
        function toggleInterviewFields() {
            const interviewType = document.getElementById('interview_type').value;
            const personalFields = document.getElementById('personal_interview_fields');
            const onlineFields = document.getElementById('online_interview_fields');
            const locationField = document.querySelector('textarea[name="interview_location"]');
            const meetingLinkField = document.querySelector('input[name="interview_meeting_link"]');
            
            if (interviewType === 'personal') {
                personalFields.style.display = 'block';
                onlineFields.style.display = 'none';
                locationField.required = true;
                meetingLinkField.required = false;
            } else if (interviewType === 'online') {
                personalFields.style.display = 'none';
                onlineFields.style.display = 'block';
                locationField.required = false;
                meetingLinkField.required = true;
            } else {
                personalFields.style.display = 'none';
                onlineFields.style.display = 'none';
                locationField.required = false;
                meetingLinkField.required = false;
            }
        }
        
        // Open schedule modal
        function openScheduleModal(applicationId, applicantName) {
            document.getElementById('schedule_application_id').value = applicationId;
            document.getElementById('applicant_name').textContent = applicantName;
            
            const modal = new bootstrap.Modal(document.getElementById('scheduleModal'));
            modal.show();
        }
        
        // Toggle documents display
        function toggleDocuments(applicationId) {
            const row = document.getElementById('documents-row-' + applicationId);
            const btn = document.getElementById('toggle-btn-' + applicationId);
            const content = document.getElementById('documents-content-' + applicationId);
            
            if (row.style.display === 'none') {
                // Close any other open documents
                if (openApplicationId && openApplicationId !== applicationId) {
                    document.getElementById('documents-row-' + openApplicationId).style.display = 'none';
                    const oldBtn = document.getElementById('toggle-btn-' + openApplicationId);
                    oldBtn.innerHTML = '<i class="bi bi-chevron-down"></i> View Documents';
                }
                
                // Open this one
                row.style.display = 'table-row';
                btn.innerHTML = '<i class="bi bi-chevron-up"></i> Hide Documents';
                openApplicationId = applicationId;
                
                // Load documents if not already loaded
                if (content.innerHTML.includes('spinner-border')) {
                    loadDocuments(applicationId);
                }
            } else {
                // Close this one
                row.style.display = 'none';
                btn.innerHTML = '<i class="bi bi-chevron-down"></i> View Documents';
                openApplicationId = null;
            }
        }
        
        // Load documents via AJAX
        function loadDocuments(applicationId) {
            const content = document.getElementById('documents-content-' + applicationId);
            
            fetch(`get_documents.php?application_id=${applicationId}`)
                .then(response => response.text())
                .then(html => {
                    content.innerHTML = html;
                })
                .catch(error => {
                    content.innerHTML = '<div class="alert alert-danger">Error loading documents.</div>';
                });
        }
        
        // Reload documents after validation
        function reloadDocuments(applicationId) {
            loadDocuments(applicationId);
        }
        
        // Validate document function (moved from get_documents.php to global scope)
        function validateDocument(documentType, status, applicationId, buttonElement) {
            console.log('validateDocument called:', documentType, status, applicationId);
            
            let remarks = '';
            if (status === 'invalid') {
                remarks = prompt('Please provide remarks for why this document is invalid:');
                if (remarks === null) {
                    console.log('User cancelled remarks prompt');
                    return; // User cancelled
                }
            }
            
            // Disable buttons during processing
            const validBtn = document.getElementById('valid-btn-' + documentType);
            const invalidBtn = document.getElementById('invalid-btn-' + documentType);
            
            console.log('Buttons found:', validBtn, invalidBtn);
            
            if (validBtn) validBtn.disabled = true;
            if (invalidBtn) invalidBtn.disabled = true;
            
            const url = 'get_documents.php?application_id=' + applicationId;
            const body = 'validate_document=1&application_id=' + applicationId + '&document_type=' + documentType + '&validation_status=' + status + '&remarks=' + encodeURIComponent(remarks);
            
            console.log('Sending request to:', url);
            console.log('Request body:', body);
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: body
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                
                if (data.success) {
                    // Update button styles immediately
                    if (status === 'valid') {
                        if (validBtn) {
                            validBtn.className = 'btn btn-sm btn-success me-1';
                        }
                        if (invalidBtn) {
                            invalidBtn.className = 'btn btn-sm btn-outline-danger';
                        }
                    } else if (status === 'invalid') {
                        if (validBtn) {
                            validBtn.className = 'btn btn-sm btn-outline-success me-1';
                        }
                        if (invalidBtn) {
                            invalidBtn.className = 'btn btn-sm btn-danger';
                        }
                    }
                    
                    // Re-enable buttons
                    if (validBtn) validBtn.disabled = false;
                    if (invalidBtn) invalidBtn.disabled = false;
                    
                    // Show notification if auto-approved
                    if (data.auto_approved) {
                        alert('✓ ' + data.notification);
                        // Reload the entire page to show updated status
                        window.location.reload();
                    } else {
                        // Show success message
                        alert('✓ Document validation updated successfully!');
                        // Reload just the documents section
                        setTimeout(() => {
                            reloadDocuments(applicationId);
                        }, 500);
                    }
                } else {
                    alert('Failed to update validation: ' + data.message);
                    // Re-enable buttons
                    if (validBtn) validBtn.disabled = false;
                    if (invalidBtn) invalidBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating validation: ' + error.message);
                // Re-enable buttons
                if (validBtn) validBtn.disabled = false;
                if (invalidBtn) invalidBtn.disabled = false;
            });
        }
    </script>
</body>
</html>
