<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SESSION['role_name'] !== 'Customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Check if file was uploaded
if (!isset($_FILES['receipt_file']) || $_FILES['receipt_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit();
}

$file = $_FILES['receipt_file'];
$notes = trim($_POST['notes'] ?? '');
$customer_id = (int)$_SESSION['user_id'];

// Validate file size (5MB max)
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit']);
    exit();
}

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only images and PDF are allowed']);
    exit();
}

// Create uploads directory if it doesn't exist
$uploadDir = '../../uploads/prescriptions/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'receipt_' . $customer_id . '_' . time() . '_' . uniqid() . '.' . $extension;
$filepath = $uploadDir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit();
}

// Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS prescription_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    notes TEXT,
    status ENUM('Pending', 'Reviewed', 'Processed', 'Rejected') DEFAULT 'Pending',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer (customer_id),
    INDEX idx_status (status)
)");

// Save to database
$stmt = $conn->prepare("INSERT INTO prescription_receipts 
    (customer_id, filename, original_filename, file_path, file_size, mime_type, notes, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");

$stmt->bind_param('isssiis', 
    $customer_id, 
    $filename, 
    $file['name'], 
    $filepath, 
    $file['size'], 
    $mimeType, 
    $notes
);

if ($stmt->execute()) {
    $receipt_id = $conn->insert_id;
    echo json_encode([
        'success' => true, 
        'message' => 'Receipt uploaded successfully',
        'receipt_id' => $receipt_id,
        'filename' => $filename
    ]);
} else {
    // Delete file if database insert fails
    unlink($filepath);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
?>
