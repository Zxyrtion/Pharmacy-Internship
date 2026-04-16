<?php
require_once 'config.php';

echo "Inserting Sample Tasks...\n\n";

// Get HR Personnel ID
$hr_sql = "SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'HR Personnel' LIMIT 1";
$hr_result = $conn->query($hr_sql);

if (!$hr_result || $hr_result->num_rows === 0) {
    die("Error: No HR Personnel found in database. Please create an HR user first.\n");
}

$hr_user = $hr_result->fetch_assoc();
$hr_id = $hr_user['id'];

echo "Found HR Personnel ID: $hr_id\n\n";

// Get all interns
$intern_sql = "SELECT u.id, u.first_name, u.last_name FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'Intern'";
$intern_result = $conn->query($intern_sql);

if (!$intern_result || $intern_result->num_rows === 0) {
    die("Error: No interns found in database. Please create intern users first.\n");
}

echo "Found " . $intern_result->num_rows . " intern(s)\n\n";

$success_count = 0;
$error_count = 0;

// Insert sample tasks for each intern
while ($intern = $intern_result->fetch_assoc()) {
    $intern_id = $intern['id'];
    $intern_name = $intern['first_name'] . ' ' . $intern['last_name'];
    
    echo "Creating tasks for: $intern_name (ID: $intern_id)\n";
    
    // Task 1: Pharmacy Orientation
    $sql1 = "INSERT INTO intern_tasks (intern_id, task_title, task_description, assigned_by, due_date, status, priority, category) 
             VALUES (?, 'Complete Pharmacy Orientation', 'Attend and complete the pharmacy orientation program', ?, DATE_ADD(NOW(), INTERVAL 3 DAY), 'Pending', 'High', 'Training')";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->bind_param("ii", $intern_id, $hr_id);
    
    if ($stmt1->execute()) {
        echo "  ✓ Task 1: Pharmacy Orientation\n";
        $success_count++;
    } else {
        echo "  ✗ Task 1 failed: " . $conn->error . "\n";
        $error_count++;
    }
    
    // Task 2: Shadow Senior Pharmacist
    $sql2 = "INSERT INTO intern_tasks (intern_id, task_title, task_description, assigned_by, due_date, status, priority, category) 
             VALUES (?, 'Shadow Senior Pharmacist', 'Observe and learn from senior pharmacist for 2 days', ?, DATE_ADD(NOW(), INTERVAL 7 DAY), 'Pending', 'Medium', 'Training')";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("ii", $intern_id, $hr_id);
    
    if ($stmt2->execute()) {
        echo "  ✓ Task 2: Shadow Senior Pharmacist\n";
        $success_count++;
    } else {
        echo "  ✗ Task 2 failed: " . $conn->error . "\n";
        $error_count++;
    }
    
    // Task 3: Learn Inventory System
    $sql3 = "INSERT INTO intern_tasks (intern_id, task_title, task_description, assigned_by, due_date, status, priority, category) 
             VALUES (?, 'Learn Inventory System', 'Complete training on pharmacy inventory management system', ?, DATE_ADD(NOW(), INTERVAL 5 DAY), 'In Progress', 'High', 'Training')";
    $stmt3 = $conn->prepare($sql3);
    $stmt3->bind_param("ii", $intern_id, $hr_id);
    
    if ($stmt3->execute()) {
        echo "  ✓ Task 3: Learn Inventory System\n";
        $success_count++;
    } else {
        echo "  ✗ Task 3 failed: " . $conn->error . "\n";
        $error_count++;
    }
    
    // Task 4: Customer Service Training (Completed)
    $sql4 = "INSERT INTO intern_tasks (intern_id, task_title, task_description, assigned_by, due_date, completed_date, status, priority, category) 
             VALUES (?, 'Customer Service Training', 'Complete customer service and communication training module', ?, DATE_SUB(NOW(), INTERVAL 2 DAY), NOW(), 'Completed', 'Medium', 'Training')";
    $stmt4 = $conn->prepare($sql4);
    $stmt4->bind_param("ii", $intern_id, $hr_id);
    
    if ($stmt4->execute()) {
        echo "  ✓ Task 4: Customer Service Training (Completed)\n";
        $success_count++;
    } else {
        echo "  ✗ Task 4 failed: " . $conn->error . "\n";
        $error_count++;
    }
    
    // Task 5: Late Task (for testing)
    $sql5 = "INSERT INTO intern_tasks (intern_id, task_title, task_description, assigned_by, due_date, status, priority, category) 
             VALUES (?, 'Submit Weekly Report', 'Submit your first week internship report', ?, DATE_SUB(NOW(), INTERVAL 1 DAY), 'Pending', 'Urgent', 'Documentation')";
    $stmt5 = $conn->prepare($sql5);
    $stmt5->bind_param("ii", $intern_id, $hr_id);
    
    if ($stmt5->execute()) {
        echo "  ✓ Task 5: Submit Weekly Report (Late)\n";
        $success_count++;
    } else {
        echo "  ✗ Task 5 failed: " . $conn->error . "\n";
        $error_count++;
    }
    
    echo "\n";
}

echo "========================================\n";
echo "Sample Tasks Insertion Complete!\n";
echo "========================================\n";
echo "Successful inserts: $success_count\n";
echo "Failed inserts: $error_count\n";
echo "========================================\n\n";

if ($error_count === 0) {
    echo "✓ All sample tasks created successfully!\n";
    echo "\nYou can now:\n";
    echo "1. Login as HR Personnel\n";
    echo "2. Go to: Users/hr/view_ready_interns.php\n";
    echo "3. View task progress for each intern\n";
    echo "4. Click 'View Tasks' to see detailed task list\n";
} else {
    echo "⚠ Some errors occurred. Please check the output above.\n";
}

$conn->close();
?>
