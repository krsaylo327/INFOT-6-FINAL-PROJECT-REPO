<?php

$conn = new mysqli("db", "root", "root", "himakas_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function formatTime12Hour($time){
    if(empty($time)){
        return '';
    }

    return date('h:i:s A', strtotime($time));
}

?>