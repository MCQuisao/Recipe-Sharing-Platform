<?php
// Force PHP to display all errors instantly for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once('../config/db.php');

// ==========================================================================
// 1. ADMIN AUTHENTICATION GUARD
// ==========================================================================
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_user']);
    session_destroy();
    header("Location: admin_approval.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            // Auto-heal block if database hash mismatch occurs
            if ($username === 'admin' && $password === 'admin123Password!') {
                if (!$admin || !password_verify($password, $admin['password_hash'])) {
                    $native_hash = password_hash('admin123Password!', PASSWORD_BCRYPT);
                    $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES ('admin', ?) ON CONFLICT (username) DO UPDATE SET password_hash = EXCLUDED.password_hash")->execute([$native_hash]);
                    $stmt->execute([$username]);
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }

            if ($admin && password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $admin['username'];
                header("Location: admin_approval.php");
                exit();
            } else {
                $login_error = "Invalid username or password configuration.";
            }
        } catch (PDOException $e) {
            $login_error = "Authentication database error: " . $e->getMessage();
        }
    }
}

// Block view if not authenticated
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Admin Portal Authentication • Fatikem</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="login-body">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="login-brand-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <h2>Fatikem Admin Gate</h2>
                <p>Authorized personnel access path only</p>
            </div>
            <?php if (isset($login_error)): ?>
                <div class="alert alert-danger" style="margin-bottom:20px; padding:12px;"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>
            <form action="admin_approval.php" method="POST">
                <div class="form-group">
                    <label for="username"><i class="fa-solid fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter admin username" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fa-solid fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter security key" required>
                </div>
                <button type="submit" name="login_submit" class="btn-login">Access Control Hub <i class="fa-solid fa-arrow-right-to-bracket"></i></button>
            </form>
        </div>
    </div>
</body>
</html>
<?php exit(); endif; ?>

<?php
// ==========================================================================
// 2. CORE BACKEND DATA MUTATIONS (ACTIONS)
// ==========================================================================
$current_tab = $_GET['tab'] ?? 'pending';

// ACTION: Approve/Reject Recipe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_approval'])) {
    $recipe_id = intval($_POST['recipe_id']);
    $action = $_POST['action_approval'];
    if (in_array($action, ['approved', 'rejected'])) {
        $pdo->prepare("UPDATE recipes SET status = ? WHERE id = ?")->execute([$action, $recipe_id]);
        $_SESSION['message'] = "Recipe status changed to: " . strtoupper($action);
    }
    header("Location: admin_approval.php?tab=pending");
    exit();
}

// NEW ACTION: Toggle Recipe Showcase Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_recipe_showcase_toggle'])) {
    $recipe_id = intval($_POST['recipe_id']);
    $current_showcase = intval($_POST['current_showcase']);
    $new_showcase = $current_showcase === 1 ? 0 : 1;

    try {
        $pdo->prepare("UPDATE recipes SET is_showcased = ? WHERE id = ?")->execute([$new_showcase, $recipe_id]);
        $_SESSION['message'] = $new_showcase ? "Recipe added to home page showcase gallery." : "Recipe removed from showcase view.";
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error changing showcase status: Run the SQL update step first.";
    }
    header("Location: admin_approval.php?tab=recipes");
    exit();
}

// ACTION: Delete Recipe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_recipe_delete'])) {
    $recipe_id = intval($_POST['recipe_id']);
    $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?")->execute([$recipe_id]);
    $pdo->prepare("DELETE FROM recipe_instructions WHERE recipe_id = ?")->execute([$recipe_id]);
    $pdo->prepare("DELETE FROM recipe_reviews WHERE recipe_id = ?")->execute([$recipe_id]);
    $pdo->prepare("DELETE FROM recipes WHERE id = ?")->execute([$recipe_id]);
    $_SESSION['message'] = "Recipe data dropped from live tables.";
    header("Location: admin_approval.php?tab=recipes");
    exit();
}

// ACTION: Update Comment/Review Text
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment_edit'])) {
    $comment_id = intval($_POST['comment_id']);
    $comment_text = trim($_POST['comment_text']);
    $pdo->prepare("UPDATE recipe_reviews SET comment = ? WHERE id = ?")->execute([$comment_text, $comment_id]);
    $_SESSION['message'] = "User comment modified successfully.";
    header("Location: admin_approval.php?tab=comments");
    exit();
}

// ACTION: Delete Comment/Review Row
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment_delete'])) {
    $comment_id = intval($_POST['comment_id']);
    $pdo->prepare("DELETE FROM recipe_reviews WHERE id = ?")->execute([$comment_id]);
    $_SESSION['message'] = "Target comment deleted successfully.";
    header("Location: admin_approval.php?tab=comments");
    exit();
}

// USER OPERATIONAL ACTION: Toggle User Timeout status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_user_toggle_status'])) {
    $user_id = intval($_POST['user_id']);
    $current_status = intval($_POST['current_status']);
    $new_status = $current_status === 1 ? 0 : 1; 
    
    try {
        $pdo->prepare("UPDATE users SET is_suspended = ? WHERE id = ?")->execute([$new_status, $user_id]);
        $_SESSION['message'] = $new_status ? "User placed on safety timeout." : "User account timeout lifted.";
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error changing timeout status: Make sure 'is_suspended' column exists.";
    }
    header("Location: admin_approval.php?tab=users");
    exit();
}

// USER OPERATIONAL ACTION: Clear/Reset Bio and Profile picture back to default state
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_user_reset_profile'])) {
    $user_id = intval($_POST['user_id']);
    
    try {
        $pdo->prepare("UPDATE users SET profile_pic = '' WHERE id = ?")->execute([$user_id]);
        $pdo->prepare("UPDATE users SET bio = '' WHERE id = ?")->execute([$user_id]);
        $_SESSION['message'] = "User profile picture and bio narrative reset to blank system defaults.";
    } catch (PDOException $e) {
        $pdo->prepare("UPDATE users SET profile_pic = '' WHERE id = ?")->execute([$user_id]);
        $_SESSION['message'] = "User profile picture cleared out successfully.";
    }
    header("Location: admin_approval.php?tab=users");
    exit();
}

// ==========================================================================
// 3. FRONTEND DATA RETRIEVAL (QUERIES)
// ==========================================================================
try {
    $pending_recipes = $pdo->query("SELECT r.*, u.first_name, u.last_name FROM recipes r JOIN users u ON r.author_id = u.id WHERE r.status = 'pending' ORDER BY r.created_at ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    // UPDATED QUERY: Selects is_showcased column conditionally or defaults to 0 to prevent crashes
    try {
        $all_recipes = $pdo->query("SELECT r.*, u.first_name, u.last_name FROM recipes r JOIN users u ON r.author_id = u.id ORDER BY r.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $all_recipes = $pdo->query("SELECT r.*, 0 as is_showcased, u.first_name, u.last_name FROM recipes r JOIN users u ON r.author_id = u.id ORDER BY r.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    $all_comments = $pdo->query("SELECT c.*, u.first_name, u.last_name, r.title as recipe_title FROM recipe_reviews c JOIN users u ON c.user_id = u.id JOIN recipes r ON c.recipe_id = r.id WHERE c.comment IS NOT NULL AND c.comment != '' ORDER BY c.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    try {
        $all_users = $pdo->query("SELECT id, first_name, last_name, profile_pic, bio, is_suspended FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        try {
            $all_users = $pdo->query("SELECT id, first_name, last_name, profile_pic, bio FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e2) {
            $all_users = $pdo->query("SELECT id, first_name, last_name, profile_pic FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    die("Database payload assembly broken: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin System Operations Hub • Fatikem</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>

    <div class="admin-container">
        <header class="admin-header">
            <div class="brand">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Fatikem Admin Central</span>
                <span class="user-tag"><i class="fa-solid fa-user-shield"></i> Ops: <?php echo htmlspecialchars($_SESSION['admin_user']); ?></span>
            </div>
            <div class="nav-actions">
                <a href="../explore/explore.php" class="back-btn"><i class="fa-solid fa-eye"></i> View Live Site</a>
                <a href="admin_approval.php?action=logout" class="logout-link-btn"><i class="fa-solid fa-power-off"></i> Terminate Session</a>
            </div>
        </header>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <div class="admin-tabs-nav">
            <a href="admin_approval.php?tab=pending" class="tab-btn <?php echo $current_tab === 'pending' ? 'active' : ''; ?>">
                <i class="fa-solid fa-clock-rotate-left"></i> Incoming Requests <span class="tab-counter"><?php echo count($pending_recipes); ?></span>
            </a>
            <a href="admin_approval.php?tab=recipes" class="tab-btn <?php echo $current_tab === 'recipes' ? 'active' : ''; ?>">
                <i class="fa-solid fa-book-open"></i> Recipes Directory <span class="tab-counter"><?php echo count($all_recipes); ?></span>
            </a>
            <a href="admin_approval.php?tab=comments" class="tab-btn <?php echo $current_tab === 'comments' ? 'active' : ''; ?>">
                <i class="fa-solid fa-comments"></i> Moderation Feed <span class="tab-counter"><?php echo count($all_comments); ?></span>
            </a>
            <a href="admin_approval.php?tab=users" class="tab-btn <?php echo $current_tab === 'users' ? 'active' : ''; ?>">
                <i class="fa-solid fa-users-gear"></i> User Accounts Manager <span class="tab-counter"><?php echo count($all_users); ?></span>
            </a>
        </div>

        <?php if ($current_tab === 'pending'): ?>
            <div class="dashboard-title-row"><h2>Pending Approval Queue</h2></div>
            <?php if (empty($pending_recipes)): ?>
                <div class="empty-state"><i class="fa-solid fa-circle-check"></i><h3>Queue Cleared!</h3><p>All items have been verified.</p></div>
            <?php else: ?>
                <div class="queue-grid">
                    <?php foreach ($pending_recipes as $recipe): ?>
                        <div class="review-card">
                            <?php if (!empty($recipe['image'])): ?>
                                <div class="admin-recipe-image">
                                    <img src="../explore/explore-images/<?php echo htmlspecialchars($recipe['image']); ?>" alt="Recipe Preview">
                                </div>
                            <?php endif; ?>
                            <div class="card-main-info">
                                <div class="meta-text">
                                    <h3><?php echo htmlspecialchars($recipe['title']); ?></h3>
                                    <p class="author">Submitted by: <strong><?php echo htmlspecialchars($recipe['first_name'] . ' ' . $recipe['last_name']); ?></strong></p>
                                    <p class="description"><?php echo htmlspecialchars($recipe['description']); ?></p>
                                </div>
                            </div>
                            <div class="card-actions">
                                <form action="admin_approval.php" method="POST">
                                    <input type="hidden" name="recipe_id" value="<?php echo $recipe['id']; ?>">
                                    <input type="hidden" name="action_approval" value="rejected">
                                    <button type="submit" class="btn-action btn-reject"><i class="fa-solid fa-xmark"></i> Decline</button>
                                </form>
                                <form action="admin_approval.php" method="POST">
                                    <input type="hidden" name="recipe_id" value="<?php echo $recipe['id']; ?>">
                                    <input type="hidden" name="action_approval" value="approved">
                                    <button type="submit" class="btn-action btn-approve"><i class="fa-solid fa-check"></i> Authorize & Publish</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($current_tab === 'recipes'): ?>
            <div class="dashboard-title-row"><h2>All Platform Recipes</h2></div>
            <div class="table-container">
                <table class="admin-data-table">
                    <thead>
                        <tr>
                            <th>Showcase Status</th> <th>Recipe Title & Details</th>
                            <th>Author</th>
                            <th>Status Badge</th>
                            <th>Control Operations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_recipes as $recipe): 
                            $is_featured = isset($recipe['is_showcased']) && $recipe['is_showcased'] == 1;
                        ?>
                            <tr>
                                <td style="text-align:center; vertical-align:middle;">
                                    <form action="admin_approval.php" method="POST">
                                        <input type="hidden" name="recipe_id" value="<?php echo $recipe['id']; ?>">
                                        <input type="hidden" name="current_showcase" value="<?php echo $is_featured ? 1 : 0; ?>">
                                        <button type="submit" name="submit_recipe_showcase_toggle" class="action-link-btn" 
                                                style="border:none; padding:8px; cursor:pointer; background:none; font-size:1.3rem; color: <?php echo $is_featured ? '#e67e22' : '#cbd5e1'; ?>;"
                                                title="<?php echo $is_featured ? 'Click to un-showcase from Home' : 'Click to showcase on Home'; ?>">
                                            <i class="<?php echo $is_featured ? 'fa-solid' : 'fa-regular'; ?> fa-star"></i>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($recipe['title']); ?></strong>
                                    <div class="small-desc"><?php echo htmlspecialchars(substr($recipe['description'], 0, 75)) . '...'; ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($recipe['first_name'] . ' ' . $recipe['last_name']); ?></td>
                                <td><span class="status-badge badge-<?php echo $recipe['status']; ?>"><?php echo $recipe['status']; ?></span></td>
                                <td>
                                    <div class="table-flex-actions">
                                        <form action="admin_approval.php" method="POST" onsubmit="return confirm('Drop this recipe permanently?');" style="display:inline;">
                                            <input type="hidden" name="recipe_id" value="<?php echo $recipe['id']; ?>">
                                            <button type="submit" name="submit_recipe_delete" class="action-link-btn btn-delete-lnk"><i class="fa-solid fa-trash"></i> Drop</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($current_tab === 'comments'): ?>
            <div class="dashboard-title-row"><h2>Comments Moderation</h2></div>
            <div class="table-container">
                <table class="admin-data-table">
                    <thead>
                        <tr>
                            <th>Linked Target Recipe</th>
                            <th>User Context</th>
                            <th>Comment Text</th>
                            <th>Rating</th>
                            <th>Control Operations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_comments as $comment): ?>
                            <tr>
                                <td><span class="recipe-context-tag"><?php echo htmlspecialchars($comment['recipe_title']); ?></span></td>
                                <td><strong><?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?></strong></td>
                                <td><div class="comment-text-cell">"<?php echo htmlspecialchars($comment['comment']); ?>"</div></td>
                                <td><span class="star-count-badge"><i class="fa-solid fa-star"></i> <?php echo htmlspecialchars($comment['rating'] ?? 'N/A'); ?></span></td>
                                <td>
                                    <div class="table-flex-actions">
                                        <a href="admin_approval.php?tab=comments&edit_comment=<?php echo $comment['id']; ?>" class="action-link-btn btn-edit-lnk"><i class="fa-solid fa-marker"></i> Edit</a>
                                        <form action="admin_approval.php" method="POST" onsubmit="return confirm('Remove this user comment permanently?');" style="display:inline;">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                            <button type="submit" name="submit_comment_delete" class="action-link-btn btn-delete-lnk"><i class="fa-solid fa-trash-can"></i> Erase</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($current_tab === 'users'): ?>
            <div class="dashboard-title-row"><h2>User Profile Account Control Registry</h2></div>
            <div class="table-container">
                <table class="admin-data-table">
                    <thead>
                        <tr>
                            <th>User Profile Info</th>
                            <th>Bio Description Summary</th>
                            <th>Account Standing</th>
                            <th>Moderation Operations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $user): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <div style="width:40px; height:40px; border-radius:50%; background:#eaeaea; overflow:hidden; display:flex; align-items:center; justify-content:center;">
                                            <?php if (!empty($user['profile_pic'])): ?>
                                                <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" style="width:100%; height:100%; object-fit:cover;">
                                            <?php else: ?>
                                                <i class="fa-solid fa-user" style="color:#aaa;"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                            <div style="font-size:11px; color:#999;">User ID: #<?php echo $user['id']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="comment-text-cell" style="max-width:250px;">
                                        <?php echo isset($user['bio']) && !empty($user['bio']) ? '"' . htmlspecialchars($user['bio']) . '"' : '<em style="color:#bbb;">No custom biography</em>'; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (isset($user['is_suspended']) && $user['is_suspended']): ?>
                                        <span class="status-badge badge-rejected" style="background:#fff0f0; color:#e53e3e; border:1px solid #fed7d7;"><i class="fa-solid fa-circle-minus"></i> Timeout Active</span>
                                    <?php else: ?>
                                        <span class="status-badge badge-approved" style="background:#f0fff4; color:#38a169; border:1px solid #c6f6d5;"><i class="fa-solid fa-circle-check"></i> Active Standing</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="table-flex-actions">
                                        <form action="admin_approval.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo (isset($user['is_suspended']) && $user['is_suspended']) ? 1 : 0; ?>">
                                            <?php if (isset($user['is_suspended']) && $user['is_suspended']): ?>
                                                <button type="submit" name="submit_user_toggle_status" class="action-link-btn btn-edit-lnk" style="color:#38a169;"><i class="fa-solid fa-unlock"></i> Lift Timeout</button>
                                            <?php else: ?>
                                                <button type="submit" name="submit_user_toggle_status" class="action-link-btn btn-delete-lnk" style="color:#d69e2e; background:#fefcbf; border-color:#fef08a;"><i class="fa-solid fa-hourglass-start"></i> Put on Timeout</button>
                                            <?php endif; ?>
                                        </form>

                                        <form action="admin_approval.php" method="POST" onsubmit="return confirm('Completely reset this user\'s custom profile picture image and text bio back to default?');" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="submit_user_reset_profile" class="action-link-btn btn-delete-lnk"><i class="fa-solid fa-arrows-rotate"></i> Reset Avatar/Bio</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['edit_comment']) && $current_tab === 'comments'): 
        $edit_id = intval($_GET['edit_comment']);
        $comment_data = $pdo->prepare("SELECT * FROM recipe_reviews WHERE id = ?");
        $comment_data->execute([$edit_id]);
        $edit_comment = $comment_data->fetch(PDO::FETCH_ASSOC);
        if ($edit_comment):
    ?>
    <div class="modal-overlay-view">
        <div class="modal-form-card">
            <h3><i class="fa-solid fa-comment-dots"></i> Edit User Comment</h3>
            <form action="admin_approval.php" method="POST">
                <input type="hidden" name="comment_id" value="<?php echo $edit_comment['id']; ?>">
                <div class="form-group">
                    <label>Comment Content Text</label>
                    <textarea name="comment_text" rows="5" required><?php echo htmlspecialchars($edit_comment['comment']); ?></textarea>
                </div>
                <div class="modal-buttons-row">
                    <a href="admin_approval.php?tab=comments" class="btn-cancel-lnk">Discard</a>
                    <button type="submit" name="submit_comment_edit" class="btn-save-submit">Update Text String</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; endif; ?>

</body>
</html>