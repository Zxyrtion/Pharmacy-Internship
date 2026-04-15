<?php
require_once '../../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'HR Personnel') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$intern_id = $_GET['intern_id'] ?? null;

if (!$intern_id) {
    echo json_encode(['success' => false, 'error' => 'Missing intern_id']);
    exit();
}

// Debug: Check what intern_id we received
error_log("Fetching tasks for intern_id: " . $intern_id);

// Check if tasks table exists
$table_check = $conn->query("SHOW TABLES LIKE 'tasks'");
$table_check2 = $conn->query("SHOW TABLES LIKE 'internship_routine'");

// Determine which table to use
$tasks_table = null;
if ($table_check && $table_check->num_rows > 0) {
    $tasks_table = 'tasks';
} elseif ($table_check2 && $table_check2->num_rows > 0) {
    $tasks_table = 'internship_routine';
}

if (!$tasks_table) {
    // Neither table exists
    echo json_encode([
        'success' => true,
        'tasks' => [],
        'debug' => 'No tasks table found (checked: tasks, internship_routine)'
    ]);
    exit();
}

// First, let's check what columns exist in the table
$columns_check = $conn->query("SHOW COLUMNS FROM `{$tasks_table}`");
$columns = [];
while ($col = $columns_check->fetch_assoc()) {
    $columns[] = $col['Field'];
}

// Determine the correct column name for user assignment
$user_column = 'user_id'; // Default for internship_routine
if ($tasks_table === 'tasks') {
    if (in_array('assigned_to', $columns)) {
        $user_column = 'assigned_to';
    }
}

// Debug: Check all tasks in the table to see what user_ids exist
$debug_all_sql = "SELECT id, user_id, assigned_to_user_id, title, status FROM `{$tasks_table}` LIMIT 10";
$debug_all_result = $conn->query($debug_all_sql);
$all_tasks_debug = [];
while ($row = $debug_all_result->fetch_assoc()) {
    $all_tasks_debug[] = $row;
}

// Get all tasks for this intern - try both user_id and assigned_to_user_id
$tasks_sql = "SELECT t.*, 
              u.first_name as assigned_by_first_name, 
              u.last_name as assigned_by_last_name
              FROM `{$tasks_table}` t
              LEFT JOIN users u ON t.assigned_by_user_id = u.id
              WHERE t.{$user_column} = ? OR t.assigned_to_user_id = ?
              ORDER BY 
                CASE LOWER(t.status)
                    WHEN 'in_progress' THEN 1
                    WHEN 'pending' THEN 2
                    WHEN 'completed' THEN 3
                    WHEN 'finished' THEN 3
                    ELSE 4
                END,
                t.id DESC";

$stmt = $conn->prepare($tasks_sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("ii", $intern_id, $intern_id);
$stmt->execute();
$result = $stmt->get_result();

$tasks = [];
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

echo json_encode([
    'success' => true,
    'tasks' => $tasks,
    'debug' => [
        'intern_id' => $intern_id,
        'table_used' => $tasks_table,
        'user_column' => $user_column,
        'columns' => $columns,
        'all_tasks_sample' => $all_tasks_debug,
        'total_found' => count($tasks)
    ]
]);
?>
