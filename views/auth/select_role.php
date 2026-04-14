<?php
require_once '../../config.php';

// Check if user came from registration
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['registration_email'])) {
    header('Location: register.php');
    exit();
}

$temp_user_id = $_SESSION['temp_user_id'];
$email = $_SESSION['registration_email'];

// Handle role selection
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['role_id']) && !empty($_POST['role_id'])) {
        $role_id = $_POST['role_id'];
        
        // Update user's role in database
        $sql = "UPDATE users SET role_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $role_id, $temp_user_id);
        
        if ($stmt->execute()) {
            // Clear temporary session data
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['registration_email']);
            
            // Get user details for session
            $sql = "SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $temp_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                createUserSession($user);
                redirectByRole($user['role_name']);
            }
        }
    }
}

// Get available roles
$roles_sql = "SELECT * FROM roles ORDER BY role_name";
$roles_result = $conn->query($roles_sql);
$roles = [];
if ($roles_result) {
    while ($row = $roles_result->fetch_assoc()) {
        $roles[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Your Role - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .role-selection-container {
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.8) 0%, rgba(155, 89, 182, 0.8) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        
        .role-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 1200px;
            width: 100%;
            margin: 0 1rem;
        }
        
        .role-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem;
            text-align: center;
        }
        
        .role-body {
            padding: 3rem;
        }
        
        .role-option {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .role-option:hover {
            border-color: #3498db;
            background: #e3f2fd;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(52, 152, 219, 0.2);
        }
        
        .role-option.selected {
            border-color: #2ecc71;
            background: #d4edda;
            box-shadow: 0 10px 25px rgba(46, 204, 113, 0.2);
        }
        
        .role-option.selected::after {
            content: '\2713';
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #2ecc71;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .role-icon {
            font-size: 3rem;
            color: #3498db;
            margin-bottom: 1rem;
        }
        
        .role-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .role-description {
            color: #6c757d;
            margin-bottom: 1rem;
        }
        
        .btn-continue {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            border: none;
            border-radius: 30px;
            padding: 1rem 3rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            margin-top: 2rem;
        }
        
        .btn-continue:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(46, 204, 113, 0.3);
        }
        
        .btn-continue:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .user-info {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        
        .progress-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .progress-step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 1rem;
            position: relative;
        }
        
        .progress-step.completed {
            background: white;
            color: #3498db;
        }
        
        .progress-step.active {
            background: #2ecc71;
            color: white;
        }
        
        .progress-step::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 40px;
            height: 2px;
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-50%);
        }
        
        .progress-step:last-child::after {
            display: none;
        }
    </style>
</head>
<body>
    <div class="role-selection-container">
        <div class="role-card">
            <div class="role-header">
                <div class="progress-indicator">
                    <div class="progress-step completed">1</div>
                    <div class="progress-step active">2</div>
                    <div class="progress-step">3</div>
                </div>
                
                <i class="bi bi-person-badge" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                <h2 class="mb-3">Choose Your Role</h2>
                <div class="user-info">
                    <p class="mb-0"><strong>Account Created:</strong> <?php echo htmlspecialchars($email); ?></p>
                    <p class="mb-0">Please select your role to complete your registration</p>
                </div>
            </div>
            
            <div class="role-body">
                <form method="POST" action="" id="roleForm">
                    <div class="row">
                        <?php foreach ($roles as $role): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="role-option" onclick="selectRole(<?php echo $role['id']; ?>)">
                                    <div class="role-icon">
                                        <?php
                                        $icon = 'bi-person';
                                        switch ($role['role_name']) {
                                            case 'Customer':
                                                $icon = 'bi-person';
                                                break;
                                            case 'Pharmacist':
                                                $icon = 'bi-hospital';
                                                break;
                                            case 'Pharmacy Assistant':
                                                $icon = 'bi-person-plus';
                                                break;
                                            case 'Pharmacy Technician':
                                                $icon = 'bi-gear';
                                                break;
                                            case 'HR Personnel':
                                                $icon = 'bi-people';
                                                break;
                                            case 'Intern':
                                                $icon = 'bi-mortarboard';
                                                break;
                                        }
                                        ?>
                                        <i class="bi <?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="role-title"><?php echo htmlspecialchars($role['role_name']); ?></div>
                                    <div class="role-description">
                                        <?php
                                        $description = '';
                                        switch ($role['role_name']) {
                                            case 'Customer':
                                                $description = 'Order medicines and manage prescriptions';
                                                break;
                                            case 'Pharmacist':
                                                $description = 'Dispense medications and provide consultation';
                                                break;
                                            case 'Pharmacy Assistant':
                                                $description = 'Assist with inventory and customer service';
                                                break;
                                            case 'Pharmacy Technician':
                                                $description = 'Prepare medications and manage inventory';
                                                break;
                                            case 'HR Personnel':
                                                $description = 'Manage staff and human resources';
                                                break;
                                            case 'Intern':
                                                $description = 'Learn and assist in pharmacy operations';
                                                break;
                                        }
                                        echo $description;
                                        ?>
                                    </div>
                                    <input type="radio" name="role_id" value="<?php echo $role['id']; ?>" style="display: none;">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-continue" id="continueBtn" disabled>
                            <i class="bi bi-check-circle"></i> Continue to Dashboard
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function selectRole(roleId) {
            // Remove selected class from all options
            document.querySelectorAll('.role-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');
            
            // Check the radio button
            document.querySelector(`input[value="${roleId}"]`).checked = true;
            
            // Enable continue button
            document.getElementById('continueBtn').disabled = false;
        }
        
        // Form submission
        document.getElementById('roleForm').addEventListener('submit', function(e) {
            const selectedRole = document.querySelector('input[name="role_id"]:checked');
            if (!selectedRole) {
                e.preventDefault();
                alert('Please select a role to continue.');
            }
        });
    </script>
</body>
</html>
