<?php
include('header.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
        $community_post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : null;
        $comment = trim($_POST['comment'] ?? '');
        
        $stmt = $conn->prepare("
            INSERT INTO community_comments (user_id, community_post_id, comment, comment_date, created_at)
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        $stmt->bind_param("iis", $user_id, $community_post_id, $comment);

        if ($stmt->execute()) {
            $commentId = $stmt->insert_id;

            $userStmt = $conn->prepare("SELECT full_name, profile_pic FROM profiles WHERE user_id = ?");
            $userStmt->bind_param("i", $user_id);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            $userData = $userResult->fetch_assoc();
    
            echo json_encode([
                'success' => true,
                'id' => $commentId,
                'user_name' => $userData['name'] ?? 'Unknown',
                'avatar' => $userData['profile_pic'] ?? null,
                'date' => date('Y-m-d H:i:s'),
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save comment.']);
        }

        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if (!isset($_GET['post_id'])) {
            echo json_encode(['success' => false, 'message' => 'post_id is required']);
            exit;
        }

        $post_id = intval($_GET['post_id']);

        $stmt = $conn->prepare("
            SELECT c.id, c.comment, c.comment_date, p.full_name, p.profile_pic
            FROM community_comments c
            LEFT JOIN profiles p ON c.user_id = p.user_id
            WHERE c.community_post_id = ?
            ORDER BY c.comment_date desc
        ");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $comments = [];

        while ($row = $result->fetch_assoc()) {
            $comments[] = [
                'id' => $row['id'],
                'text' => $row['comment'],
                'date' => $row['comment_date'],
                'name' => $row['full_name'] ?? 'Unknown',
                'avatar' => $row['profile_pic'] ?? null,
            ];
        }
         
        echo json_encode(['success' => true, 'comments' => $comments]);

        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
}