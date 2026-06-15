<?php
session_start();
require_once('../config/db.php');

$token = $_GET['token'] ?? '';
$error_message = '';
$success_message = '';

if (empty($token)) {
    die("Invalid or missing recovery token string.");
}

try {
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE reset_token = :token AND reset_expires_at > CURRENT_TIMESTAMP");
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        $error_message = "This password reset link is invalid or has expired.";
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
        $new_pass = $_POST['password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (empty($new_pass) || strlen($new_pass) < 6) {
            $error_message = "Password must be at least 6 characters long.";
        } elseif ($new_pass !== $confirm_pass) {
            $error_message = "Passwords do not match.";
        } else {
            // Hash password securely (matching your sign-up/login logic)
            $hashed_password = password_hash($new_pass, PASSWORD_BCRYPT);

            $updateStmt = $pdo->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_expires_at = NULL WHERE id = :id");
            $updateStmt->execute([
                'password' => $hashed_password,
                'id' => $user['id']
            ]);

            $success_message = "Your kitchen access credentials have been updated successfully!";
        }
    }
} catch (PDOException $e) {
    $error_message = "Database connection error occurred.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password • Fatikem</title>
    <link rel="icon" href="../images/recipe-logo-removebg-preview.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #992222;
            --bg: #fdf9f7;
            --text: #333;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            max-width: 400px;
            width: 100%;
            box-sizing: border-box;
        }
        h2 {
            margin-top: 0;
            color: var(--primary);
            font-size: 24px;
        }
        .input-group {
            margin-bottom: 20px;
            position: relative;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
            background-color: #fafafa;
        }
        input:focus {
            outline: none;
            border-color: var(--primary);
            background-color: #fff;
        }
        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 14px;
            width: 100%;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn:hover {
            background-color: #cc4444;
        }
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-danger {
            background-color: #fde8e8;
            color: #e53e3e;
            border: 1px solid #f8b4b4;
        }
        .alert-success {
            background-color: #def7ec;
            color: #03543f;
            border: 1px solid #bcf0da;
        }
        .login-link {
            display: inline-block;
            margin-top: 15px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Reset Password</h2>
    <p style="color: #666; font-size: 14px; margin-bottom: 25px;">
        Account identifier verified: <strong><?php echo htmlspecialchars($user['email'] ?? 'Guest'); ?></strong>
    </p>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <?php echo $success_message; ?><br>
            <a href="../log-in/singup.html" class="login-link">Return to Login Screen →</a>
        </div>
    <?php endif; ?>

    <?php if ($user && empty($success_message)): ?>
        <form method="POST" action="">
            <div class="input-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required placeholder="Minimum 6 characters">
            </div>
            
            <div class="input-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-type password">
            </div>
            
            <a href = "../sign up/singup.html"><button type="submit" class="btn">Update Password</button></a>
        </form>
    <?php endif; ?>
</div>

</body>
</html>