<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-list"></i> HR Menu</h5>
    </div>
    <div class="card-body p-0">
        <nav class="nav flex-column">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'internship_applications.php' ? 'active' : ''; ?>" href="internship_applications.php">
                <i class="bi bi-file-earmark-text"></i> Internship Applications
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'interview_schedule.php' ? 'active' : ''; ?>" href="interview_schedule.php">
                <i class="bi bi-calendar-check"></i> Interview Schedule
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'evaluate_interview.php' ? 'active' : ''; ?>" href="evaluate_interview.php">
                <i class="bi bi-clipboard-check"></i> Evaluate Interviews
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pharmacy_policies.php' ? 'active' : ''; ?>" href="pharmacy_policies.php">
                <i class="bi bi-file-text"></i> Pharmacy Policies
            </a>
        </nav>
    </div>
</div>

<style>
.nav-link {
    padding: 15px 20px;
    color: #333;
    border-bottom: 1px solid #eee;
    text-decoration: none;
    transition: all 0.3s ease;
    display: block;
}

.nav-link:hover {
    background-color: #f8f9fa;
    color: #007bff;
    padding-left: 25px;
}

.nav-link.active {
    background-color: #007bff;
    color: white !important;
    border-left: 4px solid #0056b3;
}

.nav-link i {
    margin-right: 10px;
    width: 20px;
}
</style>
