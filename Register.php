<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include('connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['Email'] ?? '';
    $password = $_POST['Password'] ?? '';
    $name = $_POST['Name'] ?? '';
    $usn = $_POST['USN'] ?? '';
    $yop = $_POST['YOP'] ?? '';
    $department = $_POST['Department'] ?? '';
    $gender = $_POST['Gender'] ?? '';
    $mobile_no = $_POST['Mobile_No'] ?? '';

    if (
        !$email || !$password || !$name || !$usn ||
        !$yop || !$department || !$gender || !$mobile_no
    ) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    if (!preg_match('/^\d{10}$/', $mobile_no)) {
        echo json_encode(['success' => false, 'message' => 'Invalid mobile number. Must be 10 digits.']);
        exit;
    }

    if (!isset($_FILES['Profile']) || $_FILES['Profile']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Profile picture is required.']);
        exit;
    }

    $targetDir = "uploads/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

    $fileTmp = $_FILES['Profile']['tmp_name'];
    $fileName = basename($_FILES['Profile']['name']);
    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
    $newFileName = uniqid('profile_', true) . '.' . $fileExt;
    $targetFile = $targetDir . $newFileName;

    if (!move_uploaded_file($fileTmp, $targetFile)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload profile picture.']);
        exit;
    }

    $adminApproval = 'Pending';
    $role = 'User';

    $stmt = $conn->prepare("INSERT INTO users 
        (email, password, name, usn, yop, department, profile, gender, mobile_no, admin_approval, role)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare failed.']);
        exit;
    }

    $stmt->bind_param(
        "sssssssssss", 
        $email, 
        $password, 
        $name, 
        $usn, 
        $yop, 
        $department, 
        $targetFile, 
        $gender, 
        $mobile_no, 
        $adminApproval, 
        $role
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Registration successful!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: Unable to register.']);
    }

    $stmt->close();
    $conn->close();

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
