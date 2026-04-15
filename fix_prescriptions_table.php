<?php
require_once 'config.php';

echo "<h2>Fixing Prescriptions Table</h2>";

// Check if table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'prescriptions'");

if ($tableCheck && $tableCheck->num_rows > 0) {
    echo "<p>✓ Table 'prescriptions' exists</p>";
    
    // Check if customer_id exists
    $columnCheck = $conn->query("SHOW COLUMNS FROM prescriptions LIKE 'customer_id'");
    
    if ($columnCheck && $columnCheck->num_rows == 0) {
        echo "<p>❌ Column 'customer_id' is missing. Adding it now...</p>";
        
        $alterQuery = "ALTER TABLE prescriptions ADD COLUMN customer_id INT NOT NULL AFTER id";
        
        if ($conn->query($alterQuery)) {
            echo "<p style='color: green;'>✓ Successfully added 'customer_id' column!</p>";
        } else {
            echo "<p style='color: red;'>❌ Error adding column: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>✓ Column 'customer_id' already exists</p>";
    }
    
    // Show current structure
    echo "<h3>Current Table Structure:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $result = $conn->query("DESCRIBE prescriptions");
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "<p>❌ Table 'prescriptions' does not exist. Creating it now...</p>";
    
    $createTable = "CREATE TABLE prescriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        doctor_name VARCHAR(200),
        doctor_specialization VARCHAR(200),
        doctor_prc VARCHAR(50),
        doctor_ptr VARCHAR(50),
        doctor_clinic VARCHAR(300),
        doctor_contact VARCHAR(100),
        patient_name VARCHAR(200),
        patient_age VARCHAR(10),
        patient_gender VARCHAR(10),
        patient_dob DATE NULL,
        prescription_date DATE,
        next_appointment DATE NULL,
        notes TEXT NULL,
        validity_months INT DEFAULT 3,
        status ENUM('Pending','Processing','Ready','Dispensed','Cancelled') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($createTable)) {
        echo "<p style='color: green;'>✓ Successfully created 'prescriptions' table!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating table: " . $conn->error . "</p>";
    }
}

echo "<br><a href='Users/customer/prescription_submit.php'>← Back to Prescription Form</a>";
?>
