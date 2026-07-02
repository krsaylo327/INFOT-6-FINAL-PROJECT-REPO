<?php
session_start();
include __DIR__ . '/../config/db.php';

// 1. Enforce strict role-based access control
if (!isset($_SESSION['employee_id']) || ($_SESSION['user_role'] ?? 'employee') !== 'staff') {
    header("Location: ../login.php");
    exit;
}

$id = (int) $_SESSION['employee_id'];

$user = null;
if ($stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?")) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$user) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

// Instantiate notifications array
$notifications = [];

// Date thresholds for queries
$current_month = date('Y-m');
$month_start   = date('Y-m-01');
$month_end     = date('Y-m-t');
$today         = date('Y-m-d');

/* ==========================================
   ALERT 1: LOW ATTENDANCE ALERT (MONTHLY)
   ========================================== */
$low_attendance = [];
// Patched division-by-zero check utilizing direct SQL conditional evaluation
if ($stmt = $conn->prepare("SELECT 
        e.name, 
        COUNT(a.id) as total_days,
        SUM(CASE WHEN a.morning_status = 'complete' AND a.afternoon_status = 'complete' THEN 1 ELSE 0 END) as full_days,
        IF(COUNT(a.id) > 0, ROUND((SUM(CASE WHEN a.morning_status = 'complete' AND a.afternoon_status = 'complete' THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 1), 0.0) as attendance_rate
    FROM employees e
    LEFT JOIN attendance a ON e.id = a.employee_id AND a.date BETWEEN ? AND ?
    WHERE e.role != 'staff'
    GROUP BY e.id, e.name
    HAVING attendance_rate < 70.0 AND total_days > 0")) {
    
    $stmt->bind_param("ss", $month_start, $month_end);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $low_attendance[] = $row;
    }
    $stmt->close();
}

foreach ($low_attendance as $emp) {
    $notifications[] = [
        'type' => 'warning',
        'icon' => '⚠️',
        'title' => 'Low Attendance Alert',
        'message' => htmlspecialchars($emp['name']) . " has " . $emp['attendance_rate'] . "% attendance this month (below 70% threshold)",
        'date' => date('M d, Y'),
        'time' => date('h:i A')
    ];
}

/* ==========================================
   ALERT 2: INCOMPLETE ATTENDANCE (TODAY)
   ========================================== */
$incomplete_today = [];
if ($stmt = $conn->prepare("SELECT e.name FROM employees e
    LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = ?
    WHERE e.role != 'staff' AND (a.id IS NULL OR a.morning_status != 'complete' OR a.afternoon_status != 'complete')")) {
    
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $incomplete_today[] = $row['name'];
    }
    $stmt->close();
}

if (!empty($incomplete_today)) {
    $notifications[] = [
        'type' => 'info',
        'icon' => '📅',
        'title' => 'Incomplete Attendance Today',
        'message' => count($incomplete_today) . " employee(s) have missing or incomplete clock-ins today: " . implode(', ', array_map('htmlspecialchars', $incomplete_today)),
        'date' => date('M d, Y'),
        'time' => date('h:i A')
    ];
}

/* ==========================================
   ALERT 3: PAYROLL REMINDER
   ========================================= */
$day_of_month = (int)date('j');
if ($day_of_month >= 25) {
    $notifications[] = [
        'type' => 'reminder',
        'icon' => '💰',
        'title' => 'Payroll Processing Reminder',
        'message' => 'The current cycle is coming to a close. Time to verify timesheets and process payroll for ' . date('F Y') . '.',
        'date' => date('M d, Y'),
        'time' => date('h:i A')
    ];
}

/* ==========================================
   ALERT 4: GLOBAL SYSTEM STATUS
   ========================================= */
$notifications[] = [
    'type' => 'success',
    'icon' => '✅',
    'title' => 'System Status',
    'message' => 'All core HIMAKAS modules are running normally.',
    'date' => date('M d, Y'),
    'time' => date('h:i A')
];

// Sort notifications by structural priority weighting criteria
usort($notifications, function($a, $b) {
    $priority = ['warning' => 1, 'reminder' => 2, 'info' => 3, 'success' => 4];
    return $priority[$a['type']] <=> $priority[$b['type']];
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - HIMAKAS Staff</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="staff_style.css">
</head>
<body>

<?php include __DIR__ . '/staff_sidebar.php'; ?>

<div class="staff-main">
    <div class="staff-header">
        <h1>Notifications Center</h1>
        <p>Stay updated with important operational alerts, exceptions, and reminders</p>
    </div>

    <div class="notifications-container">
        <div class="notification-stats">
            <div class="stat-card">
                <div class="number warning-count"><?php echo count(array_filter($notifications, fn($n) => $n['type'] === 'warning')); ?></div>
                <div class="label">Warnings</div>
            </div>
            <div class="stat-card">
                <div class="number reminder-count"><?php echo count(array_filter($notifications, fn($n) => $n['type'] === 'reminder')); ?></div>
                <div class="label">Reminders</div>
            </div>
            <div class="stat-card">
                <div class="number info-count"><?php echo count(array_filter($notifications, fn($n) => $n['type'] === 'info')); ?></div>
                <div class="label">Info</div>
            </div>
            <div class="stat-card">
                <div class="number total-count"><?php echo count($notifications); ?></div>
                <div class="label">Total</div>
            </div>
        </div>

        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item notification-style-<?php echo $notification['type']; ?>">
                    <div class="notification-icon"><?php echo $notification['icon']; ?></div>
                    <div class="notification-content">
                        <h3 class="notification-title">
                            <?php echo htmlspecialchars($notification['title']); ?>
                            <span class="notification-badge badge-<?php echo $notification['type']; ?>">
                                <?php echo ucfirst($notification['type']); ?>
                            </span>
                        </h3>
                        <p class="notification-message"><?php echo $notification['message']; ?></p>
                        <div class="notification-meta">
                            <span>📅 <?php echo $notification['date']; ?></span> &nbsp;•&nbsp; <span>🕒 <?php echo $notification['time']; ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-notifications">
                <div style="font-size: 48px; margin-bottom: 20px;">🔔</div>
                <p>No active alerts at this time</p>
                <p style="font-size: 14px; color: var(--text-muted);">All structural workflows running safely!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>