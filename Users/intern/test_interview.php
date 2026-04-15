<?php
// Simple test file to debug interview details
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config.php';
require_once '../../core/Database.php';
require_once '../../controllers/InternshipController.php';

echo "<h2>Testing Interview Details</h2>";

// Check if user is logged in
if (!isLoggedIn()) {
    echo "<p>Error: Not logged in</p>";
    exit();
}

echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>Role: " . $_SESSION['role_name'] . "</p>";

$user_id = $_SESSION['user_id'];

try {
    // Get database connection
    $db = new Database();
    $conn = $db->getConnection();
    echo "<p>✓ Database connected</p>";
    
    // Get user's application
    $sql = "SELECT * FROM internship_records WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $application = $result->fetch_assoc();
    
    if ($application) {
        echo "<p>✓ Application found: ID = " . $application['id'] . "</p>";
        echo "<p>Interview Scheduled: " . ($application['interview_scheduled'] ? 'Yes' : 'No') . "</p>";
        
        if ($application['interview_scheduled']) {
            echo "<h3>Interview Details:</h3>";
            echo "<ul>";
            echo "<li>Date: " . $application['interview_date'] . "</li>";
            echo "<li>Time: " . $application['interview_time'] . "</li>";
            echo "<li>Type: " . $application['interview_type'] . "</li>";
            echo "<li>Location: " . $application['interview_location'] . "</li>";
            echo "<li>Meeting Link: " . $application['interview_meeting_link'] . "</li>";
            echo "<li>Notes: " . $application['interview_notes'] . "</li>";
            echo "</ul>";
            
            // Test the controller method
            $controller = new InternshipController($conn);
            $interview_details = $controller->getInterviewDetails($application['id']);
            
            echo "<h3>Controller Method Result:</h3>";
            echo "<pre>";
            print_r($interview_details);
            echo "</pre>";
            
            // Test JSON encoding
            $response = [
                'success' => true,
                'interview' => [
                    'date' => $interview_details['interview_date'],
                    'date_formatted' => date('F d, Y', strtotime($interview_details['interview_date'])),
                    'time' => $interview_details['interview_time'],
                    'time_formatted' => date('g:i A', strtotime($interview_details['interview_time'])),
                    'type' => $interview_details['interview_type'],
                    'location' => $interview_details['interview_location'] ?? '',
                    'meeting_link' => $interview_details['interview_meeting_link'] ?? '',
                    'notes' => $interview_details['interview_notes'] ?? ''
                ]
            ];
            
            echo "<h3>JSON Response:</h3>";
            echo "<pre>";
            echo json_encode($response, JSON_PRETTY_PRINT);
            echo "</pre>";
        } else {
            echo "<p>No interview scheduled yet</p>";
        }
    } else {
        echo "<p>No application found</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
