<?php
require_once '../../config.php';
require_once '../../core/Database.php';
require_once '../../controllers/InternshipController.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'Intern') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$application_id = $_GET['application_id'] ?? null;

if (!$application_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Application ID required']);
    exit();
}

try {
    // Get database connection
    $db = new Database();
    $conn = $db->getConnection();

    // Verify this application belongs to the user
    $sql = "SELECT * FROM internship_records WHERE id = :id AND user_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $application_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Application not found']);
        exit();
    }

    // Check if interview is scheduled
    if (!$application['interview_scheduled']) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No interview scheduled']);
        exit();
    }

    // Get interview details directly from the application record
    $interview_details = [
        'interview_date' => $application['interview_date'],
        'interview_time' => $application['interview_time'],
        'interview_type' => $application['interview_type'],
        'interview_location' => $application['interview_location'],
        'interview_meeting_link' => $application['interview_meeting_link'],
        'interview_notes' => $application['interview_notes']
    ];

    // Format the data
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

    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
