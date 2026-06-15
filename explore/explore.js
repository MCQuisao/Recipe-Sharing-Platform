// --- STATE MANAGEMENT ---
let currentPage = 1;
const recipesPerPage = 8;
let currentRecipeId = null;
let currentRating = 0;

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const filterButtons = document.querySelectorAll('.filter-btn');
    const recipeGrid = document.querySelector('.recipe-grid');
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');
    const pageInfo = document.getElementById('pageInfo');

    function performFilter() {
        if (!recipeGrid) return;

        const allCards = Array.from(recipeGrid.querySelectorAll('.recipe-card'));
        const query = searchInput.value.toLowerCase().trim();

        const activeBtn = document.querySelector('.filter-btn.active');
        const activeCategory = activeBtn
            ? activeBtn.dataset.category.toLowerCase()
            : 'all';

        // No Results Element
        const noResultsMessage = document.getElementById('noResultsMessage');

        const filteredCards = allCards.filter(card => {
            const title = card.querySelector('h4').innerText.toLowerCase();
            const category = card.querySelector('.meta-category').innerText.toLowerCase();

            const matchesSearch = title.includes(query);
            const matchesCategory =
                activeCategory === 'all' || category.includes(activeCategory);

            // Hide all cards first
            card.style.display = 'none';

            return matchesSearch && matchesCategory;
        });

        // Show / Hide No Results Message
        if (noResultsMessage) {
            if (filteredCards.length === 0) {
                noResultsMessage.style.display = 'flex';
            } else {
                noResultsMessage.style.display = 'none';
            }
        }

        const totalPages = Math.ceil(filteredCards.length / recipesPerPage) || 1;

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const start = (currentPage - 1) * recipesPerPage;
        const end = start + recipesPerPage;

        const visibleCards = filteredCards.slice(start, end);

        visibleCards.forEach(card => {
            card.style.display = '';
        });

        // Pagination Info
        if (pageInfo) {
            pageInfo.innerText = `Page ${currentPage} of ${totalPages}`;
        }

        if (prevBtn) {
            prevBtn.disabled = currentPage === 1;
        }

        if (nextBtn) {
            nextBtn.disabled = currentPage === totalPages;
        }

        // Hide pagination when no results
        const paginationContainer = document.querySelector('.pagination-container');

        if (paginationContainer) {
            paginationContainer.style.display =
                filteredCards.length === 0 ? 'none' : 'flex';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            currentPage = 1;
            performFilter();
        });
    }

    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            filterButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentPage = 1;
            performFilter();
        });
    });

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                performFilter();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            currentPage++;
            performFilter();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    performFilter();
});

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

// --- MODAL & RATING LOGIC ---

const modal = document.getElementById('recipeModal');

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
    } catch (err) {
        console.error("Error refreshing ratings", err);
    }
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

async function openRecipe(id) {
    currentRecipeId = id; 
    currentRating = 0; 
    highlightStars(0); 
    document.getElementById('commentInput').value = ''; 

    const data = recipesById[id];
    if (!data) return;

    // Image and Titles
    document.getElementById('modalBanner').src = "../explore/explore-images/" + data.image;
    document.getElementById('modalTitle').innerText = data.title;
    document.getElementById('modalDescription').innerText = data.description || '';
    
    // Stats Grid
    document.getElementById('modalPrepTime').innerText = (data.prep_time || 0) + ' min';
    document.getElementById('modalCookTime').innerText = (data.cook_time || 0) + ' min';
    document.getElementById('modalTotalTime').innerText = (data.total_time || 0) + ' mins';
    document.getElementById('modalServings').innerText = data.servings;

    // Author Info
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

    // Favorite Status Check
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
    } catch (err) { console.error("Error checking favorite status", err); }

    // Ingredients & Instructions
    const ingList = document.getElementById('modalIngredients');
    ingList.innerHTML = '';
    if (data.ingredients_list) {
        data.ingredients_list.split('||').forEach(item => {
            if(item.trim()){
                const li = document.createElement('li');
                li.innerHTML = `<span class="bullet"></span> ${item.trim()}`;
                ingList.appendChild(li);
            }
        });
    }

    const stepsDiv = document.getElementById('modalInstructions');
    stepsDiv.innerHTML = '';
    if (data.instructions_list) {
        data.instructions_list.split('||').forEach((step, i) => {
            if(step.trim()){
                const div = document.createElement('div');
                div.className = 'step';
                div.innerHTML = `<div class="step-number">${i+1}</div><p>${step.trim()}</p>`;
                stepsDiv.appendChild(div);
            }
        });
    }

    refreshAverageDisplay(id);
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
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

// Star Events
const stars = document.querySelectorAll('#starContainer i');
stars.forEach(star => {
    star.addEventListener('mouseover', function() {
        highlightStars(this.getAttribute('data-value'));
    });

    star.addEventListener('click', async function() {
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

// Comment Submission
document.getElementById('postCommentBtn').addEventListener('click', async function() {
    const commentInput = document.getElementById('commentInput');
    const commentText = commentInput.value.trim();

    if (!commentText) return;

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
    } catch (err) {
        console.error("Error posting comment:", err);
    }
});