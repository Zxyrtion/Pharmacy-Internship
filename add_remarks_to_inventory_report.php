<?php
require_once 'config.php';

echo "Adding remarks column to inventory_report table...\n\n";

$sql = "ALTER TABLE inventory_report ADD COLUMN remarks TEXT DEFAULT NULL AFTER status";

if ($conn->query($sql)) {
    echo "✓ Column 'remarks' added successfully!\n\n";
    
    // Show updated structure
    $result = $conn->query("DESCRIBE inventory_report");
    if ($result) {
        echo "Updated table structure:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-25s %-20s %-10s %-10s\n", "Field", "Type", "Null", "Key");
        echo str_repeat("-", 80) . "\n";
        
        while ($row = $result->fetch_assoc()) {
            printf("%-25s %-20s %-10s %-10s\n", 
                $row['Field'], 
                $row['Type'], 
                $row['Null'], 
                $row['Key']
            );
        }
    }
} else {
    echo "✗ Error adding column: " . $conn->error . "\n";
}

$conn->close();
?>
