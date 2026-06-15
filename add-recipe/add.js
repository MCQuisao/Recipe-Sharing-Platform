// Function to add a new ingredient input line
function addIngredient() {
    const container = document.getElementById('ingredients-list');
    const div = document.createElement('div');
    div.className = 'list-item';
    div.innerHTML = `
        <div class="drag-handle"><i class="fa-solid fa-grip-lines"></i></div>
        <input type="text" placeholder="Add ingredient..." class="input-field">
        <button type="button" class="btn-delete" onclick="this.parentElement.remove()">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    container.appendChild(div);
}

// Function to add a new instruction step
function addInstruction() {
    const container = document.getElementById('instructions-list');
    const stepCount = container.children.length + 1;
    
    const div = document.createElement('div');
    div.className = 'list-item step-item';
    div.innerHTML = `
        <span class="step-number">${stepCount}</span>
        <textarea placeholder="Step ${stepCount}..." class="input-field"></textarea>
        <button type="button" class="btn-delete" onclick="removeStep(this)">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    container.appendChild(div);
}

// Remove step and re-number remaining steps
function removeStep(button) {
    const container = document.getElementById('instructions-list');
    button.parentElement.remove();
    
    // Re-index numbers
    Array.from(container.children).forEach((child, index) => {
        child.querySelector('.step-number').innerText = index + 1;
        child.querySelector('textarea').placeholder = `Step ${index + 1}...`;
    });
}

// --- Image Upload Logic --- //
document.getElementById('recipe-file-input').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            const preview = document.getElementById('image-preview');
            const placeholder = document.getElementById('upload-placeholder');
            const removeBtn = document.getElementById('remove-image-btn');
            const container = document.querySelector('.image-upload-container');

            preview.src = event.target.result;
            preview.classList.remove('hidden');
            removeBtn.classList.remove('hidden');
            placeholder.classList.add('hidden');
            
            // Remove dashed border when image is present
            container.style.borderStyle = 'solid';
        }
        reader.readAsDataURL(file);
    }
});

function removeImage(e) {
    e.stopPropagation(); 
    document.getElementById('recipe-file-input').value = '';
    
    document.getElementById('image-preview').classList.add('hidden');
    document.getElementById('remove-image-btn').classList.add('hidden');
    document.getElementById('upload-placeholder').classList.remove('hidden');
    
    const container = document.querySelector('.image-upload-container');
    container.style.borderStyle = 'dashed';
}

// Submit handler
async function submitRecipe() {
    const form = document.getElementById('recipeForm');
    const submitBtn = document.querySelector('.btn-submit');
    
    // 1. Prepare FormData
    const formData = new FormData();
    
    // Basic Info
    formData.append('title', document.getElementById('recipe-title').value);
    formData.append('description', document.querySelector('textarea[placeholder*="story"]').value);
    formData.append(
        'category',
        document.getElementById('category').value
    );
    formData.append('difficulty', document.querySelectorAll('select')[1].value);
    formData.append('prep_time', document.querySelectorAll('input[type="number"]')[0].value);
    formData.append('cook_time', document.querySelectorAll('input[type="number"]')[1].value);
    formData.append('servings', document.querySelectorAll('input[type="number"]')[2].value);
    
    // Image
    const fileInput = document.getElementById('recipe-file-input');
    if (fileInput.files[0]) {
        formData.append('recipe_image', fileInput.files[0]);
    }

    // Ingredients (get all input values)
    const ingredientInputs = document.querySelectorAll('#ingredients-list input');
    ingredientInputs.forEach(input => {
        if(input.value.trim()) formData.append('ingredients[]', input.value.trim());
    });

    // Instructions (get all textarea values)
    const instructionSteps = document.querySelectorAll('#instructions-list textarea');
    instructionSteps.forEach(textarea => {
        if(textarea.value.trim()) formData.append('instructions[]', textarea.value.trim());
    });

    // 2. UI Feedback
    submitBtn.innerText = 'Publishing...';
    submitBtn.disabled = true;

    try {
        const response = await fetch('../config/publish_recipe.php', {
            method: 'POST',
            body: formData
        });
        
        const text = await response.text();
        console.log(text);
        alert(text);
        return;

        
        if (result.status === 'success') {
            alert('Recipe Published Successfully!');
            window.location.href = '../explore/explore.html'; 
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Something went wrong during upload.');
    } finally {
        submitBtn.innerText = 'Publish Recipe';
        submitBtn.disabled = false;
    }
}