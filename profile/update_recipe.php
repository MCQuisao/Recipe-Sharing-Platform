<?php
require_once('../config/db.php');
header('Content-Type: application/json');

// Get the JSON data from the JavaScript fetch
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No data received']);
    exit;
}

try {
    $pdo->beginTransaction();

    $prep = (int)($data['prep_time'] ?? 0);
    $cook = (int)($data['cook_time'] ?? 0);
    $total = $prep + $cook;

    $sqlRecipe = "UPDATE recipes SET 
                  title = :title, 
                  description = :desc, 
                  prep_time = :prep,
                  cook_time = :cook,
                  total_time = :total, 
                  servings = :serv,
                  difficulty = :diff,
                  category = :cat 
                  WHERE id = :id";
    
    $stmt = $pdo->prepare($sqlRecipe);
    $stmt->execute([
        'title' => $data['title'],
        'desc'  => $data['description'],
        'prep'  => $prep,
        'cook'  => $cook,
        'total' => $total,
        'serv'  => (int)$data['servings'],
        'diff'  => $data['difficulty'],
        'cat'   => $data['category'],
        'id'    => $data['id']
    ]);

    $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?")->execute([$data['id']]);
    $pdo->prepare("DELETE FROM recipe_instructions WHERE recipe_id = ?")->execute([$data['id']]);

    if (!empty($data['ingredients_list'])) {
        $ingredients = explode(' || ', $data['ingredients_list']);
        $insIng = $pdo->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient_text) VALUES (?, ?)");
        foreach ($ingredients as $ing) {
            if (trim($ing)) $insIng->execute([$data['id'], trim($ing)]);
        }
    }

    if (!empty($data['instructions_list'])) {
        $instructions = explode(' || ', $data['instructions_list']);
        $insInst = $pdo->prepare("INSERT INTO recipe_instructions (recipe_id, step_number, instruction_text) VALUES (?, ?, ?)");
        foreach ($instructions as $index => $inst) {
            if (trim($inst)) {
                $insInst->execute([$data['id'], $index + 1, trim($inst)]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>