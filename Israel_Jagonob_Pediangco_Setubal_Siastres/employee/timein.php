<?php
session_start();
include __DIR__ . '/../config/db.php';

$id = $_SESSION['employee_id'];
date_default_timezone_set('Asia/Manila');
$date = date("Y-m-d");
$time = date("H:i:s");
$current_hour = date("H");

// Check if already timed in today
$check = $conn->query("SELECT id FROM attendance WHERE employee_id=$id AND date='$date'");
$existing_record = $check->fetch_assoc();

if($check->num_rows == 0){
    // No record exists, create new one
    $conn->query("INSERT INTO attendance (employee_id, date) VALUES ($id, '$date')");
    $record_id = $conn->insert_id;
} else {
    $record_id = $existing_record['id'];
}

// Determine session and status
if ($current_hour < 12) {
    // Morning session
    $session = 'morning';
    $time_in_status = (strtotime($time) > strtotime('08:00:00')) ? 'late' : 'on_time';
    $time_column = 'morning_time_in';
} else {
    // Afternoon session
    $session = 'afternoon';
    $time_in_status = (strtotime($time) > strtotime('13:00:00')) ? 'late' : 'on_time';
    $time_column = 'afternoon_time_in';
}

// Check if already timed in for this session
$session_check = $conn->query("SELECT $time_column FROM attendance WHERE id=$record_id");
$session_data = $session_check->fetch_assoc();

if (!empty($session_data[$time_column])) {
    // Already timed in for this session, redirect
    header("Location: dashboard.php");
    exit;
}

// Update the record with time in (do not write to non-existent column)
$stmt = $conn->prepare("UPDATE attendance SET $time_column = ? WHERE id = ?");
$stmt->bind_param("si", $time, $record_id);
$stmt->execute();
$stmt->close();

header("Location: dashboard.php");
?>