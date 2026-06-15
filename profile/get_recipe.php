<?php
require_once('../config/db.php');
header('Content-Type: application/json');

if(isset($_GET['id'])) {
    try {
        $id = $_GET['id'];
        $query = "SELECT r.*, 
                  (SELECT string_agg(ingredient_text, '||' ORDER BY id) 
                   FROM recipe_ingredients WHERE recipe_id = r.id) as ingredients_list,
                  (SELECT string_agg(instruction_text, '||' ORDER BY step_number) 
                   FROM recipe_instructions WHERE recipe_id = r.id) as instructions_list
                  FROM recipes r 
                  WHERE r.id = ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>