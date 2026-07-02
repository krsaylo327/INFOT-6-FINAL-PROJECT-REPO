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
$action = $_GET['action'] ?? '';

// Ensure `disabled` column exists so accounts can be disabled instead of deleted
$colCheck = $conn->query("SHOW COLUMNS FROM employees LIKE 'disabled'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE employees ADD COLUMN disabled TINYINT(1) NOT NULL DEFAULT 0");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['post_action'] ?? '';
    
    if ($post_action === 'add') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $position = trim($_POST['position']);
        $role = trim($_POST['role']) ?: 'employee';
        
        if (!empty($name) && !empty($email) && !empty($password) && !empty($position)) {
            if ($stmt = $conn->prepare("INSERT INTO employees (name, email, password, position, role) VALUES (?, ?, ?, ?, ?)")) {
                $stmt->bind_param("sssss", $name, $email, $password, $position, $role);
                if ($stmt->execute()) {
                    $message = "✅ Employee added successfully!";
                } else {
                    $message = "❌ Error adding employee.";
                }
                $stmt->close();
            }
        } else {
            $message = "❌ All fields are required.";
        }
    } elseif ($post_action === 'edit') {
        $emp_id = (int) $_POST['emp_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $position = trim($_POST['position']);
        $role = trim($_POST['role']) ?: 'employee';
        
        if (!empty($name) && !empty($email) && !empty($position)) {
            if ($stmt = $conn->prepare("UPDATE employees SET name = ?, email = ?, position = ?, role = ? WHERE id = ?")) {
                $stmt->bind_param("ssssi", $name, $email, $position, $role, $emp_id);
                if ($stmt->execute()) {
                    $message = "✅ Employee updated successfully!";
                } else {
                    $message = "❌ Error updating employee.";
                }
                $stmt->close();
            }
        } else {
            $message = "❌ All fields are required.";
        }
    } elseif ($post_action === 'disable') {
        $emp_id = (int) $_POST['emp_id'];
        if ($stmt = $conn->prepare("UPDATE employees SET disabled = 1 WHERE id = ?")) {
            $stmt->bind_param("i", $emp_id);
            if ($stmt->execute()) {
                $message = "✅ Employee disabled (cannot log in).";
            } else {
                $message = "❌ Error disabling employee.";
            }
            $stmt->close();
        }
    } elseif ($post_action === 'enable') {
        $emp_id = (int) $_POST['emp_id'];
        if ($stmt = $conn->prepare("UPDATE employees SET disabled = 0 WHERE id = ?")) {
            $stmt->bind_param("i", $emp_id);
            if ($stmt->execute()) {
                $message = "✅ Employee enabled (login restored).";
            } else {
                $message = "❌ Error enabling employee.";
            }
            $stmt->close();
        }
    }
}

$employees = [];
if ($stmt = $conn->prepare("SELECT * FROM employees WHERE role != 'admin' ORDER BY name ASC")) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    $stmt->close();
}

$edit_employee = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $emp_id = (int) $_GET['id'];
    if ($stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?")) {
        $stmt->bind_param("i", $emp_id);
        $stmt->execute();
        $edit_employee = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$activePage = 'employees';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - HIMAKAS</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="admin-main">
    <div class="admin-header">
        <h1>Manage Employees</h1>
        <p class="welcome-text">Add, edit, or delete worker accounts • Manage positions, rates, and project sites</p>
    </div>

    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 14px 16px; border-radius: 8px; margin-bottom: 20px; border-left: 5px solid #28a745;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="admin-cards">
        <div class="card">
            <h3>👥 Total Employees</h3>
            <p class="large-value" id="employeeCount">0</p>
            <small>Active worker accounts</small>
        </div>
    </div>

    <div class="table-container">
        <h2><?php echo $edit_employee ? 'Edit Employee' : 'Add New Employee'; ?></h2>
        <form method="POST" style="background: #f8fafb; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
            <input type="hidden" name="post_action" value="<?php echo $edit_employee ? 'edit' : 'add'; ?>">
            <?php if ($edit_employee): ?>
                <input type="hidden" name="emp_id" value="<?php echo $edit_employee['id']; ?>">
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?php echo $edit_employee['name'] ?? ''; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo $edit_employee['email'] ?? ''; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <?php if (!$edit_employee): ?>
                <div>
                    <label>Password</label>
                    <input type="password" name="password" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <?php endif; ?>
                <div>
                    <label>Position</label>
                    <select name="position" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; background: #fff;">
                        <?php foreach (['Foreman','Driver','Laborer','Skilled','Electrician','Mason','Plumber','Supervisor','Project Engineer','Accounting','Warehouse Supervisor','Lliason Officer','Master Electrician','Secretary','Financial Manager'] as $pos): ?>
                            <option value="<?php echo $pos; ?>" <?php echo ($edit_employee['position'] ?? '') === $pos ? 'selected' : ''; ?>><?php echo $pos; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Role</label>
                    <select name="role" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="employee" <?php echo ($edit_employee['role'] ?? 'employee') === 'employee' ? 'selected' : ''; ?>>Employee</option>
                        <option value="staff" <?php echo ($edit_employee['role'] ?? '') === 'staff' ? 'selected' : ''; ?>>Staff</option>
                    </select>
                </div>
            </div>
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" style="background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    <?php echo $edit_employee ? '✅ Update Employee' : '➕ Add Employee'; ?>
                </button>
                <?php if ($edit_employee): ?>
                <a href="manage_employees.php" style="background: #95a5a6; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="table-container">
        <h2>Employee Directory</h2>
        <?php if (!empty($employees)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Position</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="employeeTableBody">
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-records">No employees found.</div>
        <?php endif; ?>
    </div>
</div>

<script>
fetch("../api/employees.php")
    .then(response => response.json())
    .then(data => {

        let rows = "";

        data.forEach(emp => {

            if (emp.role === "admin") {
                return;
            }

            rows += `
                <tr>
                    <td>#${emp.id}</td>
                    <td>${emp.name}</td>
                    <td>${emp.email}</td>
                    <td>${emp.position}</td>
                    <td>${emp.role}</td>
                    <td>Active</td>
                    <td>
                        Edit | Disable
                    </td>
                </tr>
            `;

        });

        document.getElementById("employeeTableBody").innerHTML = rows;
        document.getElementById("employeeCount").textContent = data.filter(emp => emp.role !== "admin").length;

    })
    .catch(error => {
        console.error(error);
    });
</script>
</body>
</html>
