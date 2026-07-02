<?php
session_start();
include __DIR__ . '/../config/db.php';

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit;
}

$id = (int) $_SESSION['employee_id'];
$daily_rate = 650; // 650 pesos per day

// Get employee info
$user = null;
if ($stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?")) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Use current week (Monday - Sunday) for payroll calculations
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week', strtotime($today)));
$week_end = date('Y-m-d', strtotime('sunday this week', strtotime($today)));

$current_payroll = null;
if ($stmt = $conn->prepare("SELECT * FROM payroll WHERE employee_id = ? AND pay_date BETWEEN ? AND ? ORDER BY pay_date DESC LIMIT 1")) {
    $stmt->bind_param("iss", $id, $week_start, $week_end);
    $stmt->execute();
    $current_payroll = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Get attendance for current month to calculate current earnings
$work_days_complete = 0;
$overtime_days = 0;
$leave_early_count = 0;
$current_earnings = 0;

if ($stmt = $conn->prepare("SELECT COUNT(*) as complete_days, SUM(daily_status = 'overtime') as overtime_days FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ? AND morning_status = 'complete' AND afternoon_status = 'complete'")) {
    $stmt->bind_param("iss", $id, $week_start, $week_end);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $work_days_complete = $result['complete_days'] ?? 0;
    $overtime_days = $result['overtime_days'] ?? 0;
    // Current earnings will be calculated after attendance records are built
    $stmt->close();
}

// Count leave early instances
if ($stmt = $conn->prepare("SELECT COUNT(*) as early_count FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ? AND morning_time_in IS NOT NULL AND morning_time_out IS NOT NULL AND afternoon_time_in IS NOT NULL AND afternoon_time_out IS NOT NULL AND ((TIMESTAMPDIFF(MINUTE, CONCAT(date, ' ', morning_time_in), CONCAT(date, ' ', morning_time_out)) + TIMESTAMPDIFF(MINUTE, CONCAT(date, ' ', afternoon_time_in), CONCAT(date, ' ', afternoon_time_out))) < 480)")) {
    $stmt->bind_param("iss", $id, $week_start, $week_end);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $leave_early_count = $result['early_count'] ?? 0;
    $stmt->close();
}

// Get all payroll history
$payroll_history = [];
if ($stmt = $conn->prepare("SELECT * FROM payroll WHERE employee_id = ? ORDER BY pay_date DESC LIMIT 12")) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $payroll_history[] = $row;
    }
    $stmt->close();
}

// Get attendance records for current week
$attendance_records = [];
if ($stmt = $conn->prepare("SELECT date, morning_time_in, morning_time_out, afternoon_time_in, afternoon_time_out, morning_status, afternoon_status, daily_status FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ? ORDER BY date ASC")) {
    $stmt->bind_param("iss", $id, $week_start, $week_end);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Calculate total hours worked
        $total_hours = 0;
        if ($row['morning_time_in'] && $row['morning_time_out']) {
            $total_hours += (strtotime($row['morning_time_out']) - strtotime($row['morning_time_in'])) / 3600;
        }
        if ($row['afternoon_time_in'] && $row['afternoon_time_out']) {
            // Handle afternoon times: if stored as 12-hour format, convert to 24-hour
            $afternoon_in = strtotime($row['afternoon_time_in']);
            $afternoon_out = strtotime($row['afternoon_time_out']);
            
            // If afternoon_in is earlier than morning_out, assume it needs 12 hours added (12-hour format)
            if ($row['morning_time_out'] && $afternoon_in < strtotime($row['morning_time_out'])) {
                $afternoon_in = strtotime($row['afternoon_time_in']) + (12 * 3600);
                $afternoon_out = strtotime($row['afternoon_time_out']) + (12 * 3600);
            }
            
            $total_hours += ($afternoon_out - $afternoon_in) / 3600;
        }
        
        $row['hours_worked'] = $total_hours;
        
        // Overtime Auto-detection: Apply 9-hour grace period threshold
        // If total_hours < 9: treat as full_day
        // If total_hours >= 9: mark as overtime
        if ($row['daily_status'] === 'overtime' || $total_hours >= 9) {
            $row['day_status'] = 'overtime';
        } else {
            $row['day_status'] = $row['daily_status'] ?? (($row['morning_status'] == 'complete' && $row['afternoon_status'] == 'complete') ? 'full_day' : 'incomplete');
        }
        
        $row['daily_pay'] = 0;
        $hourly_rate = $daily_rate / 8;
        
        // Calculate Accurate Payments
        if ($row['day_status'] === 'overtime') {
            // Pay regular daily rate for the first 8 hours + premium rate for the extra hours
            $regular_pay = 8 * $hourly_rate; 
            $overtime_hours = max(0, $total_hours - 8);
            $overtime_pay = $overtime_hours * ($hourly_rate * 1.25);
            
            $row['daily_pay'] = $regular_pay + $overtime_pay;
        } elseif ($row['day_status'] === 'full_day') {
            // Full day: pay the standard daily rate
            $row['daily_pay'] = $daily_rate;
        } elseif ($row['hours_worked'] > 0) {
            // Partial day: pay based strictly on partial hours worked
            $row['daily_pay'] = $row['hours_worked'] * $hourly_rate;
        }
        
        $attendance_records[] = $row;
    }
    $stmt->close();
}

// Calculate current earnings from attendance records
$current_earnings = 0;
foreach ($attendance_records as $record) {
    $current_earnings += $record['daily_pay'];
}

// Get total disbursed this week
$total_disbursed = 0;
if ($stmt = $conn->prepare("SELECT IFNULL(SUM(amount), 0) AS total FROM payroll_disbursements WHERE employee_id = ? AND disbursement_date BETWEEN ? AND ?")) {
    $week_start_dt = $week_start . ' 00:00:00';
    $week_end_dt = $week_end . ' 23:59:59';
    $stmt->bind_param("iss", $id, $week_start_dt, $week_end_dt);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_disbursed = $result['total'] ?? 0;
    $stmt->close();
}

// Calculate remaining available earnings
$remaining_earnings = $current_earnings - $total_disbursed;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll History</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<?php include __DIR__ . '/employee_sidebar.php'; ?>

<div class="main">
    <div class="header-section">
        <h1>💰 Payroll Management</h1>
        <p class="welcome-text"><?php echo htmlspecialchars($user['name'] ?? 'Employee'); ?> • <?php echo date('M d, Y', strtotime($week_start)); ?> - <?php echo date('M d, Y', strtotime($week_end)); ?> Salary Calculation</p>
    </div>

    <div class="payroll-summary">
        <div class="summary-card">
            <h3>📊 Current Week Earnings</h3>
            <div class="earnings-display">
                <p class="earnings-amount">₱ <?php echo number_format($current_earnings, 2); ?></p>
                <small>Days Worked: <strong><?php echo $work_days_complete; ?> days</strong> × ₱<?php echo number_format($daily_rate, 2); ?>/day</small>
            </div>
        </div>

        <div class="summary-card">
            <h3>� Already Disbursed</h3>
            <div class="earnings-display">
                <p class="earnings-amount" style="color: #27ae60;">₱ <?php echo number_format($total_disbursed, 2); ?></p>
                <small>Amount paid this week</small>
            </div>
        </div>

        <div class="summary-card <?php echo $remaining_earnings < 0 ? 'overpaid-card' : ''; ?>">
            <h3>⏳ Remaining Available</h3>
            <div class="earnings-display">
                <p class="earnings-amount" style="color: <?php echo $remaining_earnings < 0 ? '#e74c3c' : '#667eea'; ?>;">₱ <?php echo number_format(max(0, $remaining_earnings), 2); ?></p>
                <small><?php echo $remaining_earnings < 0 ? 'Amount overpaid' : 'Available for next disbursement'; ?></small>
            </div>
        </div>

        <div class="summary-card">
            <h3>�📅 Payroll Period</h3>
            <div class="period-display">
                <p><?php echo date('M d, Y', strtotime($week_start)); ?> - <?php echo date('M d, Y', strtotime($week_end)); ?></p>
                <small>Daily Rate: ₱<?php echo number_format($daily_rate, 2); ?></small>
            </div>
        </div>

        <div class="summary-card">
            <h3>💼 Position</h3>
            <div class="position-display">
                <p><?php echo htmlspecialchars($user['position'] ?? 'N/A'); ?></p>
                <small>Employee ID: <?php echo htmlspecialchars($id); ?></small>
            </div>
        </div>

        <div class="summary-card <?php echo $leave_early_count > 0 ? 'leave-early-card' : ''; ?>">
            <h3>⏱️ Leave Early Count</h3>
            <div class="position-display">
                <p><?php echo $leave_early_count; ?> days</p>
                <small>Days worked less than 8 hours this week</small>
            </div>
        </div>
    </div>

    <div class="payroll-details">
        <div class="details-card">
            <h3>📋 Attendance Records - Week of <?php echo date('M d, Y', strtotime($week_start)); ?></h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Morning In</th>
                            <th>Morning Out</th>
                            <th>Morning Status</th>
                            <th>Afternoon In</th>
                            <th>Afternoon Out</th>
                            <th>Afternoon Status</th>
                            <th>Total Hours</th>
                            <th>Day Status</th>
                            <th>Daily Pay</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendance_records)): ?>
                            <tr>
                                <td colspan="10" class="empty-records">No attendance records for this week</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attendance_records as $record): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                    <td><?php echo $record['morning_time_in'] ? formatTime12Hour($record['morning_time_in']) : '-'; ?></td>
                                    <td><?php echo $record['morning_time_out'] ? formatTime12Hour($record['morning_time_out']) : '-'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $record['morning_status'] == 'complete' ? 'on_time' : 'late'; ?>">
                                            <?php echo $record['morning_status'] == 'complete' ? 'Complete' : 'Incomplete'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $record['afternoon_time_in'] ? formatTime12Hour($record['afternoon_time_in']) : '-'; ?></td>
                                    <td><?php echo $record['afternoon_time_out'] ? formatTime12Hour($record['afternoon_time_out']) : '-'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $record['afternoon_status'] == 'complete' ? 'on_time' : 'late'; ?>">
                                            <?php echo $record['afternoon_status'] == 'complete' ? 'Complete' : 'Incomplete'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $record['hours_worked'] > 0 ? number_format($record['hours_worked'], 2) . 'h' : '-'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $record['day_status'] === 'overtime' ? 'on_time' : ($record['day_status'] === 'full_day' ? 'on_time' : 'late'); ?>">
                                            <?php echo $record['day_status'] === 'overtime' ? 'Overtime' : ($record['day_status'] === 'full_day' ? '✓ Complete' : '✗ Incomplete'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($record['day_status'] === 'overtime') {
                                            echo '₱ ' . number_format($record['daily_pay'], 2);
                                        } elseif ($record['day_status'] === 'full_day') {
                                            echo '₱ ' . number_format($record['daily_pay'], 2);
                                        } elseif ($record['hours_worked'] > 0) {
                                            echo '₱ ' . number_format($record['daily_pay'], 2);
                                        } else {
                                            echo '-';
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

        <div class="details-card">
            <h3>📈 Payroll History</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Pay Date</th>
                            <th>Total Amount</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payroll_history)): ?>
                            <tr>
                                <td colspan="3" class="empty-records">No payroll history available</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payroll_history as $record): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($record['pay_date'])); ?></td>
                                    <td><strong>₱ <?php echo number_format($record['total'], 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($record['notes'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
</div>

<style>
    .payroll-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 35px;
    }

    .summary-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        border-left: 4px solid #667eea;
    }

    .summary-card.leave-early-card {
        border-left-color: #f39c12;
        background: linear-gradient(135deg, #fffbf0 0%, white 100%);
    }

    .summary-card.leave-early-card h3 {
        color: #d68910;
    }

    .summary-card.leave-early-card .position-display p {
        color: #f39c12;
    }

    .summary-card.overpaid-card {
        border-left-color: #e74c3c;
        background: linear-gradient(135deg, #fadbd8 0%, white 100%);
    }

    .summary-card.overpaid-card h3 {
        color: #c0392b;
    }

    .summary-card h3 {
        color: #2c3e50;
        margin-bottom: 15px;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .earnings-display p,
    .period-display p,
    .position-display p {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        color: #667eea;
        line-height: 1.2;
    }

    .earnings-display small,
    .period-display small,
    .position-display small {
        display: block;
        color: #7f8c8d;
        font-size: 13px;
        margin-top: 8px;
    }

    .payroll-details {
        display: flex;
        flex-direction: column;
        gap: 30px;
        margin-bottom: 30px;
    }

    .details-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }

    .details-card h3 {
        color: #2c3e50;
        margin-bottom: 20px;
        font-size: 16px;
        border-bottom: 2px solid #ecf0f1;
        padding-bottom: 12px;
    }

    .payroll-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 12px;
    }

    @media (max-width: 768px) {
        .payroll-summary {
            grid-template-columns: 1fr;
        }

        .earnings-display p,
        .period-display p,
        .position-display p {
            font-size: 20px;
        }

        .payroll-actions {
            flex-direction: column;
        }

        .payroll-actions .btn {
            width: 100%;
        }
    }
</style>
</body>
</html>
