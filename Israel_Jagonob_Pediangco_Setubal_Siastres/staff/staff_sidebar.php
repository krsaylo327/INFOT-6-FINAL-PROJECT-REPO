<?php
$sidebar_items = [
    'staff_dashboard.php' => ['icon' => '📊', 'label' => 'Dashboard'],
    'staff_profile.php' => ['icon' => '👤', 'label' => 'Profile Settings'],
    'staff_employees.php' => ['icon' => '👥', 'label' => 'Employee Management'],
    'staff_attendance.php' => ['icon' => '📅', 'label' => 'Attendance'],
    'staff_payroll.php' => ['icon' => '💰', 'label' => 'Payroll'],
    'staff_reports.php' => ['icon' => '📈', 'label' => 'Reports'],
    'staff_notifications.php' => ['icon' => '🔔', 'label' => 'Notifications'],
];
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <h2>HIMAKAS</h2>
    <?php foreach ($sidebar_items as $href => $item): ?>
        <a href="<?php echo $href; ?>"<?php echo $current_page === $href ? ' class="active"' : ''; ?>>
            <span class="icon"><?php echo htmlspecialchars($item['icon']); ?></span>
            <span class="link-text"><?php echo htmlspecialchars($item['label']); ?></span>
        </a>
    <?php endforeach; ?>
    <a href="../logout.php">
        <span class="icon">🚪</span>
        <span class="link-text">Logout</span>
    </a>
</div>
