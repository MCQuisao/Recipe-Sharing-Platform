<?php
session_start();
require_once('../config/db.php');

try {
    $query = "
        SELECT 
            r.*,
            u.first_name,
            u.last_name,
            u.profile_pic,

            (
                SELECT COALESCE(AVG(rating), 0)
                FROM recipe_reviews
                WHERE recipe_id = r.id
                AND rating IS NOT NULL
            ) AS avg_rating,

            (
                SELECT COUNT(rating)
                FROM recipe_reviews
                WHERE recipe_id = r.id
                AND rating IS NOT NULL
            ) AS total_reviews,

            (
                SELECT STRING_AGG(ingredient_text, '||')
                FROM recipe_ingredients
                WHERE recipe_id = r.id
            ) AS ingredients_list,

            (
                SELECT STRING_AGG(instruction_text, '||' ORDER BY step_number)
                FROM recipe_instructions
                WHERE recipe_id = r.id
            ) AS instructions_list

        FROM recipes r
        JOIN users u ON r.author_id = u.id
        WHERE r.status = 'approved'
        ORDER BY r.is_showcased DESC, r.created_at DESC
        LIMIT 6
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database fetch error: " . $e->getMessage());
}

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
    <title>Home • Fatikem</title>
    <link class="icon" href="../images/recipe-logo-removebg-preview.png" type="image/png">
    <link rel="stylesheet" href="mainpage.css">
    <link rel="stylesheet" href="modal.css">
    <link rel="stylesheet" href="../log-in/login.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sura&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="nav-bar">
        <div class="logo">
            <a href="mainpage.php">
                <img src="../images/recipe-logo-removebg-preview.png" alt="Fatikem Logo" class="logo-img">
            </a>
            <span class="brand-name">Fatikem</span>
        </div>

        <nav class="nav-links">
            <a href="mainpage.php" class="active">Home</a>
            <a href="../explore/explore.php">Explore</a>
            <a href="../cuisine/cuisine.php">Cuisine</a>
        </nav>

        <div class="nav-actions">
            <!-- Redirects to signup if logged out, otherwise allows access to add.php -->
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

    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">
                Discover & Share <br>
                <span>Delicious</span><br>
                Recipes
            </h1>
            <p class="hero-subtitle">
                From quick weeknight dinners to gourmet weekend feasts, find the perfect recipe for every occasion and skill level.
            </p>
            <div class="hero-buttons">
                <a href="../explore/explore.php"><button class="btn-primary">Explore Recipes</button></a>
                <a href="../add-recipe/add.php"><button class="btn-secondary">Share a Recipe</button></a>
            </div>
        </div>
        <div class="image-grid">
            <div class="col-wrapper">
                <div class="grid-item" style="margin-bottom: 20px;">
                    <img src="../explore/explore-images/Miso Black Cod.png" alt="">
                </div>
                <div class="grid-item">
                    <img src="../explore/explore-images/Crunchburger.png" alt="">
                </div>
            </div>

            <div class="col-wrapper column-2">
                <div class="grid-item" style="margin-bottom: 20px;">
                    <img src="../explore/explore-images/Momofuku Pork Buns.png" alt="">
                </div>
                <div class="grid-item">
                    <img src="../explore/explore-images/Vegan Mac & Cheese.png" alt="">
                </div>
            </div>
        </div>
    </section>

    <section class="web-info">
        <div class="web-content">
            <div class="info-item">
                <img src="../images/book-open-cover.svg" alt="Book">
                <h2>10K+</h2>
                <p>Recipes</p>
            </div>

            <div class="info-item">
                <img src="../images/users.svg" alt="People">
                <h2>50K+</h2>
                <p>Home Cooks</p>
            </div>

            <div class="info-item">
                <img src="../images/hat-chef.svg" alt="Chief Hat">
                <h2>100+</h2>
                <p>Cuisines</p>
            </div>
        </div>
    </section>

    <section class="featured-recipes">
        <div class="features-content">
            <div class="features-header">
                <div class="header-text">
                    <h3>Featured Recipes</h3>
                    <p>Hand-picked by our team</p>
                </div>
                <a href="../explore/explore.php" class="view-all-btn">
                    View all <img src="../images/arrow-small-right.svg" alt="Arrow">
                </a>
            </div>

            <div class="recipe-grid">
                <?php foreach ($recipes as $row): ?>
                    <div class="recipe-card" onclick="openRecipe('<?php echo $row['id']; ?>')">
                        <div class="card-image-wrapper">
                            <img src="../explore/explore-images/<?php echo htmlspecialchars($row['image']); ?>">
                            <span class="badge <?php echo strtolower($row['difficulty']); ?>">
                                <?php echo ucfirst($row['difficulty']); ?>
                            </span>
                        </div>

                        <div class="card-info">
                            <div class="meta-category">
                                <?php echo htmlspecialchars($row['category']); ?>
                            </div>

                            <h4>
                                <?php echo htmlspecialchars($row['title']); ?>
                            </h4>

                            <div class="meta-details">
                                <span>
                                    <i class="fa-regular fa-clock"></i>
                                    <?php echo htmlspecialchars($row['total_time']); ?> mins
                                </span>

                                <span>
                                    <i class="fa-solid fa-user-group"></i>
                                    <?php echo htmlspecialchars($row['servings']); ?> Servings
                                </span>

                                <span>
                                    <i class="fa-solid fa-star star-yellow"></i>
                                    <?php echo number_format($row['avg_rating'], 1); ?>
                                </span>
                            </div>

                            <hr class="divider">

                            <div class="card-author">
                                <?php if(!empty($row['profile_pic'])): ?>
                                    <img src="<?php echo htmlspecialchars($row['profile_pic']); ?>" class="author-img">
                                <?php else: ?>
                                    <div class="user-initials" style="background-color: <?php echo $bgColor; ?>;">
                                        <?php
                                            echo strtoupper(
                                                substr($row['first_name'], 0, 1) .
                                                substr($row['last_name'], 0, 1)
                                            );
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <span class="author-name">
                                    by <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>        
        </div>
    </section>

    <!-- Modal Elements remain unchanged for application stability -->
    <div class="modal-overlay" id="recipeModal">
        <div class="modal-content">
            <button class="close-btn" onclick="closeModal()">&times;</button>
            <div class="modal-banner-image"><img id="modalBanner" src=""></div>
            <div class="modal-body-wrapper">
                <div class="recipe-tags" id="modalTags">
                    <span class="tag-filled" id="modalCategory">Dinner</span>
                    <span class="tag-outline" id="modalDifficulty">Easy</span>
                    <button class="favorite-btn" id="modalFavoriteBtn" onclick="toggleFavorite()"><i class="fa-regular fa-bookmark"></i></button>
                </div>
                <div class="recipe-intro">
                    <h1 id="modalTitle">Apple Pie</h1>
                    <p id="modalDescription" class="description-text">Test</p>
                </div>
                <div class="meta-author-row">
                    <div class="author-info">
                        <div id="modalAuthorAvatar" class="user-avatar-circle"></div>
                        <div class="author-details-text">
                            <span class="author-name" id="modalAuthorName">Beyoncé</span>
                            <span class="review-count" id="modalReviewCount">234 reviews</span>
                        </div>
                    </div>
                    <div class="rating-display-top"><i class="fa-solid fa-star star-yellow"></i><span id="modalAverageRatingValue" class="avg-rating-number">4.8</span></div>
                </div>
                <div class="stats-grid">
                    <div class="stat-card"><span class="stat-label">PREP TIME</span><span class="stat-value" id="modalPrepTime"></span></div>
                    <div class="stat-card"><span class="stat-label">COOK TIME</span><span class="stat-value" id="modalCookTime"></span></div>
                    <div class="stat-card"><span class="stat-label">TOTAL TIME</span><span class="stat-value" id="modalTotalTime"></span></div>
                    <div class="stat-card"><span class="stat-label">SERVINGS</span><span class="stat-value" id="modalServings"></span></div>
                </div>
                <div class="recipe-content-layout">
                    <div class="recipe-sidebar">
                        <div class="ingredients-card"><h3>Ingredients</h3><ul id="modalIngredients"></ul></div>
                        <div class="rate-recipe-card">
                            <h4>Rate this Recipe</h4>
                            <div class="big-rating-display"><span class="total-ratings-count">0 ratings</span></div>
                            <div class="interactive-stars" id="starContainer">
                                <i class="fa-regular fa-star" data-value="1"></i>
                                <i class="fa-regular fa-star" data-value="2"></i>
                                <i class="fa-regular fa-star" data-value="3"></i>
                                <i class="fa-regular fa-star" data-value="4"></i>
                                <i class="fa-regular fa-star" data-value="5"></i>
                            </div>
                        </div>
                    </div>
                    <div class="recipe-main"><h3>Instructions</h3><div id="modalInstructions"></div></div>
                </div>
                <div class="comments-section">
                    <h3>Comments</h3>
                    <div class="comment-input-area">
                        <div class="avatar-placeholder">
                            <?php if(!empty($profile_pic)): ?>
                                <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile">
                            <?php else: ?>
                                <?php echo $initials; ?>
                            <?php endif; ?>
                        </div>
                        <div class="input-wrapper"><input type="text" id="commentInput" placeholder="Add a comment..."></div>
                        <button class="post-btn" id="postCommentBtn">Post</button>
                    </div>
                    <div id="commentsList" class="comments-list"></div>
                </div>
            </div>
        </div>
    </div>

    <section class="container">
        <div class="banner">
            <div class="content">
                <h2>Share your culinary creations</h2>
                <p>Join thousands of home cooks sharing their favorite recipes with the world.</p>
                <a href="../add-recipe/add.php" class="btn">Add Your Recipes</a>
            </div>
        </div>
    </section>
    
    <hr class="footer-divider">
    
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-left">
                <div class="logo-footer">
                    <img src="../images/recipe-logo-removebg-preview.png" alt="Savory logo">
                </div>
                <span class="brand-name">Fatikem</span>
            </div>
            <div class="footer-right">© 2025 Fatikem. All rights reserved.</div>
        </div>
    </footer>

    <script src="../log-in/login.js"></script>
    <script>const recipesById = <?php echo json_encode(array_column($recipes, null, 'id')); ?>;</script>
    <script src="mainpage.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
</body>
</html>