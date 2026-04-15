<?php
require_once '../../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../views/auth/login.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role_name'] !== 'HR Personnel') {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Get schedule ID from URL
$schedule_id = $_GET['schedule_id'] ?? null;
if (!$schedule_id) {
    header('Location: dashboard.php');
    exit();
}

// Get existing schedule details
$schedule_sql = "SELECT ws.*, u.first_name, u.last_name, u.email, ie.average_rating
                 FROM work_schedules ws
                 JOIN users u ON ws.user_id = u.id
                 LEFT JOIN interview_evaluations ie ON ws.evaluation_id = ie.id
                 WHERE ws.id = ?";
$schedule_stmt = $conn->prepare($schedule_sql);
$schedule_stmt->bind_param("i", $schedule_id);
$schedule_stmt->execute();
$schedule = $schedule_stmt->get_result()->fetch_assoc();

if (!$schedule) {
    header('Location: dashboard.php');
    exit();
}

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $work_start_date = $_POST['work_start_date'];
    $department = $_POST['department'];
    $shift_type = $_POST['shift_type'];
    $working_days = isset($_POST['working_days']) ? implode(',', $_POST['working_days']) : '';
    $supervisor = $_POST['supervisor'];
    $location = $_POST['location'];
    $special_instructions = $_POST['special_instructions'] ?? '';
    $formatted_schedule = $_POST['work_schedule_details'];
    
    // Get shift time based on shift type
    $shift_times = [
        'morning' => '7 AM - 3 PM',
        'afternoon' => '3 PM - 11 PM',
        'night' => '11 PM - 7 AM',
        'full_day' => '8 AM - 5 PM'
    ];
    $shift_time = $shift_times[$shift_type];
    
    // Update work_schedules table
    $update_sql = "UPDATE work_schedules SET
        start_date = ?, department = ?, shift_type = ?, shift_time = ?,
        working_days = ?, supervisor_name = ?, location = ?, special_instructions = ?,
        formatted_schedule = ?, status = 'sent', sent_at = NOW()
        WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sssssssssi",
        $work_start_date, $department, $shift_type, $shift_time,
        $working_days, $supervisor, $location, $special_instructions,
        $formatted_schedule, $schedule_id
    );
    
    if ($update_stmt->execute()) {
        // Update evaluation record
        $eval_update_sql = "UPDATE interview_evaluations 
                           SET work_start_date = ?, work_schedule_details = ? 
                           WHERE id = ?";
        $eval_update_stmt = $conn->prepare($eval_update_sql);
        $eval_update_stmt->bind_param("ssi", $work_start_date, $formatted_schedule, $schedule['evaluation_id']);
        $eval_update_stmt->execute();
        
        // Create notification for intern
        require_once '../../models/notification.php';
        require_once '../../core/Database.php';
        $db = new Database();
        $pdo_conn = $db->getConnection();
        $notification = new Notification($pdo_conn);
        
        $notification_title = "Work Schedule Updated";
        $notification_message = "Your work schedule has been updated by HR. Start Date: " . date('F d, Y', strtotime($work_start_date)) . ". Please review and sign the MOA to confirm.";
        $notification->create(
            $schedule['user_id'],
            'work_schedule_updated',
            $notification_title,
            $notification_message,
            $schedule_id
        );
        
        $success = "Work schedule updated and sent to the intern successfully!";
    } else {
        $error = "Failed to update work schedule: " . $update_stmt->error;
    }
}

// Parse working days for checkboxes
$working_days_array = explode(',', $schedule['working_days']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Work Schedule - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .schedule-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .schedule-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
            max-width: 900px;
        }
        
        .intern-info {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .rejection-info {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
        }
        
        .btn-logout {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .shift-example {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin-top: 1rem;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-hospital"></i> MediCare Pharmacy
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($full_name); ?>
                </span>
                <a href="../logout.php" class="btn btn-logout">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="schedule-container">
        <div class="container d-flex justify-content-center">
            <div class="schedule-card">
                <h2 class="mb-4">
                    <i class="bi bi-pencil-square"></i> Edit Work Schedule
                </h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <div class="text-center mt-3">
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                <?php else: ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Intern Information -->
                <div class="intern-info">
                    <h5><i class="bi bi-person-x"></i> Schedule Status: <?php echo strtoupper($schedule['status']); ?></h5>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Intern Name:</strong> <?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($schedule['email']); ?>
                        </div>
                        <div class="col-md-6">
                            <?php if ($schedule['average_rating']): ?>
                                <strong>Average Rating:</strong> <?php echo number_format($schedule['average_rating'], 2); ?>/5.00<br>
                            <?php endif; ?>
                            <strong>Current Status:</strong> <span class="badge bg-light text-danger"><?php echo strtoupper($schedule['status']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Rejection/Special Instructions -->
                <?php if ($schedule['special_instructions']): ?>
                <div class="rejection-info">
                    <h6><i class="bi bi-exclamation-triangle"></i> Intern's Feedback</h6>
                    <p class="mb-0 small">
                        <?php echo nl2br(htmlspecialchars($schedule['special_instructions'])); ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <!-- Work Schedule Form -->
                <form method="POST">
                    <h5 class="mb-3"><i class="bi bi-calendar-plus"></i> Update Work Schedule Details</h5>
                    
                    <div class="mb-4">
                        <label class="form-label"><strong>Start Date</strong> <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="work_start_date" required
                               min="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo $schedule['start_date']; ?>">
                        <small class="text-muted">Select the first day of work for the intern</small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label"><strong>Department</strong> <span class="text-danger">*</span></label>
                        <select class="form-select" name="department" id="department" required>
                            <option value="">-- Select Department --</option>
                            <option value="Pharmacy Operations" <?php echo $schedule['department'] === 'Pharmacy Operations' ? 'selected' : ''; ?>>Pharmacy Operations</option>
                            <option value="Dispensing" <?php echo $schedule['department'] === 'Dispensing' ? 'selected' : ''; ?>>Dispensing</option>
                            <option value="Inventory Management" <?php echo $schedule['department'] === 'Inventory Management' ? 'selected' : ''; ?>>Inventory Management</option>
                            <option value="Customer Service" <?php echo $schedule['department'] === 'Customer Service' ? 'selected' : ''; ?>>Customer Service</option>
                            <option value="Quality Assurance" <?php echo $schedule['department'] === 'Quality Assurance' ? 'selected' : ''; ?>>Quality Assurance</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label"><strong>Shift Type</strong> <span class="text-danger">*</span></label>
                        <select class="form-select" name="shift_type" id="shiftType" required onchange="updateSchedulePreview()">
                            <option value="">-- Select Shift --</option>
                            <option value="morning" <?php echo $schedule['shift_type'] === 'morning' ? 'selected' : ''; ?>>Morning Shift (7 AM - 3 PM)</option>
                            <option value="afternoon" <?php echo $schedule['shift_type'] === 'afternoon' ? 'selected' : ''; ?>>Afternoon Shift (3 PM - 11 PM)</option>
                            <option value="night" <?php echo $schedule['shift_type'] === 'night' ? 'selected' : ''; ?>>Night Shift (11 PM - 7 AM)</option>
                            <option value="full_day" <?php echo $schedule['shift_type'] === 'full_day' ? 'selected' : ''; ?>>Full Day (8 AM - 5 PM)</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label"><strong>Working Days</strong> <span class="text-danger">*</span></label>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="working_days[]" value="Monday" id="monday" 
                                           <?php echo in_array('Monday', $working_days_array) ? 'checked' : ''; ?> onchange="updateSchedulePreview()">
                                    <label class="form-check-label" for="monday">Monday</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="working_days[]" value="Tuesday" id="tuesday" 
                                           <?php echo in_array('Tuesday', $working_days_array) ? 'checked' : ''; ?> onchange="updateSchedulePreview()">
                                    <label class="form-check-label" for="tuesday">Tuesday</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="working_days[]" value="Wednesday" id="wednesday" 
                                           <?php echo in_array('Wednesday', $working_days_array) ? 'checked' : ''; ?> onchange="updateSchedulePreview()">
                                    <label class="form-check-label" for="wednesday">Wednesday</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="working_days[]" value="Thursday" id="thursday" 
                                           <?php echo in_array('Thursday', $working_days_array) ? 'checked' : ''; ?> onchange="updateSchedulePreview()">
                                    <label class="form-check-label" for="thursday">Thursday</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="working_days[]" value="Friday" id="friday" 
                                           <?php echo in_array('Friday', $working_days_array) ? 'checked' : ''; ?> onchange="updateSchedulePreview()">
                                    <label class="form-check-label" for="friday">Friday</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="working_days[]" value="Saturday" id="saturday" 
                                           <?php echo in_array('Saturday', $working_days_array) ? 'checked' : ''; ?> onchange="updateSchedulePreview()">
                                    <label class="form-check-label" for="saturday">Saturday</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="working_days[]" value="Sunday" id="sunday" 
                                           <?php echo in_array('Sunday', $working_days_array) ? 'checked' : ''; ?> onchange="updateSchedulePreview()">
                                    <label class="form-check-label" for="sunday">Sunday</label>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted">Select the working days for the intern</small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label"><strong>Quick Schedule Templates</strong></label>
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-outline-primary" onclick="applyTemplate('weekdays')">
                                Mon-Fri
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="applyTemplate('weekdays_sat')">
                                Mon-Sat
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="applyTemplate('all_days')">
                                All Days
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label"><strong>Supervisor Name</strong> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="supervisor" required
                               placeholder="e.g., Dr. Maria Santos"
                               value="<?php echo htmlspecialchars($schedule['supervisor_name']); ?>">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label"><strong>Location/Branch</strong> <span class="text-danger">*</span></label>
                        <select class="form-select" name="location" required>
                            <option value="">-- Select Location --</option>
                            <option value="Main Pharmacy Branch" <?php echo $schedule['location'] === 'Main Pharmacy Branch' ? 'selected' : ''; ?>>Main Pharmacy Branch</option>
                            <option value="Downtown Branch" <?php echo $schedule['location'] === 'Downtown Branch' ? 'selected' : ''; ?>>Downtown Branch</option>
                            <option value="Hospital Pharmacy" <?php echo $schedule['location'] === 'Hospital Pharmacy' ? 'selected' : ''; ?>>Hospital Pharmacy</option>
                            <option value="Retail Pharmacy" <?php echo $schedule['location'] === 'Retail Pharmacy' ? 'selected' : ''; ?>>Retail Pharmacy</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label"><strong>Special Instructions</strong></label>
                        <textarea class="form-control" name="special_instructions" rows="3"
                                  placeholder="e.g., Lunch break 12-1 PM, Bring white uniform"></textarea>
                        <small class="text-muted">Note: Previous rejection reason will be cleared when you update</small>
                    </div>
                    
                    <!-- Hidden field for formatted schedule -->
                    <input type="hidden" name="work_schedule_details" id="work_schedule_details">
                    
                    <!-- Schedule Preview -->
                    <div class="shift-example">
                        <h6><i class="bi bi-eye"></i> Schedule Preview:</h6>
                        <pre class="mb-0" style="font-size: 0.9rem;" id="schedulePreview">Loading preview...</pre>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-success btn-lg" onclick="return validateAndSubmit()">
                            <i class="bi bi-send"></i> Update & Send Schedule to Intern
                        </button>
                    </div>
                </form>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const shiftTimes = {
            'morning': '7 AM - 3 PM',
            'afternoon': '3 PM - 11 PM',
            'night': '11 PM - 7 AM',
            'full_day': '8 AM - 5 PM'
        };
        
        function applyTemplate(template) {
            // Uncheck all first
            document.querySelectorAll('input[name="working_days[]"]').forEach(cb => cb.checked = false);
            
            if (template === 'weekdays') {
                ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'].forEach(day => {
                    document.getElementById(day).checked = true;
                });
            } else if (template === 'weekdays_sat') {
                ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'].forEach(day => {
                    document.getElementById(day).checked = true;
                });
            } else if (template === 'all_days') {
                document.querySelectorAll('input[name="working_days[]"]').forEach(cb => cb.checked = true);
            }
            
            updateSchedulePreview();
        }
        
        function updateSchedulePreview() {
            const shiftType = document.getElementById('shiftType').value;
            const department = document.getElementById('department').value;
            const checkedDays = Array.from(document.querySelectorAll('input[name="working_days[]"]:checked'))
                                     .map(cb => cb.value);
            
            if (!shiftType || checkedDays.length === 0) {
                document.getElementById('schedulePreview').textContent = 
                    'Please select shift type and working days to see preview...';
                return;
            }
            
            const shiftTime = shiftTimes[shiftType];
            const allDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            
            let preview = '';
            if (department) {
                preview += `Department: ${department}\n`;
            }
            preview += `Shift: ${shiftTime}\n\n`;
            preview += 'Weekly Schedule:\n';
            
            allDays.forEach(day => {
                if (checkedDays.includes(day)) {
                    preview += `- ${day}: ${shiftTime}\n`;
                } else {
                    preview += `- ${day}: OFF\n`;
                }
            });
            
            document.getElementById('schedulePreview').textContent = preview;
        }
        
        function validateAndSubmit() {
            const shiftType = document.getElementById('shiftType').value;
            const department = document.getElementById('department').value;
            const supervisor = document.querySelector('input[name="supervisor"]').value;
            const location = document.querySelector('select[name="location"]').value;
            const specialInstructions = document.querySelector('textarea[name="special_instructions"]').value;
            
            const checkedDays = Array.from(document.querySelectorAll('input[name="working_days[]"]:checked'))
                                     .map(cb => cb.value);
            
            if (checkedDays.length === 0) {
                alert('Please select at least one working day!');
                return false;
            }
            
            // Build the formatted schedule
            const shiftTime = shiftTimes[shiftType];
            const allDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            
            let schedule = `Department: ${department}\n`;
            schedule += `Shift: ${shiftTime}\n\n`;
            schedule += 'Weekly Schedule:\n';
            
            allDays.forEach(day => {
                if (checkedDays.includes(day)) {
                    schedule += `- ${day}: ${shiftTime}\n`;
                } else {
                    schedule += `- ${day}: OFF\n`;
                }
            });
            
            schedule += `\nSupervisor: ${supervisor}\n`;
            schedule += `Location: ${location}\n`;
            
            if (specialInstructions) {
                schedule += `\nNotes: ${specialInstructions}`;
            }
            
            // Set the hidden field value
            document.getElementById('work_schedule_details').value = schedule;
            
            return true;
        }
        
        // Initialize preview on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateSchedulePreview();
        });
    </script>
</body>
</html>
