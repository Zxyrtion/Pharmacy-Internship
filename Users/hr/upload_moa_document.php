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

// Get MOA ID from URL
$moa_id = $_GET['moa_id'] ?? null;
if (!$moa_id) {
    header('Location: view_ready_interns.php');
    exit();
}

// Get MOA details
$moa_sql = "SELECT moa.*, u.first_name, u.last_name, u.email,
            ws.department, ws.start_date
            FROM moa_agreements moa
            JOIN users u ON moa.user_id = u.id
            JOIN work_schedules ws ON moa.work_schedule_id = ws.id
            WHERE moa.id = ?";
$moa_stmt = $conn->prepare($moa_sql);
$moa_stmt->bind_param("i", $moa_id);
$moa_stmt->execute();
$moa = $moa_stmt->get_result()->fetch_assoc();

if (!$moa) {
    header('Location: view_ready_interns.php');
    exit();
}

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lawyer_name = $_POST['lawyer_name'];
    $lawyer_license = $_POST['lawyer_license_number'];
    $approval_date = $_POST['approval_date'];
    $approval_notes = $_POST['approval_notes'] ?? '';
    
    // Handle file upload
    if (isset($_FILES['moa_document']) && $_FILES['moa_document']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['moa_document'];
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        
        if (!in_array($file['type'], $allowed_types)) {
            $error = "Only PDF and Word documents are allowed.";
        } else {
            // Create upload directory if it doesn't exist
            $upload_dir = '../../uploads/moa_documents/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'moa_' . $moa_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Update MOA record
                $update_sql = "UPDATE moa_agreements 
                               SET moa_document_path = ?,
                                   moa_document_name = ?,
                                   moa_uploaded_at = NOW(),
                                   moa_uploaded_by = ?,
                                   lawyer_name = ?,
                                   lawyer_license_number = ?,
                                   approval_date = ?,
                                   approval_notes = ?
                               WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssissssi",
                    $upload_path, $file['name'], $user_id,
                    $lawyer_name, $lawyer_license, $approval_date, $approval_notes,
                    $moa_id
                );
                
                if ($update_stmt->execute()) {
                    $success = "MOA document uploaded successfully!";
                } else {
                    $error = "Failed to update database: " . $update_stmt->error;
                }
            } else {
                $error = "Failed to upload file.";
            }
        }
    } else {
        $error = "Please select a file to upload.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload MOA Document - MediCare Pharmacy</title>
    
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
            max-width: 800px;
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
        
        .file-upload-area {
            border: 2px dashed #667eea;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-upload-area:hover {
            background: #e9ecef;
            border-color: #5568d3;
        }
        
        .file-upload-area.dragover {
            background: #d4e3fc;
            border-color: #4a5fc1;
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
                <a href="../../logout.php" class="btn btn-logout">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-page">
        <div class="container d-flex justify-content-center">
            <div class="page-card">
                <h2 class="mb-4">
                    <i class="bi bi-file-earmark-arrow-up"></i> Upload Approved MOA Document
                </h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <div class="text-center mt-3">
                        <a href="view_ready_interns.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Back to Active Interns
                        </a>
                    </div>
                <?php else: ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Intern Information -->
                <div class="alert alert-info">
                    <h6><i class="bi bi-person-circle"></i> Intern Information</h6>
                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($moa['first_name'] . ' ' . $moa['last_name']); ?></p>
                    <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($moa['email']); ?></p>
                    <p class="mb-1"><strong>Department:</strong> <?php echo htmlspecialchars($moa['department']); ?></p>
                    <p class="mb-0"><strong>Start Date:</strong> <?php echo date('F d, Y', strtotime($moa['start_date'])); ?></p>
                </div>
                
                <?php if ($moa['moa_document_path']): ?>
                    <div class="alert alert-success">
                        <h6><i class="bi bi-check-circle"></i> Document Already Uploaded</h6>
                        <p class="mb-1"><strong>File:</strong> <?php echo htmlspecialchars($moa['moa_document_name']); ?></p>
                        <p class="mb-1"><strong>Uploaded:</strong> <?php echo date('F d, Y h:i A', strtotime($moa['moa_uploaded_at'])); ?></p>
                        <p class="mb-1"><strong>Lawyer:</strong> <?php echo htmlspecialchars($moa['lawyer_name']); ?></p>
                        <p class="mb-0"><strong>License:</strong> <?php echo htmlspecialchars($moa['lawyer_license_number']); ?></p>
                        <a href="<?php echo $moa['moa_document_path']; ?>" target="_blank" class="btn btn-sm btn-primary mt-2">
                            <i class="bi bi-download"></i> Download Document
                        </a>
                    </div>
                    <p class="text-muted">You can upload a new document to replace the existing one.</p>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <h5 class="mb-3"><i class="bi bi-file-earmark-text"></i> MOA Document Details</h5>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Lawyer Name</strong> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="lawyer_name" required
                               value="<?php echo htmlspecialchars($moa['lawyer_name'] ?? ''); ?>"
                               placeholder="e.g., Atty. Juan Dela Cruz">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Lawyer License Number</strong> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="lawyer_license_number" required
                               value="<?php echo htmlspecialchars($moa['lawyer_license_number'] ?? ''); ?>"
                               placeholder="e.g., 12345">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Approval Date</strong> <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="approval_date" required
                               value="<?php echo $moa['approval_date'] ?? date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Approval Notes</strong></label>
                        <textarea class="form-control" name="approval_notes" rows="3"
                                  placeholder="Any additional notes or comments..."><?php echo htmlspecialchars($moa['approval_notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label"><strong>Upload MOA Document</strong> <span class="text-danger">*</span></label>
                        <div class="file-upload-area" onclick="document.getElementById('moa_document').click()">
                            <i class="bi bi-cloud-upload" style="font-size: 3rem; color: #667eea;"></i>
                            <h5 class="mt-3">Click to upload or drag and drop</h5>
                            <p class="text-muted mb-0">PDF or Word document (Max 10MB)</p>
                            <p class="text-muted small">Approved and signed by lawyer</p>
                        </div>
                        <input type="file" class="form-control d-none" id="moa_document" name="moa_document" 
                               accept=".pdf,.doc,.docx" required onchange="showFileName()">
                        <div id="fileName" class="mt-2 text-success" style="display: none;">
                            <i class="bi bi-file-earmark-check"></i> <span id="fileNameText"></span>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="view_ready_interns.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-upload"></i> Upload Document
                        </button>
                    </div>
                </form>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function showFileName() {
            const input = document.getElementById('moa_document');
            const fileName = document.getElementById('fileName');
            const fileNameText = document.getElementById('fileNameText');
            
            if (input.files.length > 0) {
                fileNameText.textContent = input.files[0].name;
                fileName.style.display = 'block';
            }
        }
        
        // Drag and drop functionality
        const uploadArea = document.querySelector('.file-upload-area');
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('moa_document').files = files;
                showFileName();
            }
        });
    </script>
</body>
</html>
