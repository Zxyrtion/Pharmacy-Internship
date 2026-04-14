<?php
$host = "127.0.0.1";
$username = "root";
$password = "";
$database = "internship";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add department column
$sql = "ALTER TABLE requisitions ADD COLUMN department VARCHAR(100) DEFAULT 'Pharmacy'";

if ($conn->query($sql)) {
    echo "Department column added successfully!";
} else {
    echo "Error adding department column: " . $conn->error;
}

$conn->close();
?>
