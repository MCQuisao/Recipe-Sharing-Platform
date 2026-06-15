<?php
session_start();
require_once('../config/db.php');
header('Content-Type: application/json');

$recipe_id = $_GET['recipe_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

try {
    $stmt = $pdo->prepare("
        SELECT 
            r.comment, 
            u.first_name, 
            u.last_name, 
            u.profile_pic,
            r.created_at
        FROM recipe_reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.recipe_id = ? AND r.comment IS NOT NULL AND r.comment != ''
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$recipe_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    $stmtAvg = $pdo->prepare("
        SELECT AVG(rating) as avg, COUNT(rating) as count 
        FROM recipe_reviews 
        WHERE recipe_id = ? AND rating IS NOT NULL
    ");
    $stmtAvg->execute([$recipe_id]);
    $stats = $stmtAvg->fetch(PDO::FETCH_ASSOC);

    $stmtUser = $pdo->prepare("
        SELECT rating 
        FROM recipe_reviews 
        WHERE recipe_id = ? AND user_id = ? AND rating IS NOT NULL
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmtUser->execute([$recipe_id, $user_id]);
    $userRating = $stmtUser->fetchColumn();

    echo json_encode([
        'success' => true,
        'comments' => $comments,
        'average' => round((float)($stats['avg'] ?? 0), 1),
        'total_ratings' => (int)($stats['count'] ?? 0),
        'user_rating' => $userRating !== false ? (int)$userRating : 0
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}