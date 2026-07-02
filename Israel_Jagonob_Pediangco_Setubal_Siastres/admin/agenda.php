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

$today = date('Y-m-d');

$completed_tasks = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM agendas WHERE status = 'completed'")) {
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $completed_tasks = $result['total'] ?? 0;
    $stmt->close();
}

$pending_tasks = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM agendas WHERE status != 'completed'")) {
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $pending_tasks = $result['total'] ?? 0;
    $stmt->close();
}

$high_priority_count = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM agendas WHERE priority = 'high'")) {
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $high_priority_count = $result['total'] ?? 0;
    $stmt->close();
}

$completion_rate = ($completed_tasks + $pending_tasks) > 0 ? round(($completed_tasks / ($completed_tasks + $pending_tasks)) * 100) : 0;

$agendaItems = [];
if ($stmt = $conn->prepare("SELECT a.*, e.name FROM agendas a JOIN employees e ON a.employee_id = e.id ORDER BY a.priority DESC, a.created_at DESC LIMIT 20")) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $agendaItems[] = $row;
    }
    $stmt->close();
}

$activePage = 'agenda';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Agenda - HIMAKAS</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="admin-main">
    <div class="admin-header">
        <h1>Agenda Management</h1>
        <p class="welcome-text">Admin overview of today’s workforce tasks</p>
    </div>

    <div class="admin-cards">
        <div class="card">
            <h3>✅ Completed</h3>
            <p class="large-value"><?php echo number_format($completed_tasks); ?></p>
            <small>Tasks finished</small>
        </div>

        <div class="card">
            <h3>⏳ Pending</h3>
            <p class="large-value"><?php echo number_format($pending_tasks); ?></p>
            <small>Tasks remaining</small>
        </div>

        <div class="card">
            <h3>🎯 Completion Rate</h3>
            <p class="large-value"><?php echo $completion_rate; ?>%</p>
            <small>Overall progress</small>
        </div>

        <div class="card">
            <h3>⚡ High Priority</h3>
            <p class="large-value"><?php echo number_format($high_priority_count); ?></p>
            <small>Urgent tasks</small>
        </div>
    </div>

    <div class="table-container">
        <h2>Today’s Agenda</h2>
        <?php if (!empty($agendaItems)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Task</th>
                        <th>Priority</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agendaItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['task_description']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($item['priority'])); ?></td>
                            <td><?php echo htmlspecialchars($item['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-records">No agenda tasks scheduled for today.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
