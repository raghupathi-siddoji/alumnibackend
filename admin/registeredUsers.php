<?php

include('../header.php');  

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? intval($_GET['limit']) : 10;
        $page = max(1, $page);
        $limit = max(1, $limit);
        $offset = ($page - 1) * $limit;

        $filterYOP = isset($_GET['yop']) ? trim($_GET['yop']) : '';
        $filterApproval = isset($_GET['admin_approval']) ? trim($_GET['admin_approval']) : '';

        $whereClauses = ["deleted_at IS NULL", "role != 'Admin'"];
        $params = [];
        $paramTypes = "";

        if ($filterYOP !== '') {
            $whereClauses[] = "yop = ?";
            $params[] = $filterYOP;
            $paramTypes .= "s";  
        }

        if ($filterApproval !== '') {
            $whereClauses[] = "admin_approval = ?";
            $params[] = $filterApproval;
            $paramTypes .= "s";
        }

        $whereSQL = implode(" AND ", $whereClauses);

        $countSQL = "SELECT COUNT(*) AS total FROM users WHERE $whereSQL";
        $countStmt = $conn->prepare($countSQL);

        if (!empty($params)) {
            $countStmt->bind_param($paramTypes, ...$params);
        }
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $total = 0;

        if ($row = $countResult->fetch_assoc()) {
            $total = intval($row['total']);
        }
        $countStmt->close();

        $sql = "SELECT 
            id,
            name,
            email,
            usn,
            yop,
            department,
            profile,
            gender,
            admin_approval
            FROM users
            WHERE $whereSQL
            ORDER BY name ASC
            LIMIT ? OFFSET ?";

        $stmt = $conn->prepare($sql);

        $paramTypesMain = $paramTypes . "ii";
        $paramsMain = array_merge($params, [$limit, $offset]);
        $stmt->bind_param($paramTypesMain, ...$paramsMain);

        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'usn' => $row['usn'],
                'yop' => $row['yop'],
                'department' => $row['department'],
                'profile' => $row['profile'],
                'gender' => $row['gender'],
                'admin_approval' => $row['admin_approval']
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => $users,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_items' => $total,
                'total_pages' => ceil($total / $limit),
            ]
        ]);

        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }

} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
