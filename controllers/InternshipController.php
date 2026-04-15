<?php
require_once __DIR__ . '/../core/Controller.php';

class InternshipController extends Controller {
    private $conn;
    
    public function __construct($db = null) {
        parent::__construct($db);
        $this->conn = $db;
    }
    
    /**
     * Submit internship application
     */
    public function submitApplication($user_id, $documents) {
        $errors = [];
        $uploaded_files = [];
        
        // Define required documents
        $required_documents = [
            'id_card' => 'ID Card / Passport',
            'cv' => 'Curriculum Vitae',
            'reference_letter' => 'Reference Letter from University',
            's1_transcript' => 'S1 Transcripts'
        ];
        
        $optional_documents = [
            's2_transcript' => 'S2 Transcripts',
            's3_transcript' => 'S3 / Latest Transcripts',
            'certificates' => 'Related Certificates'
        ];
        
        // Create upload directory if it doesn't exist
        $upload_dir = __DIR__ . '/../uploads/internship_records/' . $user_id . '/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Validate and upload files
        foreach ($required_documents as $field => $label) {
            if (!isset($documents[$field]) || $documents[$field]['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "$label is required.";
            } else {
                $file_info = $documents[$field];
                $validation_result = $this->validateFile($file_info, $label);
                
                if ($validation_result['success']) {
                    $upload_result = $this->uploadFile($file_info, $field, $upload_dir);
                    if ($upload_result['success']) {
                        $uploaded_files[$field] = $upload_result['file_info'];
                    } else {
                        $errors[] = $upload_result['error'];
                    }
                } else {
                    $errors = array_merge($errors, $validation_result['errors']);
                }
            }
        }
        
        // Handle optional documents
        foreach ($optional_documents as $field => $label) {
            if (isset($documents[$field]) && $documents[$field]['error'] === UPLOAD_ERR_OK) {
                $file_info = $documents[$field];
                $validation_result = $this->validateFile($file_info, $label);
                
                if ($validation_result['success']) {
                    $upload_result = $this->uploadFile($file_info, $field, $upload_dir);
                    if ($upload_result['success']) {
                        $uploaded_files[$field] = $upload_result['file_info'];
                    } else {
                        $errors[] = $upload_result['error'];
                    }
                } else {
                    $errors = array_merge($errors, $validation_result['errors']);
                }
            }
        }
        
        // If no errors, create application record
        if (empty($errors)) {
            return $this->createApplicationRecord($user_id, $uploaded_files);
        } else {
            // Clean up uploaded files on error
            $this->cleanupUploadedFiles($uploaded_files);
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
    }
    
    /**
     * Validate uploaded file
     */
    private function validateFile($file_info, $label) {
        $errors = [];
        $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $file_extension = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            $errors[] = "$label: Only PDF, DOC, DOCX, JPG, JPEG, and PNG files are allowed.";
        }
        
        if ($file_info['size'] > 5 * 1024 * 1024) { // 5MB limit
            $errors[] = "$label: File size must be less than 5MB.";
        }
        
        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Upload file to server
     */
    private function uploadFile($file_info, $field, $upload_dir) {
        $file_extension = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
        $unique_filename = $field . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $unique_filename;
        
        if (move_uploaded_file($file_info['tmp_name'], $upload_path)) {
            return [
                'success' => true,
                'file_info' => [
                    'name' => $file_info['name'],
                    'path' => $upload_path,
                    'size' => $file_info['size'],
                    'type' => $file_info['type']
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
     * Create application record in database
     */
    private function createApplicationRecord($user_id, $uploaded_files) {
        $this->conn->begin_transaction();
        
        try {
            // Insert application record
            $app_sql = "INSERT INTO internship_applications (user_id) VALUES (?)";
            $app_stmt = $this->conn->prepare($app_sql);
            $app_stmt->bind_param("i", $user_id);
            $app_stmt->execute();
            $application_id = $app_stmt->insert_id;
            
            // Insert document records
            $doc_sql = "INSERT INTO internship_records (application_id, document_type, file_name, file_path, file_size, file_type) VALUES (?, ?, ?, ?, ?, ?)";
            $doc_stmt = $this->conn->prepare($doc_sql);
            
            foreach ($uploaded_files as $doc_type => $file_info) {
                $doc_stmt->bind_param("isssis", $application_id, $doc_type, $file_info['name'], $file_info['path'], $file_info['size'], $file_info['type']);
                $doc_stmt->execute();
            }
            
            $this->conn->commit();
            return [
                'success' => true,
                'application_id' => $application_id,
                'message' => 'Application submitted successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'errors' => ['Database error: ' . $e->getMessage()]
            ];
        }
    }
    
    /**
     * Clean up uploaded files on error
     */
    private function cleanupUploadedFiles($uploaded_files) {
        foreach ($uploaded_files as $file_info) {
            if (file_exists($file_info['path'])) {
                unlink($file_info['path']);
            }
        }
    }
    
    /**
     * Check if user has existing application
     */
    public function hasExistingApplication($user_id) {
        $sql = "SELECT id, status FROM internship_applications WHERE user_id = ? AND status IN ('pending', 'under_review', 'approved')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get application details
     */
    public function getApplicationDetails($application_id) {
        $sql = "SELECT ir.*, u.email, ir.created_at as application_date, ir.application_status as status
                FROM internship_records ir 
                JOIN users u ON ir.user_id = u.id 
                WHERE ir.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Get application documents
     */
    public function getApplicationDocuments($application_id) {
        $application = $this->getApplicationDetails($application_id);
        if (!$application) {
            return [];
        }
        
        $user_id = $application['user_id'];
        $documents = [];
        
        // Check enrollment certificate
        if ($application['is_enrolled_higher_ed'] && !empty($application['enrollment_certificate_path'])) {
            $file_path = __DIR__ . '/../uploads/internship_documents/' . $user_id . '/' . $application['enrollment_certificate_path'];
            if (file_exists($file_path)) {
                $documents[] = [
                    'document_type' => 'enrollment_certificate',
                    'file_name' => $application['enrollment_certificate_path'],
                    'file_path' => $file_path,
                    'file_size' => filesize($file_path),
                    'uploaded_at' => $application['created_at']
                ];
            }
        }
        
        // Check recommendation letter
        if ($application['has_passed_pre_internship'] && !empty($application['recommendation_letter_path'])) {
            $file_path = __DIR__ . '/../uploads/internship_documents/' . $user_id . '/' . $application['recommendation_letter_path'];
            if (file_exists($file_path)) {
                $documents[] = [
                    'document_type' => 'recommendation_letter',
                    'file_name' => $application['recommendation_letter_path'],
                    'file_path' => $file_path,
                    'file_size' => filesize($file_path),
                    'uploaded_at' => $application['created_at']
                ];
            }
        }
        
        // Check medical certificate
        if ($application['medical_certificate_submitted'] && !empty($application['medical_certificate_path'])) {
            $file_path = __DIR__ . '/../uploads/internship_documents/' . $user_id . '/' . $application['medical_certificate_path'];
            if (file_exists($file_path)) {
                $documents[] = [
                    'document_type' => 'medical_certificate',
                    'file_name' => $application['medical_certificate_path'],
                    'file_path' => $file_path,
                    'file_size' => filesize($file_path),
                    'uploaded_at' => $application['created_at']
                ];
            }
        }
        
        // Check parental consent
        if ($application['parental_consent_submitted'] && !empty($application['parental_consent_path'])) {
            $file_path = __DIR__ . '/../uploads/internship_documents/' . $user_id . '/' . $application['parental_consent_path'];
            if (file_exists($file_path)) {
                $documents[] = [
                    'document_type' => 'parental_consent',
                    'file_name' => $application['parental_consent_path'],
                    'file_path' => $file_path,
                    'file_size' => filesize($file_path),
                    'uploaded_at' => $application['created_at']
                ];
            }
        }
        
        return $documents;
    }
    
    /**
     * Update application status
     */
    public function updateApplicationStatus($application_id, $status, $reviewer_id, $review_notes = null) {
        $sql = "UPDATE internship_records 
                SET application_status = ?, updated_at = NOW() 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $application_id);
        
        $result = $stmt->execute();
        
        // Create notification if status is approved
        if ($result && $status === 'approved') {
            // Get user_id from application
            $app_sql = "SELECT user_id FROM internship_records WHERE id = ?";
            $app_stmt = $this->conn->prepare($app_sql);
            $app_stmt->bind_param("i", $application_id);
            $app_stmt->execute();
            $app_result = $app_stmt->get_result();
            $application = $app_result->fetch_assoc();
            
            if ($application) {
                // Create notification using mysqli
                $notif_sql = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                             VALUES (?, ?, ?, ?, ?)";
                $notif_stmt = $this->conn->prepare($notif_sql);
                $notif_type = 'internship_approved';
                $notif_title = 'Internship Application Approved!';
                $notif_message = 'Congratulations! Your internship application has been approved. Click to view your schedule and location details.';
                $notif_stmt->bind_param(
                    "isssi",
                    $application['user_id'],
                    $notif_type,
                    $notif_title,
                    $notif_message,
                    $application_id
                );
                $notif_stmt->execute();
            }
        }
        
        return $result;
    }
    
    /**
     * Set internship schedule and location details
     */
    public function setInternshipSchedule($application_id, $schedule_data, $hr_user_id) {
        $this->conn->begin_transaction();
        
        try {
            // Update internship record with schedule and location
            $sql = "UPDATE internship_records 
                    SET internship_start_date = ?,
                        internship_duration = ?,
                        working_days = ?,
                        working_hours = ?,
                        special_instructions = ?,
                        pharmacy_name = ?,
                        pharmacy_address = ?,
                        contact_person = ?,
                        contact_number = ?,
                        contact_email = ?,
                        schedule_sent = 1,
                        updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "ssssssssssi",
                $schedule_data['start_date'],
                $schedule_data['duration'],
                $schedule_data['working_days'],
                $schedule_data['working_hours'],
                $schedule_data['special_instructions'],
                $schedule_data['pharmacy_name'],
                $schedule_data['pharmacy_address'],
                $schedule_data['contact_person'],
                $schedule_data['contact_number'],
                $schedule_data['contact_email'],
                $application_id
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update schedule");
            }
            
            // Get user_id from application
            $app_sql = "SELECT user_id FROM internship_records WHERE id = ?";
            $app_stmt = $this->conn->prepare($app_sql);
            $app_stmt->bind_param("i", $application_id);
            $app_stmt->execute();
            $app_result = $app_stmt->get_result();
            $application = $app_result->fetch_assoc();
            
            if (!$application) {
                throw new Exception("Application not found");
            }
            
            // Create notification for intern
            $title = 'Internship Schedule & Location Details';
            $message = sprintf(
                'Your internship schedule has been set! Start Date: %s at %s. Click to view full details.',
                date('F d, Y', strtotime($schedule_data['start_date'])),
                $schedule_data['pharmacy_name']
            );
            
            // Insert notification directly using mysqli
            $notif_sql = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                         VALUES (?, ?, ?, ?, ?)";
            $notif_stmt = $this->conn->prepare($notif_sql);
            $notif_type = 'internship_schedule';
            $notif_stmt->bind_param(
                "isssi",
                $application['user_id'],
                $notif_type,
                $title,
                $message,
                $application_id
            );
            
            if (!$notif_stmt->execute()) {
                throw new Exception("Failed to create notification");
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error setting schedule: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all applications for admin review
     */
    public function getAllApplications($status = null) {
        $sql = "SELECT ir.*, u.email, ir.created_at as application_date, 
                ir.application_status as status, ir.schedule_sent, ir.interview_scheduled
                FROM internship_records ir 
                JOIN users u ON ir.user_id = u.id
                WHERE ir.application_status IN ('submitted', 'under_review', 'approved', 'rejected')";
        
        if ($status) {
            $sql .= " AND ir.application_status = ?";
        }
        
        $sql .= " ORDER BY ir.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($status) {
            $stmt->bind_param("s", $status);
        }
        
        $stmt->execute();
        
        $applications = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $applications[] = $row;
        }
        
        return $applications;
    }
    
    /**
     * Get internship schedule details
     */
    public function getScheduleDetails($application_id) {
        $sql = "SELECT internship_start_date, internship_duration, working_days, working_hours,
                special_instructions, pharmacy_name, pharmacy_address, contact_person,
                contact_number, contact_email
                FROM internship_records 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Schedule interview for approved application
     */
    public function scheduleInterview($application_id, $interview_data, $hr_user_id) {
        $this->conn->begin_transaction();
        
        try {
            // Update internship record with interview details
            $sql = "UPDATE internship_records 
                    SET interview_scheduled = 1,
                        interview_date = ?,
                        interview_time = ?,
                        interview_type = ?,
                        interview_location = ?,
                        interview_meeting_link = ?,
                        interview_notes = ?,
                        updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "ssssssi",
                $interview_data['interview_date'],
                $interview_data['interview_time'],
                $interview_data['interview_type'],
                $interview_data['interview_location'],
                $interview_data['interview_meeting_link'],
                $interview_data['interview_notes'],
                $application_id
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to schedule interview");
            }
            
            // Get user_id from application
            $app_sql = "SELECT user_id, first_name, last_name FROM internship_records WHERE id = ?";
            $app_stmt = $this->conn->prepare($app_sql);
            $app_stmt->bind_param("i", $application_id);
            $app_stmt->execute();
            $app_result = $app_stmt->get_result();
            $application = $app_result->fetch_assoc();
            
            if (!$application) {
                throw new Exception("Application not found");
            }
            
            // Create notification for intern
            $interview_type_label = $interview_data['interview_type'] === 'personal' ? 'Personal Interview' : 'Online Interview';
            $date_formatted = date('F d, Y', strtotime($interview_data['interview_date']));
            $time_formatted = date('g:i A', strtotime($interview_data['interview_time']));
            
            $title = 'Interview Scheduled!';
            $message = sprintf(
                'Your interview has been scheduled for %s at %s. Type: %s. Click to view details.',
                $date_formatted,
                $time_formatted,
                $interview_type_label
            );
            
            // Insert notification
            $notif_sql = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                         VALUES (?, ?, ?, ?, ?)";
            $notif_stmt = $this->conn->prepare($notif_sql);
            $notif_type = 'interview_scheduled';
            $notif_stmt->bind_param(
                "isssi",
                $application['user_id'],
                $notif_type,
                $title,
                $message,
                $application_id
            );
            
            if (!$notif_stmt->execute()) {
                throw new Exception("Failed to create notification");
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error scheduling interview: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get interview details
     */
    public function getInterviewDetails($application_id) {
        $sql = "SELECT interview_date, interview_time, interview_type, interview_location,
                interview_meeting_link, interview_notes
                FROM internship_records 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
}
