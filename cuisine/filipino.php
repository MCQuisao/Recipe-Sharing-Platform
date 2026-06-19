<?php
session_start();
require_once('../config/db.php'); 

// Fetch current session properties for user layout tracking
$profile_pic = $_SESSION['profile_pic'] ?? ''; 
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$initials = ($first_name || $last_name) ? strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)) : '';
$bgColor = sprintf('#%02X%02X%02X', rand(100, 255), rand(100, 255), rand(100, 255));

// Safe space-encoded URL builder for the session user's profile image
$sessionUserImg = '';
if (!empty($profile_pic)) {
    if (strpos($profile_pic, '/') !== false) {
        $sessionSegments = explode('/', $profile_pic);
        $encodedSessionSegments = array_map('rawurlencode', $sessionSegments);
        $sessionUserImg = str_replace('%2E%2E', '..', implode('/', $encodedSessionSegments));
    } else {
        $sessionUserImg = '../images/' . rawurlencode($profile_pic);
    }
}

try {
    // Dynamically retrieve Filipino recipes from database table rows
    $query = "SELECT r.*, 
        u.first_name, u.last_name, u.profile_pic,
        c.name as cuisine_name,
        (SELECT COALESCE(AVG(rating), 0) FROM recipe_reviews WHERE recipe_id = r.id AND rating IS NOT NULL) as avg_rating,
        (SELECT COUNT(rating) FROM recipe_reviews WHERE recipe_id = r.id AND rating IS NOT NULL) as total_reviews,
        (SELECT STRING_AGG(ingredient_text, '||') FROM recipe_ingredients WHERE recipe_id = r.id) as ingredients_list,
        (SELECT STRING_AGG(instruction_text, '||' ORDER BY step_number) FROM recipe_instructions WHERE recipe_id = r.id) as instructions_list
    FROM recipes r
    JOIN users u ON r.author_id = u.id
    JOIN cuisines c ON r.cuisine_id = c.id
    WHERE r.status = 'approved' AND LOWER(c.name) = 'filipino'
    ORDER BY r.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database communication error encountered: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuisine • Philippines</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sura:wght@400;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../main-page/modal.css">
    <link rel="stylesheet" href="filipino.css">
    <link class="site-favicon" rel="icon" href="../images/recipe-logo-removebg-preview.png" type="image/png">
</head>
<body>

    <header class="nav-bar">
        <div class="logo">
            <a href="../main-page/mainpage.php">
                <img src="../images/recipe-logo-removebg-preview.png" alt="Fatikem" class="logo-img">
            </a>
            <span class="brand-name">Fatikem</span>
        </div>

        <nav class="nav-links">
            <a href="../main-page/mainpage.php">Home</a>
            <a href="../explore/explore.php">Explore</a>
            <a href="../cuisine/cuisine.php" class="active">Cuisine</a>
        </nav>

        <div class="nav-actions">
            <button class="add-recipe-btn" onclick="window.location.href='../add-recipe/add.php'">
                <span class="plus-icon">+</span>
                Add Recipe
            </button>
            <div class="profile-icon" onclick="window.location.href='../profile/profile.php'">
                <?php if (!empty($sessionUserImg)): ?>
                    <img src="<?php echo htmlspecialchars($sessionUserImg); ?>" alt="Profile" class="profile-img">
                <?php else: ?>
                    <div class="user-initials" style="background-color: <?php echo $bgColor; ?>;">
                        <?php echo htmlspecialchars($initials); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <section class="country-hero">
        <div class="tagalog-background">Lutong Pinoy</div>
        <div class="hero-content">
            <h1 class="hero-main-title">Philippines</h1>
            <p class="hero-subtitle">Pearl of the Orient</p>
            <p class="hero-desc">A bold fusion of sweet, sour, and salty flavors, bringing warmth and hospitality to every table.</p>
            <a href="../cuisine/inuman-mode.html" class="twist-btn">
                <i class="fa-solid fa-beer-mug-empty"></i> Enter Inuman Mode
            </a>
        </div>
    </section>

    <section class="explore-section">
        <div class="recipe-grid">
            <?php foreach ($recipes as $row): 
                // Fix recipe image layout containing spaces
                if (!empty($row['image'])) {
                    if (strpos($row['image'], '/') !== false) {
                        $pathSegments = explode('/', $row['image']);
                        $encodedSegments = array_map('rawurlencode', $pathSegments);
                        $cardImg = str_replace('%2E%2E', '..', implode('/', $encodedSegments));
                    } else {
                        $cardImg = '../cuisine/Filipino Image/' . rawurlencode($row['image']);
                    }
                } else {
                    $cardImg = '../images/recipe-placeholder.jpg';
                }

                // Fix author layout containing spaces
                if (!empty($row['profile_pic'])) {
                    if (strpos($row['profile_pic'], '/') !== false) {
                        $authorSegments = explode('/', $row['profile_pic']);
                        $encodedAuthorSegments = array_map('rawurlencode', $authorSegments);
                        $authorImg = str_replace('%2E%2E', '..', implode('/', $encodedAuthorSegments));
                    } else {
                        $authorImg = '../images/' . rawurlencode($row['profile_pic']);
                    }
                } else {
                    $authorImg = '../images/default-avatar.png';
                }

                $ratingDisplay = number_format($row['avg_rating'], 1);
                
                $diff = strtolower($row['difficulty'] ?? 'easy');
                $badgeStyle = "background:#fde0e0; color:#b94040;";
                if ($diff === 'medium') {
                    $badgeStyle = "background:#e0f3e6; color:#4a7a58;";
                } elseif ($diff === 'hard') {
                    $badgeStyle = "background:#FDF2D0; color:#b89209;";
                }
            ?>
                <div class="recipe-card" data-id="<?php echo $row['id']; ?>" onclick="openRecipe('<?php echo $row['id']; ?>')">
                    <div class="card-image-wrapper">
                        <img src="<?php echo $cardImg; ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
                        <button class="bookmark-btn" onclick="event.stopPropagation(); toggleBookmark('<?php echo $row['id']; ?>')">
                            <img src="../images/save-active.svg" alt="Save">
                        </button>
                        <span class="badge" style="<?php echo $badgeStyle; ?>">
                            <?php echo htmlspecialchars($row['difficulty'] ?? 'Easy'); ?>
                        </span>
                    </div>
                    <div class="card-info">
                        <div class="meta-category"><?php echo htmlspecialchars($row['category'] ?? 'Main Dish'); ?></div>
                        <h4><?php echo htmlspecialchars($row['title']); ?></h4>
                        <div class="meta-details">
                            <span><img src="../images/clock-three.svg" alt=""> <?php echo htmlspecialchars($row['total_time']); ?> mins</span>
                            <span><img src="../images/users.svg" alt=""> <?php echo htmlspecialchars($row['servings']); ?> Servings</span>
                            <span><img src="../images/star-solid-full.svg" alt=""> <?php echo $ratingDisplay; ?></span>
                        </div>
                        <div class="divider"></div>
                        <div class="card-author">
                            <img src="<?php echo $authorImg; ?>" class="author-img" alt="Author Profile Image">
                            <span class="author-name">by <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="modal-overlay" id="recipeModal">
        <div class="modal-content">
            <button class="close-btn" id="closeModalBtn" onclick="closeRecipe()">&times;</button>
            <div class="modal-banner-image">
                <img id="modalBanner" src="" alt="Recipe Detail Image">
            </div>
            
            <div class="modal-body-wrapper">
                <div class="recipe-tags" id="modalTags">
                    <span class="tag-filled" id="modalCategoryBadge">Category</span>
                    <span class="tag-outline" id="modalDifficultyBadge">Difficulty</span>
                    <button class="favorite-btn" id="modalFavoriteBtn" onclick="toggleFavorite()">
                        <i class="fa-regular fa-bookmark"></i>
                    </button>
                </div>
                
                <div class="recipe-intro">
                    <h1 id="modalTitle">Recipe Title Placeholder</h1>
                    <p class="description-text" id="modalDescription">Recipe Description details display configuration</p>
                </div>
                
                <div class="meta-author-row">
                    <div class="author-info">
                        <div id="modalAuthorAvatar" class="user-avatar-circle"></div>
                        <div class="author-details-text">
                            <span class="author-name" id="modalAuthorName">Author Name</span>
                            <span class="review-count" id="modalReviewCount">0 reviews</span>
                        </div>
                    </div>
                    <div class="rating-display-top">
                        <i class="fa-solid fa-star star-yellow"></i>
                        <span id="modalAverageRatingValue" class="avg-rating-number">0.0</span>
                    </div>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card"><span class="stat-label">PREP TIME</span><span class="stat-value" id="modalPrepTime">0 mins</span></div>
                    <div class="stat-card"><span class="stat-label">COOK TIME</span><span class="stat-value" id="modalCookTime">0 mins</span></div>
                    <div class="stat-card"><span class="stat-label">TOTAL TIME</span><span class="stat-value" id="modalTotalTime">0 mins</span></div>
                    <div class="stat-card"><span class="stat-label">SERVINGS</span><span class="stat-value" id="modalServings">0</span></div>
                </div>
                
                <div class="recipe-content-layout">
                    <div class="recipe-sidebar">
                        <div class="ingredients-card">
                            <h3>Ingredients</h3>
                            <ul class="ingredients-list-style" id="modalIngredients"></ul>
                        </div>
                        <div class="rate-recipe-card">
                            <h4>Rate this Recipe</h4>
                            <div class="big-rating-display"><span class="total-ratings-count">0 ratings</span></div>
                            <div class="interactive-stars" id="starContainer">
                                <i class="fa-regular fa-star rating-star" data-value="1" data-index="1"></i>
                                <i class="fa-regular fa-star rating-star" data-value="2" data-index="2"></i>
                                <i class="fa-regular fa-star rating-star" data-value="3" data-index="3"></i>
                                <i class="fa-regular fa-star rating-star" data-value="4" data-index="4"></i>
                                <i class="fa-regular fa-star rating-star" data-value="5" data-index="5"></i>
                            </div>
                            <span class="rating-status-hint" id="ratingTextSummary" style="display: block; margin-top: 10px;">Select your review rating to save</span>
                        </div>
                    </div>
                    
                    <div class="recipe-main">
                        <h3>Instructions</h3>
                        <div id="modalInstructions" class="instructions-wrapper"></div>
                    </div>
                </div>

                <div class="comments-section">
                    <h3>Comments (<span id="commentCount">0</span>)</h3>
                    <div class="comment-input-area">
                        <div class="avatar-placeholder">
                            <?php if(!empty($sessionUserImg)): ?>
                                <img src="<?php echo htmlspecialchars($sessionUserImg); ?>" alt="User Avatar">
                            <?php else: ?>
                                <?php echo htmlspecialchars($initials); ?>
                            <?php endif; ?>
                        </div>
                        <div class="input-wrapper">
                            <input type="text" id="commentInput" placeholder="Add a comment to the community stream...">
                        </div>
                        <button class="post-btn" id="postCommentBtn" disabled>Post</button>
                    </div>
                    <div id="commentsList" class="comments-list"></div>
                </div>
            </div>
        </div>
    </div>

    <footer class="site-footer">
        <div class="footer-container">
            <span>© 2026 Fatikem. All rights reserved.</span>
        </div>
    </footer>

    <script>
        const recipesById = <?php echo json_encode(array_column($recipes, null, 'id')); ?>;
    </script>
    <script src="filipino.js"></script>
</body>
</html>
