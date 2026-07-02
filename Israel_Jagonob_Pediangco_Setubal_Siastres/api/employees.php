<?php

include('../config/db.php');

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method == "GET") {

    $sql = "SELECT id,name,email,role,position,salary
            FROM employees";

    $result = mysqli_query($conn,$sql);

    $employees = [];

    while($row = mysqli_fetch_assoc($result)){
        $employees[] = $row;
    }

    echo json_encode($employees);

}

?>