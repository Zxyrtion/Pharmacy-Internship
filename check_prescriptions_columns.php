<?php
require_once 'config.php';

echo "Prescriptions Table Columns:\n";
echo str_repeat("=", 50) . "\n";

$result = $conn->query("DESCRIBE prescriptions");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n\nSample Prescription Data:\n";
echo str_repeat("=", 50) . "\n";

$sample = $conn->query("SELECT * FROM prescriptions WHERE status='Ready' LIMIT 1");
if ($sample && $sample->num_rows > 0) {
    $data = $sample->fetch_assoc();
    foreach ($data as $key => $value) {
        echo "$key: " . ($value ?? 'NULL') . "\n";
    }
} else {
    echo "No Ready prescriptions found\n";
}
?>
