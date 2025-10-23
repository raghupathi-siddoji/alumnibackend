<?php
include('header.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $delId = intval($_POST['id'] ?? 0);
            if ($delId > 0) {
                $stmt = $conn->prepare("UPDATE community_posts SET deleted_at = NOW() WHERE id = ?");
                $stmt->bind_param("i", $delId);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Post deleted successfully.']);
                $stmt->close();
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid post ID.']);
            }
            $conn->close();
            exit;
        }

        $content_type = $_POST['content_type'] ?? '';
        $description = $_POST['description'] ?? '';
        $existingImage = $_POST['existing_image'] ?? null;
        $user_id = $_POST['user_id'] ?? null;
        $user_role = $_POST['user_role'] ?? null;
        $id = $_POST['post_id'] ?? null;

        $imagePath = $existingImage;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $tmpName = $_FILES['image']['tmp_name'];
            $fileName = basename($_FILES['image']['name']);
            $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid('post_', true) . '.' . $fileExt;
            $targetFile = $targetDir . $newFileName;

            if (!move_uploaded_file($tmpName, $targetFile)) {
                echo json_encode(['success' => false, 'message' => 'Failed to upload image.']);
                exit;
            }

            $imagePath = $targetFile;
        }

        if ($id) {
            if ($imagePath) {
                $stmt = $conn->prepare("UPDATE community_posts SET content_type = ?, description = ?, image = ? WHERE id = ?");
                $stmt->bind_param("sssi", $content_type, $description, $imagePath, $id);
            } else {
                $stmt = $conn->prepare("UPDATE community_posts SET content_type = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssi", $content_type, $description, $id);
            }
        } else {
            $admin_approval = ($user_role == 'Admin') ? 'Approved' : 'Pending';
            $stmt = $conn->prepare("
                INSERT INTO community_posts (content_type, description, image, user_id, post_date, admin_approval)
                VALUES (?, ?, ?, ?, NOW(), ?)
            ");
            $stmt->bind_param("sssis", $content_type, $description, $imagePath, $user_id, $admin_approval);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Post saved successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: Failed to save post.']);
        }

        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $contentTypes = isset($_GET['contentTypes']) && $_GET['contentTypes'] !== ''
            ? explode(',', $_GET['contentTypes'])
            : [];

        $selectSql = "SELECT 
                        p.id, p.user_id, p.content_type, p.description, p.image, p.post_date, p.admin_approval, 
                        prof.full_name AS author_name, prof.profile_pic AS author_pic, prof.yop AS author_batch, u.role AS user_role,
                        COUNT(c.id) AS comments_count
                      FROM community_posts p 
                      LEFT JOIN profiles prof ON p.user_id = prof.user_id 
                      LEFT JOIN users u ON p.user_id = u.id
                      LEFT JOIN community_comments c ON p.id = c.community_post_id
                      WHERE p.deleted_at IS NULL";

        $selectParams = [];
        $selectTypes = '';

        if (!empty($contentTypes)) {
            $placeholders = implode(',', array_fill(0, count($contentTypes), '?'));
            $selectSql .= " AND p.content_type IN ($placeholders)";
            $selectParams = array_merge($selectParams, $contentTypes);
            $selectTypes .= str_repeat('s', count($contentTypes));
        }

        $selectSql .= " GROUP BY p.id ORDER BY p.post_date DESC";

        $stmt = $conn->prepare($selectSql);

        if (!empty($selectParams)) {
            $stmt->bind_param($selectTypes, ...$selectParams);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $posts = [];

        while ($row = $result->fetch_assoc()) {
            $authorName = ($row['user_role'] === 'Admin') ? 'Admin' : $row['author_name'];
            $posts[] = [
                'id' => $row['id'],
                'user_id' => $row['user_id'],
                'content_type' => $row['content_type'],
                'description' => $row['description'],
                'image' => $row['image'],
                'post_date' => $row['post_date'],
                'admin_approval' => $row['admin_approval'],

                'authorName' => $authorName,
                'authorPic' => $row['author_pic'],
                'authorBatch' => $row['author_batch'],

                'commentsCount' => $row['comments_count']
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => $posts,
        ]);

        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
}
