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

// Get filter parameters
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_status = $_GET['status'] ?? 'all';

// Get all active interns with MOA
$interns_sql = "SELECT DISTINCT u.id, u.first_name, u.last_name, u.email,
                ws.id as schedule_id, ws.department, ws.shift_time, ws.working_days,
                moa.accepted_at
                FROM users u
                JOIN work_schedules ws ON u.id = ws.user_id
                JOIN moa_agreements moa ON ws.id = moa.work_schedule_id
                WHERE moa.status = 'active' AND ws.status = 'acknowledged'
                ORDER BY u.first_name, u.last_name";
$interns_result = $conn->query($interns_sql);
$active_interns = [];
while ($row = $interns_result->fetch_assoc()) {
    $active_interns[] = $row;
}

// Get attendance records for the selected date
$attendance_sql = "SELECT a.*, u.first_name, u.last_name
                   FROM attendance a
                   JOIN users u ON a.user_id = u.id
                   WHERE a.attendance_date = ?";
if ($filter_status !== 'all') {
    $attendance_sql .= " AND a.status = ?";
}
$attendance_sql .= " ORDER BY u.first_name, u.last_name";

$attendance_stmt = $conn->prepare($attendance_sql);
if ($filter_status !== 'all') {
    $attendance_stmt->bind_param("ss", $filter_date, $filter_status);
} else {
    $attendance_stmt->bind_param("s", $filter_date);
}
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();
$attendance_records = [];
while ($row = $attendance_result->fetch_assoc()) {
    $attendance_records[$row['user_id']] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Tracking - MediCare Pharmacy</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        .container-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        
        .page-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin: 1rem 0;
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
        
        .status-present { background: #d4edda; color: #155724; }
        .status-absent { background: #f8d7da; color: #721c24; }
        .status-late { background: #fff3cd; color: #856404; }
        .status-excused { background: #d1ecf1; color: #0c5460; }
        .status-half_day { background: #ffeaa7; color: #856404; }
        
        .attendance-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-hospital"></i> MediCare Pharmacy
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_ready_interns.php">
                            <i class="bi bi-people-fill"></i> Active Interns
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="attendance.php">
                            <i class="bi bi-calendar-check"></i> Attendance
                        </a>
                    </li>
                </ul>
                
                <div class="navbar-nav ms-auto">
                    <span class="navbar-text me-3">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($full_name); ?>
                    </span>
                    <a href="../../logout.php" class="btn btn-logout">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-page">
        <div class="container">
            <div class="page-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="bi bi-calendar-check"></i> Intern Attendance Tracking</h2>
                        <p class="text-muted mb-0">Monitor and track intern attendance</p>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label"><strong>Date:</strong></label>
                        <input type="date" class="form-control" id="filterDate" value="<?php echo $filter_date; ?>" 
                               onchange="applyFilters()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><strong>Status:</strong></label>
                        <select class="form-select" id="filterStatus" onchange="applyFilters()">
                            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="present" <?php echo $filter_status === 'present' ? 'selected' : ''; ?>>Present</option>
                            <option value="absent" <?php echo $filter_status === 'absent' ? 'selected' : ''; ?>>Absent</option>
                            <option value="late" <?php echo $filter_status === 'late' ? 'selected' : ''; ?>>Late</option>
                            <option value="excused" <?php echo $filter_status === 'excused' ? 'selected' : ''; ?>>Excused</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-primary w-100" onclick="window.location.href='attendance.php'">
                            <i class="bi bi-arrow-clockwise"></i> Reset Filters
                        </button>
                    </div>
                </div>
                
                <!-- Attendance Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Intern Name</th>
                                <th>Department</th>
                                <th>Scheduled Shift</th>
                                <th>Clock In</th>
                                <th>Clock Out</th>
                                <th>Hours Worked</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($active_interns)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <p class="mb-0">No active interns found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($active_interns as $intern): ?>
                                    <?php 
                                    $attendance = $attendance_records[$intern['id']] ?? null;
                                    $status = $attendance ? $attendance['status'] : 'absent';
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($intern['first_name'] . ' ' . $intern['last_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($intern['email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($intern['department']); ?></td>
                                        <td><?php echo htmlspecialchars($intern['shift_time']); ?></td>
                                        <td>
                                            <?php if ($attendance && $attendance['clock_in_time']): ?>
                                                <?php echo date('h:i A', strtotime($attendance['clock_in_time'])); ?>
                                                <?php if ($attendance['is_late']): ?>
                                                    <br><small class="text-danger">Late: <?php echo $attendance['late_minutes']; ?> min</small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">--</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($attendance && $attendance['clock_out_time']): ?>
                                                <?php echo date('h:i A', strtotime($attendance['clock_out_time'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">--</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($attendance && $attendance['total_hours_worked'] > 0): ?>
                                                <strong><?php echo number_format($attendance['total_hours_worked'], 2); ?></strong> hrs
                                            <?php else: ?>
                                                <span class="text-muted">0.00 hrs</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="attendance-badge status-<?php echo $status; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($attendance && $attendance['remarks']): ?>
                                                <small><?php echo htmlspecialchars($attendance['remarks']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">--</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Summary Stats -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success"><?php echo count(array_filter($attendance_records, fn($a) => $a['status'] === 'present')); ?></h3>
                                <p class="mb-0">Present</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-danger"><?php echo count($active_interns) - count($attendance_records); ?></h3>
                                <p class="mb-0">Absent</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-warning"><?php echo count(array_filter($attendance_records, fn($a) => $a['status'] === 'late')); ?></h3>
                                <p class="mb-0">Late</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-info"><?php echo count(array_filter($attendance_records, fn($a) => $a['status'] === 'excused')); ?></h3>
                                <p class="mb-0">Excused</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function applyFilters() {
            const date = document.getElementById('filterDate').value;
            const status = document.getElementById('filterStatus').value;
            window.location.href = `attendance.php?date=${date}&status=${status}`;
        }
    </script>
</body>
</html>
