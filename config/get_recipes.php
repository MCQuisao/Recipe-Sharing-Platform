// get_recipe.php
<?php
include "../db.php";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT r.*, u.name as author_name, u.profile_pic as author_pic 
            FROM recipes r 
            LEFT JOIN users u ON r.author_id = u.id
            WHERE r.id = $id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['error' => 'Recipe not found']);
    }
}
?>
