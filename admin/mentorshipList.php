<?php
include('../header.php');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 9;
    $mentorship_type_id = isset($_GET['mentorship_type_id']) ? (int)$_GET['mentorship_type_id'] : null;
    $tag = isset($_GET['tag']) ? trim($_GET['tag']) : null;

    $offset = ($page - 1) * $limit;

    $sql = "SELECT 
            mentorships.id AS id,
            mentorships.user_id,
            mentorships.mentorship_type_id,
            mentorships.description,
            mentorships.tags,
            mentorships.attachment,
            mentorships.created_at,
            mentorships.updated_at,
            mentorships.deleted_at,
            mentorship_types.mentorship_type,
            profiles.full_name,
            profiles.profile_pic,
            profiles.contact_no
        FROM mentorships
        JOIN mentorship_types ON mentorships.mentorship_type_id = mentorship_types.id
        JOIN profiles ON mentorships.user_id = profiles.user_id
        WHERE mentorships.deleted_at IS NULL
    ";

    $params = [];
    $types = '';

    if ($mentorship_type_id) {
        $sql .= " AND mentorships.mentorship_type_id = ?";
        $params[] = $mentorship_type_id;
        $types .= 'i';
    }

    if ($tag) { 
        $sql .= " AND mentorships.tags LIKE ?";
        $params[] = '%' . $tag . '%';
        $types .= 's';
    }
    
    $sql .= " ORDER BY mentorships.id DESC";
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB prepare error: ' . $conn->error]);
        exit;
    }

    if ($types) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }

    // Count total for pagination, including filters
    $countSql = "SELECT COUNT(*) AS total
        FROM mentorships
        JOIN mentorship_types ON mentorships.mentorship_type_id = mentorship_types.id
        JOIN profiles ON mentorships.user_id = profiles.user_id
        WHERE mentorships.deleted_at IS NULL
    ";

    $countParams = [];
    $countTypes = '';

    if ($mentorship_type_id) {
        $countSql .= " AND mentorships.mentorship_type_id = ?";
        $countParams[] = $mentorship_type_id;
        $countTypes .= 'i';
    }

    if ($tag) {
        $countSql .= " AND mentorships.tags LIKE ?";
        $countParams[] = '%' . $tag . '%';
        $countTypes .= 's';
    }

    $countStmt = $conn->prepare($countSql);
    if (!$countStmt) {
        echo json_encode(['success' => false, 'message' => 'DB prepare error: ' . $conn->error]);
        exit;
    }

    if ($countTypes) {
        $countStmt->bind_param($countTypes, ...$countParams);
    }

    $countStmt->execute();
    $countRes = $countStmt->get_result();
    $total = $countRes->fetch_assoc()['total'];

    $totalPages = ceil($total / $limit);

    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => $total,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ]);

    $stmt->close();
    $countStmt->close();
    $conn->close();
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Invalid request method.'
]);
exit;
