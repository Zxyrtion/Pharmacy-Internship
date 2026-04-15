<?php
require_once '../../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'Intern') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_id'])) {
    $schedule_id = $_POST['schedule_id'];
    
    // Verify the schedule belongs to this user
    $verify_sql = "SELECT id FROM work_schedules WHERE id = ? AND user_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $schedule_id, $user_id);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Schedule not found or unauthorized']);
        exit();
    }
    
    // Update schedule status to acknowledged
    $update_sql = "UPDATE work_schedules 
                   SET status = 'acknowledged', acknowledged_at = NOW() 
                   WHERE id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $schedule_id, $user_id);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Schedule acknowledged successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update schedule']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
