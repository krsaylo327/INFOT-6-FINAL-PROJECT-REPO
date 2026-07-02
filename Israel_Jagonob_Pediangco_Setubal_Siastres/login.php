<?php
session_start();
include __DIR__ . '/config/db.php';

$error = "";
if(isset($_POST['login'])){
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if(empty($email) || empty($password)){
        $error = "Please enter both email and password.";
    } else {
        if ($stmt = $conn->prepare("SELECT id, password, role, disabled FROM employees WHERE email = ?")) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user) {
                $stored = $user['password'] ?? '';
                $passwordOk = false;

                if (!empty($stored) && password_verify($password, $stored)) {
                    $passwordOk = true;
                } elseif ($password === $stored) {
                    $passwordOk = true;
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    if ($upd = $conn->prepare("UPDATE employees SET password = ? WHERE id = ?")) {
                        $upd->bind_param("si", $newHash, $user['id']);
                        $upd->execute();
                        $upd->close();
                    }
                }

                if ($passwordOk) {
                    if (!empty($user['disabled'])) {
                        $error = "This account has been disabled. Contact an administrator.";
                    } else {
                        $_SESSION['employee_id'] = $user['id'];
                        $_SESSION['user_role'] = $user['role'] ?? 'employee';
                        if ($user['role'] === 'admin') {
                            header("Location: admin/dashboard.php");
                        } elseif ($user['role'] === 'staff') {
                            header("Location: staff/staff_dashboard.php");
                        } else {
                            header("Location: employee/dashboard.php");
                        }
                        exit;
                    }
                }
            }

            $error = "Invalid email or password.";
        } else {
            $error = "Login failed. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login - HIMAKAS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <h1>HIMAKAS</h1>
                <p>Construction Management System</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <button type="submit" name="login" class="submit-btn">Sign In</button>
            </form>

            <div class="login-footer">
                &copy; 2026 HIMAKAS. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>