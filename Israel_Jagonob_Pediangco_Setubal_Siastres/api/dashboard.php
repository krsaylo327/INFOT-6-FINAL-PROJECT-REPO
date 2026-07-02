<?php

include('../config/db.php');

header('Content-Type: application/json');

/* Total Employees */
$employees = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM employees")
);

/* Total Attendance */
$today = date('Y-m-d');

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM attendance WHERE date = ?"
);

$stmt->bind_param("s", $today);
$stmt->execute();

$attendance = $stmt->get_result()->fetch_assoc();

$stmt->close();

/* Late Arrivals */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM attendance
    WHERE date = ?
    AND (
        (morning_time_in IS NOT NULL AND morning_time_in > '08:00:00')
        OR
        (afternoon_time_in IS NOT NULL AND afternoon_time_in > '13:00:00')
    )
");

$stmt->bind_param("s", $today);
$stmt->execute();

$late = $stmt->get_result()->fetch_assoc();

$stmt->close();

/* Total Payroll */
$payroll = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT SUM(total) AS total_payroll FROM payroll")
);

/* Weather */
$weatherJson = file_get_contents("http://localhost/CAPSTONE/api/weather.php");
$weather = json_decode($weatherJson, true);

echo json_encode([
    "totalEmployees" => $employees['total'],
    "totalAttendance" => $attendance['total'],
    "lateArrivals" => $late['total'],
    "totalPayroll" => $payroll['total_payroll'],
    "city" => $weather['name'],
    "weather" => $weather['weather'][0]['main'],
    "temperature" => $weather['main']['temp']
]);

?>