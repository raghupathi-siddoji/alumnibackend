<?php

$host = 'localhost';      
$user = 'root';      
$pass = ''; 
$db   = 'alumni';
$port = '3306'; 

$conn = new mysqli($host, $user, $pass, $db, port: $port);  
if ($conn->connect_error) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'message' => 'Connection failed: ' . $conn->connect_error
    ]);
    exit;
}
?>