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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'disburse') {
        $emp_id = (int) $_POST['emp_id'];
        $base_salary = (float) $_POST['base_salary'];
        $overtime_pay = (float) ($_POST['overtime_pay'] ?? 0);
        $deductions = (float) ($_POST['deductions'] ?? 0);
        $notes = trim($_POST['notes']);
        $disbursement_date = date('Y-m-d H:i:s');
        
        $final_total = $base_salary + $overtime_pay - $deductions;
        
        // Create payroll record
        if ($stmt = $conn->prepare("INSERT INTO payroll (employee_id, total, pay_date, notes) VALUES (?, ?, ?, ?)")) {
            $pay_date = date('Y-m-d');
            $stmt->bind_param("idss", $emp_id, $final_total, $pay_date, $notes);
            if ($stmt->execute()) {
                $payroll_id = $conn->insert_id;
                
                // Create disbursement record to track what was disbursed
                if ($stmt2 = $conn->prepare("INSERT INTO payroll_disbursements (employee_id, payroll_id, amount, disbursement_date, base_salary, overtime_pay, deductions) VALUES (?, ?, ?, ?, ?, ?, ?)")) {
                    $stmt2->bind_param("iidsddd", $emp_id, $payroll_id, $final_total, $disbursement_date, $base_salary, $overtime_pay, $deductions);
                    if ($stmt2->execute()) {
                        $message = "✅ Payroll disbursed successfully! Amount: ₱" . number_format($final_total, 2);
                    } else {
                        $message = "✅ Payroll recorded (disbursement tracking available in next update)";
                    }
                    $stmt2->close();
                }
            } else {
                $message = "❌ Error disbursing payroll.";
            }
            $stmt->close();
        }
    }
}

// Use current week (Monday - Sunday) for payroll calculations
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week', strtotime($today)));
$week_end = date('Y-m-d', strtotime('sunday this week', strtotime($today)));

$total_employees = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM employees WHERE role != 'admin'")) {
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_employees = $result['total'] ?? 0;
    $stmt->close();
}

$monthlyPayrollTotal = 0;
if ($stmt = $conn->prepare("SELECT IFNULL(SUM(total), 0) AS total_sum FROM payroll WHERE pay_date BETWEEN ? AND ?")) {
    $stmt->bind_param("ss", $week_start, $week_end);
    $stmt->execute();
    $monthlyPayrollTotal = $stmt->get_result()->fetch_assoc()['total_sum'] ?? 0;
    $stmt->close();
}

$employees_paid = 0;
if ($stmt = $conn->prepare("SELECT COUNT(DISTINCT employee_id) AS total FROM payroll WHERE pay_date BETWEEN ? AND ?")) {
    $stmt->bind_param("ss", $week_start, $week_end);
    $stmt->execute();
    $employees_paid = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();
}

$average_payroll = $employees_paid > 0 ? $monthlyPayrollTotal / $employees_paid : 0;
$employees_pending = $total_employees - $employees_paid;

$employees = [];
if ($stmt = $conn->prepare("SELECT * FROM employees WHERE role != 'admin' ORDER BY name ASC")) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    $stmt->close();
}

$recentPayroll = [];
if ($stmt = $conn->prepare("SELECT p.*, e.name FROM payroll p JOIN employees e ON p.employee_id = e.id ORDER BY p.pay_date DESC LIMIT 10")) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentPayroll[] = $row;
    }
    $stmt->close();
}

$activePage = 'payroll';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Payroll - HIMAKAS</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .confirm-modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }
        .confirm-modal.active {
            display: flex;
        }
        .confirm-modal-content {
            width: 100%;
            max-width: 460px;
            background: #1b1f29;
            border-radius: 24px;
            padding: 24px;
            color: #f5f7fb;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .confirm-modal-content h3 {
            margin: 0 0 16px;
            font-size: 20px;
            letter-spacing: 0.02em;
        }
        .confirm-modal-content p {
            margin: 10px 0;
            color: rgba(245, 247, 251, 0.85);
            line-height: 1.5;
        }
        .confirm-details {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 16px;
            margin-top: 16px;
        }
        .confirm-details div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 15px;
        }
        .confirm-details div strong {
            color: #f5f7fb;
        }
        .confirm-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 22px;
        }
        .confirm-actions button {
            border: none;
            border-radius: 10px;
            padding: 12px 18px;
            font-weight: 700;
            cursor: pointer;
        }
        .confirm-actions .confirm-btn {
            background: #ff8b60;
            color: #fff;
        }
        .confirm-actions .cancel-btn {
            background: rgba(255, 255, 255, 0.08);
            color: #f5f7fb;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="admin-main compact">
    <div class="admin-header">
        <h1>Payroll & Expenses</h1>
        <p class="welcome-text">Manage employee salaries, overtime, deductions, and disbursements</p>
    </div>

    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 14px 16px; border-radius: 8px; margin-bottom: 20px; border-left: 5px solid #28a745;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <h2>Disburse Payroll</h2>
        <form method="POST" id="payrollForm" style="background: #f8fafb; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
            <input type="hidden" name="action" value="disburse">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                <div>
                    <label>Employee</label>
                    <select id="empSelect" name="emp_id" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="">-- Select Employee --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['name']); ?> (<?php echo htmlspecialchars($emp['position']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Base Salary</label>
                    <input type="number" id="baseSalary" step="0.01" name="base_salary" placeholder="0.00" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div>
                    <label>Overtime Pay</label>
                    <input type="number" id="overtimePay" step="0.01" name="overtime_pay" placeholder="0.00" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                <div>
                    <label>Deductions</label>
                    <input type="number" id="deductions" step="0.01" name="deductions" placeholder="0.00" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div>
                    <label>Notes</label>
                    <input type="text" name="notes" placeholder="e.g., April Payroll, Bonus..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
            </div>
            <div id="disbursedInfo" style="background: #e8f4f8; padding: 12px; border-radius: 6px; margin-top: 15px; border-left: 4px solid #3498db; display: none;">
                <small style="color: #2c3e50;"><strong>Already Disbursed This Week:</strong> <span id="disbursedAmount">₱0.00</span></small>
            </div>
            <div id="weekInfo" style="background: #f0f7ff; padding: 12px; border-radius: 6px; margin-top: 15px; border-left: 4px solid #667eea;"></div>
            <div style="display: flex; gap: 10px; margin-top: 20px; align-items: center;">
                <button type="button" onclick="confirmDisbursement(event)" style="background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">💳 Disburse Salary</button>
                <small id="totalDisplay" style="color: #7f8c8d;"></small>
            </div>
        </form>

        <div id="confirmModal" class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle" aria-describedby="confirmModalDesc">
            <div class="confirm-modal-content">
                <h3 id="confirmModalTitle">Confirm Payroll Disbursement</h3>
                <p id="confirmModalDesc">Please review the payroll details below before proceeding.</p>
                <div class="confirm-details">
                    <div><strong>Employee</strong><span id="confirmEmployee"></span></div>
                    <div><strong>Base Salary</strong><span id="confirmBase"></span></div>
                    <div><strong>Overtime Pay</strong><span id="confirmOvertime"></span></div>
                    <div><strong>Deductions</strong><span id="confirmDeductions"></span></div>
                    <div><strong>Total Amount</strong><span id="confirmTotal"></span></div>
                </div>
                <div class="confirm-actions">
                    <button type="button" class="cancel-btn" id="cancelDisbursement">Cancel</button>
                    <button type="button" class="confirm-btn" id="confirmDisbursementBtn">Proceed</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const empSelect = document.getElementById('empSelect');
        const baseSalaryInput = document.getElementById('baseSalary');
        const overtimePayInput = document.getElementById('overtimePay');
        const deductionsInput = document.getElementById('deductions');
        const disbursedInfo = document.getElementById('disbursedInfo');
        const disbursedAmount = document.getElementById('disbursedAmount');
        const totalDisplay = document.getElementById('totalDisplay');

        empSelect.addEventListener('change', function() {
            if (this.value) {
                // Fetch employee salary data
                fetch('get_employee_salary.php?emp_id=' + this.value)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            baseSalaryInput.value = data.base_salary;
                            overtimePayInput.value = data.overtime_pay || '0.00';
                            deductionsInput.value = '0.00';
                            
                            // Show week range and work days info
                            const weekInfo = document.getElementById('weekInfo');
                            if (weekInfo) {
                                const weekStart = new Date(data.week_start);
                                const weekEnd = new Date(data.week_end);
                                const overtimeHours = parseFloat(data.overtime_hours) || 0;
                                const overtimePay = parseFloat(data.overtime_pay) || 0;
                                weekInfo.innerHTML = `
                                    <small style="color: #666;">
                                        <strong>Week:</strong> ${weekStart.toLocaleDateString('en-PH')} - ${weekEnd.toLocaleDateString('en-PH')}<br>
                                        <strong>Work Days:</strong> ${data.work_days_this_week} days | 
                                        <strong>Overtime Hours:</strong> ${overtimeHours.toFixed(2)}h<br>
                                        <strong>Overtime Pay:</strong> ₱${overtimePay.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                                    </small>
                                `;
                            }
                            
                            if (data.total_disbursed_this_week > 0) {
                                disbursedInfo.style.display = 'block';
                                disbursedAmount.textContent = '₱' + parseFloat(data.total_disbursed_this_week).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            } else {
                                disbursedInfo.style.display = 'none';
                            }
                            
                            updateTotal();
                        } else {
                            alert('Error: ' + data.error);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            } else {
                baseSalaryInput.value = '';
                overtimePayInput.value = '';
                deductionsInput.value = '';
                disbursedInfo.style.display = 'none';
                totalDisplay.textContent = '';
                const weekInfo = document.getElementById('weekInfo');
                if (weekInfo) weekInfo.innerHTML = '';
            }
        });

        [baseSalaryInput, overtimePayInput, deductionsInput].forEach(input => {
            input.addEventListener('input', updateTotal);
        });

        function updateTotal() {
            const base = parseFloat(baseSalaryInput.value) || 0;
            const overtime = parseFloat(overtimePayInput.value) || 0;
            const deductions = parseFloat(deductionsInput.value) || 0;
            const total = base + overtime - deductions;
            
            if (total > 0) {
                totalDisplay.textContent = '→ Total: ₱' + total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            } else {
                totalDisplay.textContent = '';
            }
        }

        const confirmModal = document.getElementById('confirmModal');
        const confirmEmployee = document.getElementById('confirmEmployee');
        const confirmBase = document.getElementById('confirmBase');
        const confirmOvertime = document.getElementById('confirmOvertime');
        const confirmDeductions = document.getElementById('confirmDeductions');
        const confirmTotal = document.getElementById('confirmTotal');
        const confirmDisbursementBtn = document.getElementById('confirmDisbursementBtn');
        const cancelDisbursement = document.getElementById('cancelDisbursement');

        function confirmDisbursement(event) {
            event.preventDefault();

            if (!empSelect.value) {
                alert('Please select an employee');
                return;
            }

            const base = parseFloat(baseSalaryInput.value) || 0;
            const overtime = parseFloat(overtimePayInput.value) || 0;
            const deductions = parseFloat(deductionsInput.value) || 0;
            const total = base + overtime - deductions;
            const empName = empSelect.options[empSelect.selectedIndex].text;

            if (total <= 0) {
                alert('Please enter valid salary information');
                return;
            }

            confirmEmployee.textContent = empName;
            confirmBase.textContent = '₱' + base.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            confirmOvertime.textContent = '₱' + overtime.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            confirmDeductions.textContent = '₱' + deductions.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            confirmTotal.textContent = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            confirmModal.classList.add('active');
        }

        confirmDisbursementBtn.addEventListener('click', function() {
            confirmModal.classList.remove('active');
            document.getElementById('payrollForm').submit();
        });

        cancelDisbursement.addEventListener('click', function() {
            confirmModal.classList.remove('active');
        });

        window.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && confirmModal.classList.contains('active')) {
                confirmModal.classList.remove('active');
            }
        });
    </script>

    <div class="admin-header compact" style="margin-top: 20px; padding: 18px;">
        <h2>Payroll Overview</h2>
    </div>

    <div class="admin-cards compact-cards">
        <div class="card">
            <h3>💵 Total Payroll</h3>
            <p class="large-value">₱ <?php echo number_format($monthlyPayrollTotal, 2); ?></p>
            <small>This week</small>
        </div>
        <div class="card">
            <h3>✅ Paid Employees</h3>
            <p class="large-value"><?php echo number_format($employees_paid); ?></p>
            <small>Employees processed</small>
        </div>
        <div class="card">
            <h3>⏳ Pending</h3>
            <p class="large-value"><?php echo number_format($employees_pending); ?></p>
            <small>Awaiting payment</small>
        </div>
        <div class="card">
            <h3> Period</h3>
            <p class="large-value"><?php echo date('M d', strtotime($week_start)); ?> - <?php echo date('M d'); ?></p>
            <small>Billing cycle</small>
        </div>
    </div>

    <div class="table-container compact-table">
        <h2>Recent Payroll</h2>
        <?php if (!empty($recentPayroll)): ?>
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
                    <?php foreach ($recentPayroll as $record): ?>
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
            <div class="empty-records">No payroll entries found.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
