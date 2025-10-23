    <?php
    include('../header.php');

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 9;
        $scholarship_type_id = isset($_GET['scholarship_type_id']) ? (int)$_GET['scholarship_type_id'] : null;

        $offset = ($page - 1) * $limit;

        $sql = "SELECT 
                scholarships.id AS id,
                scholarships.user_id,
                scholarships.scholarship_type_id,
                scholarships.fund,
                scholarships.description,
                scholarship_types.scholarship_type,
                scholarships.created_at,
                scholarships.updated_at,
                scholarships.deleted_at,
                profiles.full_name,
                profiles.profile_pic,
                profiles.contact_no
            FROM scholarships
            JOIN scholarship_types ON scholarships.scholarship_type_id = scholarship_types.id
            JOIN profiles ON scholarships.user_id = profiles.user_id
            WHERE scholarships.deleted_at IS NULL
        ";

        $params = [];
        $types = '';

        if ($scholarship_type_id) {
            $sql .= " AND scholarships.scholarship_type_id = ?";
            $params[] = $scholarship_type_id;
            $types .= 'i';
        }
        
        $sql .= " ORDER BY scholarships.id DESC";
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

        $countSql = "SELECT COUNT(*) AS total
            FROM scholarships
            JOIN scholarship_types ON scholarships.scholarship_type_id = scholarship_types.id
            JOIN profiles ON scholarships.user_id = profiles.user_id
            WHERE scholarships.deleted_at IS NULL
        ";

        if ($scholarship_type_id) {
            $countSql .= " AND scholarships.scholarship_type_id = ?";
        }

        $countStmt = $conn->prepare($countSql);
        if (!$countStmt) {
            echo json_encode(['success' => false, 'message' => 'DB prepare error: ' . $conn->error]);
            exit;
        }

        if ($scholarship_type_id) {
            $countStmt->bind_param('i', $scholarship_type_id);
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
