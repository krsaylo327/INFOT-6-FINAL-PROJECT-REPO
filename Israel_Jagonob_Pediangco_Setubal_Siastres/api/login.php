<?php

session_start();

include('../config/db.php');

header('Content-Type: application/json');

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if(empty($email) || empty($password)){
    http_response_code(400);

    echo json_encode([
        "error" => "Email and Password are required"
    ]);
    exit;
}

$sql = "SELECT * FROM employees WHERE email='$email'";

$result = mysqli_query($conn,$sql);

if(mysqli_num_rows($result) == 0){

    http_response_code(401);

    echo json_encode([
        "error" => "User not found"
    ]);
    exit;
}

$row = mysqli_fetch_assoc($result);

if(password_verify($password, $row['password'])){

    $_SESSION['user_id'] = $row['id'];

    echo json_encode([
        "status" => "success",
        "user" => [
            "id" => $row['id'],
            "name" => $row['name'],
            "email" => $row['email'],
            "role" => $row['role']
        ]
    ]);

}else{

    http_response_code(401);

    echo json_encode([
        "error" => "Invalid Password"
    ]);
}
?>