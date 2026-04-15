<?php
require_once __DIR__ . '/../core/Controller.php';

class PharmacyDocumentController extends Controller {
    private $conn;
    
    public function __construct($db = null) {
        parent::__construct($db);
        $this->conn = $db;
    }
    
    /**
     * Upload pharmacy business document
     */
    public function uploadDocument($data, $file) {
        $errors = [];
        
        // Validate required fields
        $required_fields = ['title', 'document_type', 'category'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst($field) . " is required.";
            }
        }
        
        // Validate file upload
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Document file is required.";
        } else {
            $validation_result = $this->validateFile($file);
            if (!$validation_result['success']) {
                $errors = array_merge($errors, $validation_result['errors']);
            }
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Create upload directory if it doesn't exist
        $upload_dir = __DIR__ . '/../uploads/pharmacy_documents/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Upload file
        $upload_result = $this->uploadFile($file, $upload_dir);
        if (!$upload_result['success']) {
            return ['success' => false, 'errors' => [$upload_result['error']]];
        }
        
        // Save to database
        return $this->saveDocument($data, $upload_result['file_info']);
    }
    
    /**
     * Validate uploaded file
     */
    private function validateFile($file) {
        $errors = [];
        $allowed_types = ['pdf', 'doc', 'docx', 'txt', 'ppt', 'pptx', 'xls', 'xlsx'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            $errors[] = "Only PDF, DOC, DOCX, TXT, PPT, PPTX, XLS, and XLSX files are allowed.";
        }
        
        if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
            $errors[] = "File size must be less than 10MB.";
        }
        
        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Upload file to server
     */
    private function uploadFile($file, $upload_dir) {
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $unique_filename = 'pharmacy_doc_' . time() . '_' . uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $unique_filename;
        
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            return [
                'success' => true,
                'file_info' => [
                    'name' => $file['name'],
                    'path' => $upload_path,
                    'size' => $file['size'],
                    'type' => $file['type']
                ]
            ];
        } else {
            return [
                'success' => false,
                'error' => "Failed to upload file."
            ];
        }
    }
    
    /**
     * Save document to database
     */
    private function saveDocument($data, $file_info) {
        try {
            $sql = "INSERT INTO Pharmacy_Business_Document 
                    (title, description, document_type, category, file_name, file_path, file_size, file_type, uploaded_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssssssisi", 
                $data['title'], 
                $data['description'], 
                $data['document_type'], 
                $data['category'], 
                $file_info['name'], 
                $file_info['path'], 
                $file_info['size'], 
                $file_info['type'], 
                $data['uploaded_by']
            );
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'document_id' => $stmt->insert_id,
                    'message' => 'Document uploaded successfully'
                ];
            } else {
                // Clean up uploaded file on database error
                if (file_exists($file_info['path'])) {
                    unlink($file_info['path']);
                }
                return ['success' => false, 'errors' => ['Database error: Failed to save document']];
            }
        } catch (Exception $e) {
            // Clean up uploaded file on exception
            if (file_exists($file_info['path'])) {
                unlink($file_info['path']);
            }
            return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Get all documents
     */
    public function getAllDocuments($filters = []) {
        $sql = "SELECT pbd.*, u.first_name, u.last_name 
                FROM Pharmacy_Business_Document pbd 
                JOIN users u ON pbd.uploaded_by = u.id 
                WHERE pbd.is_active = 1";
        
        $params = [];
        $types = "";
        
        // Apply filters
        if (!empty($filters['document_type'])) {
            $sql .= " AND pbd.document_type = ?";
            $params[] = $filters['document_type'];
            $types .= "s";
        }
        
        if (!empty($filters['category'])) {
            $sql .= " AND pbd.category = ?";
            $params[] = $filters['category'];
            $types .= "s";
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (pbd.title LIKE ? OR pbd.description LIKE ?)";
            $search_term = "%" . $filters['search'] . "%";
            $params[] = $search_term;
            $params[] = $search_term;
            $types .= "ss";
        }
        
        $sql .= " ORDER BY pbd.upload_date DESC";
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        
        $documents = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $documents[] = $row;
        }
        
        return $documents;
    }
    
    /**
     * Get document by ID
     */
    public function getDocumentById($id) {
        $sql = "SELECT pbd.*, u.first_name, u.last_name 
                FROM Pharmacy_Business_Document pbd 
                JOIN users u ON pbd.uploaded_by = u.id 
                WHERE pbd.id = ? AND pbd.is_active = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Update document
     */
    public function updateDocument($id, $data) {
        try {
            $sql = "UPDATE Pharmacy_Business_Document 
                    SET title = ?, description = ?, document_type = ?, category = ?, last_updated = NOW() 
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssssi", 
                $data['title'], 
                $data['description'], 
                $data['document_type'], 
                $data['category'], 
                $id
            );
            
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Delete document (soft delete)
     */
    public function deleteDocument($id) {
        try {
            $sql = "UPDATE Pharmacy_Business_Document SET is_active = 0 WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get document categories
     */
    public function getCategories() {
        $sql = "SELECT DISTINCT category FROM Pharmacy_Business_Document WHERE is_active = 1 ORDER BY category";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        $categories = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
        
        return $categories;
    }
    
    /**
     * Get document statistics
     */
    public function getDocumentStats() {
        $sql = "SELECT 
                    COUNT(*) as total_documents,
                    COUNT(CASE WHEN document_type = 'policy' THEN 1 END) as policies,
                    COUNT(CASE WHEN document_type = 'guideline' THEN 1 END) as guidelines,
                    COUNT(CASE WHEN document_type = 'procedure' THEN 1 END) as procedures,
                    COUNT(CASE WHEN document_type = 'manual' THEN 1 END) as manuals,
                    COUNT(CASE WHEN document_type = 'regulation' THEN 1 END) as regulations,
                    COUNT(CASE WHEN document_type = 'other' THEN 1 END) as others
                FROM Pharmacy_Business_Document 
                WHERE is_active = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
}
?>
