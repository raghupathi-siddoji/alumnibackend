<?php
include('header.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Missing post ID']);
            exit;
        }

        $deletedAt = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE mentorships SET deleted_at = ? WHERE id = ?");
        $stmt->bind_param("si", $deletedAt, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }

        $stmt->close();
        $conn->close();
        exit;
    }

    $id = $_POST['id'] ?? null;
    $user_id = $_POST['user_id'] ?? null;
    $mentorship_type_id = $_POST['mentorship_type_id'] ?? null;
    $description = $_POST['description'] ?? '';
    $existingAttachment = $_POST['existing_attachment'] ?? null;
    $tags_json = $_POST['tags'] ?? '[]';

    $tags_array = json_decode($tags_json, true);
    $tags_list = is_array($tags_array) ? implode(",", $tags_array) : $tags_json;

    $attachmentPath = $existingAttachment;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "uploads/mentorship/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $tmpName = $_FILES['attachment']['tmp_name'];
        $fileName = basename($_FILES['attachment']['name']);
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = uniqid('mentorship_', true) . '.' . $fileExt;
        $targetFile = $targetDir . $newFileName;

        if (!move_uploaded_file($tmpName, $targetFile)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload attachment.']);
            exit;
        }

        $attachmentPath = $targetFile;
    }

    if ($id) {
        if ($attachmentPath) {
            $stmt = $conn->prepare("
            UPDATE mentorships 
            SET mentorship_type_id = ?, description = ?, tags = ?, attachment = ?, user_id = ?, updated_at = NOW(), deleted_at = NULL 
            WHERE id = ?
        ");
            $stmt->bind_param("ssssii", $mentorship_type_id, $description, $tags_list, $attachmentPath, $user_id, $id);
        } else {
            $stmt = $conn->prepare("
            UPDATE mentorships 
            SET mentorship_type_id = ?, description = ?, tags = ?, user_id = ?, updated_at = NOW(), deleted_at = NULL 
            WHERE id = ?
        ");
            $stmt->bind_param("sssii", $mentorship_type_id, $description, $tags_list, $user_id, $id);
        }
    } else {
        $stmt = $conn->prepare("
        INSERT INTO mentorships (user_id, mentorship_type_id, description, tags, attachment, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");
        $stmt->bind_param("iisss", $user_id, $mentorship_type_id, $description, $tags_list, $attachmentPath);
    }


    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => $id ? 'Mentorship Details Updated' : 'Mentorship Details Stored']);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mentorship_type_id = $_GET['mentorship_type_id'] ?? null;
    $tag = $_GET['tag'] ?? null;  

    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("
            SELECT mentorships.id AS id,
                   mentorships.user_id,
                   mentorships.mentorship_type_id,
                   mentorships.description,
                   mentorships.tags,
                   mentorship_types.mentorship_type, 
                   mentorships.attachment
            FROM mentorships
            JOIN mentorship_types ON mentorships.mentorship_type_id = mentorship_types.id
            WHERE mentorships.id = ? AND mentorships.deleted_at IS NULL
            ORDER BY mentorships.id DESC
        ");

        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'DB prepare error: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Record not found']);
        }

        $stmt->close();
        $conn->close();
        exit;
    }

    $user_id = $_GET['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'user_id missing']);
        exit;
    }

    $query = "
        SELECT 
            mentorships.id AS id,
            mentorships.user_id,
            mentorships.mentorship_type_id,
            mentorships.description,
            mentorships.tags,
            mentorship_types.mentorship_type,
            mentorships.attachment
        FROM mentorships
        JOIN mentorship_types ON mentorships.mentorship_type_id = mentorship_types.id
        WHERE mentorships.user_id = ?
          AND mentorships.deleted_at IS NULL
    ";

    $types = "i"; 
    $params = [$user_id];

    if ($mentorship_type_id) {
        $query .= " AND mentorships.mentorship_type_id = ? ";
        $types .= "i";
        $params[] = $mentorship_type_id;
    }

    if ($tag) {
        $query .= " AND mentorships.tags LIKE ? ";
        $types .= "s";
        $params[] = "%$tag%";
    }

    $query .= " ORDER BY mentorships.id DESC";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB prepare error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param($types, ...$params);

    $stmt->execute();
    $res = $stmt->get_result();

    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $data]);

    $stmt->close();
    $conn->close();
    exit;
}


 else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
   exit;
}
