<?php
session_start();
require_once('../config/db.php');

$profile_pic = $_SESSION['profile_pic'] ?? ''; 

$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$initials = '';

if ($first_name || $last_name) {
    $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
}

$bgColor = sprintf('#%02X%02X%02X', rand(100, 255), rand(100, 255), rand(100, 255));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuisine • Fatikem</title>
    <link rel="icon" href="../images/recipe-logo-removebg-preview.png" type="image/png">
    <link rel="stylesheet" href="cuisine.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sura&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&display=swap" rel="stylesheet">

    <style>
        .user-initials {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
        }
        .user-initials a {
            color: white;
            text-decoration: none;
        }
        .profile-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .profile-icon img.profile-img {
            height: 40px;
            width: 40px;
            border-radius: 50%;
            object-fit: cover;
            display: block;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <header class="nav-bar">
        <div class="logo">
            <a href="../main-page/mainpage.php">
                <img src="../images/recipe-logo-removebg-preview.png" alt="" class="logo-img">
            </a>
            <span class="brand-name">Fatikem</span>
        </div>
        
        <nav class="nav-links">
            <a href="../main-page/mainpage.php">Home</a>
            <a href="../explore/explore.php">Explore</a>
            <a href="cuisine.php" class="active">Cuisine</a>
        </nav>

        <div class="nav-actions">
            <a href="<?php echo (!empty($profile_pic) || $initials) ? '../add-recipe/add.php' : '../sign up/singup.html'; ?>">
                <button class="add-recipe-btn">
                    <span class="plus-icon">+</span>
                    Add Recipe
                </button>
            </a>
            
            <?php if(!empty($profile_pic)): ?>
                <div class="profile-icon">
                    <a href="../profile/profile.php">
                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" class="profile-img">
                    </a>
                </div>
            <?php elseif($initials): ?>
                <div class="profile-icon">
                    <a href="../profile/profile.php" style="text-decoration: none;">
                        <div class="user-initials" style="background-color: <?php echo $bgColor; ?>;">
                            <?php echo $initials; ?>
                        </div>
                    </a>
                </div>
            <?php else: ?>
                <a href="../sign up/singup.html" class="login-link">Login</a>
            <?php endif; ?>
        </div>
    </header>

    <section class="cuisine-section">
        <div class="container">
            <header>
                <h1>Cuisines</h1>
                <p>Discover different cuisines around the world</p>
            </header>

            <div class="recipe-grid">
                <div class="recipe-card" onclick="window.location.href='japan.php'">
                    <img src="../cuisine-country/Japan/japan.jpg" alt="">
                    <div class="card-content">
                        <h2 class="country-name" id="japan">Japanese</h2>
                        <div class="card-subtitle">
                            <a href="../cuisine/japan.php"><span>View 3 Cuisines</span></a>
                        </div>
                    </div>
                </div>

                <div class="recipe-card" onclick="window.location.href='indian.php'">
                    <img src="../cuisine-country/India/india.jpg" alt="India">
                    <div class="card-content">
                        <h2 class="country-name" id="india">Indian</h2>
                        <div class="card-subtitle">
                            <a href="../cuisine/indian.php"><span>View 3 Cuisines</span></a>
                        </div>
                    </div>
                </div>

                <div class="recipe-card" onclick="window.location.href='filipino.php'">
                    <img src="../cuisine-country/Philippines/Philippine.jpg" alt="Philippines">
                    <div class="card-content">
                        <h2 class="country-name" id="filipino">Filipino</h2>
                        <div class="card-subtitle">
                            <a href="../cuisine/filipino.php"><span>View 3 Cuisines</span></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>
</html>