<?php
require_once '../../config.php';

// Check if user is already logged in
if (isLoggedIn()) {
    $userRole = getUserRole($_SESSION['user_id'], $conn);
    redirectByRole($userRole);
}

// Handle registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $birth_date = trim($_POST['birth_date']);
    $phone_number = trim($_POST['phone_number']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    // Role will be selected after registration
    $role_id = 4; // Default to Customer role, will be updated later
    
    $errors = [];
    
    // Validation
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($birth_date)) $errors[] = "Birth date is required";
    if (empty($phone_number)) $errors[] = "Phone number is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($password)) $errors[] = "Password is required";
    
    // Validate age (must be at least 18)
    if (!empty($birth_date)) {
        $birth_date_obj = new DateTime($birth_date);
        $today = new DateTime();
        $age = $today->diff($birth_date_obj)->y;
        if ($age < 18) {
            $errors[] = "You must be at least 18 years old to register";
        }
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Validate password match
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Validate password strength (minimum 8 characters)
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    // Validate phone number format (Philippine mobile number)
    if (!preg_match('/^09\d{9}$/', $phone_number)) {
        $errors[] = "Phone number must be in format 09XXXXXXXXX";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Email already exists";
        }
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (first_name, middle_name, last_name, birth_date, phone_number, email, password, role_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", 
            $first_name, $middle_name, $last_name, $birth_date, 
            $phone_number, $email, $hashed_password, $role_id
        );
        
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            
            // Store temporary user info in session for role selection
            $_SESSION['temp_user_id'] = $user_id;
            $_SESSION['registration_email'] = $email;
            
            // Redirect to role selection page
            header('Location: select_role.php');
            exit();
        } else {
            $errors[] = "Registration failed. Please try again. Error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .register-container {
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.8) 0%, rgba(155, 89, 182, 0.8) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
        }
        
        .register-left {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .register-right {
            padding: 3rem;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .form-control {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            margin-bottom: 0.5rem;
        }
        
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .btn-register-submit {
            background: #2ecc71;
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            color: white;
        }
        
        .btn-register-submit:hover {
            background: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
            color: white;
        }
        
        .alert {
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }
        
        .mb-3 {
            margin-bottom: 0.8rem !important;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="row g-0">
                <div class="col-lg-5">
                    <div class="register-left">
                        <div class="text-center">
                            <i class="bi bi-hospital" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                            <h2 class="mb-3">Join MediCare!</h2>
                            <p class="mb-4">Create your account and start your journey with MediCare Pharmacy Internship Program.</p>
                            <div class="mt-4">
                                <h5>Why Register?</h5>
                                <ul class="list-unstyled text-start">
                                    <li class="mb-2"><i class="bi bi-check-circle me-2"></i> Apply for internship positions</li>
                                    <li class="mb-2"><i class="bi bi-check-circle me-2"></i> Track your application status</li>
                                    <li class="mb-2"><i class="bi bi-check-circle me-2"></i> Access learning resources</li>
                                    <li class="mb-2"><i class="bi bi-check-circle me-2"></i> Manage your schedule</li>
                                    <li class="mb-2"><i class="bi bi-check-circle me-2"></i> Connect with mentors</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="register-right">
                        <h3 class="mb-3">Create Your Account</h3>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <strong><i class="bi bi-exclamation-triangle"></i> Registration Errors:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required 
                                           value="<?php echo isset($first_name) ? htmlspecialchars($first_name) : ''; ?>"
                                           placeholder="Enter first name">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="middle_name" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="middle_name" name="middle_name" 
                                           value="<?php echo isset($middle_name) ? htmlspecialchars($middle_name) : ''; ?>"
                                           placeholder="Optional">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required 
                                           value="<?php echo isset($last_name) ? htmlspecialchars($last_name) : ''; ?>"
                                           placeholder="Enter last name">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="birth_date" class="form-label">Birth Date * (18+ only)</label>
                                    <input type="date" class="form-control" id="birth_date" name="birth_date" required 
                                           value="<?php echo isset($birth_date) ? htmlspecialchars($birth_date) : ''; ?>"
                                           max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone_number" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone_number" name="phone_number" required 
                                           value="<?php echo isset($phone_number) ? htmlspecialchars($phone_number) : ''; ?>"
                                           placeholder="09XXXXXXXXX" pattern="^09\d{9}$">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" required 
                                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                                           placeholder="your@email.com">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" class="form-control" id="password" name="password" required 
                                           placeholder="Min 8 characters">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                                           placeholder="Re-enter password">
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mt-3">
                                <button type="submit" class="btn btn-register-submit">
                                    <i class="bi bi-person-plus"></i> Create Account
                                </button>
                            </div>
                            
                            <div class="text-center mt-3">
                                <p class="mb-0">Already have an account? <a href="login.php" class="text-decoration-none">Login here</a></p>
                                <p class="text-muted small mt-2">By registering, you agree to our terms and conditions.</p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
