<?php
session_start();
include __DIR__ . '/../config/db.php';

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit;
}

$id = (int) $_SESSION['employee_id'];
$user = null;
$message = '';

if ($stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?")) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$oldPhoto = $user['photo'] ?? '';

if (!$user) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['save_profile'])) {
    $uploadDir = __DIR__ . '/../uploads/profile_photos';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $photoPath = $user['photo'] ?? '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowedMime = ['image/jpeg', 'image/png', 'image/gif'];
            $mimeType = mime_content_type($_FILES['photo']['tmp_name']);
            $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

            if (!in_array($mimeType, $allowedMime) || !in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $message = 'Only JPG, PNG, and GIF files are allowed for profile photos.';
            } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                $message = 'Profile photo must be 2MB or smaller.';
            } else {
                $filename = uniqid('profile_', true) . '.' . $extension;
                $target = $uploadDir . '/' . $filename;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                    $photoPath = 'uploads/profile_photos/' . $filename;
                } else {
                    $message = 'Unable to save profile photo. Please try again.';
                }
            }
        } else {
            $message = 'Error uploading profile photo.';
        }
    } else {
        $message = 'Please choose a profile photo to upload.';
    }

    if (empty($message) && !empty($photoPath) && $photoPath !== ($user['photo'] ?? '')) {
        $colCheck = $conn->query("SHOW COLUMNS FROM employees LIKE 'photo'");
        if (!($colCheck && $colCheck->num_rows > 0)) {
            $conn->query("ALTER TABLE employees ADD COLUMN photo VARCHAR(255) NULL AFTER position");
        }

        if ($stmtPhoto = $conn->prepare("UPDATE employees SET photo = ? WHERE id = ?")) {
            $stmtPhoto->bind_param("si", $photoPath, $id);
            if ($stmtPhoto->execute()) {
                if (!empty($oldPhoto) && strpos($oldPhoto, 'uploads/profile_photos/') === 0) {
                    $oldPath = __DIR__ . '/../' . $oldPhoto;
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $message = 'Profile photo updated successfully.';
            } else {
                $message = 'Unable to save profile photo. Please try again.';
            }
            $stmtPhoto->close();
        }

        if (empty($message)) {
            $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - HIMAKAS</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<?php include __DIR__ . '/employee_sidebar.php'; ?>

<div class="main">
    <div class="header-section">
        <h1>My Profile</h1>
        <p class="welcome-text"><?php echo htmlspecialchars($user['name']); ?> • Update your profile information</p>
    </div>

    <div class="profile-summary profile-center">
        <div class="photo-action-row">
            <label for="photo" class="profile-avatar" style="cursor:pointer; text-align:center;">
                <img src="<?php echo htmlspecialchars(!empty($user['photo']) ? '../' . $user['photo'] : 'https://via.placeholder.com/150/667eea/ffffff?text=Avatar'); ?>" alt="Profile Photo">
            </label>
            <label for="photo" class="edit-photo-action" style="cursor:pointer;">
                <img src="../images/uploadPhoto.png" alt="Edit Photo Icon">
                <span>Edit Photo</span>
            </label>
            <?php if ($message): ?>
                <div class="success-message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
        </div>

        <div style="margin-top:8px; font-size:14px; color:#667eea;">Click the avatar or edit button to choose a photo</div>
        <div class="profile-details">
            <div class="profile-item"><span class="profile-key">Full name</span><span class="profile-value"><?php echo htmlspecialchars($user['name']); ?></span></div>
            <div class="profile-item"><span class="profile-key">Email</span><span class="profile-value"><?php echo htmlspecialchars($user['email']); ?></span></div>
            <div class="profile-item"><span class="profile-key">Position</span><span class="profile-value"><?php echo htmlspecialchars($user['position']); ?></span></div>
            <div class="profile-item"><span class="profile-key">Role</span><span class="profile-value"><?php echo htmlspecialchars($user['role']); ?></span></div>
        </div>
    </div>

    <form method="POST" action="profile_settings.php" class="profile-form" enctype="multipart/form-data">
        <input type="hidden" name="save_profile" value="1">
        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/gif" style="display:none;">
    </form>
    <script>
        const employeePhotoInput = document.getElementById('photo');
        const profileForm = document.querySelector('.profile-form');
        if (employeePhotoInput && profileForm) {
            employeePhotoInput.addEventListener('change', function() {
                if (this.files.length) {
                    profileForm.submit();
                }
            });
        }
    </script>
</div>

</body>
</html>
