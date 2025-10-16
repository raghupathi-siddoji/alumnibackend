<?php

include('header.php');
  
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Missing ID']);
            exit;
        }

        $deletedAt = date('Y-m-d H:i:s');
        
        $stmt = $conn->prepare("UPDATE openings SET deleted_at = ? WHERE id = ?");
        $stmt->bind_param("si", $deletedAt, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }

        $stmt->close();
        $conn->close();
        exit;
    }

    $id = $_POST['id'] ?? null;
    $opening_type = $_POST['opening_type'] ?? '';
    $company_name = $_POST['company_name'] ?? '';
    $skills_json = $_POST['skills'] ?? '';
    $package = $_POST['package'] ?? '';
    $location = $_POST['location'] ?? '';
    $designation = $_POST['designation'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    $last_date = $_POST['last_date'] ?? null;
    $no_of_openings = $_POST['no_of_openings'] ?? '';
    $status = $_POST['status'] ?? '';
    $admin_approval = 'Pending';

    $skills_array = json_decode($skills_json, true);
    $skills_list = is_array($skills_array) ? implode(",", $skills_array) : $skills_json;

    $logoPath = null;
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $tmpName = $_FILES['company_logo']['tmp_name'];
        $originalName = basename($_FILES['company_logo']['name']);
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array(strtolower($ext), $allowedExts)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type.']);
            exit;
        }

        $filename = uniqid("logo_", true) . "." . $ext;
        $logoPath = $uploadDir . $filename;

        if (!move_uploaded_file($tmpName, $logoPath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload logo.']);
            exit;
        }
    }

    if ($id) {
        $sql = "UPDATE openings SET opening_type=?, user_id=?, company_name=?, skills=?, package=?, location=?, designation=?, last_date=?, no_of_openings=?, status=?, deleted_at=NULL";

        if ($logoPath) {
            $sql .= ", company_logo=?";
        }

        $sql .= " WHERE id=?";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit;
        }

        if ($logoPath) {
            $stmt->bind_param(
                "sssssssssssi",
                $opening_type,
                $user_id,
                $company_name,
                $skills_list,
                $package,
                $location,
                $designation,
                $last_date,
                $no_of_openings,
                $status,
                $logoPath,
                $id
            );
        } else {
            $stmt->bind_param(
                "ssssssssssi",
                $opening_type,
                $user_id,
                $company_name,
                $skills_list,
                $package,
                $location,
                $designation,
                $last_date,
                $no_of_openings,
                $status,
                $id
            );
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Opening updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update failed.']);
        }

        $stmt->close();
        $conn->close();
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO openings (opening_type, user_id, company_name, skills, package, location, designation, company_logo, last_date, no_of_openings, status, admin_approval) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
        exit;
    }

    $stmt->bind_param(
        "ssssssssssss",
        $opening_type,
        $user_id,
        $company_name,
        $skills_list,
        $package,
        $location,
        $designation,
        $logoPath,
        $last_date,
        $no_of_openings,
        $status,
        $admin_approval
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Opening details added successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database insert failed.']);
    }

    $stmt->close();
    $conn->close();
    exit;
}

elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {

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

    $user_id = $_GET['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'user_id missing']);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM openings WHERE user_id = ? AND deleted_at IS NULL");
     
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB prepare error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("s", $user_id);
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
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}
