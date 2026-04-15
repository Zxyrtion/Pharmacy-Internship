<?php
require_once '../../config.php';
require_once '../../controllers/InternshipController.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(403);
    exit('Access denied');
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'HR Personnel') {
    http_response_code(403);
    exit('Access denied');
}

if (!isset($_GET['application_id'])) {
    http_response_code(400);
    exit('Application ID required');
}

$application_id = $_GET['application_id'];
$controller = new InternshipController($conn);

// Handle document validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_document'])) {
    header('Content-Type: application/json');
    
    // Log the request for debugging
    error_log("=== VALIDATION REQUEST ===");
    error_log("POST: " . print_r($_POST, true));
    error_log("GET: " . print_r($_GET, true));
    
    // Get application_id from POST or GET
    $application_id = $_POST['application_id'] ?? $_GET['application_id'] ?? null;
    
    if (!$application_id) {
        error_log("ERROR: Application ID is missing");
        echo json_encode(['success' => false, 'message' => 'Application ID is required', 'debug' => ['post' => $_POST, 'get' => $_GET]]);
        exit;
    }
    
    $document_type = $_POST['document_type'] ?? null;
    $validation_status = $_POST['validation_status'] ?? null;
    $remarks = $_POST['remarks'] ?? '';
    
    error_log("Application ID: $application_id");
    error_log("Document Type: $document_type");
    error_log("Validation Status: $validation_status");
    
    if (!$document_type || !$validation_status) {
        error_log("ERROR: Document type or validation status missing");
        echo json_encode(['success' => false, 'message' => 'Document type and validation status are required']);
        exit;
    }
    
    // Validate document type
    $allowed_types = ['enrollment_certificate', 'recommendation_letter', 'medical_certificate', 'parental_consent'];
    if (!in_array($document_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid document type']);
        exit;
    }
    
    // Validate status
    $allowed_statuses = ['pending', 'valid', 'invalid'];
    if (!in_array($validation_status, $allowed_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid validation status']);
        exit;
    }
    
    $status_field = $document_type . '_status';
    $remarks_field = $document_type . '_remarks';
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update document validation status using safe column names
        $sql = "UPDATE internship_records SET `$status_field` = ?, `$remarks_field` = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ssi", $validation_status, $remarks, $application_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        error_log("Document validation updated successfully");
        
        // If validating enrollment certificate as valid, automatically verify internship subject enrollment
        if ($document_type === 'enrollment_certificate' && $validation_status === 'valid') {
            $sql2 = "UPDATE internship_records SET is_enrolled_internship_subject = 1 WHERE id = ?";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param("i", $application_id);
            $stmt2->execute();
        }
        
        // If marking enrollment certificate as invalid, uncheck internship subject enrollment
        if ($document_type === 'enrollment_certificate' && $validation_status === 'invalid') {
            $sql2 = "UPDATE internship_records SET is_enrolled_internship_subject = 0 WHERE id = ?";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param("i", $application_id);
            $stmt2->execute();
        }
        
        $conn->commit();
        
        // Check if all documents are now valid and auto-approve
        $check_sql = "SELECT 
            enrollment_certificate_status,
            recommendation_letter_status,
            medical_certificate_status,
            parental_consent_status,
            application_status
        FROM internship_records WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $application_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result()->fetch_assoc();
        
        // If all documents are valid and status is not already approved, auto-approve
        if ($result['enrollment_certificate_status'] === 'valid' &&
            $result['recommendation_letter_status'] === 'valid' &&
            $result['medical_certificate_status'] === 'valid' &&
            $result['parental_consent_status'] === 'valid' &&
            $result['application_status'] !== 'approved') {
            
            // Update to approved status
            $approve_sql = "UPDATE internship_records SET application_status = 'approved' WHERE id = ?";
            $approve_stmt = $conn->prepare($approve_sql);
            $approve_stmt->bind_param("i", $application_id);
            $approve_stmt->execute();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Document validation updated',
                'auto_approved' => true,
                'notification' => 'All documents are valid! Application has been automatically approved.'
            ]);
        } else {
            echo json_encode(['success' => true, 'message' => 'Document validation updated']);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to update validation: ' . $e->getMessage()]);
    }
    exit;
}

// Get application details
$application = $controller->getApplicationDetails($application_id);
$documents = $controller->getApplicationDocuments($application_id);

if (!$application) {
    echo '<div class="alert alert-danger">Application not found.</div>';
    exit;
}
?>

<div class="application-info mb-4">
    <h6><i class="bi bi-person-circle"></i> Applicant Information</h6>
    <div class="row">
        <div class="col-md-6">
            <strong>Name:</strong> <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?><br>
            <strong>Email:</strong> <?php echo htmlspecialchars($application['email']); ?><br>
            <strong>Date of Birth:</strong> <?php echo date('F d, Y', strtotime($application['date_of_birth'])); ?><br>
            <strong>Institution:</strong> <?php echo htmlspecialchars($application['higher_ed_institution'] ?? 'N/A'); ?>
        </div>
        <div class="col-md-6">
            <strong>Application Date:</strong> <?php echo date('F d, Y', strtotime($application['application_date'])); ?><br>
            <strong>Status:</strong> 
            <span class="status-badge status-<?php echo $application['status']; ?>">
                <?php 
                $status_labels = [
                    'pending' => 'Pending',
                    'submitted' => 'Submitted',
                    'under_review' => 'Under Review',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected'
                ];
                echo $status_labels[$application['status']] ?? $application['status'];
                ?>
            </span>
        </div>
    </div>
</div>

<h6><i class="bi bi-file-earmark-text"></i> Requirements Status</h6>

<div class="mb-4">
    <div class="list-group">
        <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>16.1.1 Higher Education Enrollment</strong>
                    <br>
                    <small class="text-muted">Upload Certificate of Enrollment (COE)</small>
                    <?php if ($application['is_enrolled_higher_ed']): ?>
                        <?php
                        $cert_status = $application['enrollment_certificate_status'] ?? 'pending';
                        $status_class = ['pending' => 'warning', 'valid' => 'success', 'invalid' => 'danger'];
                        $status_label = ['pending' => 'Pending Review', 'valid' => 'Valid', 'invalid' => 'Invalid'];
                        ?>
                        <span class="badge bg-<?php echo $status_class[$cert_status]; ?> ms-2">
                            <?php echo $status_label[$cert_status]; ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary ms-2">Not Submitted</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>16.1.2 Internship Subject Enrollment</strong>
                    <br>
                    <small class="text-muted">Automatically verified when COE is validated</small>
                    <?php 
                    $cert_status = $application['enrollment_certificate_status'] ?? 'pending';
                    if ($application['is_enrolled_internship_subject'] && $cert_status === 'valid'): ?>
                        <span class="badge bg-success ms-2">✓ Verified (via COE)</span>
                    <?php elseif ($application['is_enrolled_higher_ed'] && $cert_status === 'pending'): ?>
                        <span class="badge bg-warning ms-2">Pending COE Validation</span>
                    <?php else: ?>
                        <span class="badge bg-secondary ms-2">Not Verified</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>16.1.3 Age Requirement (18+ years)</strong>
                    <br>
                    <small class="text-muted">Automatically verified from profile birthdate</small>
                    <?php if ($application['is_at_least_18']): ?>
                        <span class="badge bg-success ms-2">✓ Verified</span>
                    <?php else: ?>
                        <span class="badge bg-secondary ms-2">Not Verified</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>16.1.4 Pre-internship Requirements</strong>
                    <br>
                    <small class="text-muted">Recommendation letter from Program Head or Dean</small>
                    <?php if ($application['has_passed_pre_internship']): ?>
                        <?php
                        $rec_status = $application['recommendation_letter_status'] ?? 'pending';
                        $status_class = ['pending' => 'warning', 'valid' => 'success', 'invalid' => 'danger'];
                        $status_label = ['pending' => 'Pending Review', 'valid' => 'Valid', 'invalid' => 'Invalid'];
                        ?>
                        <span class="badge bg-<?php echo $status_class[$rec_status]; ?> ms-2">
                            <?php echo $status_label[$rec_status]; ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary ms-2">Not Submitted</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>16.1.5 Medical Certificate</strong>
                    <br>
                    <small class="text-muted">From DOH accredited clinics/hospitals</small>
                    <?php if ($application['medical_certificate_submitted']): ?>
                        <?php
                        $med_status = $application['medical_certificate_status'] ?? 'pending';
                        $status_class = ['pending' => 'warning', 'valid' => 'success', 'invalid' => 'danger'];
                        $status_label = ['pending' => 'Pending Review', 'valid' => 'Valid', 'invalid' => 'Invalid'];
                        ?>
                        <span class="badge bg-<?php echo $status_class[$med_status]; ?> ms-2">
                            <?php echo $status_label[$med_status]; ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary ms-2">Not Submitted</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>16.1.6 Parental Consent</strong>
                    <br>
                    <small class="text-muted">Notarized written consent</small>
                    <?php if ($application['parental_consent_submitted']): ?>
                        <?php
                        $consent_status = $application['parental_consent_status'] ?? 'pending';
                        $status_class = ['pending' => 'warning', 'valid' => 'success', 'invalid' => 'danger'];
                        $status_label = ['pending' => 'Pending Review', 'valid' => 'Valid', 'invalid' => 'Invalid'];
                        ?>
                        <span class="badge bg-<?php echo $status_class[$consent_status]; ?> ms-2">
                            <?php echo $status_label[$consent_status]; ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary ms-2">Not Submitted</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<h6><i class="bi bi-file-earmark-text"></i> Submitted Documents</h6>
<div class="document-list">
    <?php 
    $document_info = [
        'enrollment_certificate' => [
            'label' => 'Certificate of Enrollment',
            'submitted' => $application['is_enrolled_higher_ed'],
            'path' => $application['enrollment_certificate_path'],
            'status' => $application['enrollment_certificate_status'] ?? 'pending',
            'remarks' => $application['enrollment_certificate_remarks'] ?? ''
        ],
        'recommendation_letter' => [
            'label' => 'Internship Recommendation Letter (from Program Head/Dean)',
            'submitted' => $application['has_passed_pre_internship'],
            'path' => $application['recommendation_letter_path'],
            'status' => $application['recommendation_letter_status'] ?? 'pending',
            'remarks' => $application['recommendation_letter_remarks'] ?? ''
        ],
        'medical_certificate' => [
            'label' => 'Medical Certificate',
            'submitted' => $application['medical_certificate_submitted'],
            'path' => $application['medical_certificate_path'],
            'status' => $application['medical_certificate_status'] ?? 'pending',
            'remarks' => $application['medical_certificate_remarks'] ?? ''
        ],
        'parental_consent' => [
            'label' => 'Parental Consent',
            'submitted' => $application['parental_consent_submitted'],
            'path' => $application['parental_consent_path'],
            'status' => $application['parental_consent_status'] ?? 'pending',
            'remarks' => $application['parental_consent_remarks'] ?? ''
        ]
    ];
    
    $has_documents = false;
    foreach ($document_info as $doc_type => $info) {
        if ($info['submitted'] && !empty($info['path'])) {
            $has_documents = true;
            break;
        }
    }
    
    if (!$has_documents): ?>
        <div class="alert alert-warning">No documents uploaded yet.</div>
    <?php else: ?>
        <?php foreach ($document_info as $doc_type => $info): ?>
            <?php if ($info['submitted'] && !empty($info['path'])): ?>
                <div class="document-item border rounded p-3 mb-3">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <strong>
                                <i class="bi bi-file-earmark-pdf"></i>
                                <?php echo htmlspecialchars($info['label']); ?>
                            </strong>
                            <br>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($info['path']); ?>
                            </small>
                        </div>
                        <div class="col-md-3">
                            <?php
                            $status_class = [
                                'pending' => 'warning',
                                'valid' => 'success',
                                'invalid' => 'danger'
                            ];
                            $status_icon = [
                                'pending' => 'clock',
                                'valid' => 'check-circle',
                                'invalid' => 'x-circle'
                            ];
                            $status_label = [
                                'pending' => 'Pending Review',
                                'valid' => 'Valid',
                                'invalid' => 'Invalid'
                            ];
                            ?>
                            <span class="badge bg-<?php echo $status_class[$info['status']]; ?> p-2">
                                <i class="bi bi-<?php echo $status_icon[$info['status']]; ?>"></i>
                                <?php echo $status_label[$info['status']]; ?>
                            </span>
                            <?php if (!empty($info['remarks'])): ?>
                                <br><small class="text-muted">Remarks: <?php echo htmlspecialchars($info['remarks']); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-5 text-end">
                            <?php 
                            $relative_path = '../../uploads/internship_documents/' . $application['user_id'] . '/' . $info['path'];
                            ?>
                            <a href="<?php echo htmlspecialchars($relative_path); ?>" 
                               target="_blank" 
                               class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <a href="<?php echo htmlspecialchars($relative_path); ?>" 
                               download="<?php echo htmlspecialchars($info['path']); ?>"
                               class="btn btn-sm btn-outline-secondary me-1">
                                <i class="bi bi-download"></i> Download
                            </a>
                            <button class="btn btn-sm <?php echo $info['status'] === 'valid' ? 'btn-success' : 'btn-outline-success'; ?> me-1" 
                                    onclick="validateDocument('<?php echo $doc_type; ?>', 'valid', <?php echo $application_id; ?>, this)"
                                    id="valid-btn-<?php echo $doc_type; ?>">
                                <i class="bi bi-check"></i> Valid
                            </button>
                            <button class="btn btn-sm <?php echo $info['status'] === 'invalid' ? 'btn-danger' : 'btn-outline-danger'; ?>" 
                                    onclick="validateDocument('<?php echo $doc_type; ?>', 'invalid', <?php echo $application_id; ?>, this)"
                                    id="invalid-btn-<?php echo $doc_type; ?>">
                                <i class="bi bi-x"></i> Invalid
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
