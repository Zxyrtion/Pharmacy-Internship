<?php
require_once '../../config.php';

if (!isLoggedIn()) {
    header('Location: ../../views/auth/login.php');
    exit();
}

if (($_SESSION['role_name'] ?? '') !== 'HR Personnel') {
    header('Location: ../../index.php');
    exit();
}

$full_name = $_SESSION['full_name'] ?? 'HR';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$role = isset($_GET['role']) ? trim($_GET['role']) : '';
$active = isset($_GET['active']) ? trim($_GET['active']) : '';

// Load roles for filter dropdown
$roles = [];
$roles_res = $conn->query("SELECT role_name FROM roles ORDER BY role_name");
if ($roles_res) {
    while ($r = $roles_res->fetch_assoc()) {
        $roles[] = $r['role_name'];
    }
}

$where = [];
$params = [];
$types = '';

if ($q !== '') {
    $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone_number LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'ssss';
}

if ($role !== '') {
    $where[] = "r.role_name = ?";
    $params[] = $role;
    $types .= 's';
}

if ($active === '1' || $active === '0') {
    $where[] = "u.is_active = ?";
    $params[] = (int)$active;
    $types .= 'i';
}

$sql = "SELECT u.id, u.first_name, u.middle_name, u.last_name, u.email, u.phone_number, u.is_active, u.created_at,
               r.role_name
        FROM users u
        JOIN roles r ON r.id = u.role_id";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY r.role_name, u.last_name, u.first_name";

$stmt = $conn->prepare($sql);
$employees = [];

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $employees[] = $row;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees - HR - MediCare Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../style.css">
    <style>
        .page-wrap {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        .panel {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        .page-header {
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            color: white;
            padding: 1.75rem 2rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
        }
        .btn-logout {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
        }
        .btn-logout:hover { background: #c0392b; color: white; }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .25rem .6rem;
            border-radius: 999px;
            font-size: .85rem;
            background: #f1f3f5;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">
                <i class="bi bi-hospital"></i> MediCare Pharmacy
            </a>
            <div class="navbar-nav ms-auto align-items-lg-center gap-2">
                <span class="navbar-text me-lg-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($full_name); ?>
                </span>
                <a href="../logout.php" class="btn btn-logout btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="page-wrap">
        <div class="container">
            <div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h1 class="h3 mb-1"><i class="bi bi-person-lines-fill"></i> Employees</h1>
                    <p class="mb-0 opacity-75">All users (including Interns) from the database.</p>
                </div>
                <a href="dashboard.php" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Back to HR dashboard
                </a>
            </div>

            <div class="panel">
                <form class="row g-2 align-items-end" method="get" action="">
                    <div class="col-md-5">
                        <label class="form-label" for="q">Search</label>
                        <input class="form-control" id="q" name="q" value="<?php echo htmlspecialchars($q); ?>"
                               placeholder="Name, email, phone...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="role">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="">All roles</option>
                            <?php foreach ($roles as $rn): ?>
                                <option value="<?php echo htmlspecialchars($rn); ?>" <?php echo ($role === $rn) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rn); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="active">Status</label>
                        <select class="form-select" id="active" name="active">
                            <option value="" <?php echo ($active === '') ? 'selected' : ''; ?>>All</option>
                            <option value="1" <?php echo ($active === '1') ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo ($active === '0') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a class="btn btn-outline-secondary" href="employees.php" title="Reset">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="panel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="pill">
                        <i class="bi bi-people"></i>
                        <span><?php echo (int)count($employees); ?> result(s)</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($employees)): ?>
                                <tr>
                                    <td colspan="8" class="text-muted">No employees found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($employees as $e): ?>
                                    <tr>
                                        <td><?php echo (int)$e['id']; ?></td>
                                        <td class="fw-semibold">
                                            <?php
                                                $name = trim($e['first_name'] . ' ' . ($e['middle_name'] ?? '') . ' ' . $e['last_name']);
                                                echo htmlspecialchars(preg_replace('/\s+/', ' ', $name));
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($e['role_name']); ?></td>
                                        <td><?php echo htmlspecialchars($e['email']); ?></td>
                                        <td><?php echo htmlspecialchars($e['phone_number']); ?></td>
                                        <td>
                                            <?php if ((int)$e['is_active'] === 1): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($e['created_at']); ?></small></td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary"
                                               href="<?php echo 'assign_task.php?user_id=' . (int)$e['id']; ?>">
                                                <i class="bi bi-clipboard-plus"></i> Assign Task
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

