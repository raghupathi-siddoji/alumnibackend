<?php

include('../header.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['admin_approval']) && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $admin_approval = trim($_POST['admin_approval']);
        $allowed = ['Approved', 'Rejected', 'Pending'];
        if ($id <= 0 || !in_array($admin_approval, $allowed, true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters for approval']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE openings SET admin_approval = ? WHERE id = ? AND deleted_at IS NULL");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'DB prepare error: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("si", $admin_approval, $id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Approval status updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No record updated (maybe id invalid or already same status)']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Execute error: ' . $stmt->error]);
        }
        $stmt->close();
        $conn->close();
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $stmt = $conn->prepare("SELECT * FROM openings WHERE id = ? AND deleted_at IS NULL");

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

    if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
        $search = trim($_GET['search']);
        $searchParam = '%' . $conn->real_escape_string($search) . '%';

        $query = "SELECT * FROM openings WHERE deleted_at IS NULL AND (
        company_name LIKE ? OR 
        skills LIKE ? OR 
        opening_type LIKE ? OR 
        package LIKE ? OR 
        location LIKE ? OR 
        designation LIKE ? OR 
        no_of_openings LIKE ? OR 
        status LIKE ? OR 
        admin_approval LIKE ?
    )";

        $params = array_fill(0, 9, $searchParam);
        $types = str_repeat("s", 9);

        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $search)) {
            $dateParts = explode('-', $search);
            $formattedDate = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];

            $query .= " OR last_date = ?";
            $params[] = $formattedDate;
            $types .= "s";
        }

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

    $stmt = $conn->prepare("SELECT * FROM openings WHERE deleted_at IS NULL");

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
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}
