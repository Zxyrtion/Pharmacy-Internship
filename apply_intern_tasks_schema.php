<?php
require_once 'config.php';

echo "Applying Intern Tasks Schema...\n\n";

// Read the SQL file
$sql_file = __DIR__ . '/database/intern_tasks_schema.sql';

if (!file_exists($sql_file)) {
    die("Error: SQL file not found at $sql_file\n");
}

$sql = file_get_contents($sql_file);

if ($sql === false) {
    die("Error: Could not read SQL file\n");
}

// Split SQL into individual statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        return !empty($stmt) && 
               !preg_match('/^--/', $stmt) && 
               !preg_match('/^\/\*/', $stmt);
    }
);

$success_count = 0;
$error_count = 0;

foreach ($statements as $statement) {
    if (empty(trim($statement))) {
        continue;
    }
    
    // Add semicolon back
    $statement = trim($statement) . ';';
    
    echo "Executing statement...\n";
    
    if ($conn->query($statement)) {
        $success_count++;
        echo "✓ Success\n\n";
    } else {
        $error_count++;
        echo "✗ Error: " . $conn->error . "\n\n";
    }
}

echo "\n========================================\n";
echo "Schema Application Complete!\n";
echo "========================================\n";
echo "Successful statements: $success_count\n";
echo "Failed statements: $error_count\n";
echo "========================================\n\n";

if ($error_count === 0) {
    echo "✓ All tables created successfully!\n";
    echo "\nYou can now:\n";
    echo "1. View ready interns at: Users/hr/view_ready_interns.php\n";
    echo "2. Each intern will show task progress (Finished, Pending, In Progress, Late)\n";
    echo "3. Click 'View Tasks' to see detailed task list\n";
} else {
    echo "⚠ Some errors occurred. Please check the output above.\n";
}

$conn->close();
?>
