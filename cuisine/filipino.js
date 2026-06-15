// --- STATE MANAGEMENT ---
let currentRecipeId = null;
let currentRating = 0;

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const recipeGrid = document.querySelector('.recipe-grid');
    const noResultsMessage = document.getElementById('noResultsMessage');

    // Inline Filtration System matching lookup logic
    function filterFilipinoRecipes() {
        if (!recipeGrid) return;

        const cards = Array.from(recipeGrid.querySelectorAll('.recipe-card'));
        const query = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        cards.forEach(card => {
            const recipeId = card.dataset.id;
            const data = recipesById[recipeId];
            
            if (!data) return;

            const title = (data.title || '').toLowerCase();
            const category = (data.category || '').toLowerCase();
            const desc = (data.description || '').toLowerCase();

            const matchesSearch = title.includes(query) || category.includes(query) || desc.includes(query);

            if (matchesSearch) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (visibleCount === 0 && cards.length > 0) {
            if (noResultsMessage) noResultsMessage.style.display = 'block';
        } else {
            if (noResultsMessage) noResultsMessage.style.display = 'none';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterFilipinoRecipes);
    }

    // Input monitoring for comment text area interface controls
    const commentInput = document.getElementById('commentInput');
    const postCommentBtn = document.getElementById('postCommentBtn');

    if (commentInput && postCommentBtn) {
        commentInput.addEventListener('input', () => {
            if (commentInput.value.trim().length > 0) {
                postCommentBtn.classList.add('active');
                postCommentBtn.disabled = false;
            } else {
                postCommentBtn.classList.remove('active');
                postCommentBtn.disabled = true;
            }
        });

        postCommentBtn.addEventListener('click', submissionCommentTask);
    }

    setupRatingEngineHandlers();
});

// --- OVERLAY WINDOW PRESENTATION ACTIONS ---
async function openRecipe(id) {
    const recipe = recipesById[id];
    if (!recipe) return;

    currentRecipeId = id;
    currentRating = 0;

    // Reset components to dynamic standards
    document.getElementById('modalTitle').innerText = recipe.title || 'Untitled Recipe';
    document.getElementById('modalDescription').innerText = recipe.description || '';
    document.getElementById('modalCategoryBadge').innerText = recipe.category || 'Main Dish';
    document.getElementById('modalDifficultyBadge').innerText = recipe.difficulty || 'Easy';
    
    document.getElementById('modalPrepTime').innerText = `${recipe.prep_time || 0} mins`;
    document.getElementById('modalCookTime').innerText = `${recipe.cook_time || 0} mins`;
    document.getElementById('modalTotalTime').innerText = `${recipe.total_time || 0} mins`;
    document.getElementById('modalServings').innerText = `${recipe.servings || 1}`;

    // Dynamic Image path layout configurations 
    let imgPath = '../images/recipe-placeholder.jpg';
    if (recipe.image) {
        if (recipe.image.includes('/')) {
            imgPath = recipe.image;
        } else {
            imgPath = `../explore/explore-images/${recipe.image}`;
        }
    }
    document.getElementById('modalBanner').src = imgPath;

    // Author Info Rendering
    document.getElementById('modalAuthorName').innerText = `${recipe.first_name} ${recipe.last_name}`;
    const avatar = document.getElementById('modalAuthorAvatar');
    if (recipe.profile_pic) {
        avatar.style.backgroundImage = `url('${recipe.profile_pic}')`;
        avatar.style.backgroundSize = 'cover';
        avatar.innerText = '';
    } else {
        avatar.style.backgroundImage = 'none';
        avatar.style.backgroundColor = '#8B1A1A';
        avatar.style.color = 'white';
        avatar.style.display = 'flex';
        avatar.style.alignItems = 'center';
        avatar.style.justifyContent = 'center';
        avatar.style.fontWeight = 'bold';
        avatar.innerText = recipe.first_name[0] + (recipe.last_name ? recipe.last_name[0] : '');
    }

    // Map ingredients parsed out using relational split arrays with styled bullets
    const ingredientsContainer = document.getElementById('modalIngredients');
    ingredientsContainer.innerHTML = '';
    if (recipe.ingredients_list) {
        recipe.ingredients_list.split('||').forEach(item => {
            if (item.trim()) {
                const li = document.createElement('li');
                li.innerHTML = `<span class="bullet"></span> ${item.trim()}`;
                ingredientsContainer.appendChild(li);
            }
        });
    }

    // Map sequential processing instruction blocks matching modal.css class structures
    const instructionsContainer = document.getElementById('modalInstructions');
    instructionsContainer.innerHTML = '';
    if (recipe.instructions_list) {
        recipe.instructions_list.split('||').forEach((step, index) => {
            if (step.trim()) {
                const stepDiv = document.createElement('div');
                stepDiv.className = 'step';
                stepDiv.innerHTML = `
                    <div class="step-number">${index + 1}</div>
                    <p>${step.trim()}</p>
                `;
                instructionsContainer.appendChild(stepDiv);
            }
        });
    }

    // Sync Favorite Icon state from database endpoints
    const favBtn = document.getElementById('modalFavoriteBtn');
    const favIcon = favBtn.querySelector('i');
    try {
        const favResponse = await fetch(`check_favorite.php?recipe_id=${id}`);
        const favData = await favResponse.json();
        if (favData.is_favorited) {
            favBtn.classList.add('active');
            favIcon.classList.replace('fa-regular', 'fa-solid');
        } else {
            favBtn.classList.remove('active');
            favIcon.classList.replace('fa-solid', 'fa-regular');
        }
    } catch (err) { 
        console.error("Error checking favorite status", err); 
    }

    // Refresh displays and retrieve review inputs
    highlightStars(0);
    document.getElementById('ratingTextSummary').innerText = "Select your review rating to save";
    
    const commentInput = document.getElementById('commentInput');
    const postCommentBtn = document.getElementById('postCommentBtn');
    if (commentInput) commentInput.value = '';
    if (postCommentBtn) {
        postCommentBtn.disabled = true;
        postCommentBtn.classList.remove('active');
    }

    refreshAverageDisplay(id);

    // Turn viewport structural overlay views on
    const modalElement = document.getElementById('recipeModal');
    if (modalElement) {
        modalElement.classList.add('active');
    }
    document.body.style.overflow = 'hidden';
}

function closeRecipe() {
    const modalElement = document.getElementById('recipeModal');
    if (modalElement) {
        modalElement.classList.remove('active');
    }
    document.body.style.overflow = '';
    currentRecipeId = null;
}

// --- ASYNCHRONOUS REVIEW COMMUNICATIONS AND CALCULATIONS ---
async function refreshAverageDisplay(recipeId) {
    const commentsList = document.getElementById('commentsList');
    const commentCountSpan = document.getElementById('commentCount');
    if (!commentsList) return;

    commentsList.innerHTML = '<p class="status-info-text">Loading communication streams...</p>';

    try {
        // Aligned to match get_comments.php output metrics structure
        const response = await fetch(`get_comments.php?recipe_id=${recipeId}`);
        const result = await response.json();

        if (result.success) {
            currentRating = result.user_rating || 0;
            highlightStars(currentRating);

            // Synchronize counters and textual values
            const topAvgVal = document.getElementById('modalAverageRatingValue');
            const topReviewCount = document.getElementById('modalReviewCount');
            
            if (topAvgVal) topAvgVal.innerText = result.average > 0 ? result.average.toFixed(1) : "0.0";
            if (topReviewCount) {
                const reviewWord = result.total_ratings === 1 ? 'review' : 'reviews';
                topReviewCount.innerText = `${result.total_ratings} ${reviewWord}`;
            }

            const bottomCount = document.querySelector('.total-ratings-count');
            if (bottomCount) {
                const ratingWord = result.total_ratings === 1 ? 'rating' : 'ratings';
                bottomCount.innerText = `${result.total_ratings} ${ratingWord}`;
            }

            // Render matching comments output formatting
            commentsList.innerHTML = '';
            if (!result.comments || result.comments.length === 0) {
                if (commentCountSpan) commentCountSpan.innerText = '0';
                commentsList.innerHTML = '<p class="status-info-text">No comments logged yet. Be the first to express an opinion!</p>';
                return;
            }

            if (commentCountSpan) commentCountSpan.innerText = result.comments.length;

            result.comments.forEach(c => {
                let avatarMarkup = '';
                if (c.profile_pic && c.profile_pic.trim() !== '') {
                    avatarMarkup = `<img src="${c.profile_pic}" class="comment-avatar" alt="Avatar image">`;
                } else {
                    const initial = c.first_name ? c.first_name.charAt(0).toUpperCase() : 'U';
                    avatarMarkup = `<div class="comment-avatar-initials">${initial}</div>`;
                }

                const rowHTML = `
                    <div class="comment-item">
                        ${avatarMarkup}
                        <div class="comment-body">
                            <div class="comment-meta">
                                <span class="comment-author">${c.first_name} ${c.last_name}</span>
                                <span class="comment-timestamp" style="font-size:11px; color:#aaa; margin-left:10px;">${c.created_at || ''}</span>
                            </div>
                            <p class="comment-text">${c.comment}</p>
                        </div>
                    </div>
                `;
                commentsList.insertAdjacentHTML('beforeend', rowHTML);
            });
        } else {
            commentsList.innerHTML = '<p class="status-info-text">Unable to sync database reviews.</p>';
        }
    } catch (err) {
        console.error("Communication error retrieving data streams:", err);
        commentsList.innerHTML = '<p class="status-info-text">Connection error experienced.</p>';
    }
}

async function submissionCommentTask() {
    const commentInput = document.getElementById('commentInput');
    const text = commentInput ? commentInput.value.trim() : '';

    if (!text || !currentRecipeId) return;

    try {
        const response = await fetch('save_review.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                recipe_id: currentRecipeId,
                comment: text,
                action: 'comment'
            })
        });

        const result = await response.json();
        if (result.success) {
            if (commentInput) commentInput.value = '';
            const postCommentBtn = document.getElementById('postCommentBtn');
            if (postCommentBtn) {
                postCommentBtn.disabled = true;
                postCommentBtn.classList.remove('active');
            }
            refreshAverageDisplay(currentRecipeId);
        } else {
            alert(result.error || "An authorization context conflict prevented submission.");
        }
    } catch (err) {
        console.error("Error committing comment payload:", err);
    }
}

// --- RATINGS CONTROL SCHEME MANAGEMENT SYSTEMS ---
function setupRatingEngineHandlers() {
    const starContainer = document.getElementById('starContainer');
    if (!starContainer) return;

    const stars = Array.from(starContainer.querySelectorAll('.rating-star'));

    stars.forEach(star => {
        star.style.cursor = 'pointer';
        
        star.addEventListener('mouseenter', function() {
            const val = this.getAttribute('data-value');
            highlightStars(val);
        });

        star.addEventListener('click', async function() {
            const val = this.getAttribute('data-value');
            currentRating = val;
            highlightStars(currentRating);
            
            const summaryHint = document.getElementById('ratingTextSummary');
            if (summaryHint) {
                summaryHint.innerText = `You rated this recipe ${val} Star${val > 1 ? 's' : ''}!`;
            }

            try {
                const response = await fetch('save_review.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        recipe_id: currentRecipeId,
                        rating: currentRating,
                        action: 'rate'
                    })
                });
                const result = await response.json();
                if (result.success) {
                    refreshAverageDisplay(currentRecipeId);
                }
            } catch (err) {
                console.error("Failed writing metrics payload to backend:", err);
            }
        });
    });

    starContainer.addEventListener('mouseleave', () => {
        highlightStars(currentRating);
    });
}

function highlightStars(ratingValue) {
    const stars = document.querySelectorAll('#starContainer .rating-star');
    stars.forEach(star => {
        const val = star.getAttribute('data-value');
        if (parseInt(val) <= parseInt(ratingValue)) {
            star.classList.replace('fa-regular', 'fa-solid');
            star.style.color = '#FCD116';
        } else {
            star.classList.replace('fa-solid', 'fa-regular');
            star.style.color = '#ccc';
        }
    });
}

// --- FAVORITES LOGIC ---
async function toggleFavorite() {
    const btn = document.getElementById('modalFavoriteBtn');
    const icon = btn.querySelector('i');
    
    if (!currentRecipeId) return;

    try {
        const response = await fetch('toggle_favorite.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ recipe_id: currentRecipeId })
        });

        const result = await response.json();

        if (result.success) {
            if (result.status === 'added') {
                btn.classList.add('active');
                icon.classList.replace('fa-regular', 'fa-solid');
            } else {
                btn.classList.remove('active');
                icon.classList.replace('fa-solid', 'fa-regular');
            }
        } else if (result.error === 'Login required') {
            alert("Please log in to favorite recipes!");
        }
    } catch (err) {
        console.error("Favorite toggle failed:", err);
    }
}

function toggleBookmark(recipeId) {
    alert(`Recipe reference identifier structure [${recipeId}] has track alterations noted structural changes saved!`);
}