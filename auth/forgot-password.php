<?php
header("Content-Type: application/json");
require_once('../config/db.php'); 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed."]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);
$ui_email = trim($input['email'] ?? '');

if (empty($ui_email) || !filter_var($ui_email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["error" => "Please enter a valid email address."]);
    exit();
}


$your_resend_account_email = 'matthewaquisao@gmail.com';
$resendApiKey = 're_jgG63p4F_EUbDfC2Lawiz2h2Yj1kqkY4M';

try {
    // Check if user exists so the demo acts normally
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $ui_email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(["message" => "If that email exists, a reset link has been sent to your registered inbox."]);
        exit();
    }

    $demoToken = bin2hex(random_bytes(16));

    $updateStmt = $pdo->prepare("
        UPDATE users 
        SET reset_token = :token, 
            reset_expires_at = CURRENT_TIMESTAMP + INTERVAL '1 hour' 
        WHERE email = :email
    ");
    $updateStmt->execute(['token' => $demoToken, 'email' => $ui_email]);

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $resetLink = $protocol . $_SERVER['HTTP_HOST'] . "/Recipe Sharing Platform/auth/reset-password.php?token=" . $demoToken . "&demo_email=" . urlencode($ui_email);
    
    $emailData = [
        "from" => "Fatikem Kitchen <onboarding@resend.dev>",
        "to" => [$your_resend_account_email], 
        "subject" => "Reset Your Fatikem Password",
        "html" => "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333; padding: 20px; border: 1px solid #eee; border-radius: 8px;'>
                <h2 style='color: #992222; margin-top: 0;'>Password Reset Request</h2>
                <p>Hello,</p>
                <p>A password reset was requested for: <strong>{$ui_email}</strong>.</p>
                <p>Click the button below to simulate changing the password for that account:</p>
                <p style='margin: 30px 0; text-align: center;'>
                    <a href='{$resetLink}' style='background-color: #992222; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;'>Reset Password</a>
                </p>
                <p style='font-size: 12px; color: #777;'>This simulation link will expire in 1 hour.</p>
            </div>
        "
    ];

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $resendApiKey,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        echo json_encode(["message" => "If that email exists, a reset link has been sent to your registered inbox."]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to dispatch email delivery pipeline via Resend Sandbox."]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database exception engine error."]);
}



