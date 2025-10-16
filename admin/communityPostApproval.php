<?php
include('../header.php');  

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $admin_approval = $_POST['admin_approval'] ?? null;

    if (!$id || !$admin_approval) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE community_posts SET admin_approval = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $admin_approval, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
