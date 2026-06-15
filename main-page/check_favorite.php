<?php
session_start();
require_once('../config/db.php');
header('Content-Type: application/json');

$recipe_id = $_GET['recipe_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$recipe_id || !$user_id) {
    echo json_encode(['is_favorited' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND recipe_id = ?");
$stmt->execute([$user_id, $recipe_id]);

echo json_encode(['is_favorited' => (bool)$stmt->fetch()]);
?>