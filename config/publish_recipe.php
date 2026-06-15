<?php
header('Content-Type: application/json');
session_start();

include 'db.php';

if (!isset($pdo)) {
    echo json_encode(['status' => 'error', 'message' => 'DB connection missing']);
    exit;
}

try {
    $pdo->beginTransaction();

    $author_id  = $_SESSION['user_id'] ?? 1;
    $title      = $_POST['title'] ?? '';
    $description= $_POST['description'] ?? '';
    $category = trim($_POST['category'] ?? '');
    if ($category === '') {
        throw new Exception('Category is required');
    }

    $difficulty = $_POST['difficulty'] ?? 'Medium';
    $prep       = (int)($_POST['prep_time'] ?? 0);
    $cook       = (int)($_POST['cook_time'] ?? 0);
    $servings   = (int)($_POST['servings'] ?? 1);
    $total      = $prep + $cook;

    $img = 'default.jpg';
    if (!empty($_FILES['recipe_image']['name'])) {
        $img = time() . '_' . basename($_FILES['recipe_image']['name']);
        move_uploaded_file(
            $_FILES['recipe_image']['tmp_name'],
            "../explore/explore-images/" . $img
        );
    }

    $stmt = $pdo->prepare("
        INSERT INTO recipes
        (title, description, image, category, difficulty, prep_time, cook_time, total_time, servings, author_id)
        VALUES
        (:title, :description, :image, :category, :difficulty, :prep, :cook, :total, :servings, :author)
        RETURNING id
    ");

    $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':image' => $img,
        ':category' => $category,
        ':difficulty' => $difficulty,
        ':prep' => $prep,
        ':cook' => $cook,
        ':total' => $total,
        ':servings' => $servings,
        ':author' => $author_id
    ]);

    $recipe_id = $stmt->fetchColumn();

    if (!empty($_POST['ingredients'])) {
        $stmtIng = $pdo->prepare("
            INSERT INTO recipe_ingredients (recipe_id, ingredient_text)
            VALUES (:recipe, :name)
        ");

        foreach ($_POST['ingredients'] as $ing) {
            if (trim($ing)) {
                $stmtIng->execute([
                    ':recipe' => $recipe_id,
                    ':name' => $ing
                ]);
            }
        }
    }

    if (!empty($_POST['instructions'])) {
        $stmtIns = $pdo->prepare("
            INSERT INTO recipe_instructions (recipe_id, step_number, instruction_text)
            VALUES (:recipe, :step, :text)
        ");

        foreach ($_POST['instructions'] as $i => $text) {
            if (trim($text)) {
                $stmtIns->execute([
                    ':recipe' => $recipe_id,
                    ':step' => $i + 1,
                    ':text' => $text
                ]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Recipe uploaded']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
