<?php
include('../header.php'); 

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Only GET method is allowed.'
    ]);
    exit;
}

$query = "SELECT id AS value, mentorship_type AS name FROM mentorship_types WHERE deleted_at IS NULL";

$result = $conn->query($query);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "Database query failed: " . $conn->error
    ]);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $data
]);

$conn->close();
