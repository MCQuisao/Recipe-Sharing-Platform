<?php
session_start();

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
    <title>Add Recipe • Fatikem</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="add.css">
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
            <button class="add-recipe-btn">
                <span class="plus-icon">+</span>
                Add Recipe
            </button>
            <div class="profile-icon">
                <?php if ($initials): ?>
                    <div class="user-initials" style="background-color: <?= $bgColor ?>">
                        <a href="../profile/profile.php">
                            <?php echo $initials; ?>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="../sign up/singup.html">
                        <img src="../images/iconamoon_profile-fill.svg" alt="Login">
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <div class="bg-gradient"></div>

    <div class="page-wrapper">
        <header class="form-header">
            <h1>Add New Recipe</h1>
            <p>Share your culinary creation with the community</p>
        </header>

    <form class="recipe-form" id="recipeForm" onsubmit="event.preventDefault(); submitRecipe();"> 
            <section class="form-card">
                <div class="card-header">
                    <i class="fa-solid fa-list-check"></i>
                    <h2>Basic Information</h2>
                </div>
                
                <div class="form-group">
                    <label>Recipe Title <span class="required">*</span></label>
                    <input type="text" id="recipe-title" placeholder="e.g., Grandma's Apple Pie" class="input-field">
                </div>

                <div class="form-group">
                    <label>Description <span class="required">*</span></label>
                    <textarea placeholder="Tell us the story behind this dish..." rows="4" class="input-field"></textarea>
                </div>

                <div class="row-2">
                    <div class="form-group">
                        <label>Category <span class="required">*</span></label>
                        <div class="select-wrapper">
                            <select class="input-field" id="category" required>
                                <option value="" disabled selected>Select category</option>
                                <option value="Breakfast">Breakfast</option>
                                <option value="Lunch">Lunch</option>
                                <option value="Dinner">Dinner</option>
                                <option value="Dessert">Dessert</option>
                            </select>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Difficulty</label>
                        <div class="select-wrapper">
                            <select class="input-field">
                                <option>Easy</option>
                                <option selected>Medium</option>
                                <option>Hard</option>
                            </select>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                    </div>
                </div>

                <div class="row-3">
                    <div class="form-group">
                        <label>Prep Time (min)</label>
                        <input type="number" value="15" min="0" onkeydown="if(event.key==='-') event.preventDefault()" class="input-field">
                    </div>
                    <div class="form-group">
                        <label>Cook Time (min)</label>
                        <input type="number" value="30" min="0" onkeydown="if(event.key==='-') event.preventDefault()" class="input-field">
                    </div>
                    <div class="form-group">
                        <label>Servings</label>
                        <input type="number" value="4" min="0" onkeydown="if(event.key==='-') event.preventDefault()" class="input-field">
                    </div>
                </div>

                <div class="form-group">
                    <label>Recipe Image</label>
                    <div class="image-upload-container" onclick="document.getElementById('recipe-file-input').click()">
                        <input type="file" id="recipe-file-input" hidden accept="image/*">
                        
                        <div id="upload-placeholder">
                            <div class="icon-box">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </div>
                            <p><strong>Click to upload</strong> or drag and drop</p>
                            <span class="sub-text">SVG, PNG, JPG (max. 3MB)</span>
                        </div>

                        <img id="image-preview" class="hidden" alt="Recipe Preview">
                        <button type="button" id="remove-image-btn" class="hidden" onclick="removeImage(event)">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
            </section>

            <section class="form-card">
                <div class="card-header">
                    <i class="fa-solid fa-basket-shopping"></i>
                    <h2>Ingredients</h2>
                </div>
                <div id="ingredients-list">
                    <div class="list-item">
                        <div class="drag-handle"><i class="fa-solid fa-grip-lines"></i></div>
                        <input type="text" placeholder="e.g., 2 cups flour" class="input-field">
                        <button type="button" class="btn-delete" onclick="this.parentElement.remove()">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn-add-item" onclick="addIngredient()">
                    <i class="fa-solid fa-plus"></i> Add Ingredient
                </button>
            </section>

            <section class="form-card">
                <div class="card-header">
                    <i class="fa-solid fa-fire-burner"></i>
                    <h2>Instructions</h2>
                </div>
                <div id="instructions-list">
                    <div class="list-item step-item">
                        <span class="step-number">1</span>
                        <textarea placeholder="Explain this step..." class="input-field"></textarea>
                        <button type="button" class="btn-delete" onclick="removeStep(this)">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn-add-item" onclick="addInstruction()">
                    <i class="fa-solid fa-plus"></i> Add Step
                </button>
            </section>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn-submit">Publish Recipe</button>
            </div>
        </form>
    </div>

    <script src="add.js"></script>
</body>
</html>