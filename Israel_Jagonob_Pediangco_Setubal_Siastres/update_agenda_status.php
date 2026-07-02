<?php
session_start();
include __DIR__ . '/config/db.php';

if(!isset($_SESSION['employee_id'])){
    echo "error";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agenda_id']) && isset($_POST['status'])) {
    $agenda_id = (int)$_POST['agenda_id'];
    $status = $_POST['status'];
    $employee_id = $_SESSION['employee_id'];

    // Validate status
    $valid_statuses = ['pending', 'in_progress', 'completed'];
    if (!in_array($status, $valid_statuses)) {
        echo "error";
        exit;
    }

    // Update the agenda status (only if it belongs to the current user)
    $stmt = $conn->prepare("UPDATE agendas SET status = ? WHERE id = ? AND employee_id = ?");
    if (!$stmt) {
        echo "error";
        exit;
    }

    $stmt->bind_param("sii", $status, $agenda_id, $employee_id);

    if ($stmt->execute() && $stmt->affected_rows >= 0) {
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
} else {
    echo "error";
}

$conn->close();
?>