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

if (!$user) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

$today = date('Y-m-d');
$firstDayOfMonth = date('Y-m-01');

$total_employees = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM employees WHERE role != 'admin'")) {
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_employees = $result['total'] ?? 0;
    $stmt->close();
}

$today_attendance = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM attendance WHERE date = ?")) {
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $today_attendance = $result['total'] ?? 0;
    $stmt->close();
}

$late_arrivals = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM attendance WHERE date = ? AND ((morning_time_in IS NOT NULL AND morning_time_in > '08:00:00') OR (afternoon_time_in IS NOT NULL AND afternoon_time_in > '13:00:00'))")) {
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $late_arrivals = $result['total'] ?? 0;
    $stmt->close();
}

$completed_agendas = 0;
$pending_agendas = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM agendas WHERE status = 'completed'")) {
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $completed_agendas = $result['total'] ?? 0;
    $stmt->close();
}
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM agendas WHERE status != 'completed'")) {
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $pending_agendas = $result['total'] ?? 0;
    $stmt->close();
}

// Build dynamic workforce breakdown grouped by position (exclude admins)
$workforce = [];
if ($stmt = $conn->prepare("SELECT position, COUNT(*) AS total FROM employees WHERE role != 'admin' GROUP BY position ORDER BY total DESC")) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $workforce[$row['position']] = (int)$row['total'];
    }
    $stmt->close();
}

$task_completion_rate = 0;
if (($completed_agendas + $pending_agendas) > 0) {
    $task_completion_rate = round(($completed_agendas / ($completed_agendas + $pending_agendas)) * 100);
}

$monthly_payroll_total = 0;
if ($stmt = $conn->prepare("SELECT IFNULL(SUM(total), 0) AS total_sum FROM payroll WHERE pay_date BETWEEN ? AND ?")) {
    $stmt->bind_param("ss", $firstDayOfMonth, $today);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $monthly_payroll_total = $result['total_sum'] ?? 0;
    $stmt->close();
}

$recent_attendance = [];
if ($stmt = $conn->prepare("SELECT a.*, e.name FROM attendance a JOIN employees e ON a.employee_id = e.id WHERE a.date = ? ORDER BY a.id DESC LIMIT 8")) {
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_attendance[] = $row;
    }
    $stmt->close();
}

$recent_payroll = [];
if ($stmt = $conn->prepare("SELECT p.*, e.name FROM payroll p JOIN employees e ON p.employee_id = e.id ORDER BY p.pay_date DESC LIMIT 8")) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_payroll[] = $row;
    }
    $stmt->close();
}

$activePage = 'dashboard';
// Get dashboard data from API
$dashboardApi = json_decode(
    file_get_contents("http://localhost/CAPSTONE/api/dashboard.php"),
    true
);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HIMAKAS</title>

    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="style.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="admin-main">
    <div class="admin-header">
        <h1>Welcome back, <?php echo htmlspecialchars($user['name']); ?></h1>
        <p>Admin Dashboard • <?php echo date('l, F j, Y'); ?></p>
    </div>

    <div class="admin-cards">
        <div class="card">
            <h3>� Today's Attendance</h3>
            <p class="large-value">
                <?php echo $dashboardApi['totalAttendance']; ?>
                /
                <?php echo $dashboardApi['totalEmployees']; ?>
            </p>
            <small>Present out of total workforce</small>
        </div>

        <div class="card">
            <h3>⚠️ Late Alerts</h3>
            <p class="large-value"><?php echo $dashboardApi['lateArrivals']; ?></p>
            <small>Employees missed cutoff times</small>
        </div>

        <div class="card">
            <h3>🏗️ Project Progress</h3>
            <p class="large-value"><?php echo $task_completion_rate; ?>%</p>
            <small>Tasks completed across all sites</small>
        </div>

        

        <div class="card">
            <h3>💰 Total Payroll Expense</h3>
            <p class="large-value">₱ <?php echo number_format($dashboardApi['totalPayroll'],2); ?></p>
            <small>Billing cycle: <?php echo date('F 1', strtotime($firstDayOfMonth)); ?> - Today</small>
        </div>

        <div class="card">
            <h3>🌤 Weather</h3>

            <p class="large-value">
                <?php echo $dashboardApi['temperature']; ?>°C
            </p>

            <small>
                <?php
                echo $dashboardApi['city'] .
                    " - " .
                    $dashboardApi['weather'];
                ?>
            </small>
        </div>

    </div>
    <div class="table-container">
        <h2>Attendance Overview</h2>
        <canvas id="attendanceChart" height="120"></canvas>
    </div>

    <div class="admin-widgets">
        <div class="table-container">
            <h2>Today's Attendance</h2>
            <?php if (!empty($recent_attendance)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Morning In</th>
                            <th>Afternoon In</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_attendance as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['name']); ?></td>
                                <td><?php echo $record['morning_time_in'] ? htmlspecialchars(date('h:i A', strtotime($record['morning_time_in']))) : '—'; ?></td>
                                <td><?php echo $record['afternoon_time_in'] ? htmlspecialchars(date('h:i A', strtotime($record['afternoon_time_in']))) : '—'; ?></td>
                                <td>
                                    <?php
                                        $status = 'In Progress';
                                        if ($record['morning_status'] === 'complete' && $record['afternoon_status'] === 'complete') {
                                            $status = 'Complete';
                                        } elseif ($record['morning_status'] === 'complete' || $record['afternoon_status'] === 'complete') {
                                            $status = 'Partial';
                                        }
                                        echo '<span class="status-badge ' . ($status === 'Complete' ? 'complete' : 'incomplete') . '">' . $status . '</span>';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-records">No attendance records found for today.</div>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <h2>Recent Payroll</h2>
            <?php if (!empty($recent_payroll)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Pay Date</th>
                            <th>Total</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_payroll as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['name']); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($record['pay_date']))); ?></td>
                                <td>₱ <?php echo number_format($record['total'], 2); ?></td>
                                <td><?php echo htmlspecialchars($record['notes'] ?: '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-records">No payroll entries found yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
fetch("../api/dashboard.php")
.then(response => response.json())
.then(data => {

    new Chart(document.getElementById("attendanceChart"), {
        type: "bar",
        data: {
            labels: ["Employees", "Attendance", "Late"],
            datasets: [{
                label: "Today's Statistics",
                data: [
                    data.totalEmployees,
                    data.totalAttendance,
                    data.lateArrivals
                ]
            }]
        },
        options: {
            responsive: true
        }
    });

});
</script>



</body>
</html>
