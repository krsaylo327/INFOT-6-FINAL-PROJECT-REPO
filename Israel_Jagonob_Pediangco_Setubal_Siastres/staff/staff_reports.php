<?php
session_start();
include __DIR__ . '/../config/db.php';

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

$today = date('Y-m-d');

// Determine month range (default: current month). Allow optional GET overrides.
$month_start = $_GET['start'] ?? date('Y-m-01');
$month_end = $_GET['end'] ?? date('Y-m-t');
// Validate format YYYY-MM-DD to avoid SQL errors
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $month_start)) {
    $month_start = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $month_end)) {
    $month_end = date('Y-m-t');
}

// Live breakdown for today's construction workforce
$attendance_report = [];
if ($stmt = $conn->prepare("SELECT
    COUNT(DISTINCT e.id) as total_workforce,
    SUM(CASE WHEN a.morning_time_in IS NOT NULL THEN 1 ELSE 0 END) as present_today,
    SUM(CASE WHEN (a.morning_time_in IS NOT NULL AND a.morning_time_out IS NULL) OR (a.afternoon_time_in IS NOT NULL AND a.afternoon_time_out IS NULL) THEN 1 ELSE 0 END) as onsite_right_now,
    SUM(CASE WHEN a.morning_time_in IS NULL AND a.afternoon_time_in IS NULL THEN 1 ELSE 0 END) as absent_today
    FROM employees e
    LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = ?
    WHERE e.role NOT IN ('admin', 'staff')")) {
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $attendance_report = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Get employee performance report safely avoiding division by zero
$employee_performance = [];
if ($stmt = $conn->prepare("SELECT
    e.name,
    COUNT(a.id) as total_days,
    SUM(CASE WHEN a.morning_status = 'complete' AND a.afternoon_status = 'complete' THEN 1 ELSE 0 END) as full_days,
    ROUND(IF(COUNT(a.id) > 0, (SUM(CASE WHEN a.morning_status = 'complete' AND a.afternoon_status = 'complete' THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 0.0), 1) as attendance_rate
    FROM employees e
    LEFT JOIN attendance a ON e.id = a.employee_id AND a.date BETWEEN ? AND ?
    WHERE e.role != 'staff' AND e.role != 'admin'
    GROUP BY e.id, e.name
    ORDER BY attendance_rate DESC")) {
    $stmt->bind_param("ss", $month_start, $month_end);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $employee_performance[] = $row;
    }
    $stmt->close();
}

// Get payroll summary using optimized indexed performance boundaries
$payroll_summary = [];
if ($stmt = $conn->prepare("SELECT
    COUNT(id) as total_payrolls,
    IFNULL(SUM(total), 0) as total_amount,
    IFNULL(AVG(total), 0) as average_payroll,
    IFNULL(MAX(total), 0) as highest_payroll,
    IFNULL(MIN(total), 0) as lowest_payroll
    FROM payroll WHERE pay_date BETWEEN ? AND ?")) {
    $stmt->bind_param("ss", $month_start, $month_end);
    $stmt->execute();
    $payroll_summary = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - HIMAKAS Staff</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="staff_style.css">
</head>
<body>

<?php include __DIR__ . '/staff_sidebar.php'; ?>

<div class="staff-main">
    <div class="staff-header">
        <h1>Reports & Analytics</h1>
        <p><?php echo date('F Y'); ?> • Performance insights and summaries</p>
    </div>

    <div class="report-overview-card">
        <div>
            <h2>Team Performance Snapshot</h2>
            <p>Review attendance, payroll, and engagement metrics for the current month.</p>
        </div>
        <span class="report-badge">Updated <?php echo date('F j, Y'); ?></span>
    </div>

    <div class="report-section">
        <div class="section-header">
            <h2>📊 Today's Live Breakdown</h2>
            <p>Monitor real-time workforce activity for today's shifts on site.</p>
        </div>
        <div class="report-grid">
            <div class="report-card">
                <div class="label">Total Workforce</div>
                <div class="value"><?php echo (int)($attendance_report['total_workforce'] ?? 0); ?></div>
            </div>
            <div class="report-card">
                <div class="label">Present Today</div>
                <div class="value"><?php echo (int)($attendance_report['present_today'] ?? 0); ?></div>
            </div>
            <div class="report-card">
                <div class="label">On-Site Right Now</div>
                <div class="value"><?php echo (int)($attendance_report['onsite_right_now'] ?? 0); ?></div>
            </div>
            <div class="report-card">
                <div class="label">Absent Today</div>
                <div class="value"><?php echo (int)($attendance_report['absent_today'] ?? 0); ?></div>
            </div>
        </div>
    </div>

    <div class="report-section">
        <div class="section-header">
            <h2>👥 Employee Performance</h2>
            <p>See who is meeting attendance expectations and where support is needed.</p>
        </div>
        <?php if (!empty($employee_performance)): ?>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Employee Name</th>
                    <th>Total Days</th>
                    <th>Full Days</th>
                    <th>Attendance Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employee_performance as $emp): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($emp['name']); ?></td>
                        <td><?php echo (int)$emp['total_days']; ?></td>
                        <td><?php echo (int)$emp['full_days']; ?></td>
                        <td>
                            <span class="performance-rate <?php
                                $rate = $emp['attendance_rate'];
                                if ($rate >= 80) echo 'high';
                                elseif ($rate >= 60) echo 'medium';
                                else echo 'low';
                            ?>">
                                <?php echo number_format($rate, 1); ?>%
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="text-align: center; color: #7f8c8d;">No employee records found for this period.</p>
        <?php endif; ?>
    </div>

    <div class="report-section">
        <div class="section-header">
            <h2>💰 Payroll Summary</h2>
            <p>A quick snapshot of payroll volume and payout trends for the current month.</p>
        </div>
        <div class="report-grid">
            <div class="report-card">
                <div class="label">Total Payrolls</div>
                <div class="value"><?php echo (int)($payroll_summary['total_payrolls'] ?? 0); ?></div>
            </div>
            <div class="report-card">
                <div class="label">Total Amount</div>
                <div class="value">₱ <?php echo number_format($payroll_summary['total_amount'], 2); ?></div>
            </div>
            <div class="report-card">
                <div class="label">Average Payroll</div>
                <div class="value">₱ <?php echo number_format($payroll_summary['average_payroll'], 2); ?></div>
            </div>
            <div class="report-card">
                <div class="label">Highest Payroll</div>
                <div class="value">₱ <?php echo number_format($payroll_summary['highest_payroll'], 2); ?></div>
            </div>
            <div class="report-card">
                <div class="label">Lowest Payroll</div>
                <div class="value">₱ <?php echo number_format($payroll_summary['lowest_payroll'], 2); ?></div>
            </div>
        </div>
    </div>
</div>

</body>
</html>