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

$total_employees = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM employees WHERE role != 'admin'")) {
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_employees = $result['total'] ?? 0;
    $stmt->close();
}

$total_staff = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS total FROM employees WHERE role = 'staff'")) {
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_staff = $result['total'] ?? 0;
    $stmt->close();
}

$by_position = [];
if ($stmt = $conn->prepare("SELECT position, COUNT(*) AS total FROM employees WHERE role != 'admin' GROUP BY position ORDER BY total DESC")) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $by_position[$r['position']] = (int)$r['total'];
    }
    $stmt->close();
}

$recent_hires = [];
if ($stmt = $conn->prepare("SELECT * FROM employees WHERE role != 'admin' ORDER BY id DESC LIMIT 8")) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $recent_hires[] = $r;
    }
    $stmt->close();
}

$employees = [];
if ($stmt = $conn->prepare("SELECT e.*, (SELECT date FROM attendance WHERE employee_id = e.id ORDER BY date DESC LIMIT 1) AS last_attendance FROM employees e WHERE e.role != 'admin' ORDER BY e.name ASC")) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $employees[] = $r;
    }
    $stmt->close();
}

$activePage = 'overview';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Overview - HIMAKAS</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="admin-main">
    <div class="admin-header">
        <h1>Employee Overview</h1>
        <p>Snapshot of workforce: headcount, positions, recent hires, and last attendance</p>
    </div>

    <div class="admin-cards">
        <div class="card">
            <h3>👥 Total Employees</h3>
            <p class="large-value"><?php echo $total_employees; ?></p>
            <small>Non-admin worker accounts</small>
        </div>

        <div class="card">
            <h3>🧑‍💼 Staff Count</h3>
            <p class="large-value"><?php echo $total_staff; ?></p>
            <small>Employees with `staff` role</small>
        </div>

        <div class="card">
            <h3>📊 Top Positions</h3>
            <div style="padding-top:6px;">
                <?php if (!empty($by_position)): ?>
                    <?php foreach ($by_position as $pos => $cnt): ?>
                        <div style="font-size:13px; margin-bottom:6px;"><strong><?php echo htmlspecialchars($pos); ?></strong>: <?php echo $cnt; ?></div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="large-value">No data</div>
                <?php endif; ?>
            </div>
            <small>Count by job position</small>
        </div>
    </div>

    <div class="admin-widgets">
        <div class="table-container">
            <h2>Employee Directory</h2>
            <?php if (!empty($employees)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Role</th>
                            <th>Last Attendance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $e): ?>
                            <tr>
                                <td>#<?php echo $e['id']; ?></td>
                                <td><?php echo htmlspecialchars($e['name']); ?></td>
                                <td><?php echo htmlspecialchars($e['position']); ?></td>
                                <td><?php echo htmlspecialchars($e['role']); ?></td>
                                <td><?php echo $e['last_attendance'] ? htmlspecialchars(date('M d, Y', strtotime($e['last_attendance']))) : '—'; ?></td>
                                <td>
                                    <a href="manage_employees.php?action=edit&id=<?php echo $e['id']; ?>" style="background:#3498db;color:#fff;padding:6px 10px;border-radius:4px;text-decoration:none;font-size:12px;">✏️ Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-records">No employees found.</div>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <h2>Recent Hires</h2>
            <?php if (!empty($recent_hires)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Role</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_hires as $h): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($h['name']); ?></td>
                                <td><?php echo htmlspecialchars($h['position']); ?></td>
                                <td><?php echo htmlspecialchars($h['role']); ?></td>
                                <td><?php echo htmlspecialchars($h['email']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-records">No recent hires.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
