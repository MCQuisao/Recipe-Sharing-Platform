<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require "../config/db.php";
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$first = trim($data['first_name'] ?? '');
$last = trim($data['last_name'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$first || !$last || !$email || !$password) {
    http_response_code(400);
    echo json_encode(["error" => "All fields are required."]);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash) VALUES (:f, :l, :e, :p)");
    $stmt->execute([
        "f" => $first,
        "l" => $last,
        "e" => $email,
        "p" => $hash
    ]);

    $userId = $pdo->lastInsertId();

    session_start();
    $_SESSION['user_id'] = $userId;
    $_SESSION['first_name'] = $first;
    $_SESSION['email'] = $email;

    echo json_encode(["success" => true, "redirect" => "../main-page/mainpage.php"]);

} catch (PDOException $e) {
    if ($e->getCode() == 23000 || $e->getCode() == 23505) {
        http_response_code(409);
        echo json_encode(["error" => "Email is already registered."]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Database Error: " . $e->getMessage()]);
    }
}