<?php

include('../config/db.php');

header('Content-Type: application/json');

$sql = "SELECT * FROM payroll";

$result = mysqli_query($conn, $sql);

$data = [];

while($row = mysqli_fetch_assoc($result)){
    $data[] = $row;
}

echo json_encode($data);

?>