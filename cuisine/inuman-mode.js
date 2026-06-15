// Recipe Data
const pulutanData = {
    sisig: {
        title: "Sizzling Pork Sisig",
        desc: "A Kapampangan delicacy composed of minced pork meat, ears, and face. Best served sizzling.",
        ingredients: [
            "1 lb Pig ears and snout (boiled & grilled)",
            "1/2 lb Chicken liver",
            "1 large Onion, minced",
            "3 cloves Garlic, minced",
            "3 Thai Chili peppers (Siling Labuyo)",
            "3 tbsp Soy Sauce",
            "4 tbsp Calamansi juice",
            "1 tbsp Butter",
            "Salt & Pepper to taste",
            "1 Egg (optional topping)"
        ],
        steps: [
            "Boil the pig ears and snout until tender (approx. 45-60 mins). Drain and grill until charred.",
            "Chop the grilled pork into fine pieces. Grill and chop the chicken liver as well.",
            "Heat a pan or sizzling plate. Melt butter and sauté garlic and onions until aromatic.",
            "Add the chopped meat and liver. Sauté for 5 minutes on high heat.",
            "Pour in soy sauce and calamansi juice. Stir well.",
            "Add chopped chili peppers. Season with salt and pepper.",
            "Serve on a sizzling plate and crack an egg on top while hot."
        ]
    },
    tokwatbaboy: {
        title: "Tokwa't Baboy",
        desc: "Deep-fried tofu and pork belly cubes served in a savory vinegar-soy sauce dip.",
        ingredients: [
            "1 lb Pork Belly",
            "2 blocks Firm Tofu",
            "1 cup White Vinegar",
            "1/2 cup Soy Sauce",
            "1 Red Onion, chopped",
            "1 tbsp Sugar",
            "Pinch of Salt",
            "Oil for deep frying"
        ],
        steps: [
            "Boil the pork belly in salted water until tender. Let cool, then cut into cubes.",
            "Deep fry the firm tofu until golden brown. Cut into cubes.",
            "(Optional) Deep fry the pork belly for a crispy texture.",
            "In a bowl, mix vinegar, soy sauce, sugar, salt, and chopped onions.",
            "Combine pork and tofu in a serving bowl and pour the sauce over, or serve sauce on the side."
        ]
    },
    liempo: {
        title: "Inihaw na Liempo",
        desc: "Filipino-style grilled pork belly marinated in a sweet and savory soy-garlic blend.",
        ingredients: [
            "1kg Pork Belly Slabs",
            "1/2 cup Soy Sauce",
            "1/4 cup Calamansi Juice",
            "1/2 cup Banana Ketchup",
            "5 cloves Garlic, minced",
            "2 tbsp Brown Sugar",
            "1 tsp Ground Black Pepper"
        ],
        steps: [
            "Combine soy sauce, calamansi, ketchup, garlic, sugar, and pepper in a bowl.",
            "Marinate the pork belly slabs for at least 3 hours (overnight is best).",
            "Prepare a charcoal grill. Grill the pork for 5-8 minutes per side, basting with the marinade.",
            "Cook until the fat is charred and meat is tender.",
            "Let rest for 5 minutes before chopping into serving pieces."
        ]
    },
    gambas: {
        title: "Spicy Gambas",
        desc: "Shrimp stir-fried in a rich tomato-garlic sauce with a spicy kick.",
        ingredients: [
            "500g Shrimp (peeled & deveined)",
            "5 cloves Garlic, minced",
            "1/2 cup Tomato Sauce",
            "1 tbsp Olive Oil",
            "3 pcs Bird's Eye Chili (chopped)",
            "1 tbsp Paprika",
            "1 tbsp Brown Sugar",
            "Salt & Pepper"
        ],
        steps: [
            "Heat olive oil in a pan over medium heat.",
            "Sauté garlic until fragrant (be careful not to burn).",
            "Add the shrimp and cook for 1 minute.",
            "Stir in tomato sauce, paprika, sugar, and chilies.",
            "Simmer for 2-3 minutes until sauce thickens and shrimp is fully cooked.",
            "Season with salt and pepper. Serve hot."
        ]
    }
};

// Logic
const modal = document.getElementById('recipeModal');

function openRecipe(key) {
    const data = pulutanData[key];
    if(!data) return;

    // Set Text
    document.getElementById('modalTitle').innerText = data.title;
    document.getElementById('modalDesc').innerText = data.desc;

    // Set Ingredients
    const ingList = document.getElementById('modalIngredients');
    ingList.innerHTML = "";
    data.ingredients.forEach(item => {
        let li = document.createElement('li');
        li.innerText = item;
        ingList.appendChild(li);
    });

    // Set Steps
    const stepDiv = document.getElementById('modalSteps');
    stepDiv.innerHTML = "";
    let ol = document.createElement('ol');
    data.steps.forEach(step => {
        let li = document.createElement('li');
        li.innerText = step;
        ol.appendChild(li);
    });
    stepDiv.appendChild(ol);

    // Show
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Close on outside click
window.onclick = function(e) {
    if(e.target == modal) closeModal();
}