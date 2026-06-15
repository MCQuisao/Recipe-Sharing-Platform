<?php
session_start();
require_once('../config/db.php');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Login required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$recipe_id = $data['recipe_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$recipe_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid Recipe ID']);
    exit;
}

try {
    $check = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND recipe_id = ?");
    $check->execute([$user_id, $recipe_id]);
    
    if ($check->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND recipe_id = ?");
        $stmt->execute([$user_id, $recipe_id]);
        echo json_encode(['success' => true, 'status' => 'removed']);
    } else {
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, recipe_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $recipe_id]);
        echo json_encode(['success' => true, 'status' => 'added']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>