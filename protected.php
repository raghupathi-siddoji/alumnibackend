<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'vendor/autoload.php';
include('jwt_helper.php');

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

$headers = apache_request_headers();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    echo json_encode(['status' => 'error', 'message' => 'Authorization header not found or invalid']);
    exit;
}

$jwt = $matches[1];
$secret_key = "Alumni";

try {
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));

    echo json_encode([
        'status' => 'success',
        'message' => 'Token is valid',
        'data' => $decoded
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Token is invalid or expired',
        'error' => $e->getMessage()
    ]);
}
