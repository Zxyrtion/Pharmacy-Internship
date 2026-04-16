<?php
require_once 'config.php';

echo "Creating stock_changes table...\n\n";

$sql = "CREATE TABLE IF NOT EXISTS `stock_changes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `medicine_id` int(11) DEFAULT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `previous_stock` int(11) NOT NULL DEFAULT 0,
  `new_stock` int(11) NOT NULL DEFAULT 0,
  `change_amount` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `reference_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `medicine_id` (`medicine_id`),
  KEY `changed_by` (`changed_by`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql)) {
    echo "✓ Table 'stock_changes' created successfully!\n\n";
    
    // Check if table exists and show structure
    $result = $conn->query("DESCRIBE stock_changes");
    if ($result) {
        echo "Table structure:\n";
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
    echo "✗ Error creating table: " . $conn->error . "\n";
}

$conn->close();
?>
