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

// Get total employees count (exclude administrators)
$total_employees = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) as total FROM employees WHERE role != 'admin'")) {
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_employees = isset($result['total']) ? (int) $result['total'] : 0;
    $stmt->close();
}

// Get today's attendance count
$today = date("Y-m-d");
$today_attendance = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE date = ?")) {
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $today_attendance = $result['total'] ?? 0;
    $stmt->close();
}

// Get employees with complete day today
$complete_days = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance WHERE date = ? AND morning_status = 'complete' AND afternoon_status = 'complete'")) {
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $complete_days = $result['total'] ?? 0;
    $stmt->close();
}

// Get recent payroll records
$payroll_records = [];
if ($stmt = $conn->prepare("SELECT p.*, e.name FROM payroll p JOIN employees e ON p.employee_id = e.id ORDER BY p.pay_date DESC LIMIT 10")) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $payroll_records[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - HIMAKAS</title>
    <link rel="stylesheet" href="../style.css">

</head>
<body>

<?php include __DIR__ . '/staff_sidebar.php'; ?>

<div class="staff-main">
    <div class="staff-header">
        <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
        <p>Staff Dashboard • <?php echo date('l, F j, Y'); ?></p>
    </div>

    <div class="staff-cards">
        <div class="staff-card">
            <h3>👥 Total Employees</h3>
            <div class="value"><?php echo $total_employees; ?></div>
            <div class="label">Active employees</div>
        </div>

        <div class="staff-card">
            <h3>📅 Present Today</h3>
            <div class="value"><?php echo $today_attendance; ?></div>
            <div class="label"><?php echo $today_attendance > 0 ? 'employees present' : 'no attendance'; ?></div>
        </div>

        <div class="staff-card">
            <h3>✅ Complete Day</h3>
            <div class="value"><?php echo $complete_days; ?></div>
            <div class="label">full day worked</div>
        </div>

        <div class="staff-card">
            <h3>📈 Attendance Rate</h3>
            <div class="value"><?php echo $total_employees > 0 ? round(($today_attendance / $total_employees) * 100) : 0; ?>%</div>
            <div class="label">attendance percentage</div>
        </div>
    </div>

    <div class="staff-table-container">
        <h2>Recent Payroll Records</h2>
        <?php if (!empty($payroll_records)): ?>
            <table class="staff-table">
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Pay Date</th>
                        <th>Total Amount</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payroll_records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($record['pay_date'])); ?></td>
                            <td>₱ <?php echo number_format($record['total'], 2); ?></td>
                            <td><?php echo htmlspecialchars($record['notes'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #7f8c8d;">No payroll records found</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>




