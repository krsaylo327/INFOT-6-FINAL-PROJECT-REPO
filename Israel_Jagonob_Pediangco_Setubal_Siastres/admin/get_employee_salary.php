<?php
session_start();
include __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Only allow admin access
if (!isset($_SESSION['employee_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['emp_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Employee ID required']);
    exit;
}

$emp_id = (int) $_GET['emp_id'];

// Get employee details with salary
$employee = null;
if ($stmt = $conn->prepare("SELECT id, name, position, role, salary FROM employees WHERE id = ?")) {
    $stmt->bind_param("i", $emp_id);
    $stmt->execute();
    $employee = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$employee) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Employee not found']);
    exit;
}

// Use actual salary from employee record if available, otherwise use position-based rates
if ($employee['salary']) {
    $base_salary = (float) $employee['salary'];
} else {
    // Fallback to position-based rates if no salary is set in record
    $salary_rates = [
        'Site Manager' => 15000,
        'Administrator' => 15000,
        'Foreman' => 12000,
        'Driver' => 14300,  // ₱650/day × 22 work days (Construction Worker)
        'default' => 10000
    ];
    $base_salary = $salary_rates[$employee['position']] ?? $salary_rates['default'];
}

// Use current week (Monday - Sunday) for payroll calculations
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week', strtotime($today)));
$week_end = date('Y-m-d', strtotime('sunday this week', strtotime($today)));

// 1. Establish true labor rates from the monthly reference salary
// Allow position-based daily-rate overrides to match other parts of the app
$position_daily_overrides = [
    'Foreman' => 650,
    'Driver' => 650,
    'Construction Worker' => 650
];

if (isset($position_daily_overrides[$employee['position']])) {
    $daily_rate = (float) $position_daily_overrides[$employee['position']];
    $monthly_rate = $daily_rate * 22; // derive monthly reference from daily override
} else {
    $monthly_rate = $base_salary;
    $daily_rate = $monthly_rate / 22;  // DOLE standard: 22 work days per month
}

$hourly_rate = $daily_rate / 8;

// Initialize weekly payroll accumulators
$total_overtime_pay = 0;
$work_days = 0;
$total_overtime_hours = 0;
$weekly_base_salary = 0; // Weekly earned base pay (regular hours only)

// 2. Fetch Weekly Attendance Records
if ($stmt = $conn->prepare("SELECT date, morning_time_in, morning_time_out, afternoon_time_in, afternoon_time_out, morning_status, afternoon_status, daily_status FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ? ORDER BY date ASC")) {
    $stmt->bind_param("iss", $emp_id, $week_start, $week_end);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Calculate total hours worked for the day
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
        
        // Safeguard logic: Auto-detect overtime using 9-hour grace period threshold
        // If total_hours < 9: treat as full_day (grace period)
        // If total_hours >= 9: mark as overtime
        if ($total_hours >= 9) {
            $day_status = 'overtime';
        } else {
            $day_status = $row['daily_status'] ?? (($row['morning_status'] == 'complete' && $row['afternoon_status'] == 'complete') ? 'full_day' : 'incomplete');
        }
        
        // Process standard shift compensation
        if ($day_status === 'full_day' || $day_status === 'overtime') {
            $work_days++;
            $weekly_base_salary += $daily_rate; // Full standard day base payout
        } elseif ($total_hours > 0) {
            // Partial/Incomplete shift payout calculated strictly on actual hours logged
            $weekly_base_salary += ($total_hours * $hourly_rate);
        }
        
        // Process regular overtime premium compensation (DOLE Standard: 1.25x for hours beyond 8)
        // Overtime only applies if total_hours >= 9 (after 1-hour grace period)
        if ($total_hours >= 9) {
            $overtime_hours = $total_hours - 8;
            $total_overtime_hours += $overtime_hours;
            $total_overtime_pay += ($overtime_hours * $hourly_rate * 1.25);
        }
    }
    $stmt->close();
}

// 3. Compute total weekly gross earnings
$total_gross_earned_this_week = $weekly_base_salary + $total_overtime_pay;

// Get total disbursed payments this week
$total_disbursed = 0;
if ($stmt = $conn->prepare("SELECT IFNULL(SUM(amount), 0) AS total FROM payroll_disbursements WHERE employee_id = ? AND disbursement_date BETWEEN ? AND ?")) {
    $week_start_dt = $week_start . ' 00:00:00';
    $week_end_dt = $week_end . ' 23:59:59';
    $stmt->bind_param("iss", $emp_id, $week_start_dt, $week_end_dt);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_disbursed = $result['total'] ?? 0;
    $stmt->close();
}

// 4. Construct response mapping variables to the frontend UI expectations
$response = [
    'success' => true,
    'base_salary' => round($weekly_base_salary, 2),
    'overtime_pay' => round($total_overtime_pay, 2),
    'week_start' => $week_start,
    'week_end' => $week_end,
    'work_days_this_week' => $work_days,
    'overtime_hours' => round($total_overtime_hours, 2),
    'total_disbursed_this_week' => round($total_disbursed, 2)
];

// Include employee metadata for frontend form display
$response['id'] = $employee['id'];
$response['name'] = htmlspecialchars($employee['name']);
$response['position'] = htmlspecialchars($employee['position']);

echo json_encode($response);
?>