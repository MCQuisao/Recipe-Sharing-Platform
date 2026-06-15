<?php
session_start();
require_once('../config/db.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (isset($_FILES['profile_pic'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['profile_pic'];
    
    // 1. Validate File
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type.']);
        exit;
    }

    // 2. Create directory if it doesn't exist
    $upload_dir = '../uploads/avatars/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // 3. Name the file (User ID + extension to prevent duplicates)
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = "user_" . $user_id . "_" . time() . "." . $ext;
    $target_path = $upload_dir . $filename;

    // 4. Move file and Update Database
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET profile_pic = :path WHERE id = :id");
            $stmt->execute(['path' => $target_path, 'id' => $user_id]);
            
            // Update session so it shows up on next load
            $_SESSION['profile_pic'] = $target_path;

            echo json_encode(['success' => true, 'filepath' => $target_path]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to move file.']);
    }
}