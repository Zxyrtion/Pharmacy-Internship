<?php
require_once 'config.php';

$result = $conn->query("SELECT id, first_name, last_name, role_name FROM users WHERE id = 18");
$user = $result->fetch_assoc();

echo json_encode($user, JSON_PRETTY_PRINT);
?>
