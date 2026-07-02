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

$message = '';
$action = $_GET['action'] ?? '';
$edit_employee = null;

// Ensure `disabled` column exists so accounts can be disabled instead of deleted
$colCheck = $conn->query("SHOW COLUMNS FROM employees LIKE 'disabled'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE employees ADD COLUMN disabled TINYINT(1) NOT NULL DEFAULT 0");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['post_action'] ?? '';

    if ($post_action === 'create') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $position = trim($_POST['position']);
        $password = trim($_POST['password']);

        if (empty($name) || empty($email) || empty($position) || empty($password)) {
            $message = 'Name, email, position, and password are required to create an employee account.';
        } else {
            if ($check = $conn->prepare("SELECT id FROM employees WHERE email = ?")) {
                $check->bind_param("s", $email);
                $check->execute();
                $res = $check->get_result()->fetch_assoc();
                $check->close();
                if ($res) {
                    $message = 'Email address is already registered.';
                }
            }

            if (empty($message)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                if ($stmt = $conn->prepare("INSERT INTO employees (name, email, position, password, role) VALUES (?, ?, ?, ?, 'employee')")) {
                    $stmt->bind_param("ssss", $name, $email, $position, $hashed);
                    if ($stmt->execute()) {
                        $message = 'Employee account created successfully.';
                    } else {
                        $message = 'Unable to create employee account. Please try again.';
                    }
                    $stmt->close();
                }
            }
        }
    } elseif ($post_action === 'save') {
        $emp_id = (int) $_POST['emp_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $position = trim($_POST['position']);

        if (empty($name) || empty($email) || empty($position)) {
            $message = 'Name, email, and position are required.';
        } else {
            if ($check = $conn->prepare("SELECT id FROM employees WHERE email = ? AND id != ? AND role = 'employee'")) {
                $check->bind_param("si", $email, $emp_id);
                $check->execute();
                $res = $check->get_result()->fetch_assoc();
                $check->close();
                if ($res) {
                    $message = 'Email address is already in use by another employee.';
                }
            }

            if (empty($message)) {
                if ($stmt = $conn->prepare("UPDATE employees SET name = ?, email = ?, position = ? WHERE id = ? AND role = 'employee'")) {
                    $stmt->bind_param("sssi", $name, $email, $position, $emp_id);
                    if ($stmt->execute()) {
                        $message = 'Employee details updated successfully.';
                    } else {
                        $message = 'Unable to update employee. Please try again.';
                    }
                    $stmt->close();
                }
            }
        }
    } elseif ($post_action === 'disable') {
        $emp_id = (int) $_POST['emp_id'];
        if ($stmt = $conn->prepare("UPDATE employees SET disabled = 1 WHERE id = ? AND role = 'employee'")) {
            $stmt->bind_param("i", $emp_id);
            if ($stmt->execute()) {
                $message = 'Employee account disabled.';
            } else {
                $message = 'Unable to disable employee. Please try again.';
            }
            $stmt->close();
        }
    } elseif ($post_action === 'enable') {
        $emp_id = (int) $_POST['emp_id'];
        if ($stmt = $conn->prepare("UPDATE employees SET disabled = 0 WHERE id = ? AND role = 'employee'")) {
            $stmt->bind_param("i", $emp_id);
            if ($stmt->execute()) {
                $message = 'Employee account enabled.';
            } else {
                $message = 'Unable to enable employee. Please try again.';
            }
            $stmt->close();
        }
    }
}

if ($action === 'edit' && isset($_GET['id'])) {
    $emp_id = (int) $_GET['id'];
    if ($stmt = $conn->prepare("SELECT * FROM employees WHERE id = ? AND role = 'employee'")) {
        $stmt->bind_param("i", $emp_id);
        $stmt->execute();
        $edit_employee = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$employees = [];
if ($stmt = $conn->prepare("SELECT * FROM employees WHERE role = 'employee' ORDER BY name ASC")) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees - HIMAKAS Staff</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<?php include __DIR__ . '/staff_sidebar.php'; ?>

<div class="staff-main">
    <div class="staff-header" style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div>
            <h1>Employee Management</h1>
            <p>View and manage only employee accounts. Total: <?php echo count($employees); ?></p>
        </div>
        <button type="button" id="toggle-create-employee" style="background:#2ecc71;color:#fff;border:none;padding:10px 18px;border-radius:8px;cursor:pointer;font-weight:600;">Create Employee</button>
    </div>

    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 14px 16px; border-radius: 8px; margin-bottom: 20px; border-left: 5px solid #28a745;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div id="create-employee-panel" class="staff-card" style="margin-bottom:20px; display:none;">
        <h2>Create New Employee</h2>
        <form method="POST" action="staff_employees.php" style="display:grid; gap:16px;">
            <input type="hidden" name="post_action" value="create">
            <div>
                <label>Name</label>
                <input type="text" name="name" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
            </div>
            <div>
                <label>Position</label>
                <select name="position" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; background:#fff;">
                    <option value="Foreman">Foreman</option>
                    <option value="Driver">Driver</option>
                    <option value="Laborer">Laborer</option>
                    <option value="Skilled">Skilled</option>
                    <option value="Electrician">Electrician</option>
                    <option value="Mason">Mason</option>
                    <option value="Plumber">Plumber</option>
                </select>
            </div>
            <div>
                <label>Password</label>
                <input type="password" name="password" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
            </div>
            <button type="submit" class="submit-btn" style="background:#2ecc71;">Create Employee</button>
        </form>
    </div>

    <?php if ($edit_employee): ?>
        <div class="staff-card" style="margin-bottom:20px;">
            <h2>Edit Employee</h2>
            <form method="POST" action="staff_employees.php" style="display:grid; gap:16px;">
                <input type="hidden" name="post_action" value="save">
                <input type="hidden" name="emp_id" value="<?php echo $edit_employee['id']; ?>">
                <div>
                    <label>Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($edit_employee['name']); ?>" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($edit_employee['email']); ?>" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;">
                </div>
                <div>
                    <label>Position</label>
                    <select name="position" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; background:#fff;">
                        <option value="Foreman" <?php echo htmlspecialchars($edit_employee['position']) === 'Foreman' ? 'selected' : ''; ?>>Foreman</option>
                        <option value="Driver" <?php echo htmlspecialchars($edit_employee['position']) === 'Driver' ? 'selected' : ''; ?>>Driver</option>
                        <option value="Laborer" <?php echo htmlspecialchars($edit_employee['position']) === 'Laborer' ? 'selected' : ''; ?>>Laborer</option>
                        <option value="Skilled" <?php echo htmlspecialchars($edit_employee['position']) === 'Skilled' ? 'selected' : ''; ?>>Skilled</option>
                        <option value="Electrician" <?php echo htmlspecialchars($edit_employee['position']) === 'Electrician' ? 'selected' : ''; ?>>Electrician</option>
                        <option value="Mason" <?php echo htmlspecialchars($edit_employee['position']) === 'Mason' ? 'selected' : ''; ?>>Mason</option>
                        <option value="Plumber" <?php echo htmlspecialchars($edit_employee['position']) === 'Plumber' ? 'selected' : ''; ?>>Plumber</option>
                    </select>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button type="submit" class="submit-btn" style="background:#3498db;">Save Employee</button>
                    <a href="staff_employees.php" style="background:#95a5a6;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="staff-table-container">
        <?php if (!empty($employees)): ?>
            <table class="staff-table">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Position</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td>#<?php echo $emp['id']; ?></td>
                            <td><?php echo htmlspecialchars($emp['name']); ?></td>
                            <td><?php echo htmlspecialchars($emp['email']); ?></td>
                            <td><?php echo htmlspecialchars($emp['position']); ?></td>
                            <td>
                                <?php if (!empty($emp['disabled'])): ?>
                                    <span style="background:#fdecea;color:#c0392b;padding:4px 8px;border-radius:4px;font-size:12px;">Disabled</span>
                                <?php else: ?>
                                    <span style="background:#e8f4f8;color:#0277bd;padding:4px 8px;border-radius:4px;font-size:12px;">Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="staff_employees.php?action=edit&id=<?php echo $emp['id']; ?>" style="margin-right:8px;background:#3498db;color:#fff;padding:6px 10px;border-radius:4px;text-decoration:none;font-size:12px;">Edit</a>
                                <?php if (!empty($emp['disabled'])): ?>
                                    <form method="POST" action="staff_employees.php" style="display:inline;">
                                        <input type="hidden" name="post_action" value="enable">
                                        <input type="hidden" name="emp_id" value="<?php echo $emp['id']; ?>">
                                        <button type="submit" style="background:#27ae60;color:#fff;padding:6px 10px;border:none;border-radius:4px;font-size:12px;cursor:pointer;">Enable</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="staff_employees.php" style="display:inline;" onsubmit="return confirm('Disable this employee?');">
                                        <input type="hidden" name="post_action" value="disable">
                                        <input type="hidden" name="emp_id" value="<?php echo $emp['id']; ?>">
                                        <button type="submit" style="background:#e74c3c;color:#fff;padding:6px 10px;border:none;border-radius:4px;font-size:12px;cursor:pointer;">Disable</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #7f8c8d; padding: 30px;">No employee records found.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    const createPanel = document.getElementById('create-employee-panel');
    const createButton = document.getElementById('toggle-create-employee');
    if (createButton && createPanel) {
        createButton.addEventListener('click', function() {
            const isVisible = createPanel.style.display === 'block';
            createPanel.style.display = isVisible ? 'none' : 'block';
            createButton.textContent = isVisible ? 'Create Employee' : 'Hide Form';
        });
    }
</script>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['post_action'] ?? '') === 'create'): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const createPanel = document.getElementById('create-employee-panel');
        const createButton = document.getElementById('toggle-create-employee');
        if (createPanel) {
            createPanel.style.display = 'block';
        }
        if (createButton) {
            createButton.textContent = 'Hide Form';
        }
    });
</script>
<?php endif; ?>

</body>
</html>




