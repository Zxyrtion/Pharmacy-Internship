<?php
require_once '../../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../views/auth/login.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'Intern') {
    header('Location: ../index.php');
    exit();
}

require_once '../../core/Database.php';
require_once '../../models/internship.php';

$db = new Database();
$conn = $db->getConnection();
$internship = new Internship($conn);

// Get or create internship record for this user
$internship_record = $internship->getByUserId($_SESSION['user_id']);
if (!$internship_record) {
    $internship->create($_SESSION['user_id']);
    $internship_record = $internship->getByUserId($_SESSION['user_id']);
} else {
    // Auto-verify age if birthdate exists but not yet verified
    if (!empty($internship_record['date_of_birth']) && !$internship_record['is_at_least_18']) {
        $internship->updateField($_SESSION['user_id'], 'date_of_birth', $internship_record['date_of_birth']);
        $internship_record = $internship->getByUserId($_SESSION['user_id']);
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_requirement'])) {
        $requirement = $_POST['requirement'];
        $value = isset($_POST['value']) ? 1 : 0;
        
        if ($requirement === 'higher_ed_institution') {
            $internship->updateField($_SESSION['user_id'], 'higher_ed_institution', $_POST['institution']);
            $internship->updateField($_SESSION['user_id'], 'is_enrolled_higher_ed', 1);
        } else {
            $internship->updateField($_SESSION['user_id'], $requirement, $value);
        }
        
        // Recalculate eligibility
        $internship->checkEligibility($_SESSION['user_id']);
        
        header('Location: apply_internship.php');
        exit();
    }
    
    if (isset($_POST['upload_document'])) {
        $document_type = $_POST['requirement'];
        $file_field = $document_type;
        
        // Handle enrollment certificate with institution name
        if ($document_type === 'enrollment_certificate' && isset($_POST['institution'])) {
            if ($internship->uploadDocument($_SESSION['user_id'], $document_type, $_FILES[$file_field], $_POST['institution'])) {
                $_SESSION['success'] = 'Certificate of Enrollment uploaded successfully!';
            } else {
                $_SESSION['error'] = 'Failed to upload document. Please check file format and try again.';
            }
        } else {
            if ($internship->uploadDocument($_SESSION['user_id'], $document_type, $_FILES[$file_field])) {
                $_SESSION['success'] = 'Document uploaded successfully!';
            } else {
                $_SESSION['error'] = 'Failed to upload document. Please check file format and try again.';
            }
        }
        
        header('Location: apply_internship.php');
        exit();
    }
    
    if (isset($_POST['submit_application'])) {
        if ($internship_record['is_eligible']) {
            $internship->updateField($_SESSION['user_id'], 'application_status', 'submitted');
            $_SESSION['success'] = 'Application submitted successfully!';
        } else {
            $_SESSION['error'] = 'Please complete all requirements before submitting.';
        }
        header('Location: apply_internship.php');
        exit();
    }
}

// Refresh record after any updates
$internship_record = $internship->getByUserId($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Internship - Pharmacy Internship System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../style.css">
    <style>
        .requirement-card {
            border-left: 4px solid #0004ffff;
            transition: all 0.3s ease;
        }
        .requirement-card.completed {
            border-left-color: #28a745;
            background-color: #f8fff9;
        }
        .requirement-card.pending {
            border-left-color: #ffc107;
            background-color: #fffdf7;
        }
        .check-icon {
            color: #28a745;
            font-size: 1.2rem;
        }
        .pending-icon {
            color: #ffc107;
            font-size: 1.2rem;
        }
        .upload-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Apply Internship</h4>
                <small>Submit your internship application</small>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Personal Information -->
                        <div class="mb-4">
                            <h5>Personal Information</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['first_name'] ?? ''); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['last_name'] ?? ''); ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- Requirements Section -->
                        <h5 class="mb-3">Internship Requirements</h5>
                        
                        <!-- Requirement 16.1.1 -->
                        <div class="requirement-card card mb-3 <?php echo $internship_record['is_enrolled_higher_ed'] ? 'completed' : 'pending'; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6>16.1.1 Higher Education Enrollment</h6>
                                        <p class="mb-2">Upload your Certificate of Enrollment from a legitimate Philippine higher education institution.</p>
                                         <p class="mb-2">COE must be validated school year</p>
                                        <?php if ($internship_record['is_enrolled_higher_ed']): ?>
                                            <span class="badge bg-success">Completed</span>
                                            <p class="mt-2 mb-0">
                                                <strong>Institution:</strong> <?php echo htmlspecialchars($internship_record['higher_ed_institution']); ?><br>
                                                <a href="../../uploads/internship_documents/<?php echo $internship_record['enrollment_certificate_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-2">View Certificate</a>
                                            </p>
                                        <?php else: ?>
                                            <div class="upload-section">
                                                <form method="POST" enctype="multipart/form-data">
                                                    <input type="hidden" name="requirement" value="enrollment_certificate">
                                                    <div class="mb-2">
                                                        <input type="text" name="institution" class="form-control mb-2" placeholder="Enter institution name" required>
                                                        <input type="file" name="enrollment_certificate" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                                    </div>
                                                    <button type="submit" name="upload_document" class="btn btn-primary btn-sm">Upload Certificate</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php echo $internship_record['is_enrolled_higher_ed'] ? '<i class="fas fa-check-circle check-icon"></i>' : '<i class="fas fa-clock pending-icon"></i>'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Requirement 16.1.2 -->
                        <div class="requirement-card card mb-3 <?php echo $internship_record['is_enrolled_internship_subject'] ? 'completed' : 'pending'; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6>16.1.2 Internship Subject Enrollment</h6>
                                        <p class="mb-2">Be enrolled in an internship subject.</p>
                                        <small class="text-muted">This is automatically verified when you upload your Certificate of Enrollment (16.1.1).</small>
                                        <?php if ($internship_record['is_enrolled_internship_subject']): ?>
                                            <br><span class="badge bg-success mt-2">Completed (via Certificate of Enrollment)</span>
                                        <?php else: ?>
                                            <br><span class="badge bg-warning mt-2">Upload COE to complete this requirement</span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php echo $internship_record['is_enrolled_internship_subject'] ? '<i class="fas fa-check-circle check-icon"></i>' : '<i class="fas fa-clock pending-icon"></i>'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Requirement 16.1.3 -->
                        <div class="requirement-card card mb-3 <?php echo $internship_record['is_at_least_18'] ? 'completed' : 'pending'; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6>16.1.3 Age Requirement</h6>
                                        <p class="mb-2">Be at least eighteen (18) years of age from the start of the internship period.</p>
                                        <div class="row">
                                            <div class="col-md-8">
                                                <label class="form-label">Date of Birth:</label>
                                                <input type="date" name="date_of_birth" class="form-control" 
                                                       value="<?php echo $internship_record['date_of_birth'] ?? $_SESSION['birth_date'] ?? ''; ?>" 
                                                       readonly>
                                                <small class="text-muted">Birthdate automatically retrieved from your profile</small>
                                            </div>
                                            <div class="col-md-4">
                                                <?php if (!$internship_record['is_at_least_18']): ?>
                                                    <form method="POST" class="mt-4">
                                                        <input type="hidden" name="requirement" value="date_of_birth">
                                                        <input type="hidden" name="date_of_birth" value="<?php echo $internship_record['date_of_birth'] ?? $_SESSION['birth_date'] ?? ''; ?>">
                                                        <button type="submit" name="update_requirement" class="btn btn-primary btn-sm">Verify Age</button>
                                                    </form>
                                                <?php else: ?>
                                                    <div class="mt-4">
                                                        <span class="badge bg-success">Age Verified</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <?php echo $internship_record['is_at_least_18'] ? '<i class="fas fa-check-circle check-icon"></i>' : '<i class="fas fa-clock pending-icon"></i>'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Requirement 16.1.4 -->
                        <div class="requirement-card card mb-3 <?php echo $internship_record['has_passed_pre_internship'] ? 'completed' : 'pending'; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6>16.1.4 Pre-internship Requirements</h6>
                                        <p class="mb-2">Pass pre-internship requirements as specified in the internship plan.</p>
                                        <p class="mb-2"><strong>Upload Internship Recommendation Letter from Program Head or Dean</strong></p>
                                        <?php if ($internship_record['has_passed_pre_internship']): ?>
                                            <span class="badge bg-success">Completed</span>
                                            <p class="mt-2 mb-0"><a href="../../uploads/internship_documents/<?php echo $_SESSION['user_id']; ?>/<?php echo $internship_record['recommendation_letter_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">View Recommendation Letter</a></p>
                                        <?php else: ?>
                                            <div class="upload-section">
                                                <form method="POST" enctype="multipart/form-data">
                                                    <input type="hidden" name="requirement" value="recommendation_letter">
                                                    <div class="mb-2">
                                                        <input type="file" name="recommendation_letter" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                                        <small class="text-muted">Upload recommendation letter from your Program Head or Dean</small>
                                                    </div>
                                                    <button type="submit" name="upload_document" class="btn btn-primary btn-sm">Upload Recommendation Letter</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php echo $internship_record['has_passed_pre_internship'] ? '<i class="fas fa-check-circle check-icon"></i>' : '<i class="fas fa-clock pending-icon"></i>'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Requirement 16.1.5 -->
                        <div class="requirement-card card mb-3 <?php echo $internship_record['medical_certificate_submitted'] ? 'completed' : 'pending'; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6>16.1.5 Medical Certificate</h6>
                                        <p class="mb-2">Submit a Medical Certificate indicating good health and emotional fitness from DOH accredited clinics/hospitals.</p>
                                        <?php if ($internship_record['medical_certificate_submitted']): ?>
                                            <span class="badge bg-success">Completed</span>
                                            <p class="mt-2 mb-0"><a href="../../uploads/internship_documents/<?php echo $internship_record['medical_certificate_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">View Document</a></p>
                                        <?php else: ?>
                                            <div class="upload-section">
                                                <form method="POST" enctype="multipart/form-data">
                                                    <input type="hidden" name="requirement" value="medical_certificate">
                                                    <div class="mb-2">
                                                        <input type="file" name="medical_certificate" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                                    </div>
                                                    <button type="submit" name="upload_document" class="btn btn-primary btn-sm">Upload Document</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php echo $internship_record['medical_certificate_submitted'] ? '<i class="fas fa-check-circle check-icon"></i>' : '<i class="fas fa-clock pending-icon"></i>'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Requirement 16.1.6 -->
                        <div class="requirement-card card mb-3 <?php echo $internship_record['parental_consent_submitted'] ? 'completed' : 'pending'; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6>16.1.6 Parental Consent</h6>
                                        <p class="mb-2">Have a notarized written consent from your parents or legal guardian (no waiver is allowed).</p>
                                        <?php if ($internship_record['parental_consent_submitted']): ?>
                                            <span class="badge bg-success">Completed</span>
                                            <p class="mt-2 mb-0"><a href="../../uploads/internship_documents/<?php echo $internship_record['parental_consent_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">View Document</a></p>
                                        <?php else: ?>
                                            <div class="upload-section">
                                                <form method="POST" enctype="multipart/form-data">
                                                    <input type="hidden" name="requirement" value="parental_consent">
                                                    <div class="mb-2">
                                                        <input type="file" name="parental_consent" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                                    </div>
                                                    <button type="submit" name="upload_document" class="btn btn-primary btn-sm">Upload Document</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php echo $internship_record['parental_consent_submitted'] ? '<i class="fas fa-check-circle check-icon"></i>' : '<i class="fas fa-clock pending-icon"></i>'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Application Button -->
                        <div class="mt-4 text-center">
                            <?php if ($internship_record['is_eligible']): ?>
                                <form method="POST" class="d-inline">
                                    <button type="submit" name="submit_application" class="btn btn-success btn-lg">
                                        <i class="fas fa-paper-plane"></i> Submit Application
                                    </button>
                                </form>
                            <?php else: ?>
                                <button type="button" class="btn btn-secondary btn-lg" disabled>
                                    <i class="fas fa-lock"></i> Complete All Requirements First
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>
