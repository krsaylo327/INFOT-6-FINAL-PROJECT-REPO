<?php
$activePage = $activePage ?? '';
function adminActive($page) {
    global $activePage;
    return $activePage === $page ? 'active' : '';
}
?>

<div class="sidebar">
    <h2>HIMAKAS</h2>
    <a href="dashboard.php" class="<?php echo adminActive('dashboard'); ?>">
        <span class="icon">📊</span>
        <span class="link-text">Dashboard</span>
    </a>
    <a href="profile_settings.php" class="<?php echo adminActive('profile'); ?>">
        <span class="icon">👤</span>
        <span class="link-text">Profile</span>
    </a>
    <a href="employee_overview.php" class="<?php echo adminActive('overview'); ?>">
        <span class="icon">👥</span>
        <span class="link-text">Employee Overview</span>
    </a>
    <a href="manage_employees.php" class="<?php echo adminActive('employees'); ?>">
        <span class="icon">👥</span>
        <span class="link-text">Employee Management</span>
    </a>
    <a href="attendance.php" class="<?php echo adminActive('attendance'); ?>">
        <span class="icon">📅</span>
        <span class="link-text">Attendance</span>
    </a>
    <a href="agenda.php" class="<?php echo adminActive('agenda'); ?>">
        <span class="icon">🏗️</span>
        <span class="link-text">Project Agendas</span>
    </a>
    <a href="payroll.php" class="<?php echo adminActive('payroll'); ?>">
        <span class="icon">💰</span>
        <span class="link-text">Payroll & Expenses</span>
    </a>
    <a href="appraisals.php" class="<?php echo adminActive('appraisals'); ?>">
        <span class="icon">⭐</span>
        <span class="link-text">Performance Appraisals</span>
    </a>
    <a href="../logout.php">
        <span class="icon">🚪</span>
        <span class="link-text">Logout</span>
    </a>
</div>
