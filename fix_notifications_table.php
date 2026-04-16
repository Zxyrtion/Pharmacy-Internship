<?php
require_once 'config.php';

echo "Fixing notifications table...\n\n";

// Check current structure
$result = $conn->query("DESCRIBE notifications");
echo "Current columns:\n";
while ($row = $result->fetch_assoc()) {
    echo "  - " . $row['Field'] . "\n";
}

// Add missing columns if they don't exist
echo "\nAdding missing columns...\n";

$alterations = [
    "ALTER TABLE notifications ADD COLUMN related_type VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE notifications ADD COLUMN related_id INT(11) DEFAULT 0"
];

foreach ($alterations as $sql) {
    if ($conn->query($sql)) {
        echo "✓ Column added successfully\n";
    } else {
        if (strpos($conn->error, "Duplicate column") !== false) {
            echo "  Column already exists\n";
        } else {
            echo "✗ Error: " . $conn->error . "\n";
        }
    }
}

echo "\nUpdated table structure:\n";
$result = $conn->query("DESCRIBE notifications");
while ($row = $result->fetch_assoc()) {
    echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

$conn->close();
?>
