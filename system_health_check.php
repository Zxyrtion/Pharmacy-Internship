<?php
// Comprehensive System Health Check
echo "<h1>System Health Check - GitHub Merge Issues</h1>";

$errors = [];
$warnings = [];

// Check PHP syntax of all PHP files
$dir = __DIR__;
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$php_files = [];
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $php_files[] = $file->getPathname();
    }
}

echo "<h2>Checking " . count($php_files) . " PHP files...</h2>";

$syntax_errors = [];
foreach ($php_files as $file) {
    // Skip vendor and node_modules if they exist
    if (strpos($file, 'vendor') !== false || strpos($file, 'node_modules') !== false) {
        continue;
    }
    
    $output = [];
    $return_var = 0;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return_var);
    
    if ($return_var !== 0) {
        $syntax_errors[] = [
            'file' => str_replace($dir, '', $file),
            'error' => implode("\n", $output)
        ];
    }
}

if (!empty($syntax_errors)) {
    echo "<div style='color: red;'><h3>Syntax Errors Found:</h3>";
    foreach ($syntax_errors as $err) {
        echo "<p><strong>" . htmlspecialchars($err['file']) . ":</strong><br>";
        echo "<pre>" . htmlspecialchars($err['error']) . "</pre></p>";
    }
    echo "</div>";
} else {
    echo "<div style='color: green;'>✓ No PHP syntax errors found</div>";
}

// Check database connectivity
echo "<h2>Database Connectivity</h2>";
try {
    require_once 'config.php';
    if (isset($conn) && $conn->ping()) {
        echo "<div style='color: green;'>✓ Database connection OK</div>";
        
        // Check required tables
        $required_tables = [
            'users', 'product_inventory', 'inventory_report', 
            'requisition_reports', 'requisition_report_items', 'notifications'
        ];
        
        echo "<h3>Table Check:</h3>";
        foreach ($required_tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                echo "<div style='color: green;'>✓ $table</div>";
            } else {
                echo "<div style='color: red;'>✗ $table MISSING</div>";
            }
        }
    } else {
        echo "<div style='color: red;'>✗ Database connection failed</div>";
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>✗ Error: " . $e->getMessage() . "</div>";
}

echo "<h2>Completed</h2>";
echo "<p>Check complete. Review any errors above.</p>";
?>
