<?php

include('header.php');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = ($page - 1) * $limit;

    $query = "SELECT SQL_CALC_FOUND_ROWS 
        id,
        company_name,
        designation,
        opening_type,
        last_date,
        status
    FROM openings
    WHERE admin_approval = 'Approved' AND deleted_at IS NULL";
    
    $params = [];
    $types = '';

    if ($search !== '') {
        $query .= " AND designation LIKE ?";
        $params[] = '%' . $search . '%';
        $types .= 's';
    }
       
    if ($startDate && $endDate) {
        $startDateObj = DateTime::createFromFormat('Y-m-d', $startDate);
        $endDateObj = DateTime::createFromFormat('Y-m-d', $endDate);
        if ($startDateObj && $endDateObj) {
            $start = $startDateObj->format('Y-m-d') . " 00:00:00";
            $end = $endDateObj->format('Y-m-d') . " 23:59:59";

            $query .= " AND created_at BETWEEN ? AND ?";
            $params[] = $start;
            $params[] = $end;
            $types .= 'ss';
        }
    }

    $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to prepare query: ' . $conn->error
        ]);
        exit;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    $jobs = [];
    while ($row = $res->fetch_assoc()) {
        $jobs[] = [
            'id' => $row['id'],
            'company' => $row['company_name'],
            'title' => $row['designation'],
            'type' => $row['opening_type'],
            'last_date' => $row['last_date'],
            'status' => $row['status'],
        ];
    }

    $totalRes = $conn->query("SELECT FOUND_ROWS() as total");
    $totalRows = $totalRes->fetch_assoc()['total'] ?? 0;

    echo json_encode([
        'success' => true,
        'data' => $jobs,
        'total' => (int)$totalRows,
        'page' => $page,
        'limit' => $limit,
    ]);

    $stmt->close();
    $conn->close();
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
exit;