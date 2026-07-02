<?php
session_start();
include __DIR__ . '/../config/db.php';

if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit;
}

$id = $_SESSION['employee_id'];
date_default_timezone_set('Asia/Manila');
$date = date("Y-m-d");
$time = date("H:i:s");
$current_hour = date("H");

// Get today's attendance record
$stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
$stmt->bind_param("is", $id, $date);
$stmt->execute();
$result = $stmt->get_result();
$record = $result->fetch_assoc();
$stmt->close();

if ($record) {
    $record_id = $record['id'];

    // Determine session based on current time
    if ($current_hour < 12) {
        // Morning session timeout
        $session = 'morning';
        $time_in_column = 'morning_time_in';
        $time_out_column = 'morning_time_out';
        $status_column = 'morning_status';
        $required_hours = 4;
    } else {
        // Afternoon session timeout
        $session = 'afternoon';
        $time_in_column = 'afternoon_time_in';
        $time_out_column = 'afternoon_time_out';
        $status_column = 'afternoon_status';
        $required_hours = 4;
    }

    // Check if already timed out for this session
    if (!empty($record[$time_out_column])) {
        // Already timed out for this session, redirect
        header("Location: dashboard.php");
        exit;
    }

    // Check if timed in for this session
    if (empty($record[$time_in_column])) {
        // Haven't timed in for this session, redirect
        header("Location: dashboard.php");
        exit;
    }

    // Update time out
    $stmt = $conn->prepare("UPDATE attendance SET $time_out_column = ? WHERE id = ?");
    $stmt->bind_param("si", $time, $record_id);
    $stmt->execute();
    $stmt->close();

    // Calculate hours worked and determine status
    $hours_worked = (strtotime($time) - strtotime($record[$time_in_column])) / 3600;
    $session_status = ($hours_worked >= $required_hours) ? 'complete' : 'incomplete';

    // Update session status
    $stmt = $conn->prepare("UPDATE attendance SET $status_column = ? WHERE id = ?");
    $stmt->bind_param("si", $session_status, $record_id);
    $stmt->execute();
    $stmt->close();

    // Update daily status when both sessions are complete and both time-outs exist
    $stmt = $conn->prepare("SELECT morning_time_in, morning_time_out, afternoon_time_in, afternoon_time_out, morning_status, afternoon_status FROM attendance WHERE id = ?");
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $updated_record = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!empty($updated_record['morning_time_out']) && !empty($updated_record['afternoon_time_out'])) {
        $morning_hours = (strtotime($updated_record['morning_time_out']) - strtotime($updated_record['morning_time_in'])) / 3600;
        $afternoon_hours = (strtotime($updated_record['afternoon_time_out']) - strtotime($updated_record['afternoon_time_in'])) / 3600;
        $total_hours = $morning_hours + $afternoon_hours;

        if ($updated_record['morning_status'] === 'complete' && $updated_record['afternoon_status'] === 'complete') {
            // Grace period logic: if worked less than 9 hours, consider it a full 8-hour day
            // If worked 9+ hours, the excess hours count as overtime (1-hour threshold)
            if ($total_hours < 9) {
                $daily_status = 'full_day';
            } else {
                $daily_status = 'overtime';
            }
        } else {
            $daily_status = 'incomplete';
        }

        $stmt = $conn->prepare("UPDATE attendance SET daily_status = ? WHERE id = ?");
        $stmt->bind_param("si", $daily_status, $record_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: dashboard.php");
exit;
?>