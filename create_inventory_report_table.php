<?php
require_once 'config.php';

echo "Creating inventory_report table...\n\n";

$sql = "CREATE TABLE IF NOT EXISTS `inventory_report` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `intern_id` int(11) NOT NULL,
  `inventory_period` varchar(50) NOT NULL,
  `reporter` varchar(100) NOT NULL,
  `reorder_required` enum('Yes','No') DEFAULT 'No',
  `item_number_name` varchar(200) NOT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cost_per_item` decimal(10,2) DEFAULT 0.00,
  `stock_quantity` int(11) DEFAULT 0,
  `inventory_value` decimal(10,2) DEFAULT 0.00,
  `reorder_point` int(11) DEFAULT 0,
  `reorder_cycle` varchar(50) DEFAULT 'Monthly',
  `item_reorder_quantity` int(11) DEFAULT 0,
  `item_discontinued` enum('Yes','No') DEFAULT 'No',
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `intern_id` (`intern_id`),
  KEY `inventory_period` (`inventory_period`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql)) {
    echo "✓ Table 'inventory_report' created successfully!\n\n";
    
    // Check if table exists and show structure
    $result = $conn->query("DESCRIBE inventory_report");
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
