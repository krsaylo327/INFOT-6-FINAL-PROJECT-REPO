<?php
session_start();
include __DIR__ . '/../config/db.php';

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit;
}

$id = $_SESSION['employee_id'];

// Get Employee Info
$user = $conn->query("SELECT * FROM employees WHERE id=$id")->fetch_assoc();

// Get Attendance Records
$query = $conn->query("SELECT * FROM attendance WHERE employee_id=$id ORDER BY date DESC LIMIT 30");
$records = [];
while($row = $query->fetch_assoc()){
    $records[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Records - HIMAKAS</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<?php include __DIR__ . '/employee_sidebar.php'; ?>

<div class="main">
    <div class="header-section">
        <h1>Attendance Records</h1>
        <p class="welcome-text"><?php echo htmlspecialchars($user['name']); ?> • Last 30 days</p>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Morning Time In</th>
                    <th>Morning Time Out</th>
                    <th>Morning Status</th>
                    <th>Afternoon Time In</th>
                    <th>Afternoon Time Out</th>
                    <th>Afternoon Status</th>
                    <th>Total Hours</th>
                    <th>Daily Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($records)): ?>
                    <tr>
                        <td colspan="9" class="empty-records">
                            No attendance records found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach($records as $record): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                            <td><?php echo $record['morning_time_in'] ? htmlspecialchars(formatTime12Hour($record['morning_time_in'])) : '<span class="empty-data">—</span>'; ?></td>
                            <td><?php echo $record['morning_time_out'] ? htmlspecialchars(formatTime12Hour($record['morning_time_out'])) : '<span class="empty-data">—</span>'; ?></td>
                            <td>
                                <span class="status-badge <?php echo htmlspecialchars($record['morning_status'] ?? 'incomplete'); ?>">
                                    <?php 
                                    $morning_status = $record['morning_status'] ?? 'incomplete';
                                    $morning_text = $morning_status == 'complete' ? 'Complete' : 'Incomplete';
                                    echo $morning_text;
                                    ?>
                                </span>
                            </td>
                            <td><?php echo $record['afternoon_time_in'] ? htmlspecialchars(formatTime12Hour($record['afternoon_time_in'])) : '<span class="empty-data">—</span>'; ?></td>
                            <td><?php echo $record['afternoon_time_out'] ? htmlspecialchars(formatTime12Hour($record['afternoon_time_out'])) : '<span class="empty-data">—</span>'; ?></td>
                            <td>
                                <span class="status-badge <?php echo htmlspecialchars($record['afternoon_status'] ?? 'incomplete'); ?>">
                                    <?php 
                                    $afternoon_status = $record['afternoon_status'] ?? 'incomplete';
                                    $afternoon_text = $afternoon_status == 'complete' ? 'Complete' : 'Incomplete';
                                    echo $afternoon_text;
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $total_hours = 0;
                                if ($record['morning_time_in'] && $record['morning_time_out']) {
                                    $total_hours += (strtotime($record['morning_time_out']) - strtotime($record['morning_time_in'])) / 3600;
                                }
                                if ($record['afternoon_time_in'] && $record['afternoon_time_out']) {
                                    $total_hours += (strtotime($record['afternoon_time_out']) - strtotime($record['afternoon_time_in'])) / 3600;
                                }
                                echo $total_hours > 0 ? number_format($total_hours, 2) . ' hrs' : '<span class="empty-data">—</span>';
                                ?>
                            </td>
                            <td>
                                <?php 
                                $daily_status = $record['daily_status'] ?? null;
                                $morning_complete = ($record['morning_status'] ?? 'incomplete') == 'complete';
                                $afternoon_complete = ($record['afternoon_status'] ?? 'incomplete') == 'complete';

                                if ($daily_status === 'overtime') {
                                    echo '<span class="status-badge on_time">Overtime</span>';
                                } elseif ($daily_status === 'full_day' || ($morning_complete && $afternoon_complete)) {
                                    echo '<span class="status-badge on_time">Full Day</span>';
                                } elseif ($morning_complete || $afternoon_complete) {
                                    echo '<span class="status-badge late">Half Day</span>';
                                } else {
                                    echo '<span class="status-badge incomplete">Incomplete</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
