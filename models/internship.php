<?php

class Internship {
    private $conn;
    private $table_name = "internship_records";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new internship record
    public function create($user_id) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, first_name, last_name, date_of_birth, is_at_least_18) 
                  VALUES (:user_id, :first_name, :last_name, :date_of_birth, :is_at_least_18)";
        
        $stmt = $this->conn->prepare($query);
        
        // Get user info from database
        $user_query = "SELECT first_name, last_name, birth_date FROM users WHERE id = :user_id";
        $user_stmt = $this->conn->prepare($user_query);
        $user_stmt->bindParam(':user_id', $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        $first_name = $user_result['first_name'] ?? '';
        $last_name = $user_result['last_name'] ?? '';
        $birth_date = $user_result['birth_date'] ?? null;
        
        // Calculate age if birth date is available
        $is_at_least_18 = 0;
        if (!empty($birth_date)) {
            $is_at_least_18 = $this->calculateAge($birth_date) >= 18 ? 1 : 0;
        }
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':date_of_birth', $birth_date);
        $stmt->bindParam(':is_at_least_18', $is_at_least_18);
        
        return $stmt->execute();
    }

    // Get internship record by user ID
    public function getByUserId($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update specific field
    public function updateField($user_id, $field, $value) {
        // Handle date of birth and age calculation
        if ($field === 'date_of_birth') {
            $query = "UPDATE " . $this->table_name . " 
                      SET date_of_birth = ?, is_at_least_18 = ? 
                      WHERE user_id = ?";
            
            $stmt = $this->conn->prepare($query);
            
            // Calculate if user is at least 18
            $is_18 = $this->calculateAge($value) >= 18 ? 1 : 0;
            
            $stmt->bindParam(1, $value);
            $stmt->bindParam(2, $is_18);
            $stmt->bindParam(3, $user_id);
        } else {
            $query = "UPDATE " . $this->table_name . " SET " . $field . " = ? WHERE user_id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $value);
            $stmt->bindParam(2, $user_id);
        }
        
        if ($stmt->execute()) {
            // Check eligibility after update
            $this->checkEligibility($user_id);
            return true;
        }
        
        return false;
    }

    // Calculate age from date of birth
    private function calculateAge($date_of_birth) {
        if (empty($date_of_birth)) return 0;
        
        $birth_date = new DateTime($date_of_birth);
        $today = new DateTime();
        $age = $today->diff($birth_date)->y;
        
        return $age;
    }

    // Check if user meets all eligibility requirements
    public function checkEligibility($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$record) return false;
        
        // Check all requirements
        $is_eligible = (
            $record['is_enrolled_higher_ed'] && 
            $record['is_enrolled_internship_subject'] && 
            $record['is_at_least_18'] && 
            $record['has_passed_pre_internship'] && 
            $record['medical_certificate_submitted'] && 
            $record['parental_consent_submitted']
        );
        
        // Update eligibility status
        $update_query = "UPDATE " . $this->table_name . " SET is_eligible = ? WHERE user_id = ?";
        $update_stmt = $this->conn->prepare($update_query);
        $update_stmt->bindParam(1, $is_eligible);
        $update_stmt->bindParam(2, $user_id);
        
        return $update_stmt->execute();
    }

    // Handle file upload for documents
    public function uploadDocument($user_id, $document_type, $file, $institution_name = null) {
        // Check if file was uploaded
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        // Validate file type
        $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            return false;
        }
        
        // Create upload directory if it doesn't exist
        $upload_dir = '../../uploads/internship_documents/' . $user_id . '/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $filename = $document_type . '_' . time() . '.' . $file_extension;
        $filepath = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Update database
            $field_name = $document_type . '_path';
            $submitted_field = $document_type . '_submitted';
            
            // Special handling for enrollment certificate
            if ($document_type === 'enrollment_certificate') {
                // Update institution name
                $this->updateField($user_id, 'higher_ed_institution', $institution_name);
                
                // Update file path using enrollment_certificate_path field
                $this->updateField($user_id, 'enrollment_certificate_path', $filename);
                
                // Mark as enrolled in higher ed
                $this->updateField($user_id, 'is_enrolled_higher_ed', 1);
                
                // Automatically mark internship subject enrollment as completed (16.1.2)
                // since COE proves enrollment in internship subject
                $this->updateField($user_id, 'is_enrolled_internship_subject', 1);
            } elseif ($document_type === 'recommendation_letter') {
                // Update file path
                $this->updateField($user_id, 'recommendation_letter_path', $filename);
                
                // Mark pre-internship requirements as passed
                $this->updateField($user_id, 'has_passed_pre_internship', 1);
            } else {
                // Update file path
                $this->updateField($user_id, $field_name, $filename);
                
                // Mark as submitted
                $this->updateField($user_id, $submitted_field, 1);
            }
            
            return true;
        }
        
        return false;
    }

    // Get all internship applications (for admin)
    public function getAllApplications() {
        $query = "SELECT ir.*, u.username, u.email 
                  FROM " . $this->table_name . " ir
                  LEFT JOIN users u ON ir.user_id = u.id
                  ORDER BY ir.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update application status (for admin)
    public function updateApplicationStatus($user_id, $status) {
        $query = "UPDATE " . $this->table_name . " 
                  SET application_status = ? 
                  WHERE user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->bindParam(2, $user_id);
        
        return $stmt->execute();
    }

    // Get application statistics
    public function getStatistics() {
        $query = "SELECT 
                    COUNT(*) as total_applications,
                    SUM(CASE WHEN application_status = 'submitted' THEN 1 ELSE 0 END) as submitted,
                    SUM(CASE WHEN application_status = 'under_review' THEN 1 ELSE 0 END) as under_review,
                    SUM(CASE WHEN application_status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN application_status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN is_eligible = 1 THEN 1 ELSE 0 END) as eligible
                  FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>