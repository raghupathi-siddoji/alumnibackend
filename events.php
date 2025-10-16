<?php
include('header.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {

        if (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $delId = intval($_POST['id'] ?? 0);
            if ($delId > 0) {
                $stmt = $conn->prepare("UPDATE events SET deleted_at = NOW() WHERE id = ?");
                $stmt->bind_param("i", $delId);
                $stmt->execute();
                echo json_encode(['success' => true, 'message' => 'Event deleted successfully.']);
                $stmt->close();
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid event ID.']);
            }
            $conn->close();
            exit;
        }

        $title        = $_POST['title'] ?? '';
        $event_date   = $_POST['event_date'] ?? '';
        $location     = $_POST['location'] ?? '';
        $sdescription = $_POST['sdescription'] ?? '';
        $description  = $_POST['description'] ?? '';
        $status       = $_POST['status'] ?? 'Inactive';
        $existingImage = $_POST['existing_image'] ?? null;
        $id           = $_POST['id'] ?? null;

        if (!$title || !$event_date || !$location || !$sdescription || !$description) {
            echo json_encode([
                'success' => false,
                'message' => 'Missing required fields.'
            ]);
            exit;
        }

        $imagePath = $existingImage;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $tmpName = $_FILES['image']['tmp_name'];
            $fileName = basename($_FILES['image']['name']);
            $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid('event_', true) . '.' . $fileExt;
            $targetFile = $targetDir . $newFileName;

            if (!move_uploaded_file($tmpName, $targetFile)) {
                echo json_encode(['success' => false, 'message' => 'Failed to upload image.']);
                exit;
            }

            $imagePath = $targetFile;
        }

        if ($id) {
            if ($imagePath) {
                $stmt = $conn->prepare("UPDATE events 
                    SET title = ?, event_date = ?, location = ?, sdescription = ?, description = ?, image = ?, status = ? 
                    WHERE id = ?");
                $stmt->bind_param("sssssssi", $title, $event_date, $location, $sdescription, $description, $imagePath, $status, $id);
            } else {
                $stmt = $conn->prepare("UPDATE events 
                    SET title = ?, event_date = ?, location = ?, sdescription = ?, description = ?, status = ? 
                    WHERE id = ?");
                $stmt->bind_param("ssssssi", $title, $event_date, $location, $sdescription, $description, $status, $id);
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO events 
                (title, event_date, location, sdescription, description, image, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $title, $event_date, $location, $sdescription, $description, $imagePath, $status);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Event saved successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: Failed to save event.']);
        }

        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
         $userRole = isset($_GET['userRole']) && $_GET['userRole'] !== '' ? $_GET['userRole'] : null;
        
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
           
            $query = "SELECT id, title, image, location, sdescription, description, event_date, status
                      FROM events
                      WHERE id = ? AND deleted_at IS NULL";

            if ($userRole === "User") {
                $query .= " AND status = 'Active'";
            }

            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($event = $result->fetch_assoc()) {
                $stmt->close();
                 
                $otherQuery = "SELECT id, title, image, location, sdescription, description, event_date, status
                               FROM events
                               WHERE deleted_at IS NULL AND id != ?";

                if ($userRole === "User") {
                    $otherQuery .= " AND status = 'Active'";
                }

                $otherQuery .= " ORDER BY event_date DESC LIMIT 5";

                $stmt2 = $conn->prepare($otherQuery);
                $stmt2->bind_param("i", $id);
                $stmt2->execute();
                $result2 = $stmt2->get_result();

                $otherEvents = [];
                while ($row = $result2->fetch_assoc()) {
                    $otherEvents[] = $row;
                }
                $stmt2->close();

                echo json_encode([
                    'success' => true,
                    'data' => $event,
                    'other_events' => $otherEvents
                ]);
            } else {
                $stmt->close();
                echo json_encode(['success' => false, 'message' => 'Event not found']);
            }

        } else {
            $query = "SELECT id, title, image, location, sdescription, description, event_date, status
                      FROM events
                      WHERE deleted_at IS NULL";

            if ($userRole === "User") {
                $query .= " AND status = 'Active'";
            }

            $query .= " ORDER BY event_date DESC LIMIT 6";

            $stmt = $conn->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();

            $events = [];
            while ($row = $result->fetch_assoc()) {
                $events[] = $row;
            }

            $stmt->close();
            echo json_encode(['success' => true, 'data' => $events]);
        }

        $conn->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
