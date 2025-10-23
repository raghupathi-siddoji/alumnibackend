<?php

include('header.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Missing scholarship ID']);
            exit;
        }

        $deletedAt = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("UPDATE scholarships SET deleted_at = ? WHERE id = ?");
        $stmt->bind_param("si", $deletedAt, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Scholarship deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }

        $stmt->close();
        $conn->close();
        exit;
    }

    $id = $_POST['id'] ?? null;
    $scholarship_type_id = $_POST['scholarship_type_id'] ?? '';
    $fund = $_POST['fund'] ?? '';
    $description = $_POST['description'] ?? '';
    $user_id = $_POST['user_id'] ?? '';

    if (!is_numeric($scholarship_type_id) || !is_numeric($fund) || empty($description) || !is_numeric($user_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit;
    }

    if ($id) {
        $sql = "UPDATE scholarships SET scholarship_type_id=?, fund=?, description=?, user_id=?, updated_at=NOW(), deleted_at= NULL WHERE id=?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param("idsii", $scholarship_type_id, $fund, $description, $user_id, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Scholarship updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
        }

        $stmt->close();
        $conn->close();
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO scholarships (scholarship_type_id, fund, description, user_id, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("idsi", $scholarship_type_id, $fund, $description, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Scholarship added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Insert failed: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $type = $_GET['scholarship_type_id'] ?? null;

    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("
                SELECT scholarships.id AS id,
                       scholarships.user_id,
                       scholarships.scholarship_type_id,
                       scholarships.fund,
                       scholarships.description,
                       scholarship_types.scholarship_type,
                       scholarships.created_at,
                       scholarships.updated_at,
                       scholarships.deleted_at
                FROM scholarships
                JOIN scholarship_types ON scholarships.scholarship_type_id = scholarship_types.id
                WHERE scholarships.id = ? AND scholarships.deleted_at IS NULL
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

  

    if ($type) {
        $stmt = $conn->prepare("
        SELECT 
          scholarships.id AS id,
          scholarships.user_id,
          scholarships.scholarship_type_id,
          scholarships.fund,
          scholarships.description,
          scholarship_types.scholarship_type,
          scholarships.created_at,
          scholarships.updated_at,
          scholarships.deleted_at
        FROM scholarships
        JOIN scholarship_types ON scholarships.scholarship_type_id = scholarship_types.id
        WHERE scholarships.user_id = ? 
          AND scholarships.scholarship_type_id = ? 
          AND scholarships.deleted_at IS NULL
    ");

        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'DB prepare error: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param("ii", $user_id, $type);
    } else {
        $stmt = $conn->prepare("
        SELECT 
          scholarships.id AS id,
          scholarships.user_id,
          scholarships.scholarship_type_id,
          scholarships.fund,
          scholarships.description,
          scholarship_types.scholarship_type,
          scholarships.created_at,
          scholarships.updated_at,
          scholarships.deleted_at
        FROM scholarships
        JOIN scholarship_types ON scholarships.scholarship_type_id = scholarship_types.id
        WHERE scholarships.user_id = ? 
          AND scholarships.deleted_at IS NULL
    ");

        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'DB prepare error: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param("i", $user_id);
    }


    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB prepare error: ' . $conn->error]);
        exit;
    }

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

echo json_encode([
    'success' => false,
    'message' => 'Invalid request method.'
]);
exit;
