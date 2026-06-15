<?php
session_start();
require_once('../config/db.php');
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$recipe_id = $data['recipe_id'];
$action = $data['action'];

try {
    if ($action === 'rate') {
        $val = $data['rating'];
        $check = $pdo->prepare("SELECT id FROM recipe_reviews WHERE recipe_id = ? AND user_id = ? AND rating IS NOT NULL LIMIT 1");
        $check->execute([$recipe_id, $user_id]);
        $existing = $check->fetch();

        if ($existing) {

            $stmt = $pdo->prepare("UPDATE recipe_reviews SET rating = ? WHERE id = ?");
            $stmt->execute([$val, $existing['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO recipe_reviews (recipe_id, user_id, rating, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$recipe_id, $user_id, $val]);
        }
    } 
    else if ($action === 'comment') {
        $val = trim($data['comment']);
        $sql = "INSERT INTO recipe_reviews (recipe_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$recipe_id, $user_id, $val]);
    }
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}