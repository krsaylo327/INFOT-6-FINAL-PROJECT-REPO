<?php
session_start();
include __DIR__ . '/config/db.php';

if (!isset($_SESSION['employee_id'])) {
    echo "error";
    exit;
}

$id = (int) $_SESSION['employee_id'];
$daily_rate = 650; // 650 pesos per day

// Get current month and year
$current_month = date('Y-m');
$month_start = $current_month . '-01';
$month_end = date('Y-m-t'); // Last day of current month

// Check if payroll for this month already exists
$stmt = $conn->prepare("SELECT id FROM payroll WHERE employee_id = ? AND pay_date LIKE ?");
$date_pattern = $current_month . '%';
$stmt->bind_param("is", $id, $date_pattern);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    // Update existing payroll for this month
    $stmt = $conn->prepare("SELECT COUNT(*) as work_days, SUM(daily_status = 'overtime') as overtime_days
        FROM attendance 
        WHERE employee_id = ? AND date BETWEEN ? AND ? AND morning_status = 'complete' AND afternoon_status = 'complete'
    ");
    $stmt->bind_param("iss", $id, $month_start, $month_end);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $complete_days = $result['work_days'] ?? 0;
    $overtime_days = $result['overtime_days'] ?? 0;
    $base_pay = $complete_days * $daily_rate;
    $overtime_bonus = $overtime_days * ($daily_rate * 0.1);
    $total_payroll = $base_pay + $overtime_bonus;
    
    // Update payroll
    $pay_date = date('Y-m-d');
    $stmt = $conn->prepare("UPDATE payroll SET total = ?, notes = ? WHERE employee_id = ? AND pay_date LIKE ?");
    $notes = "For period: $month_start to $month_end. Days worked: $complete_days. Overtime days: $overtime_days";
    $stmt->bind_param("dsis", $total_payroll, $notes, $id, $date_pattern);
    $stmt->execute();
    $stmt->close();
    
    echo "updated";
} else {
    // Create new payroll for this month
    $stmt = $conn->prepare("SELECT COUNT(*) as work_days, SUM(daily_status = 'overtime') as overtime_days
        FROM attendance 
        WHERE employee_id = ? AND date BETWEEN ? AND ? AND morning_status = 'complete' AND afternoon_status = 'complete'
    ");
    $stmt->bind_param("iss", $id, $month_start, $month_end);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $complete_days = $result['work_days'] ?? 0;
    $overtime_days = $result['overtime_days'] ?? 0;
    $base_pay = $complete_days * $daily_rate;
    $overtime_bonus = $overtime_days * ($daily_rate * 0.1);
    $total_payroll = $base_pay + $overtime_bonus;
    $pay_date = date('Y-m-d');
    $notes = "For period: $month_start to $month_end. Days worked: $complete_days. Overtime days: $overtime_days";
    $stmt = $conn->prepare("INSERT INTO payroll (employee_id, pay_date, total, notes) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idds", $id, $pay_date, $total_payroll, $notes);
    
    if ($stmt->execute()) {
        echo "created";
    } else {
        echo "error";
    }
    $stmt->close();
}

$conn->close();
?>
