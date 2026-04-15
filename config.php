<?php
// Database configuration
$host = "127.0.0.1";
$username = "root";
$password = "";
$database = "internship";

// Create database connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Start session for user management
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Base URL (project folder under htdocs). Example: /Pharmacy-internship/
if (!defined('BASE_URL')) {
    define('BASE_URL', '/' . basename(__DIR__) . '/');
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to get user role
function getUserRole($userId, $conn) {
    $sql = "SELECT r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['role_name'];
    }
    return null;
}

// Function to sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate phone number (Philippine format)
function validatePhoneNumber($phone) {
    return preg_match('/^09\d{9}$/', $phone);
}

// Function to generate random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Function to create user session
function createUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_name'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
}

// Function to redirect based on role
function redirectByRole($role) {
    switch($role) {
        case 'Customer':
            header('Location: ' . BASE_URL . 'Users/customer/dashboard.php');
            break;
        case 'Pharmacist':
            header('Location: ' . BASE_URL . 'Users/pharmacist/dashboard.php');
            break;
        case 'Pharmacy Assistant':
            header('Location: ' . BASE_URL . 'Users/assistant/dashboard.php');
            break;
        case 'Pharmacy Technician':
            header('Location: ' . BASE_URL . 'Users/technician/dashboard.php');
            break;
        case 'HR Personnel':
            header('Location: ' . BASE_URL . 'Users/hr/dashboard.php');
            break;
        case 'Intern':
            header('Location: ' . BASE_URL . 'Users/intern/dashboard.php');
            break;
        default:
            header('Location: ' . BASE_URL . 'index.php');
            break;
    }
    exit();
}
?>
