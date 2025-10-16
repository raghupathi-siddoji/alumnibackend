<?php
require 'vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
$secret_key = "AlumniAPI";
function generateJWT($user_id, $user_email)
{
    global $secret_key;
    $issued_at = time();
    $expiration_time =  $issued_at + 60 * 60 * 24 * 7;
    $payload = [
        'iat' => $issued_at,
        'exp' => $expiration_time,
        'uid' => $user_id,
        'email' => $user_email
    ];
    return [
        'token' => JWT::encode($payload, $secret_key, 'HS256'),
        'payload' => $payload
    ];
}
function verifyJWT($token)
{
    global $secret_key;
    try {
        $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
        return (array) $decoded;
    } catch (Exception $e) {
        return false;
    }
}