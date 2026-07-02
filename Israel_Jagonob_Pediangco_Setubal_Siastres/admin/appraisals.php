<?php
session_start();
include __DIR__ . '/../config/db.php';

if (!isset($_SESSION['employee_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
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

$message = '';

$evaluation_end = date('Y-m-d');
$evaluation_start = date('Y-m-d', strtotime('-30 days'));

$employeePerformance = [];
$totalAttendanceRate = 0;
$totalTaskRate = 0;
$totalPerformanceScore = 0;
$employeeCount = 0;

if ($stmt = $conn->prepare("SELECT
        e.id,
        e.name,
        e.position,
        (SELECT COUNT(*) FROM attendance a WHERE a.employee_id = e.id AND a.date BETWEEN ? AND ?) AS attendance_days,
        (SELECT COUNT(*) FROM attendance a WHERE a.employee_id = e.id AND a.date BETWEEN ? AND ? AND a.morning_status = 'complete' AND a.afternoon_status = 'complete') AS full_days,
        (SELECT COUNT(*) FROM agendas ag WHERE ag.employee_id = e.id AND ag.date BETWEEN ? AND ?) AS total_tasks,
        (SELECT COUNT(*) FROM agendas ag WHERE ag.employee_id = e.id AND ag.date BETWEEN ? AND ? AND ag.status = 'completed') AS completed_tasks
    FROM employees e
    WHERE e.role != 'admin'
    ORDER BY e.name ASC")) {
    $stmt->bind_param("ssssssss", $evaluation_start, $evaluation_end, $evaluation_start, $evaluation_end, $evaluation_start, $evaluation_end, $evaluation_start, $evaluation_end);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $attendanceDays = (int) ($row['attendance_days'] ?? 0);
        $fullDays = (int) ($row['full_days'] ?? 0);
        $totalTasks = (int) ($row['total_tasks'] ?? 0);
        $completedTasks = (int) ($row['completed_tasks'] ?? 0);

        $attendanceRate = $attendanceDays > 0 ? round(($fullDays / $attendanceDays) * 100) : 0;
        $taskRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
        $performanceScore = round(($attendanceRate + $taskRate) / 2);

        $employeePerformance[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'position' => $row['position'],
            'attendance_days' => $attendanceDays,
            'full_days' => $fullDays,
            'attendance_rate' => $attendanceRate,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'task_rate' => $taskRate,
            'performance_score' => $performanceScore,
        ];

        $totalAttendanceRate += $attendanceRate;
        $totalTaskRate += $taskRate;
        $totalPerformanceScore += $performanceScore;
        $employeeCount++;
    }
    $stmt->close();
}

$averageAttendanceRate = $employeeCount > 0 ? round($totalAttendanceRate / $employeeCount) : 0;
$averageTaskRate = $employeeCount > 0 ? round($totalTaskRate / $employeeCount) : 0;
$averagePerformanceScore = $employeeCount > 0 ? round($totalPerformanceScore / $employeeCount) : 0;

$activePage = 'appraisals';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Appraisals - HIMAKAS</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="admin-main">
    <div class="admin-header">
        <h1>Employee Performance Overview</h1>
        <p class="welcome-text">Review attendance and on-site task performance for all employees.</p>
    </div>

    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 14px 16px; border-radius: 8px; margin-bottom: 20px; border-left: 5px solid #28a745;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="admin-cards">
        <div class="card">
            <h3>👥 Total Employees</h3>
            <p class="large-value"><?php echo number_format(count($employeePerformance)); ?></p>
            <small>Frontline staff included</small>
        </div>

        <div class="card">
            <h3>📈 Avg Attendance</h3>
            <p class="large-value"><?php echo $averageAttendanceRate; ?>%</p>
            <small>Full-day attendance rate</small>
        </div>

        <div class="card">
            <h3>✅ Avg Task Completion</h3>
            <p class="large-value"><?php echo $averageTaskRate; ?>%</p>
            <small>Site tasks completed</small>
        </div>

        <div class="card">
            <h3>🌟 Avg Performance</h3>
            <p class="large-value"><?php echo $averagePerformanceScore; ?>%</p>
            <small>Attendance + work quality</small>
        </div>
    </div>

    <div class="table-container">
        <h2>Employee Performance Overview</h2>
        <p style="margin-bottom: 20px; color: #4a5568;">Last 30 days attendance and on-site task performance for all employees.</p>
        <?php if (!empty($employeePerformance)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Position</th>
                        <th>Attendance Rate</th>
                        <th>Task Completion</th>
                        <th>Performance Score</th>
                        <th>Full Days</th>
                        <th>Completed Tasks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employeePerformance as $performance): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($performance['name']); ?></td>
                            <td><?php echo htmlspecialchars($performance['position']); ?></td>
                            <td><?php echo $performance['attendance_rate']; ?>%</td>
                            <td><?php echo $performance['task_rate']; ?>%</td>
                            <td><?php echo $performance['performance_score']; ?>%</td>
                            <td><?php echo $performance['full_days']; ?> / <?php echo $performance['attendance_days']; ?></td>
                            <td><?php echo $performance['completed_tasks']; ?> / <?php echo $performance['total_tasks']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-records">No performance data available.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
