<?php
session_start();
include __DIR__ . '/../config/db.php';

if (!isset($_SESSION['employee_id'])) {
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
    header("Location: ../../login.php");
    exit;
}

$today = date("Y-m-d");
$attendance = null;
if ($stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?")) {
    $stmt->bind_param("is", $id, $today);
    $stmt->execute();
    $attendance = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}



$pay = null;
if ($stmt = $conn->prepare("SELECT * FROM payroll WHERE employee_id = ? ORDER BY pay_date DESC LIMIT 1")) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $pay = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$today_agendas = [];

// Add default mandatory tasks for today
$default_today_tasks = [
    ['id' => 'default_1', 'task_description' => '✔️ Complete Morning Site Inspection', 'priority' => 'high', 'status' => 'pending'],
    ['id' => 'default_2', 'task_description' => '✔️ Safety Equipment Check', 'priority' => 'high', 'status' => 'pending'],
    ['id' => 'default_3', 'task_description' => '✔️ Submit Daily Progress Report', 'priority' => 'medium', 'status' => 'pending']
];

if ($stmt = $conn->prepare("SELECT * FROM agendas WHERE employee_id = ? AND date = ? ORDER BY priority DESC, created_at ASC")) {
    $stmt->bind_param("is", $id, $today);
    $stmt->execute();
    $agenda_result = $stmt->get_result();
    while ($row = $agenda_result->fetch_assoc()) {
        $today_agendas[] = $row;
    }
    $stmt->close();
}

// Merge default tasks with database tasks
$today_agendas = array_merge($default_today_tasks, $today_agendas);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - HIMAKAS</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<?php include __DIR__ . '/employee_sidebar.php'; ?>

<div class="main">
    <div class="header-section">
        <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
        <p class="welcome-text">Employee Dashboard • <?php echo date('l, F j, Y'); ?> </p>
    </div>

    <div class="cards">
        <div class="card">
            <h3>🌅 Morning Session</h3>
            <p><?php 
            
                if ($attendance) {
                    $morning_status = $attendance['morning_status'] ?? 'incomplete';
                    if ($attendance['morning_time_in']) {
                        $morning_late = strtotime($attendance['morning_time_in']) > strtotime('08:00:00');
                        $status = $morning_late ? 'Late' : 'On Time';
                        if ($attendance['morning_time_out']) {
                            $status .= ' - ' . ($morning_status == 'complete' ? 'Complete' : 'Incomplete');
                        } else {
                            $status .= ' - Timed In';
                        }
                        echo htmlspecialchars($status);
                    } else {
                        echo "Not Started";
                    }
                } else {
                    echo "No Record";
                }
            ?></p>
            <small><?php
                if ($attendance && $attendance['morning_time_in']) {
                    echo 'Time In: ' . htmlspecialchars(formatTime12Hour($attendance['morning_time_in']));
                    if ($attendance['morning_time_out']) {
                        echo ' | Out: ' . htmlspecialchars(formatTime12Hour($attendance['morning_time_out']));
                    }
                } else {
                    echo 'Morning session (8 AM - 12 PM)';
                }
            ?></small>
        </div>

        <div class="card">
            <h3>🌇 Afternoon Session</h3>
            <p><?php
                if ($attendance) {
                    $afternoon_status = $attendance['afternoon_status'] ?? 'incomplete';
                    if ($attendance['afternoon_time_in']) {
                        $afternoon_late = strtotime($attendance['afternoon_time_in']) > strtotime('13:00:00');
                        $status = $afternoon_late ? 'Late' : 'On Time';
                        if ($attendance['afternoon_time_out']) {
                            $status .= ' - ' . ($afternoon_status == 'complete' ? 'Complete' : 'Incomplete');
                        } else {
                            $status .= ' - Timed In';
                        }
                        echo htmlspecialchars($status);
                    } else {
                        echo "Not Started";
                    }
                } else {
                    echo "No Record";
                }
            ?></p>
            <small><?php
                if ($attendance && $attendance['afternoon_time_in']) {
                    echo 'Time In: ' . htmlspecialchars(formatTime12Hour($attendance['afternoon_time_in']));
                    if ($attendance['afternoon_time_out']) {
                        echo ' | Out: ' . htmlspecialchars(formatTime12Hour($attendance['afternoon_time_out']));
                    }
                } else {
                    echo 'Afternoon session (1 PM - 5 PM)';
                }
            ?></small>
        </div>

        <div class="card">
            <h3>📊 Daily Progress</h3>
            <p><?php 
                if ($attendance) {
                    $morning_complete = ($attendance['morning_status'] ?? 'incomplete') == 'complete';
                    $afternoon_complete = ($attendance['afternoon_status'] ?? 'incomplete') == 'complete';
                    
                    if ($morning_complete && $afternoon_complete) {
                        echo '✓ Full Day Complete';
                    } elseif ($morning_complete || $afternoon_complete) {
                        echo '⏳ Half Day Complete';
                    } else {
                        echo 'In Progress';
                    }
                } else {
                    echo "No Record";
                }
            ?></p>
            <small>8-hour workday requirement</small>
        </div>

        <div class="card">
            <h3>👤 Position</h3>
            <p class="card-position-text"><?php echo htmlspecialchars($user['position']); ?></p>
            <small>Your role in the organization</small>
        </div>

        <div class="card">
            <h3>💵 Latest Salary</h3>
            <p>₱ <?php echo $pay ? number_format($pay['total'], 2) : "0.00"; ?></p>
            <small>Most recent payroll information</small>
        </div>

        <div class="card">
            <h3>🏗️ Today's Agenda</h3>
            <div class="agenda-list">
                <?php if (!empty($today_agendas)): ?>
                    <?php foreach ($today_agendas as $agenda): ?>
                        <div class="agenda-item priority-<?php echo $agenda['priority']; ?>">
                            <span class="agenda-task"><?php echo htmlspecialchars($agenda['task_description']); ?></span>
                            <span class="agenda-priority"><?php echo ucfirst($agenda['priority']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-agenda">No tasks scheduled for today</div>
                <?php endif; ?>
            </div>
            <small>Construction site tasks for <?php echo date('F j, Y'); ?></small>
        </div>
    </div>
</div>

</body>
</html>