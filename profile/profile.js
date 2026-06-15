document.addEventListener('DOMContentLoaded', () => {
    // Event Delegation for close buttons and outside clicks
    window.addEventListener('click', (e) => {
        const viewModal = document.getElementById('recipeModal');
        const editModal = document.getElementById('editModal');
        
        // Close if clicking overlay
        if (e.target === viewModal) closeModal();
        if (e.target === editModal) closeEditModal();
        
        // Generic close button handler (works for any modal)
        if (e.target.classList.contains('close-btn') || e.target.classList.contains('close-modal')) {
            closeModal();
            closeEditModal();
        }
    });

    const editForm = document.getElementById('editRecipeForm');
    if (editForm) editForm.addEventListener('submit', handleRecipeUpdate);
    
    // Setup file upload listener safely
    const fileUpload = document.getElementById('file-upload');
    if (fileUpload) {
        fileUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const currentAvatar = document.getElementById('current-avatar');
                    if (currentAvatar) currentAvatar.src = event.target.result;
                };
                reader.readAsDataURL(file);
                showToast('Success', 'Avatar preview updated!');
            }
        });
    }
});

/* --- SHARED MODAL UTILS --- */
function toggleScroll(isFixed) {
    document.body.style.overflow = isFixed ? 'hidden' : 'auto';
}

/* --- PROFILE MANAGEMENT --- */
function toggleEditMode() {
    const viewMode = document.getElementById('profile-view');
    const editMode = document.getElementById('profile-edit');
    if (!viewMode || !editMode) return;

    const isEditing = editMode.classList.toggle('hidden');
    viewMode.classList.toggle('hidden');

    if (!isEditing) {
        // Populate inputs from the current live view display elements
        const fullName = document.getElementById('display-name').innerText.trim();
        const currentBio = document.getElementById('display-bio').innerText.trim();
        const parts = fullName.split(' ');
        
        const fNameInput = document.getElementById('input-first-name');
        const lNameInput = document.getElementById('input-last-name');
        const bioInput = document.getElementById('input-bio');
        
        if (fNameInput) fNameInput.value = parts[0] || '';
        if (lNameInput) lNameInput.value = parts.slice(1).join(' ') || '';
        if (bioInput) bioInput.value = currentBio || '';
    }
}

async function saveProfile() {
    const fNameEl = document.getElementById('input-first-name');
    const lNameEl = document.getElementById('input-last-name');
    const bioEl = document.getElementById('input-bio');

    if (!fNameEl || !lNameEl || !bioEl) {
        console.error("Could not find input elements. Check HTML IDs.");
        return;
    }

    const first_name = fNameEl.value.trim();
    const last_name = lNameEl.value.trim();
    const bio = bioEl.value.trim();

    if (!first_name) {
        showToast("Error", "First name is required");
        return;
    }

    try {
        const response = await fetch('update_profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ first_name, last_name, bio })
        });

        const result = await response.json();

        if (result.success) {
            document.getElementById('display-name').innerText = `${first_name} ${last_name}`;
            document.getElementById('display-bio').innerText = bio;

            toggleEditMode();
            showToast('Success', 'Profile updated successfully!');
            
            setTimeout(() => location.reload(), 600);
        } else {
            throw new Error(result.error);
        }
    } catch (err) {
        console.error("Save Error:", err);
        showToast('Error', 'Update operation failed.');
    }
}

function triggerUpload() {
    const fileUpload = document.getElementById('file-upload');
    if (fileUpload) fileUpload.click();
}

function handleImageUpload(input) {
    if (input.files && input.files[0]) {
        const formData = new FormData();
        formData.append('profile_pic', input.files[0]);

        fetch('upload_avatar.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const avatarImg = document.getElementById('current-avatar');
                if (avatarImg) {
                    avatarImg.src = data.filepath + '?t=' + new Date().getTime(); // Anti-cache
                } else {
                    location.reload(); 
                }
                showToast('Success', "Profile picture updated!");
            } else {
                alert("Upload failed: " + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("An error occurred during upload.");
        });
    }
}

/* --- RECIPE DATA HANDLER --- */
async function fetchRecipe(id) {
    const response = await fetch(`get_recipe.php?id=${id}`);
    if (!response.ok) throw new Error("Server error");
    const data = await response.json();
    if (data.error) throw new Error(data.error);
    return data;
}

/* --- VIEW MODAL --- */
async function openViewModal(recipeId) {
    try {
        const data = await fetchRecipe(recipeId);

        document.getElementById('modalBanner').src = "../explore/explore-images/" + data.image;
        document.getElementById('modalTitle').innerText = data.title;
        document.getElementById('modalDescription').innerText = data.description || '';
        
        document.getElementById('modalPrepTime').innerText = (data.prep_time || 0) + 'm';
        document.getElementById('modalCookTime').innerText = (data.cook_time || 0) + 'm';
        document.getElementById('modalTotalTime').innerText = (data.total_time || 0) + 'm';
        document.getElementById('modalServings').innerText = data.servings || '1';

        const ingList = document.getElementById('modalIngredients');
        ingList.innerHTML = data.ingredients_list ? data.ingredients_list.split('||').map(item => 
            `<li><span class="bullet"></span> ${item.trim()}</li>`
        ).join('') : '<li>No ingredients listed</li>';

        const stepsDiv = document.getElementById('modalInstructions');
        stepsDiv.innerHTML = data.instructions_list ? data.instructions_list.split('||').map((step, i) => 
            `<div class="step">
                <div class="step-number">${i + 1}</div>
                <p>${step.trim()}</p>
            </div>`
        ).join('') : '<p>No instructions listed</p>';

        document.getElementById('recipeModal').classList.add('active');
        toggleScroll(true);
    } catch (err) {
        console.error(err);
        showToast('Error', 'Could not open recipe');
    }
}

function closeModal() {
    const modal = document.getElementById('recipeModal');
    if(modal) modal.classList.remove('active');
    toggleScroll(false);
}

/* --- EDIT MODAL --- */
async function openEditModal(recipeId) {
    try {
        const recipe = await fetchRecipe(recipeId);
        
        document.getElementById('edit-recipe-id').value = recipe.id;
        document.getElementById('edit-title').value = recipe.title;
        document.getElementById('edit-description').value = recipe.description || '';
        
        document.getElementById('edit-prep-time').value = recipe.prep_time || 0;
        document.getElementById('edit-cook-time').value = recipe.cook_time || 0;
        document.getElementById('edit-servings').value = recipe.servings || '1'; 
        document.getElementById('edit-difficulty').value = recipe.difficulty || 'Easy';
        
        const categoryDropdown = document.getElementById('edit-category');
        if(categoryDropdown) categoryDropdown.value = recipe.category || 'Breakfast';

        const ingContainer = document.getElementById('edit-ingredients-list');
        const insContainer = document.getElementById('edit-instructions-list');
        ingContainer.innerHTML = '';
        insContainer.innerHTML = '';

        const ings = recipe.ingredients_list ? recipe.ingredients_list.split('||') : [];
        const inss = recipe.instructions_list ? recipe.instructions_list.split('||') : [];

        if (ings.length > 0) {
            ings.forEach(val => addEditListItem('ingredient', val.trim()));
        } else {
            addEditListItem('ingredient', '');
        }

        if (inss.length > 0) {
            inss.forEach(val => addEditListItem('instruction', val.trim()));
        } else {
            addEditListItem('instruction', '');
        }

        document.getElementById('editModal').classList.add('active');
        toggleScroll(true);
    } catch (err) {
        console.error(err);
        showToast('Error', 'Could not fetch recipe details');
    }
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    if(modal) modal.classList.remove('active');
    toggleScroll(false);
}

function addEditListItem(type, value = '') {
    const container = document.getElementById(`edit-${type}s-list`);
    const div = document.createElement('div');
    div.className = 'list-item';
    
    if (type === 'ingredient') {
        div.innerHTML = `
            <input type="text" value="${value}" class="input-field ingredient-input" placeholder="e.g. 2 Cups of Flour">
            <button type="button" class="btn-delete" onclick="this.parentElement.remove()"><i class="fa-solid fa-trash"></i></button>`;
    } else {
        div.classList.add('step-item');
        const stepNum = container.children.length + 1;
        div.innerHTML = `
            <span class="step-number">${stepNum}</span>
            <textarea class="input-field instruction-input" placeholder="Step details...">${value}</textarea>
            <button type="button" class="btn-delete" onclick="this.parentElement.remove(); updateStepNumbers();"><i class="fa-solid fa-trash"></i></button>`;
    }
    container.appendChild(div);
}

function updateStepNumbers() {
    document.querySelectorAll('#edit-instructions-list .step-number').forEach((span, i) => span.innerText = i + 1);
}

async function handleRecipeUpdate(e) {
    e.preventDefault();

    const getList = (selector) => Array.from(document.querySelectorAll(selector))
        .map(i => i.value.trim())
        .filter(v => v !== "")
        .join(' || ');

    const data = {
        id: document.getElementById('edit-recipe-id').value,
        title: document.getElementById('edit-title').value,
        description: document.getElementById('edit-description').value,
        prep_time: document.getElementById('edit-prep-time').value,
        cook_time: document.getElementById('edit-cook-time').value,
        servings: document.getElementById('edit-servings').value,
        difficulty: document.getElementById('edit-difficulty').value,
        category: document.getElementById('edit-category') ? document.getElementById('edit-category').value : 'Breakfast',
        ingredients_list: getList('.ingredient-input'),
        instructions_list: getList('.instruction-input')
    };

    try {
        const res = await fetch('update_recipe.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const textResponse = await res.text();
        const result = JSON.parse(textResponse);
        
        if (result.success) {
            showToast('Success', 'Recipe updated!');
            setTimeout(() => location.reload(), 800);
        } else throw new Error(result.error);
    } catch (err) {
        console.error("Full Error:", err);
        showToast('Error', 'Update failed.');
    }
}

async function deleteRecipe() {
    const id = document.getElementById('edit-recipe-id').value;
    if (!confirm("Are you sure you want to delete this recipe?")) return;

    try {
        const res = await fetch('delete_recipe.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const result = await res.json();
        if (result.success) {
            location.reload();
        } else throw new Error(result.error);
    } catch (err) {
        showToast('Error', 'Delete failed');
    }
}

function switchTab(tabId, element) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.style.display = 'none';
    });

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    const target = document.getElementById(tabId);
    if (target) {
        target.style.display = 'block';
        element.classList.add('active');
    }
}

function showToast(title, message) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `<strong>${title}</strong><br><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
}

function handleSignOut() {
    if (confirm("Are you sure you want to sign out?")) {
        window.location.href = "../auth/logout.php";
    }
}