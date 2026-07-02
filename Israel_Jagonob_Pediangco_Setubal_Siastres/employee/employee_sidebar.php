<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$sidebar_items = [
    'dashboard.php' => ['icon' => '📊', 'label' => 'Dashboard'],
    'profile_settings.php' => ['icon' => '👤', 'label' => 'Profile'],
    'attendance.php' => ['icon' => '📅', 'label' => 'Attendance'],
    'agenda.php' => ['icon' => '🏗️', 'label' => 'Agenda'],
    'payroll.php' => ['icon' => '💰', 'label' => 'Payroll'],
];

$clockAction = 'timein';
$clockLabel = 'Clock In';
$clockClass = 'timein';

if (isset($_SESSION['employee_id']) && isset($conn)) {
    $today = date('Y-m-d');
    $id = (int) $_SESSION['employee_id'];
    $currentHour = (int) date('H');
    $timeInColumn = $currentHour < 12 ? 'morning_time_in' : 'afternoon_time_in';
    $timeOutColumn = $currentHour < 12 ? 'morning_time_out' : 'afternoon_time_out';

    if ($stmt = $conn->prepare('SELECT ' . $timeInColumn . ', ' . $timeOutColumn . ' FROM attendance WHERE employee_id = ? AND date = ?')) {
        $stmt->bind_param('is', $id, $today);
        $stmt->execute();
        $attendanceRecord = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($attendanceRecord) {
            if (!empty($attendanceRecord[$timeInColumn]) && empty($attendanceRecord[$timeOutColumn])) {
                $clockAction = 'timeout';
                $clockLabel = 'Clock Out';
                $clockClass = 'timeout';
            }
        }
    }
}
?>
<div class="sidebar">
    <h2>HIMAKAS</h2>
    <?php foreach ($sidebar_items as $href => $item): ?>
        <a href="<?php echo htmlspecialchars($href); ?>"<?php echo $current_page === $href ? ' class="active"' : ''; ?>>
            <span class="icon"><?php echo htmlspecialchars($item['icon']); ?></span>
            <span class="link-text"><?php echo htmlspecialchars($item['label']); ?></span>
        </a>
    <?php endforeach; ?>
    <a href="../logout.php">
        <span class="icon">🚪</span>
        <span class="link-text">Logout</span>
    </a>

    <div class="clock-section">
        <button id="clockToggleBtn" type="button" class="clock-btn <?php echo $clockClass; ?>" data-state="<?php echo $clockAction; ?>" aria-label="<?php echo htmlspecialchars($clockLabel); ?>">
            <img class="clock-image" src="../images/<?php echo $clockAction === 'timeout' ? 'clockOut' : 'clockIn'; ?>.png" alt="<?php echo htmlspecialchars($clockLabel); ?>">
        </button>
        <form id="clockToggleForm" action="<?php echo $clockAction === 'timeout' ? 'timeout.php' : 'timein.php'; ?>" method="POST"></form>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('clockToggleBtn');
        var form = document.getElementById('clockToggleForm');
        var img = btn ? btn.querySelector('.clock-image') : null;
        if (!btn || !form || !img) return;

        btn.addEventListener('click', function() {
            var state = btn.dataset.state;
            if (state === 'timein') {
                form.action = 'timein.php';
                btn.dataset.state = 'timeout';
                btn.classList.remove('timein');
                btn.classList.add('timeout');
                img.src = '../images/clockOut.png';
                img.alt = 'Clock Out';
                btn.setAttribute('aria-label', 'Clock Out');
            } else {
                form.action = 'timeout.php';
                btn.dataset.state = 'timein';
                btn.classList.remove('timeout');
                btn.classList.add('timein');
                img.src = '../images/clockIn.png';
                img.alt = 'Clock In';
                btn.setAttribute('aria-label', 'Clock In');
            }
            form.submit();
        });
    });
</script>

