<?php
require_once __DIR__ . '/config.php';

// Simple routing system
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];
$path = str_replace(dirname($script_name), '', $request_uri);
$path = trim($path, '/');

// Route definitions
$routes = [
    // Auth routes
    'login' => 'views/auth/login.php',
    'register' => 'views/auth/register.php',
    'logout' => 'views/auth/logout.php',
    
    // Dashboard routes
    'intern/dashboard' => 'Users/intern/dashboard.php',
    'intern/apply-internship' => 'Users/intern/apply_internship.php',
    
    // Admin/HR routes (can be expanded later)
    'hr/dashboard' => 'Users/hr/dashboard.php',
    'hr/pharmacy-policies' => 'Users/hr/pharmacy_policies.php',
    'hr/internship-applications' => 'Users/hr/internship_applications.php',
    'hr/conduct-interview' => 'Users/hr/conduct_interview.php',
    'hr/employee-profiles' => 'Users/hr/employee_profiles.php',
    'admin/applications' => 'admin/applications.php',
    
    // Default route
    '' => 'index.php'
];

// Route matching
if (isset($routes[$path])) {
    include __DIR__ . '/' . $routes[$path];
} else {
    // Handle 404
    http_response_code(404);
    echo '<h1>404 - Page Not Found</h1>';
    echo '<p>The requested page could not be found.</p>';
}
?>