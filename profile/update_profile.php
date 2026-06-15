<?php
session_start();
require_once('../config/db.php');

header('Content-Type: application/json');

// Decode the JSON payload received from profile.js
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['first_name'])) {
    $user_id = $_SESSION['user_id'];
    $fName = trim($data['first_name']);
    $lName = trim($data['last_name']);
    $bio = trim($data['bio']);

    try {
        // Update the database table columns (Ensure 'bio' column exists in your 'users' table)
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, bio = ? WHERE id = ?");
        $stmt->execute([$fName, $lName, $bio, $user_id]);

        // Update Session structures so modifications remain fluid across other pages
        $_SESSION['first_name'] = $fName;
        $_SESSION['last_name'] = $lName;
        $_SESSION['bio'] = $bio;

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
}
?>