<?php
require_once __DIR__ . '/../core/Controller.php';

class InterviewController extends Controller {
    private $conn;
    
    public function __construct($db = null) {
        parent::__construct($db);
        $this->conn = $db;
    }
    
    /**
     * Create interview record
     */
    public function createInterview($data) {
        try {
            $sql = "INSERT INTO Interview_Records 
                    (application_id, hr_id, interview_type, questions, answers, notes, rating, outcome, feedback) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iisssisss", 
                $data['application_id'], 
                $data['hr_id'], 
                $data['interview_type'], 
                $data['questions'], 
                $data['answers'], 
                $data['notes'], 
                $data['rating'], 
                $data['outcome'], 
                $data['feedback']
            );
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'interview_id' => $stmt->insert_id,
                    'message' => 'Interview record created successfully'
                ];
            } else {
                return ['success' => false, 'errors' => ['Failed to create interview record']];
            }
        } catch (Exception $e) {
            return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Update interview record
     */
    public function updateInterview($id, $data) {
        try {
            $sql = "UPDATE Interview_Records 
                    SET questions = ?, answers = ?, notes = ?, rating = ?, outcome = ?, feedback = ?, updated_at = NOW() 
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssisssi", 
                $data['questions'], 
                $data['answers'], 
                $data['notes'], 
                $data['rating'], 
                $data['outcome'], 
                $data['feedback'], 
                $id
            );
            
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get interview by ID
     */
    public function getInterviewById($id) {
        $sql = "SELECT ir.*, ia.user_id as applicant_id, u.first_name, u.last_name, u.email,
                       hr.first_name as hr_first_name, hr.last_name as hr_last_name
                FROM Interview_Records ir 
                JOIN internship_applications ia ON ir.application_id = ia.id
                JOIN users u ON ia.user_id = u.id
                JOIN users hr ON ir.hr_id = hr.id
                WHERE ir.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Get interviews by application ID
     */
    public function getInterviewsByApplication($application_id) {
        $sql = "SELECT ir.*, u.first_name as hr_first_name, u.last_name as hr_last_name
                FROM Interview_Records ir 
                JOIN users u ON ir.hr_id = u.id
                WHERE ir.application_id = ? 
                ORDER BY ir.interview_date DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        
        $interviews = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $interviews[] = $row;
        }
        
        return $interviews;
    }
    
    /**
     * Get all interviews
     */
    public function getAllInterviews($filters = []) {
        $sql = "SELECT ir.*, ia.user_id as applicant_id, u.first_name, u.last_name, u.email,
                       hr.first_name as hr_first_name, hr.last_name as hr_last_name
                FROM Interview_Records ir 
                JOIN internship_applications ia ON ir.application_id = ia.id
                JOIN users u ON ia.user_id = u.id
                JOIN users hr ON ir.hr_id = hr.id
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        // Apply filters
        if (!empty($filters['outcome'])) {
            $sql .= " AND ir.outcome = ?";
            $params[] = $filters['outcome'];
            $types .= "s";
        }
        
        if (!empty($filters['hr_id'])) {
            $sql .= " AND ir.hr_id = ?";
            $params[] = $filters['hr_id'];
            $types .= "i";
        }
        
        $sql .= " ORDER BY ir.interview_date DESC";
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        
        $interviews = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $interviews[] = $row;
        }
        
        return $interviews;
    }
    
    /**
     * Create employee profile from successful interview
     */
    public function createEmployeeProfile($interview_id, $data) {
        $this->conn->begin_transaction();
        
        try {
            // Get interview details
            $interview = $this->getInterviewById($interview_id);
            if (!$interview) {
                throw new Exception("Interview not found");
            }
            
            // Check if employee profile already exists
            $check_sql = "SELECT id FROM Employee_Profile WHERE user_id = ?";
            $check_stmt = $this->conn->prepare($check_sql);
            $check_stmt->bind_param("i", $interview['applicant_id']);
            $check_stmt->execute();
            $existing = $check_stmt->get_result()->fetch_assoc();
            
            if ($existing) {
                throw new Exception("Employee profile already exists");
            }
            
            // Generate employee ID
            $employee_id = $this->generateEmployeeId();
            
            // Create employee profile
            $sql = "INSERT INTO Employee_Profile 
                    (user_id, interview_id, employee_id, position, department, start_date, supervisor_id, salary) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iissssid", 
                $interview['applicant_id'], 
                $interview_id, 
                $employee_id, 
                $data['position'], 
                $data['department'], 
                $data['start_date'], 
                $data['supervisor_id'], 
                $data['salary']
            );
            
            $stmt->execute();
            
            // Update interview outcome to 'Hired'
            $this->updateInterview($interview_id, [
                'questions' => $interview['questions'],
                'answers' => $interview['answers'],
                'notes' => $interview['notes'],
                'rating' => $interview['rating'],
                'outcome' => 'Hired',
                'feedback' => $interview['feedback']
            ]);
            
            // Update user role to 'Intern' if not already
            $update_user_sql = "UPDATE users SET role_id = 1 WHERE id = ?";
            $update_user_stmt = $this->conn->prepare($update_user_sql);
            $update_user_stmt->bind_param("i", $interview['applicant_id']);
            $update_user_stmt->execute();
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'employee_id' => $employee_id,
                'message' => 'Employee profile created successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'errors' => ['Error creating employee profile: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Generate unique employee ID
     */
    private function generateEmployeeId() {
        do {
            $year = date('Y');
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $employee_id = "EMP{$year}{$random}";
            
            $check_sql = "SELECT id FROM Employee_Profile WHERE employee_id = ?";
            $check_stmt = $this->conn->prepare($check_sql);
            $check_stmt->bind_param("s", $employee_id);
            $check_stmt->execute();
            $exists = $check_stmt->get_result()->fetch_assoc();
            
        } while ($exists);
        
        return $employee_id;
    }
    
    /**
     * Get employee profile by user ID
     */
    public function getEmployeeProfile($user_id) {
        $sql = "SELECT ep.*, u.first_name, u.last_name, u.email,
                       ir.interview_date, ir.rating, ir.feedback
                FROM Employee_Profile ep 
                JOIN users u ON ep.user_id = u.id
                JOIN Interview_Records ir ON ep.interview_id = ir.id
                WHERE ep.user_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Get all employee profiles
     */
    public function getAllEmployeeProfiles() {
        $sql = "SELECT ep.*, u.first_name, u.last_name, u.email,
                       ir.interview_date, ir.rating, ir.feedback
                FROM Employee_Profile ep 
                JOIN users u ON ep.user_id = u.id
                JOIN Interview_Records ir ON ep.interview_id = ir.id
                ORDER BY ep.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        $profiles = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $profiles[] = $row;
        }
        
        return $profiles;
    }
    
    /**
     * Get interview statistics
     */
    public function getInterviewStats() {
        $sql = "SELECT 
                    COUNT(*) as total_interviews,
                    COUNT(CASE WHEN outcome = 'Recommended' THEN 1 END) as recommended,
                    COUNT(CASE WHEN outcome = 'Not Recommended' THEN 1 END) as not_recommended,
                    COUNT(CASE WHEN outcome = 'Hired' THEN 1 END) as hired,
                    COUNT(CASE WHEN outcome = 'Pending' THEN 1 END) as pending,
                    AVG(rating) as average_rating
                FROM Interview_Records";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
}
?>
