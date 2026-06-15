<?php
session_start();
require_once('../config/db.php'); 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../sign up/singup.html");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // 1. FETCH USER PROFILE DATA FRESH FROM THE DATABASE
    $user_stmt = $pdo->prepare("SELECT first_name, last_name, bio, profile_pic FROM users WHERE id = :user_id");
    $user_stmt->execute(['user_id' => $user_id]);
    $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

    // Fallback to session data if the columns don't exist yet, otherwise use database values
    $first_name = $user_data['first_name'] ?? $_SESSION['first_name'] ?? '';
    $last_name = $user_data['last_name'] ?? $_SESSION['last_name'] ?? '';
    $bio = $user_data['bio'] ?? '';
    $profile_pic = $user_data['profile_pic'] ?? $_SESSION['profile_pic'] ?? ''; 

    $initials = '';
    if ($first_name || $last_name) {
        $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
    }

    // 2. FETCH RECIPES AND FAVORITES
    $query = "SELECT r.id, r.title, r.image, r.total_time, r.prep_time, r.cook_time, r.difficulty, r.description, r.category, r.servings,
            (SELECT string_agg(ingredient_text, '||' ORDER BY id) 
                FROM recipe_ingredients 
                WHERE recipe_id = r.id) as ingredients_list,
            (SELECT string_agg(instruction_text, '||' ORDER BY step_number) 
                FROM recipe_instructions 
                WHERE recipe_id = r.id) as instructions_list
            FROM recipes r
            WHERE r.author_id = :user_id 
            ORDER BY r.created_at DESC";

    $fav_query = "SELECT r.id, r.title, r.image, r.total_time, r.difficulty, r.category, r.servings
                  FROM recipes r
                  JOIN favorites f ON r.id = f.recipe_id
                  WHERE f.user_id = :user_id 
                  ORDER BY f.created_at DESC";
                  
    $fav_stmt = $pdo->prepare($fav_query);
    $fav_stmt->execute(['user_id' => $user_id]);
    $favoriteRecipes = $fav_stmt->fetchAll(PDO::FETCH_ASSOC);
        
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $myRecipes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    $myRecipes = [];
    $favoriteRecipes = [];
    
    // Fallback variables in case database query crashes
    $first_name = $_SESSION['first_name'] ?? '';
    $last_name = $_SESSION['last_name'] ?? '';
    $bio = $_SESSION['bio'] ?? '';
    $profile_pic = $_SESSION['profile_pic'] ?? '';
    $initials = '';
    if ($first_name || $last_name) {
        $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
    }
}

$bgColor = sprintf('#%02X%02X%02X', rand(100, 255), rand(100, 255), rand(100, 255));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile • Fatikem</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="profile.css">
</head>
<body>
    
    <header class="nav-bar">
        <div class="logo">
            <a href="../main-page/mainpage.html">
                <img src="../images/recipe-logo-removebg-preview.png" alt="" class="logo-img">
            </a>
            <span class="brand-name">Fatikem</span>
        </div>

        <nav class="nav-links">
            <a href="../main-page/mainpage.php" class="active">Home</a>
            <a href="../explore/explore.php">Explore</a>
            <a href="../cuisine/cuisine.html">Cuisine</a>
        </nav>

        <div class="nav-actions">
            <a href="../add-recipe/add.php"><button class="add-recipe-btn">
                <span class="plus-icon">+</span>
                Add Recipe
            </button></a>
            <div class="profile-icon">
                <?php if(!empty($profile_pic)): ?>
                    <img src="<?php echo $profile_pic; ?>" alt="Profile" class="profile-img">
                <?php elseif($initials): ?>
                    <div class="user-initials" style="background-color: <?php echo $bgColor; ?>;">
                        <a href="profile.php">
                            <?php echo $initials; ?>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="../sign up/singup.html">
                        <img src="../images/iconamoon_profile-fill.svg" alt="Login" style="cursor: pointer;">
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="main-layout">
        
        <aside class="profile-sidebar">
            <div class="profile-card">
                <div class="avatar-large" onclick="triggerUpload()">
                    <?php if(!empty($profile_pic)): ?>
                        <img id="current-avatar" src="<?php echo $profile_pic; ?>" alt="Avatar">
                    <?php else: ?>
                        <div id="current-avatar-placeholder" class="user-initials-large" 
                            style="background-color: <?php echo $bgColor; ?>; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: white;">
                            <?php echo $initials; ?>
                        </div>
                    <?php endif; ?>
                    <div class="edit-overlay"><i class="fa-solid fa-camera"></i></div>
                </div>

                <input type="file" id="file-upload" style="display: none;" accept="image/*" onchange="handleImageUpload(this)">

                <div class="profile-info" id="profile-view">
                    <h1 id="display-name">
                        <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>
                    </h1>
                    
                    <p id="display-bio">
                        <?php echo htmlspecialchars(!empty($bio) ? $bio : 'Creating flavors and exploring the world, one recipe at a time.'); ?>
                    </p>
                    
                    <button class="btn-edit-profile" onclick="toggleEditMode()">
                        Edit Profile
                    </button>
                </div>

                <div id="profile-edit" class="profile-edit-form hidden">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" id="input-first-name" class="clean-input" 
                            value="<?php echo htmlspecialchars($first_name); ?>">
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" id="input-last-name" class="clean-input" 
                            value="<?php echo htmlspecialchars($last_name); ?>">
                    </div>
                    <div class="form-group">
                        <label>Bio</label>
                        <textarea id="input-bio" rows="3" class="clean-input"><?php echo htmlspecialchars($bio); ?></textarea>
                    </div>
                    <div class="edit-buttons">
                        <button type="button" class="btn-small-cancel" onclick="toggleEditMode()">Cancel</button>
                        <button type="button" class="btn-small-save" onclick="saveProfile()">Save</button>
                    </div>
                </div>

                <div class="stats-row">
                    <div class="stat-box">
                        <span class="stat-num"><?php echo count($myRecipes); ?></span>
                        <span class="stat-lbl">Recipes</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-num"><?php echo count($favoriteRecipes); ?></span>
                        <span class="stat-lbl">Saved</span>
                    </div>
                </div>
                <div class="stat-box">
                    <a href="../auth/logout.php" style="text-decoration: none;">
                        <button class="btn-signout">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span class="stat-lbl">Sign Out</span>
                        </button>
                    </a>
                </div>
            </div>
        </aside>

        <main class="content-area">
            <div class="tabs-header">
                <button class="tab-btn active" onclick="switchTab('my-recipes', this)">My Recipes</button>
                <button class="tab-btn" onclick="switchTab('favorites', this)">Favorites</button>
            </div>

            <div id="my-recipes" class="tab-content">
                <?php if (empty($myRecipes)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fa-solid fa-utensils"></i></div>
                        <h3>No recipes yet</h3>
                        <p>Share your first culinary masterpiece with the world.</p>
                        <a href="../add-recipe/add.html" class="btn-primary">Create Recipe</a>
                    </div>
                <?php else: ?>
                    <div class="recipe-grid">
                        <?php foreach ($myRecipes as $recipe): ?>
                            <div class="recipe-card" onclick="openViewModal(<?php echo $recipe['id']; ?>)">
                                <div class="card-image-wrapper">
                                    <img src="../explore/explore-images/<?php echo htmlspecialchars($recipe['image']); ?>" alt="">
                                    
                                    <span class="badge <?php echo strtolower($recipe['difficulty']); ?>">
                                        <?php echo ucfirst($recipe['difficulty']); ?>
                                    </span>

                                    <button onclick="event.stopPropagation(); openEditModal(<?php echo $recipe['id']; ?>)" class="edit-btn">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                </div>

                                <div class="card-info">
                                    <div class="meta-category"><?php echo htmlspecialchars($recipe['category'] ?? 'General'); ?></div>
                                    
                                    <h4><?php echo htmlspecialchars($recipe['title']); ?></h4>
                                    
                                    <div class="meta-details">
                                        <span><i class="fa-regular fa-clock"></i> <?php echo $recipe['total_time']; ?>m</span>
                                        <span><i class="fa-solid fa-user-group"></i> <?php echo $recipe['servings'] ?? '1'; ?></span>
                                    </div>

                                    <hr class="divider">

                                    <div class="card-author">
                                        <?php if(!empty($profile_pic)): ?>
                                            <img src="<?php echo $profile_pic; ?>" alt="Author" class="author-img-circle">
                                        <?php else: ?>
                                            <div class="initials-circle" style="background-color: <?php echo $bgColor; ?>;">
                                                <?php echo $initials; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <span class="author-name">by <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div id="favorites" class="tab-content" style="display: none;">
                <?php if (empty($favoriteRecipes)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fa-regular fa-heart"></i></div>
                        <h3>No favorites yet</h3>
                        <p>Explore recipes and save the ones you love!</p>
                        <a href="../explore/explore.php" class="btn-primary">Explore Recipes</a>
                    </div>
                <?php else: ?>
                    <div class="recipe-grid">
                        <?php foreach ($favoriteRecipes as $fav): ?>
                            <div class="recipe-card" onclick="openViewModal(<?php echo $fav['id']; ?>)">
                                <div class="card-image-wrapper">
                                    <img src="../explore/explore-images/<?php echo htmlspecialchars($fav['image']); ?>" alt="">
                                    
                                    <span class="badge <?php echo strtolower($fav['difficulty']); ?>">
                                        <?php echo ucfirst($fav['difficulty']); ?>
                                    </span>
                                    
                                    </div>

                                <div class="card-info">
                                    <div class="meta-category"><?php echo htmlspecialchars($fav['category'] ?? 'General'); ?></div>
                                    
                                    <h4><?php echo htmlspecialchars($fav['title']); ?></h4>
                                    
                                    <div class="meta-details">
                                        <span><i class="fa-regular fa-clock"></i> <?php echo $fav['total_time']; ?>m</span>
                                        <span><i class="fa-solid fa-user-group"></i> <?php echo $fav['servings'] ?? '1'; ?></span>
                                    </div>

                                    <hr class="divider">

                                    <div class="card-author">
                                        <span class="author-name">View Recipe Details</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div id="toast-container"></div>

    <script src="profile.js"></script>

    <div id="editModal" class="modal">
        <div class="modal-content-wrapper">
            <div class="form-header">
                <span class="close-modal" onclick="closeEditModal()">&times;</span>
                <h1>Edit Recipe</h1>
            </div>

            <form id="editRecipeForm">
                <input type="hidden" id="edit-recipe-id">
                
                <section class="form-card">
                    <div class="card-header">
                        <i class="fa-solid fa-list-check"></i>
                        <h2>Basic Information</h2>
                    </div>
                    <div class="form-group">
                        <label>Recipe Title</label>
                        <input type="text" id="edit-title" class="input-field" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="edit-description" rows="3" class="input-field"></textarea>
                    </div>

                    <div class="row-4" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                        <div class="form-group">
                            <label>Prep Time (m)</label>
                            <input type="number" id="edit-prep-time" min="1" onkeydown="if(event.key==='-') event.preventDefault()" class="input-field">
                        </div>
                        <div class="form-group">
                            <label>Cook Time (m)</label>
                            <input type="number" id="edit-cook-time" min="1" onkeydown="if(event.key==='-') event.preventDefault()" class="input-field">
                        </div>
                        <div class="form-group">
                            <label>Servings</label>
                            <input type="number" id="edit-servings" min="1" onkeydown="if(event.key==='-') event.preventDefault()" class="input-field">
                        </div>
                        <div class="form-group">
                            <label>Difficulty</label>
                            <select id="edit-difficulty" class="input-field">
                                <option value="Easy">Easy</option>
                                <option value="Medium">Medium</option>
                                <option value="Hard">Hard</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select id="edit-category" class="input-field">
                                <option value="Breakfast">Breakfast</option>
                                <option value="Lunch">Lunch</option>
                                <option value="Dinner">Dinner</option>
                                <option value="Dessert">Dessert</option>
                            </select>
                        </div>
                    </div>
                </section>

                <section class="form-card">
                    <div class="card-header">
                        <i class="fa-solid fa-basket-shopping"></i>
                        <h2>Ingredients</h2>
                    </div>
                    <div id="edit-ingredients-list"></div>
                    <button type="button" class="btn-add-item" onclick="addEditListItem('ingredient', '')">
                        <i class="fa-solid fa-plus"></i> Add Ingredient
                    </button>
                </section>

                <section class="form-card">
                    <div class="card-header">
                        <i class="fa-solid fa-fire-burner"></i>
                        <h2>Instructions</h2>
                    </div>
                    <div id="edit-instructions-list"></div>
                    <button type="button" class="btn-add-item" onclick="addEditListItem('instruction', '')">
                        <i class="fa-solid fa-plus"></i> Add Step
                    </button>
                </section>

                <div class="form-actions">
                    <div class="right-actions">
                        <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                        
                        <button type="button" class="btn-delete-recipe" onclick="deleteRecipe()">
                            <i class="fa-solid fa-trash-can"></i> Delete
                        </button>

                        <button type="submit" class="btn-submit">Update Recipe</button>
                    </div>
                </div>  
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="recipeModal">
        <div class="modal-content">
            <button class="close-btn" onclick="closeModal()">&times;</button>
            
            <div class="modal-banner-image">
                <img id="modalBanner" src="" alt="Recipe Banner">
            </div>

            <div class="recipe-intro">
                <h1 id="modalTitle"></h1> <p id="modalDescription"></p> </div>

            <div class="stats-row">
                <div class="stat-box"><span class="label">Prep</span><span class="value" id="modalPrepTime"></span></div>
                <div class="stat-box"><span class="label">Cook</span><span class="value" id="modalCookTime"></span></div>
                <div class="stat-box"><span class="label">Total</span><span class="value" id="modalTotalTime"></span></div>
                <div class="stat-box"><span class="label">Servings</span><span class="value" id="modalServings"></span></div>
            </div>

            <div class="modal-body">
                <div class="ingredients-card">
                    <h3>Ingredients</h3>
                    <ul id="modalIngredients"></ul> </div>
                <div class="instructions">
                    <h3>Instructions</h3>
                    <div id="modalInstructions"></div> </div>
            </div>
        </div>
    </div>
</body>
</html>