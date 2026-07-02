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

$today = date('Y-m-d');
$total_employees = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM employees WHERE role != 'admin'")) {
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_employees = $result['total'] ?? 0;
    $stmt->close();
}

$presentToday = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM attendance WHERE date = ?")) {
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $presentToday = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
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

$absent_count = $total_employees - $presentToday;
$attendance_rate = $total_employees > 0 ? round(($presentToday / $total_employees) * 100) : 0;

$todayRecords = [];
if ($stmt = $conn->prepare("SELECT a.*, e.name FROM attendance a JOIN employees e ON a.employee_id = e.id WHERE a.date = ? ORDER BY e.name ASC LIMIT 30")) {
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $todayRecords[] = $row;
    }
    $stmt->close();
}

$activePage = 'attendance';
// Load attendance data from API
$attendanceData = json_decode(
    file_get_contents("http://localhost/CAPSTONE/api/attendance.php"),
    true
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Attendance - HIMAKAS</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="admin-main">
    <div class="admin-header">
        <h1>Attendance Overview</h1>
        <p class="welcome-text">Admin view for workforce attendance • <?php echo date('l, F j, Y'); ?></p>
    </div>

    <div class="admin-cards">
        <div class="card">
            <h3>� Present Today</h3>
            <p class="large-value"><?php echo $presentToday; ?> / <?php echo $total_employees; ?></p>
            <small>Present out of total workforce</small>
        </div>

        <div class="card">
            <h3>❌ Absent</h3>
            <p class="large-value"><?php echo number_format($absent_count); ?></p>
            <small>Not checked in</small>
        </div>

        <div class="card">
            <h3>⏰ Late Arrivals</h3>
            <p class="large-value"><?php echo number_format($late_arrivals); ?></p>
            <small>Missed cutoff times</small>
        </div>

        <div class="card">
            <h3>📈 Attendance Rate</h3>
            <p class="large-value"><?php echo $attendance_rate; ?>%</p>
            <small>Percentage present today</small>
        </div>

        <div class="card">
            <h3>📅 Date</h3>
            <p class="large-value"><?php echo date('F j'); ?></p>
            <small><?php echo date('l'); ?></small>
        </div>
    </div>

    <div class="table-container">
        <h2>Today’s Attendance</h2>
        <?php if (!empty($todayRecords)): ?>
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
                    <?php foreach ($attendanceData as $record): ?>
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
</div>

<script>
fetch("../api/attendance.php")
    .then(response => response.json())
    .then(data => {
        console.log(data);
    })
    .catch(error => console.error(error));
</script>

</body>
</html>
