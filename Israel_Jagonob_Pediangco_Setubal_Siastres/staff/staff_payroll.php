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

// Get current month payroll
$current_month = date('Y-m');
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

$payroll_records = [];
if ($stmt = $conn->prepare("SELECT p.*, e.name FROM payroll p JOIN employees e ON p.employee_id = e.id WHERE p.pay_date LIKE ? ORDER BY p.pay_date DESC")) {
    $pattern = $current_month . '%';
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $payroll_records[] = $row;
    }
    $stmt->close();
}

// Get summary stats
$total_payroll = 0;
$total_employees_paid = 0;
foreach ($payroll_records as $record) {
    $total_payroll += $record['total'];
    $total_employees_paid++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll - HIMAKAS Staff</title>
    <link rel="stylesheet" href="../style.css">

</head>
<body>

<?php include __DIR__ . '/staff_sidebar.php'; ?>

<div class="staff-main">
    <div class="staff-header">
        <h1>Payroll Management</h1>
        <p><?php echo date('F Y'); ?> • Manage employee salaries</p>
    </div>

    <div class="staff-cards">
        <div class="staff-card">
            <h3>💰 Total Payroll</h3>
            <div class="value">₱ <?php echo number_format($total_payroll, 2); ?></div>
            <div class="label">This month</div>
        </div>

        <div class="staff-card">
            <h3>👥 Employees Paid</h3>
            <div class="value"><?php echo $total_employees_paid; ?></div>
            <div class="label">processed payrolls</div>
        </div>

    </div>

    <div class="staff-table-container">
        <h2 style="margin-top: 0; color: #2c3e50; border-bottom: 2px solid #667eea; padding-bottom: 15px;">Payroll Records - <?php echo date('F Y'); ?></h2>
        <?php if (!empty($payroll_records)): ?>
            <table class="staff-table">
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Pay Date</th>
                        <th>Total Amount</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payroll_records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($record['pay_date'])); ?></td>
                            <td><strong>₱ <?php echo number_format($record['total'], 2); ?></strong></td>
                            <td><?php echo htmlspecialchars($record['notes'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #7f8c8d; padding: 30px;">No payroll records found for <?php echo date('F Y'); ?></p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>




