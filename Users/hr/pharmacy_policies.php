<?php
require_once '../../config.php';
require_once '../../controllers/PharmacyDocumentController.php';

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
$controller = new PharmacyDocumentController($conn);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'upload':
                $data = [
                    'title' => $_POST['title'],
                    'description' => $_POST['description'],
                    'document_type' => $_POST['document_type'],
                    'category' => $_POST['category'],
                    'uploaded_by' => $user_id
                ];
                
                $result = $controller->uploadDocument($data, $_FILES['document_file']);
                
                if ($result['success']) {
                    $success = "Document uploaded successfully!";
                } else {
                    $errors = $result['errors'];
                }
                break;
                
            case 'update':
                $data = [
                    'title' => $_POST['title'],
                    'description' => $_POST['description'],
                    'document_type' => $_POST['document_type'],
                    'category' => $_POST['category']
                ];
                
                if ($controller->updateDocument($_POST['document_id'], $data)) {
                    $success = "Document updated successfully!";
                } else {
                    $errors = ["Failed to update document."];
                }
                break;
                
            case 'delete':
                if ($controller->deleteDocument($_POST['document_id'])) {
                    $success = "Document deleted successfully!";
                } else {
                    $errors = ["Failed to delete document."];
                }
                break;
        }
    }
}

// Get filters
$filters = [];
if (isset($_GET['document_type']) && !empty($_GET['documents_type'])) {
    $filters['document_type'] = $_GET['document_type'];
}
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $filters['category'] = $_GET['category'];
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Get documents and statistics
$documents = $controller->getAllDocuments($filters);
$stats = $controller->getDocumentStats();
$categories = $controller->getCategories();

// Document types for dropdown
$document_types = [
    'policy' => 'Policy',
    'guideline' => 'Guideline',
    'procedure' => 'Procedure',
    'manual' => 'Manual',
    'regulation' => 'Regulation',
    'other' => 'Other'
];

// Default categories
$default_categories = [
    'Medication Management',
    'Patient Safety',
    'Inventory Control',
    'Staff Training',
    'Compliance',
    'Emergency Procedures',
    'Quality Assurance',
    'General Administration'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Policies - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .policies-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .policies-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .document-type-badge {
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
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
        
        .upload-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .filter-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .document-item {
            border-left: 4px solid #667eea;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 0 10px 10px 0;
            transition: all 0.3s ease;
        }
        
        .document-item:hover {
            border-left-color: #764ba2;
            background: #e9ecef;
        }
        
        .file-upload-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-upload-input {
            position: absolute;
            left: -9999px;
        }
        
        .file-upload-label {
            display: block;
            padding: 12px 20px;
            background: #e9ecef;
            border: 2px dashed #6c757d;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-upload-label:hover {
            background: #dee2e6;
            border-color: #495057;
        }
        
        .file-upload-label.has-file {
            background: #d4edda;
            border-color: #28a745;
            border-style: solid;
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

    <div class="policies-container">
        <div class="container">
            <div class="policies-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-folder-fill"></i> Pharmacy Policies & Guidelines</h2>
                        <p class="text-muted mb-0">Organize and manage pharmacy business documents</p>
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
                
                <?php if (isset($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle"></i>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Section -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['total_documents']; ?></div>
                            <div>Total Documents</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['policies']; ?></div>
                            <div>Policies</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['guidelines']; ?></div>
                            <div>Guidelines</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['procedures']; ?></div>
                            <div>Procedures</div>
                        </div>
                    </div>
                </div>
                
                <!-- Upload Section -->
                <div class="upload-section">
                    <h4><i class="bi bi-cloud-upload"></i> Upload New Document</h4>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="title" class="form-label">Document Title *</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="document_type" class="form-label">Document Type *</label>
                                <select class="form-select" id="document_type" name="document_type" required>
                                    <option value="">Select Type</option>
                                    <?php foreach ($document_types as $value => $label): ?>
                                        <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category *</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($default_categories as $category): ?>
                                        <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                                    <?php endforeach; ?>
                                    <?php foreach ($categories as $category): ?>
                                        <?php if (!in_array($category, $default_categories)): ?>
                                            <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="document_file" class="form-label">Document File *</label>
                                <div class="file-upload-wrapper">
                                    <input type="file" name="document_file" id="document_file" class="file-upload-input" required accept=".pdf,.doc,.docx,.txt,.ppt,.pptx,.xls,.xlsx">
                                    <label for="document_file" class="file-upload-label">
                                        <i class="bi bi-cloud-upload"></i> Choose File
                                    </label>
                                </div>
                                <small class="text-muted">PDF, DOC, DOCX, TXT, PPT, PPTX, XLS, XLSX (Max 10MB)</small>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter document description..."></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload"></i> Upload Document
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5><i class="bi bi-funnel"></i> Filter Documents</h5>
                        </div>
                        <div class="col-md-6">
                            <form method="GET" class="d-flex gap-2">
                                <select name="document_type" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Types</option>
                                    <?php foreach ($document_types as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo (isset($_GET['document_type']) && $_GET['document_type'] == $value) ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                <button type="submit" class="btn btn-outline-primary">Filter</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Documents List -->
                <h4><i class="bi bi-file-earmark-text"></i> Documents</h4>
                
                <?php if (empty($documents)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-folder" style="font-size: 3rem; color: #6c757d;"></i>
                        <p class="text-muted">No documents found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($documents as $document): ?>
                        <div class="document-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h5><?php echo htmlspecialchars($document['title']); ?></h5>
                                    <p class="text-muted mb-2"><?php echo htmlspecialchars($document['description']); ?></p>
                                    <div class="d-flex gap-2 mb-2">
                                        <span class="badge bg-primary document-type-badge">
                                            <?php echo $document_types[$document['document_type']] ?? $document['document_type']; ?>
                                        </span>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($document['category']); ?>
                                        </span>
                                        <small class="text-muted">
                                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($document['first_name'] . ' ' . $document['last_name']); ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($document['upload_date'])); ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <?php 
                                    $file_path = $document['file_path'];
                                    $relative_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file_path);
                                    ?>
                                    <a href="<?php echo htmlspecialchars($relative_path); ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="<?php echo htmlspecialchars($relative_path); ?>" 
                                       download="<?php echo htmlspecialchars($document['file_name']); ?>"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteDocument(<?php echo $document['id']; ?>)">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this document? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="document_id" id="deleteDocumentId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // File upload label update
        document.getElementById('document_file').addEventListener('change', function() {
            const label = this.nextElementSibling;
            if (this.files && this.files[0]) {
                label.innerHTML = '<i class="bi bi-check-circle"></i> ' + this.files[0].name;
                label.classList.add('has-file');
            } else {
                label.innerHTML = '<i class="bi bi-cloud-upload"></i> Choose File';
                label.classList.remove('has-file');
            }
        });
        
        // Delete document function
        function deleteDocument(documentId) {
            document.getElementById('deleteDocumentId').value = documentId;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
    </script>
</body>
</html>
