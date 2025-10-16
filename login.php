<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include('connection.php');
require 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

$data = json_decode(file_get_contents('php://input'), true);

$email = $data['Email'];
$password = $data['Password'];

$query = "SELECT id, name, email, password, role FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($id, $name, $db_email, $db_password, $role);
    $stmt->fetch();
 
    if ($password === $db_password) {
        $secretKey = 'AlumniAPI';
        $issuedAt  = time();
        $expire  = $issuedAt + 3600 * 24 * 30;
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'uid' => $id,
            'email' => $db_email,
            "role" => $role
        ];

        $token = JWT::encode($payload, $secretKey, 'HS256');

        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful',
            'token' => $token,
            'data' => ['id' => $id, 'name' => $name, 'email' => $db_email, 'role' => $role]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid password.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'User not found.']);
}

$stmt->close();
$conn->close();
