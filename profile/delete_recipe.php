<?php
session_start();
require_once('../config/db.php');

// Get the JSON data from the JavaScript fetch request
$data = json_decode(file_get_contents('php://input'), true);
$recipe_id = $data['id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$recipe_id || !$user_id) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit;
}

try {
    // We include author_id to ensure a user can only delete THEIR OWN recipes
    $stmt = $pdo->prepare("DELETE FROM recipes WHERE id = :id AND author_id = :author_id");
    $stmt->execute([
        'id' => $recipe_id,
        'author_id' => $user_id
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>