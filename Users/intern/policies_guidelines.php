<?php
require_once '../../config.php';
require_once '../../controllers/PharmacyDocumentController.php';

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

// Initialize controller
$controller = new PharmacyDocumentController($conn);

// Get filters
$filters = [];
if (isset($_GET['document_type']) && !empty($_GET['document_type'])) {
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policies & Guidelines - MediCare Pharmacy</title>
    
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
            transform: translateX(5px);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="policies-container">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <?php include 'sidebar.php'; ?>
                </div>
                
                <div class="col-md-9">
                    <div class="policies-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2><i class="bi bi-folder-fill"></i> Policies & Guidelines</h2>
                                <p class="text-muted mb-0">View pharmacy policies and guidelines posted by HR</p>
                            </div>
                        </div>
                        
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
                        
                        <!-- Filter Section -->
                        <div class="filter-section">
                            <div class="row align-items-center">
                                <div class="col-md-12">
                                    <h5><i class="bi bi-funnel"></i> Filter Documents</h5>
                                    <form method="GET" class="row g-2 mt-2">
                                        <div class="col-md-4">
                                            <select name="document_type" class="form-select">
                                                <option value="">All Types</option>
                                                <?php foreach ($document_types as $value => $label): ?>
                                                    <option value="<?php echo $value; ?>" <?php echo (isset($_GET['document_type']) && $_GET['document_type'] == $value) ? 'selected' : ''; ?>>
                                                        <?php echo $label; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <select name="category" class="form-select">
                                                <option value="">All Categories</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $category) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($category); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="bi bi-search"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Documents List -->
                        <h4><i class="bi bi-file-earmark-text"></i> Available Documents</h4>
                        
                        <?php if (empty($documents)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-folder" style="font-size: 4rem; color: #6c757d;"></i>
                                <p class="text-muted mt-3">No documents found.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($documents as $document): ?>
                                <div class="document-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h5 class="mb-2">
                                                <i class="bi bi-file-earmark-pdf"></i>
                                                <?php echo htmlspecialchars($document['title']); ?>
                                            </h5>
                                            <?php if (!empty($document['description'])): ?>
                                                <p class="text-muted mb-2"><?php echo htmlspecialchars($document['description']); ?></p>
                                            <?php endif; ?>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <span class="badge bg-primary document-type-badge">
                                                    <i class="bi bi-tag"></i>
                                                    <?php echo $document_types[$document['document_type']] ?? $document['document_type']; ?>
                                                </span>
                                                <span class="badge bg-info document-type-badge">
                                                    <i class="bi bi-folder"></i>
                                                    <?php echo htmlspecialchars($document['category']); ?>
                                                </span>
                                                <small class="text-muted">
                                                    <i class="bi bi-person"></i> Posted by: <?php echo htmlspecialchars($document['first_name'] . ' ' . $document['last_name']); ?>
                                                </small>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($document['upload_date'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2 ms-3">
                                            <?php 
                                            // Convert absolute file path to web-accessible relative path
                                            $file_path = $document['file_path'];
                                            
                                            // Extract just the uploads/pharmacy_documents/filename.ext part
                                            if (strpos($file_path, 'uploads/pharmacy_documents/') !== false) {
                                                $relative_path = '../../' . substr($file_path, strpos($file_path, 'uploads/pharmacy_documents/'));
                                            } else {
                                                // Fallback: try to extract from document root
                                                $relative_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file_path);
                                                if (substr($relative_path, 0, 1) !== '/') {
                                                    $relative_path = '/' . $relative_path;
                                                }
                                                $relative_path = '../..' . $relative_path;
                                            }
                                            ?>
                                            <a href="<?php echo htmlspecialchars($relative_path); ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-outline-primary"
                                               title="View Document">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <a href="<?php echo htmlspecialchars($relative_path); ?>" 
                                               download="<?php echo htmlspecialchars($document['file_name']); ?>"
                                               class="btn btn-sm btn-outline-success"
                                               title="Download Document">
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
