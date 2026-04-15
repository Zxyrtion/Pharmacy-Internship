<?php
require_once '../../config.php';

// Check if user is HR
if (!isLoggedIn() || $_SESSION['role_name'] !== 'HR Personnel') {
    header('Location: ../../index.php');
    exit();
}

// Function to create task and notify intern
function createTaskWithNotification($conn, $taskData) {
    // Insert task
    $stmt = $conn->prepare("INSERT INTO internship_routine (title, duties, date_from, date_to, assigned_to, assigned_by_user_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
    
    if ($stmt) {
        $stmt->bind_param("sssii", $taskData['title'], $taskData['duties'], $taskData['date_from'], $taskData['date_to'], $taskData['assigned_to'], $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $task_id = $conn->insert_id;
            
            // Create notification for intern
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, related_id, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
            if ($notif_stmt) {
                $notif_type = "task_assigned";
                $title = "New Task Assigned";
                $message = "You have been assigned a new task: " . $taskData['title'];
                $notif_stmt->bind_param("isssi", $taskData['assigned_to'], $notif_type, $title, $message, $task_id);
                $notif_stmt->execute();
            }
            
            return $task_id;
        }
    }
    return false;
}

// Example usage - this should be integrated into your HR task creation form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskData = [
        'title' => $_POST['title'] ?? '',
        'duties' => $_POST['duties'] ?? '',
        'date_from' => $_POST['date_from'] ?? '',
        'date_to' => $_POST['date_to'] ?? '',
        'assigned_to' => (int)($_POST['assigned_to'] ?? 0)
    ];
    
    if (createTaskWithNotification($conn, $taskData)) {
        echo "Task created and intern notified successfully!";
    } else {
        echo "Failed to create task.";
    }
}
?>
