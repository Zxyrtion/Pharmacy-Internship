<?php
require_once 'config.php';

echo "Checking product_inventory table structure...\n\n";

$result = $conn->query("DESCRIBE product_inventory");

if ($result) {
    echo "product_inventory table columns:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-20s %-20s %-10s %-10s\n", "Field", "Type", "Null", "Key");
    echo str_repeat("-", 80) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        printf("%-20s %-20s %-10s %-10s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key']
        );
    }
} else {
    echo "Error: " . $conn->error . "\n";
    echo "\nTable might not exist. Checking all tables...\n\n";
    
    $tables = $conn->query("SHOW TABLES");
    if ($tables) {
        echo "Available tables:\n";
        while ($table = $tables->fetch_array()) {
            echo "  - " . $table[0] . "\n";
        }
    }
}

$conn->close();
?>
