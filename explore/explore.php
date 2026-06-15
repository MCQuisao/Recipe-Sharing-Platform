<?php
session_start();
require_once('../config/db.php'); 

$profile_pic = $_SESSION['profile_pic'] ?? ''; 

$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$initials = ($first_name || $last_name) ? strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)) : '';
$bgColor = sprintf('#%02X%02X%02X', rand(100, 255), rand(100, 255), rand(100, 255));

try {
    $query = "SELECT r.*, 
        u.first_name, u.last_name, u.profile_pic,
        (SELECT COALESCE(AVG(rating), 0) FROM recipe_reviews WHERE recipe_id = r.id AND rating IS NOT NULL) as avg_rating,
        (SELECT COUNT(rating) FROM recipe_reviews WHERE recipe_id = r.id AND rating IS NOT NULL) as total_reviews,
        
        (SELECT STRING_AGG(ingredient_text, '||') FROM recipe_ingredients WHERE recipe_id = r.id) as ingredients_list,
        (SELECT STRING_AGG(instruction_text, '||' ORDER BY step_number) FROM recipe_instructions WHERE recipe_id = r.id) as instructions_list
    FROM recipes r
    JOIN users u ON r.author_id = u.id
    WHERE r.status = 'approved'
    ORDER BY r.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore • Fatikem</title>
    <link rel="icon" href="../images/recipe-logo-removebg-preview.png" type="image/png">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sura&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Poppins:wght=300;400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="explore.css">
    <link rel="stylesheet" href="../main-page/modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="nav-bar">
        <div class="logo">
            <a href="../main-page/mainpage.php">
                <img src="../images/recipe-logo-removebg-preview.png" alt="Fatikem Logo" class="logo-img">
            </a>
            <span class="brand-name">Fatikem</span>
        </div>
        <nav class="nav-links">
            <a href="../main-page/mainpage.php">Home</a>
            <a href="explore.php" class="active">Explore</a>
            <a href="../cuisine/cuisine.php">Cuisine</a>
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

    <section class="explore-section">
        <div class="container">
            <header>
                <h1>Explore Recipes</h1>
                <p>Discover delicious recipes from around the world</p>
            </header>

            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="searchInput" class="search-input" placeholder="Search recipes...">
            </div>

            <div class="filter-container">
                <button class="filter-btn active" data-category="all">🍽️ All Recipes</button>
                <button class="filter-btn" data-category="breakfast">🥞 Breakfast</button>
                <button class="filter-btn" data-category="lunch">🥗 Lunch</button>
                <button class="filter-btn" data-category="dinner">🍝 Dinner</button>
                <button class="filter-btn" data-category="dessert">🍰 Dessert</button>
            </div>

            <div class="recipe-grid">
                <?php foreach ($recipes as $row): ?>
                    <div class="recipe-card" onclick="openRecipe('<?php echo $row['id']; ?>')">
                        <div class="card-image-wrapper">
                            <img src="../explore/explore-images/<?php echo htmlspecialchars($row['image']); ?>">
                            <span class="badge <?php echo strtolower($row['difficulty']); ?>"><?php echo ucfirst($row['difficulty']); ?></span>
                        </div>
                        <div class="card-info">
                            <div class="meta-category"><?php echo htmlspecialchars($row['category']); ?></div>
                            <h4><?php echo htmlspecialchars($row['title']); ?></h4>
                            <div class="meta-details">
                                <span><i class="fa-regular fa-clock"></i> <?php echo $row['total_time']; ?> mins</span>
                                <span><i class="fa-solid fa-user-group"></i> <?php echo $row['servings']; ?></span>
                                
                                <span class="card-rating">
                                    <i class="fa-solid fa-star star-yellow"></i> 
                                    <?php echo number_format($row['avg_rating'], 1); ?> 
                                    <small>(<?php echo $row['total_reviews']; ?>)</small>
                                </span>
                            </div>
                            <hr class="divider">
                            <div class="card-author">
                                <?php if(!empty($row['profile_pic'])): ?>
                                    <img src="<?php echo htmlspecialchars($row['profile_pic']); ?>" alt="Author" class="author-img-circle">
                                <?php else: ?>
                                    <div class="initials-circle" style="background-color: <?php echo $bgColor; ?>; display: flex;">
                                        <?php echo strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <span class="author-name">by <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div id="noResultsMessage" class="no-results" style="display: none;">
                    <i class="fa-solid fa-face-frown"></i>
                    <p>No recipes found.</p>
                </div>
            </div>
            <div class="pagination-container">
                <button id="prevPage" class="page-btn">Previous</button>
                <span id="pageInfo">Page 1 of 1</span>
                <button id="nextPage" class="page-btn">Next</button>
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

    <footer class="site-footer">
        <div class="footer-container">
            <span>© 2026 Fatikem. All rights reserved.</span>
        </div>
    </footer>

    <script>const recipesById = <?php echo json_encode(array_column($recipes, null, 'id')); ?>;</script>
    <script src="explore.js"></script>
</body>
</html>