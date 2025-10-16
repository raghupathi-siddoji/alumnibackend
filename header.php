<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

include("connection.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage === 'login.php' || $currentPage === 'Register.php') {
    return;
}

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

if (empty($authHeader)) {
    http_response_code(401);
    echo json_encode(['status' => 401, 'error' => 'Unauthorized: No token provided']);
    exit;
}
 
try {
    $secretKey = 'AlumniAPI';
    $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));

    // $_SESSION['user_id'] = $decoded->user_id;
    // $_SESSION['email'] = $decoded->email;
    
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 401, 'error' => 'Unauthorized: Invalid or expired token']);
    exit;
}
