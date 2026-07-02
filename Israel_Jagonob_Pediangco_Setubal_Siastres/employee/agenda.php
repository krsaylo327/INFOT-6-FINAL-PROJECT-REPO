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
    header("Location: ../login.php");
    exit;
}

$today = date("Y-m-d");
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

$all_agendas = [];

// Add default overall project tasks
$default_overall_tasks = [
    ['id' => 'overall_1', 'task_description' => '🏗️ Main Building Construction - Phase 1', 'priority' => 'high', 'status' => 'in_progress'],
    ['id' => 'overall_2', 'task_description' => '⚙️ Equipment Maintenance & Inspection Schedule', 'priority' => 'medium', 'status' => 'pending']
];

if ($stmt = $conn->prepare("SELECT * FROM agendas WHERE employee_id = ? ORDER BY date DESC, priority DESC")) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $agenda_result = $stmt->get_result();
    while ($row = $agenda_result->fetch_assoc()) {
        $all_agendas[] = $row;
    }
    $stmt->close();
}

// Merge default tasks with database tasks
$all_agendas = array_merge($default_overall_tasks, $all_agendas);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Construction Agenda - HIMAKAS</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<?php include __DIR__ . '/employee_sidebar.php'; ?>

<div class="main">
    <div class="header-section">
        <h1>🏗️ Construction Site Agenda</h1>
        <p class="welcome-text"><?php echo date('l, F j, Y'); ?> • Your daily tasks and schedule</p>
    </div>

    <div class="agenda-section">
        <div class="agenda-card">
            <h3>📋 Today's Tasks (<?php echo date('M j'); ?>)</h3>
            <?php if (!empty($today_agendas)): ?>
                <div class="agenda-items">
                    <?php foreach ($today_agendas as $agenda): ?>
                        <div class="agenda-item-full priority-<?php echo $agenda['priority']; ?>">
                            <div class="agenda-content">
                                <div class="agenda-task"><?php echo htmlspecialchars($agenda['task_description']); ?></div>
                                <div class="agenda-meta">
                                    <span class="agenda-priority"><?php echo ucfirst($agenda['priority']); ?> Priority</span>
                                    <span class="agenda-status"><?php echo ucfirst(str_replace('_', ' ', $agenda['status'])); ?></span>
                                </div>
                            </div>
                            <div class="agenda-actions">
                                <?php if (strpos($agenda['id'], 'default_') === false && strpos($agenda['id'], 'overall_') === false): ?>
                                    <button class="btn-status" data-id="<?php echo $agenda['id']; ?>" data-status="in_progress">Start</button>
                                    <button class="btn-status" data-id="<?php echo $agenda['id']; ?>" data-status="completed">Complete</button>
                                <?php else: ?>
                                    <span class="mandatory-task">Mandatory Task</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-agenda">
                    <p>🎯 No tasks scheduled for today</p>
                    <small>Enjoy your day or check upcoming tasks below</small>
                </div>
            <?php endif; ?>
        </div>

        <div class="agenda-card">
            <h3>📅 All Tasks</h3>
            <?php if (!empty($all_agendas)): ?>
                <div class="agenda-items">
                    <?php foreach ($all_agendas as $agenda): ?>
                        <div class="agenda-item-full priority-<?php echo $agenda['priority']; ?>">
                            <div class="agenda-content">
                                <div class="agenda-task"><?php echo htmlspecialchars($agenda['task_description']); ?></div>
                                <div class="agenda-meta">
                                    <span class="agenda-date"><?php echo isset($agenda['date']) ? date('M j, Y', strtotime($agenda['date'])) : 'Ongoing'; ?></span>
                                    <span class="agenda-priority"><?php echo ucfirst($agenda['priority']); ?> Priority</span>
                                    <span class="agenda-status"><?php echo ucfirst(str_replace('_', ' ', $agenda['status'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-agenda">
                    <p>📝 No tasks found</p>
                    <small>Your agenda will appear here when tasks are assigned</small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusButtons = document.querySelectorAll('.btn-status');

    statusButtons.forEach(button => {
        button.addEventListener('click', function() {
            const agendaId = this.getAttribute('data-id');
            const newStatus = this.getAttribute('data-status');

            // Simple call to update status
            fetch('update_agenda_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'agenda_id=' + agendaId + '&status=' + newStatus
            })
            .then(response => response.text())
            .then(data => {
                if (data === 'success') {
                    location.reload(); // Refresh to show updated status
                } else {
                    alert('Error updating task status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating task status');
            });
        });
    });
});
</script>

</body>
</html>