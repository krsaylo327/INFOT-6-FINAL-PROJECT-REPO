<?php

include('../config/db.php');

header('Content-Type: application/json');

$today = date('Y-m-d');

$sql = "SELECT
            attendance.*,
            employees.name
        FROM attendance
        JOIN employees
        ON attendance.employee_id = employees.id
        WHERE attendance.date = '$today'
        ORDER BY employees.name ASC";

$result = mysqli_query($conn,$sql);

$data = [];

while($row = mysqli_fetch_assoc($result)){
    $data[] = $row;
}

echo json_encode($data);