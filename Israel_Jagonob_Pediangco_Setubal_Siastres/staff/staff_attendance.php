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

// Get all attendance records from the database
$attendance_records = [];
if ($stmt = $conn->prepare("SELECT a.*, e.name FROM attendance a JOIN employees e ON a.employee_id = e.id WHERE e.role != 'staff' ORDER BY a.date DESC, e.name ASC")) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $attendance_records[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - HIMAKAS Staff</title>
    <link rel="stylesheet" href="../style.css">

</head>
<body>

<?php include __DIR__ . '/staff_sidebar.php'; ?>

<div class="staff-main">
    <div class="staff-header">
        <h1>Attendance Records</h1>
        <p>View all employee attendance</p>
    </div>

    <div class="staff-table-container">
        <?php if (!empty($attendance_records)): ?>
            <table class="staff-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Morning In</th>
                        <th>Morning Out</th>
                        <th>Afternoon In</th>
                        <th>Afternoon Out</th>
                        <th>Morning Status</th>
                        <th>Afternoon Status</th>
                        <th>Day Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance_records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                            <td><?php echo $record['morning_time_in'] ? formatTime12Hour($record['morning_time_in']) : '-'; ?></td>
                            <td><?php echo $record['morning_time_out'] ? formatTime12Hour($record['morning_time_out']) : '-'; ?></td>
                            <td><?php echo $record['afternoon_time_in'] ? formatTime12Hour($record['afternoon_time_in']) : '-'; ?></td>
                            <td><?php echo $record['afternoon_time_out'] ? formatTime12Hour($record['afternoon_time_out']) : '-'; ?></td>
                            <td><span class="status-badge <?php echo $record['morning_status'] ?? 'incomplete'; ?>"><?php echo $record['morning_status'] == 'complete' ? 'Complete' : 'Incomplete'; ?></span></td>
                            <td><span class="status-badge <?php echo $record['afternoon_status'] ?? 'incomplete'; ?>"><?php echo $record['afternoon_status'] == 'complete' ? 'Complete' : 'Incomplete'; ?></span></td>
                            <td>
                                <?php 
                                $day_status = (($record['morning_status'] ?? 'incomplete') == 'complete' && ($record['afternoon_status'] ?? 'incomplete') == 'complete') ? 'complete' : 'incomplete';
                                ?>
                                <span class="status-badge <?php echo $day_status; ?>"><?php echo $day_status == 'complete' ? 'Full Day' : 'Partial'; ?></span>
                            </td>
                            
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #7f8c8d; padding: 30px;">No attendance records found</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>




