<?php

include('header.php');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    $stmt = $conn->prepare("SELECT 
        id,
        full_name,
        profile_pic,
        yop
    FROM profiles 
    WHERE deleted_at IS NULL");

    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to prepare query: ' . $conn->error
        ]);
        exit;
    }
    
    $stmt->execute();
    $res = $stmt->get_result();

    $profiles = [];

    while ($row = $res->fetch_assoc()) {
        $profiles[] = [
            'id'          => $row['id'],
            'name'   => $row['full_name'],
            'profile_pic' => $row['profile_pic'],
            'batch'       => $row['yop'],
        ];
    }

    echo json_encode([
        'success' => true,
        'data'    => $profiles
    ]);

    $stmt->close();
    $conn->close();
    exit;
}
