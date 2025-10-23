<?php

include('header.php');

$method = $_SERVER['REQUEST_METHOD'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $personal = $_POST;
    $professionsJson = $_POST['professional_experiences'] ?? '[]';
    $professional = json_decode($professionsJson, true);
    if (!is_array($professional)) {
        $professional = [];
    }
    $user_id = $personal['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'user_id missing']);
        exit;
    }

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $tmpName = $_FILES['profile_pic']['tmp_name'];
        $fileName = basename($_FILES['profile_pic']['name']);
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = uniqid('profile_', true) . '.' . $fileExt;
        $targetFile = $targetDir . $newFileName;

        if (move_uploaded_file($tmpName, $targetFile)) {
            $personal['profile_pic'] = $targetFile;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload profile picture.']);
            exit;
        }
    } else {
        $personal['profile_pic'] = $personal['profile_pic'] ?? null;
    }

    $stmt = $conn->prepare("SELECT id FROM profiles WHERE user_id = ? AND deleted_at IS NULL");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $profile_id = intval($row['id']);

        $updateProfileStmt = $conn->prepare(
            "UPDATE profiles SET 
                full_name = ?, 
                email = ?, 
                dob = ?, 
                contact_no = ?, 
                linked_in = ?, 
                whatsapp_no = ?, 
                instagram_id = ?, 
                facebook = ?, 
                twitter_id = ?, 
                yop = ?, 
                branch = ?, 
                profile_pic = ?, 
                updated_at = NOW() 
            WHERE id = ?"
        );

        $updateProfileStmt->bind_param(
            "ssssssssssssi",
            $personal['full_name'],
            $personal['email'],
            $personal['dob'],
            $personal['contact_no'],
            $personal['linked_in'],
            $personal['whatsapp_no'],
            $personal['instagram_id'],
            $personal['facebook'],
            $personal['twitter_id'],
            $personal['yop'],
            $personal['branch'],
            $personal['profile_pic'],
            $profile_id
        );

        $updateProfileStmt->execute();
        $updateProfileStmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO profiles (user_id, full_name, email, dob, contact_no, linked_in, whatsapp_no, instagram_id, facebook, twitter_id, yop, branch, profile_pic, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param(
            "isssssssssss",
            $user_id,
            $personal['full_name'],
            $personal['email'],
            $personal['dob'],
            $personal['contact_no'],
            $personal['linked_in'],
            $personal['whatsapp_no'],
            $personal['instagram_id'],
            $personal['facebook'],
            $personal['twitter_id'],
            $personal['yop'],
            $personal['branch'],
            $personal['profile_pic']
        );
        $stmt->execute();
        $profile_id = $stmt->insert_id;
        $stmt->close();
    }

    $existingIds = [];
    $receivedIds = [];

    $stmt = $conn->prepare("SELECT id FROM user_professions WHERE profile_id = ? AND deleted_at IS NULL");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $existingIds = [];
    while ($r = $res->fetch_assoc()) {
        $existingIds[] = intval($r['id']);
    }
    $stmt->close();

    foreach ($professional as $profession) {
        $prof_id          = isset($profession['id']) ? intval($profession['id']) : null;
        $company_name     = $profession['company_name'] ?? '';
        $designation      = $profession['designation'] ?? '';
        $doj              = $profession['doj'] ?? null;
        $department       = $profession['department'] ?? '';
        $total_experience = $profession['total_experience'] ?? '';
        $specific_field   = $profession['specific_field'] ?? '';
        $skills           = $profession['skills'] ?? [];
        $location         = $profession['location'] ?? '';

        $skills_json = is_array($skills) ? implode(',', $skills) : $skills;

        if ($prof_id && in_array($prof_id, $existingIds)) {
            $sql = "UPDATE user_professions SET company_name=?, designation=?, doj=?, department=?, total_experience=?, specific_field=?, skills=?, location=?, updated=NOW() WHERE id=? AND profile_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssssssssii",
                $company_name,
                $designation,
                $doj,
                $department,
                $total_experience,
                $specific_field,
                $skills_json,
                $location,
                $prof_id,
                $profile_id
            );
            $stmt->execute();
            $stmt->close();

            $receivedIds[] = $prof_id;
        } else {
            $sql = "INSERT INTO user_professions (user_id, profile_id, company_name, designation, doj, department, total_experience, specific_field, skills, location, created_at, updated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "iissssssss",
                $user_id,
                $profile_id,
                $company_name,
                $designation,
                $doj,
                $department,
                $total_experience,
                $specific_field,
                $skills_json,
                $location
            );
            $stmt->execute();
            $receivedIds[] = $stmt->insert_id;
            $stmt->close();
        }
    }

    $toDelete = array_diff($existingIds, $receivedIds);

    if (!empty($toDelete)) {
        $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
        $types = str_repeat('i', count($toDelete)) . 'i';
        $sql = "UPDATE user_professions SET deleted_at = NOW() WHERE id IN ($placeholders) AND profile_id = ?";

        $stmt = $conn->prepare($sql);
        $params = array_merge($toDelete, [$profile_id]);

        $bind_names[] = $types;
        for ($i = 0; $i < count($params); $i++) {
            $bind_name = 'bind' . $i;
            $$bind_name = $params[$i];
            $bind_names[] = &$$bind_name;
        }

        call_user_func_array([$stmt, 'bind_param'], $bind_names);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['success' => true, 'message' => 'Profile created successfully']);
    exit;
} elseif ($method === 'GET') {
    $user_id = $_GET['user_id'] ?? null;

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Missing user_id']);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM profiles WHERE user_id = ? AND deleted_at IS NULL");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if (!$profile = $res->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'Profile not found']);
        exit;
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM user_professions WHERE user_id = ? AND profile_id = ? AND deleted_at IS NULL");
    $stmt->bind_param("ss", $user_id, $profile['id']);
    $stmt->execute();
    $res = $stmt->get_result();

    $professions = [];
    while ($row = $res->fetch_assoc()) {
        if (!empty($row['skills']) && is_string($row['skills'])) {
            $decodedSkills = json_decode($row['skills'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedSkills)) {
                $row['skills'] = $decodedSkills;
            } else {
                $row['skills'] = array_map('trim', explode(',', $row['skills']));
            }
        } else {
            $row['skills'] = [];
        }
        $professions[] = $row;
    }
    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'data' => [
            'profile' => $profile,
            'professional_experiences' => $professions
        ]
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
exit;
