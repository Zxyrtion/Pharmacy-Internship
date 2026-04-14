<?php
require_once '../../config.php';

// Check if user is already logged in
if (isLoggedIn()) {
    $userRole = getUserRole($_SESSION['user_id'], $conn);
    redirectByRole($userRole);
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Prepare statement to prevent SQL injection
    $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.password, u.role_id, r.role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.email = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verify password (assuming passwords are hashed)
        if (password_verify($password, $user['password'])) {
            // Set session variables
            createUserSession($user);
            
            // Redirect based on role
            redirectByRole($user['role_name']);
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.8) 0%, rgba(155, 89, 182, 0.8) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        
        .login-left {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-right {
            padding: 3rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            margin-bottom: 1rem;
        }
        
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .btn-login-submit {
            background: #2ecc71;
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-login-submit:hover {
            background: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
        }
        
        .alert {
            border-radius: 10px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="row g-0">
                <div class="col-lg-6">
                    <div class="login-left">
                        <div class="text-center">
                            <i class="bi bi-hospital" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                            <h2 class="mb-3">Welcome Back!</h2>
                            <p class="mb-4">Access your MediCare Pharmacy account and manage your healthcare needs.</p>
                            <div class="mt-4">
                                <h5>Available Roles:</h5>
                                <ul class="list-unstyled">
                                    <li><i class="bi bi-check-circle"></i> Customer</li>
                                    <li><i class="bi bi-check-circle"></i> Pharmacist</li>
                                    <li><i class="bi bi-check-circle"></i> Pharmacy Assistant</li>
                                    <li><i class="bi bi-check-circle"></i> Pharmacy Technician</li>
                                    <li><i class="bi bi-check-circle"></i> HR Personnel</li>
                                    <li><i class="bi bi-check-circle"></i> Intern</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="login-right">
                        <h3 class="mb-4">Login to Your Account</h3>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required 
                                       placeholder="Enter your email">
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required 
                                       placeholder="Enter your password">
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            
                            <button type="submit" class="btn btn-login-submit">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p>Don't have an account? <a href="register.php" class="text-decoration-none">Register here</a></p>
                            <p><a href="#" class="text-decoration-none">Forgot your password?</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
