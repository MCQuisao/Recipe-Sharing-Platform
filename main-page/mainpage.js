// --- STATE MANAGEMENT ---
let currentRecipeId = null;
let currentRating = 0;

// --- 1. MODAL OVERLAY & POPULATION LOGIC ---
const modal = document.getElementById('recipeModal');

async function openRecipe(id) {
    currentRecipeId = id; 
    currentRating = 0; 
    highlightStars(0); 
    document.getElementById('commentInput').value = ''; 

    // Retrieve live recipe entry from the database object array
    const data = recipesById[id];
    if (!data) return;

    // Image and Titles
    document.getElementById('modalBanner').src = "../explore/explore-images/" + data.image;
    document.getElementById('modalTitle').innerText = data.title;
    document.getElementById('modalDescription').innerText = data.description || '';
    
    // Core Stats Grid Matrix Information
    document.getElementById('modalPrepTime').innerText = (data.prep_time || 0) + ' min';
    document.getElementById('modalCookTime').innerText = (data.cook_time || 0) + ' min';
    document.getElementById('modalTotalTime').innerText = (data.total_time || 0) + ' mins';
    document.getElementById('modalServings').innerText = data.servings;

    // Category and Difficulty Badges Sync
    const categoryEl = document.getElementById('modalCategory');
    const difficultyEl = document.getElementById('modalDifficulty');
    if (categoryEl) categoryEl.innerText = data.category || 'Recipe';
    if (difficultyEl) difficultyEl.innerText = data.difficulty ? (data.difficulty.charAt(0).toUpperCase() + data.difficulty.slice(1).toLowerCase()) : 'Easy';

    // Author Profiling Initials Logic
    document.getElementById('modalAuthorName').innerText = data.first_name + ' ' + data.last_name;
    const avatar = document.getElementById('modalAuthorAvatar');
    
    if (data.profile_pic) {
        avatar.style.backgroundImage = `url('${data.profile_pic}')`;
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
        avatar.innerText = data.first_name[0] + (data.last_name ? data.last_name[0] : '');
    }

    // Favorite Status Realtime Check
    const favBtn = document.getElementById('modalFavoriteBtn');
    const favIcon = favBtn ? favBtn.querySelector('i') : null;
    if (favBtn && favIcon) {
        try {
            const favResponse = await fetch(`check_favorite.php?recipe_id=${id}`);
            const favData = await favResponse.json();
            if (favData.is_favorited) {
                favBtn.classList.add('active');
                favIcon.className = 'fa-solid fa-bookmark';
            } else {
                favBtn.classList.remove('active');
                favIcon.className = 'fa-regular fa-bookmark';
            }
        } catch (err) { console.error("Error checking favorite status:", err); }
    }

    // Split and Append Relational Ingredients rows
    const ingList = document.getElementById('modalIngredients');
    ingList.innerHTML = '';
    if (data.ingredients_list) {
        data.ingredients_list.split('||').forEach(item => {
            if (item.trim()) {
                const li = document.createElement('li');
                li.innerHTML = `<span class="bullet"></span> ${item.trim()}`;
                ingList.appendChild(li);
            }
        });
    }

    // Split and Append Ordered Instructions Steps
    const stepsDiv = document.getElementById('modalInstructions');
    stepsDiv.innerHTML = '';
    if (data.instructions_list) {
        data.instructions_list.split('||').forEach((step, i) => {
            if (step.trim()) {
                const div = document.createElement('div');
                div.className = 'step';
                div.innerHTML = `<div class="step-number">${i + 1}</div><p>${step.trim()}</p>`;
                stepsDiv.appendChild(div);
            }
        });
    }

    // Fetch and sync active comments
    refreshAverageDisplay(id);
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Global modal background dismiss dismissals
window.onclick = function(event) {
    if (event.target == modal) closeModal();
}
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && modal.classList.contains('active')) closeModal();
});

// --- 2. FAVORITES SYSTEM SYNC ---
async function toggleFavorite() {
    const btn = document.getElementById('modalFavoriteBtn');
    const icon = btn ? btn.querySelector('i') : null;
    
    if (!currentRecipeId || !btn || !icon) return;

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
    } catch (err) { console.error("Favorite toggle failed:", err); }
}

// --- 3. REVIEWS & COMMENTS DATA FEED ---
async function refreshAverageDisplay(id) {
    try {
        const response = await fetch(`get_comments.php?recipe_id=${id}`);
        const result = await response.json();
        
        if (result.success) {
            currentRating = result.user_rating || 0;
            highlightStars(currentRating);

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

            renderComments(result.comments);
        }
    } catch (err) { console.error("Error refreshing ratings", err); }
}

function renderComments(comments) {
    const list = document.getElementById('commentsList');
    list.innerHTML = '';

    if (!comments || comments.length === 0) {
        list.innerHTML = '<p style="padding:10px; color:#888;">No comments yet.</p>';
        return;
    }

    comments.forEach(c => {
        const commentDiv = document.createElement('div');
        commentDiv.className = 'comment-item';
        
        let avatarHTML = '';
        if (c.profile_pic && c.profile_pic.trim() !== '') {
            avatarHTML = `<img src="${c.profile_pic}" class="comment-avatar">`;
        } else {
            const initial = c.first_name ? c.first_name.charAt(0).toUpperCase() : 'U';
            avatarHTML = `<div class="comment-avatar-initials">${initial}</div>`;
        }

        commentDiv.innerHTML = `
            ${avatarHTML}
            <div class="comment-body">
                <div class="comment-meta">
                    <span class="comment-author">${c.first_name} ${c.last_name}</span>
                </div>
                <p class="comment-text">${c.comment}</p>
            </div>
        `;
        list.appendChild(commentDiv);
    });
}

function highlightStars(count) {
    const starIcons = document.querySelectorAll('#starContainer i');
    starIcons.forEach(s => {
        const v = s.getAttribute('data-value');
        if (v <= count) {
            s.classList.replace('fa-regular', 'fa-solid');
            s.style.color = "#FFD700";
        } else {
            s.classList.replace('fa-solid', 'fa-regular');
            s.style.color = "#ccc";
        }
    });
}

// Initialize Global Interactivity Listeners
document.addEventListener('DOMContentLoaded', () => {
    // Star Mouseover Hover & Click Triggers
    const stars = document.querySelectorAll('#starContainer i');
    stars.forEach(star => {
        star.addEventListener('mouseover', function() {
            highlightStars(this.getAttribute('data-value'));
        });

        star.addEventListener('click', async function() {
            if (!currentRecipeId) return;
            currentRating = this.getAttribute('data-value');
            highlightStars(currentRating);

            try {
                await fetch('save_review.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        recipe_id: currentRecipeId,
                        rating: currentRating,
                        action: 'rate'
                    })
                });
                refreshAverageDisplay(currentRecipeId);
            } catch (err) { console.error("Save failed", err); }
        });
    });

    const starContainer = document.getElementById('starContainer');
    if (starContainer) {
        starContainer.addEventListener('mouseleave', () => highlightStars(currentRating));
    }

    // Comment Post Click Handler pipeline
    const postCommentBtn = document.getElementById('postCommentBtn');
    if (postCommentBtn) {
        postCommentBtn.addEventListener('click', async function() {
            const commentInput = document.getElementById('commentInput');
            const commentText = commentInput ? commentInput.value.trim() : '';

            if (!commentText || !currentRecipeId) return;

            try {
                const response = await fetch('save_review.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        recipe_id: currentRecipeId,
                        comment: commentText,
                        action: 'comment'
                    })
                });

                const result = await response.json();
                if (result.success) {
                    commentInput.value = '';
                    refreshAverageDisplay(currentRecipeId);
                }
            } catch (err) { console.error("Error posting comment:", err); }
        });
    }
});