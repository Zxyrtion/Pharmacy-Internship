<?php
require_once 'config.php';

echo "Creating intern_product_inventory table...\n\n";

$sql = "CREATE TABLE IF NOT EXISTS `intern_product_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `intern_id` int(11) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `price` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `intern_id` (`intern_id`),
  KEY `product_name` (`product_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql)) {
    echo "✓ Table 'intern_product_inventory' created successfully!\n\n";
    
    // Check if table exists and show structure
    $result = $conn->query("DESCRIBE intern_product_inventory");
    if ($result) {
        echo "Table structure:\n";
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
    }
} else {
    echo "✗ Error creating table: " . $conn->error . "\n";
}

$conn->close();
?>
