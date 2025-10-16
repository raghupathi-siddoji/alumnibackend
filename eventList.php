<?php
include('header.php');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
        $page = max(1, $page);

        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? intval($_GET['limit']) : 6;

        $offset = ($page - 1) * $limit;

        $userRole = isset($_GET['userRole']) ? $_GET['userRole'] : null;

        if ($userRole === "User") {
            $countSql = "SELECT COUNT(*) as total FROM events WHERE deleted_at IS NULL AND status = 'active'";
        } else {
            $countSql = "SELECT COUNT(*) as total FROM events WHERE deleted_at IS NULL";
        }

        $countStmt = $conn->prepare($countSql);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $total = 0;
        if ($row = $countResult->fetch_assoc()) {
            $total = intval($row['total']);
        }
        $countStmt->close();

        if ($userRole === "User") {
            $sql = "SELECT 
                        id, 
                        title, 
                        image, 
                        location, 
                        sdescription, 
                        description, 
                        event_date,
                        status
                    FROM events 
                    WHERE deleted_at IS NULL AND status = 'Active'
                    ORDER BY event_date DESC 
                    LIMIT ? OFFSET ?";
        } else {
            $sql = "SELECT 
                        id, 
                        title, 
                        image, 
                        location, 
                        sdescription, 
                        description, 
                        event_date,
                        status
                    FROM events 
                    WHERE deleted_at IS NULL
                    ORDER BY event_date DESC 
                    LIMIT ? OFFSET ?";
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $events = [];

        while ($row = $result->fetch_assoc()) {
            $events[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'image' => $row['image'],
                'location' => $row['location'],
                'short_description' => $row['sdescription'],
                'description' => $row['description'],
                'event_date' => $row['event_date'],
                'status' => $row['status'],
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => $events,
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
