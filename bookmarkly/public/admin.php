<?php
session_start();

// Check voor remember me cookie en herstel sessie indien nodig
if (!isset($_SESSION['logged_in']) && isset($_COOKIE['remember_me'])) {
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../src/Database.php';

$db = new Database();
$db->cleanupDatabase();
$categories = $db->getCategories();
$favorites = $db->getFavorites();

$translations = require '../src/config/translations.php';
$current_settings = $db->getSettings();
$lang = $translations[$current_settings['language'] ?? 'nl'];

// Voeg deze helper functie toe bovenaan het bestand, na de require statements
function getCategoryPosition($categories, $categoryId) {
    foreach ($categories as $index => $category) {
        if ($category['id'] === $categoryId) {
            return $index;
        }
    }
    return -1;
}

// Update de move-category logica
if (isset($_GET['move']) && isset($_GET['category'])) {
    $direction = $_GET['move'];
    $categoryId = $_GET['category'];
    $categories = $db->getCategories();
    $currentPosition = getCategoryPosition($categories, $categoryId);

    if ($currentPosition !== -1) {
        if ($direction === 'up' && $currentPosition > 0) {
            // Wissel met de vorige categorie
            $temp = $categories[$currentPosition];
            $categories[$currentPosition] = $categories[$currentPosition - 1];
            $categories[$currentPosition - 1] = $temp;
            $db->updateCategoriesOrder($categories);
        } elseif ($direction === 'down' && $currentPosition < count($categories) - 1) {
            // Wissel met de volgende categorie
            $temp = $categories[$currentPosition];
            $categories[$currentPosition] = $categories[$currentPosition + 1];
            $categories[$currentPosition + 1] = $temp;
            $db->updateCategoriesOrder($categories);
        }
    }
    header('Location: admin.php');
    exit();
}

// Voeg dit toe aan het begin van admin.php waar de andere POST handlers staan
if (isset($_POST['update_title'])) {
    $current_settings = $db->getSettings();
    $current_settings['dashboard_title'] = $_POST['dashboard_title'] ?? 'BOOKMARKS';
    $db->updateSettings($current_settings);
    header('Location: admin.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_settings['language'] ?? 'nl'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookmarkly - <?php echo $lang['admin_panel']; ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/font-awesome-line-awesome/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title"><?php echo $lang['admin_panel']; ?></h1>
            <div class="header-links">
                <a href="add-category.php" class="add-link"><i class="las la-border-all"></i> <?php echo $lang['menu_add_category']; ?></a>
                <a href="add-bookmark.php" class="add-link"><i class="las la-bookmark"></i> <?php echo $lang['menu_add_bookmark']; ?></a>
                <a href="settings.php" class="settings-link"><?php echo $lang['menu_settings']; ?></a>
                <a href="logout.php" class="logout-link"><?php echo $lang['menu_logout']; ?></a>
                <a href="index.php" class="back-link"><?php echo $lang['menu_back_dashboard']; ?></a>
            </div>
        </div>

        <!-- Voeg dit toe in de admin interface, bijvoorbeeld na de bestaande knoppen -->
        <div class="admin-section">
            <h4><?php echo $lang['dashboard_title'] ?? 'Dashboard Titel'; ?></h4>
            <form method="POST" class="title-form">
                <div class="input-group">
                    <input type="text" 
                           name="dashboard_title" 
                           value="<?php echo htmlspecialchars($current_settings['dashboard_title'] ?? 'BOOKMARKS'); ?>"
                           class="form-control"
                           placeholder="BOOKMARKS">
                    <button type="submit" 
                            name="update_title" 
                            class="btn btn-primary">
                        <?php echo $lang['save_changes']; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Favorieten sectie eerst -->
        <div class="category-section">
            <div class="category-header">
                <div class="category-title-wrapper">
                    <div class="category-title"><?php echo htmlspecialchars($current_settings['dashboard_title'] ?? 'BOOKMARKS'); ?></div>
                    <a href="add-bookmark.php?favorite=1" class="add-bookmark-btn" title="<?php echo $lang['add_bookmark']; ?>">
                        <i class="las la-bookmark"></i>
                    </a>
                </div>
            </div>
            
            <div class="bookmark-list">
                <?php foreach ($favorites as $bookmark): ?>
                <div class="item-row">
                    <div class="item-title"><?php echo htmlspecialchars($bookmark['title']); ?></div>
                    <div class="action-buttons">
                        <!-- Move buttons voor favorieten -->
                        <a href="move-favorite.php?id=<?php echo $bookmark['id']; ?>&direction=up" 
                           class="move-btn" 
                           title="<?php echo $lang['move_up']; ?>">
                            <i class="las la-arrow-up"></i>
                        </a>
                        <a href="move-favorite.php?id=<?php echo $bookmark['id']; ?>&direction=down" 
                           class="move-btn" 
                           title="<?php echo $lang['move_down']; ?>">
                            <i class="las la-arrow-down"></i>
                        </a>
                        <a href="edit-bookmark.php?id=<?php echo $bookmark['id']; ?>" class="edit-btn" title="Bewerken">
                            <i class="las la-edit"></i>
                        </a>
                        <a href="#" onclick="event.preventDefault(); if(confirm('<?php echo $lang['confirm_delete_bookmark']; ?>')) document.getElementById('delete-favorite-<?php echo $bookmark['id']; ?>').submit();" 
                           class="delete-btn" title="Verwijderen">
                            <i class="las la-trash"></i>
                        </a>
                        <form id="delete-favorite-<?php echo $bookmark['id']; ?>" method="POST" action="delete-bookmark.php" style="display: none;">
                            <input type="hidden" name="id" value="<?php echo $bookmark['id']; ?>">
                            <input type="hidden" name="from_favorites" value="1">
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Daarna de categorieën -->
        <?php foreach ($categories as $category): ?>
        <div class="category-section">
            <div class="category-header">
                <div class="category-title-wrapper">
                    <div class="category-title"><?php echo htmlspecialchars($category['name']); ?></div>
                    <div class="order-input-wrapper">
                        <input type="number" 
                               class="order-input" 
                               value="<?php echo $category['order'] ?? 99; ?>"
                               min="1" 
                               max="99"
                               data-category-id="<?php echo $category['id']; ?>"
                               onchange="updateCategoryOrder(this)">
                    </div>
                    <a href="add-bookmark.php?category=<?php echo $category['id']; ?>" 
                       class="add-bookmark-btn" 
                       title="<?php echo $lang['add_bookmark']; ?>">
                        <i class="las la-bookmark"></i>
                    </a>
                </div>
                <div class="action-buttons">
                    <!-- Move buttons voor categorieën -->
                    <a href="move-category.php?id=<?php echo $category['id']; ?>&direction=up" 
                       class="move-btn" 
                       title="<?php echo $lang['move_up']; ?>">
                        <i class="las la-arrow-up"></i>
                    </a>
                    <a href="move-category.php?id=<?php echo $category['id']; ?>&direction=down" 
                       class="move-btn" 
                       title="<?php echo $lang['move_down']; ?>">
                        <i class="las la-arrow-down"></i>
                    </a>
                    <a href="edit-category.php?id=<?php echo $category['id']; ?>" class="edit-btn" title="Bewerken">
                        <i class="las la-edit"></i>
                    </a>
                    <a href="#" onclick="event.preventDefault(); if(confirm('<?php echo $lang['confirm_delete_category']; ?>')) document.getElementById('delete-category-<?php echo $category['id']; ?>').submit();" 
                       class="delete-btn" title="Verwijderen">
                        <i class="las la-trash"></i>
                    </a>
                    <form id="delete-category-<?php echo $category['id']; ?>" method="POST" action="delete-category.php" style="display: none;">
                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                    </form>
                    <button onclick="toggleVisibility('<?php echo $category['id']; ?>', <?php echo ($category['hidden'] ?? false) ? 'false' : 'true'; ?>)" 
                            class="visibility-btn" 
                            title="<?php echo ($category['hidden'] ?? false) ? $lang['show_category'] : $lang['hide_category']; ?>">
                        <i class="las <?php echo ($category['hidden'] ?? false) ? 'la-eye-slash' : 'la-eye'; ?>"></i>
                    </button>
                </div>
            </div>
            
            <div class="bookmark-list">
                <?php 
                $bookmarks = $db->getBookmarksByCategory($category['id']);
                foreach ($bookmarks as $bookmark):
                ?>
                <div class="item-row">
                    <div class="item-title"><?php echo htmlspecialchars($bookmark['title']); ?></div>
                    <div class="action-buttons">
                        <!-- Move buttons voor bookmarks -->
                        <a href="move-bookmark.php?id=<?php echo $bookmark['id']; ?>&categoryId=<?php echo $category['id']; ?>&direction=up" 
                           class="move-btn" 
                           title="<?php echo $lang['move_up']; ?>">
                            <i class="las la-arrow-up"></i>
                        </a>
                        <a href="move-bookmark.php?id=<?php echo $bookmark['id']; ?>&categoryId=<?php echo $category['id']; ?>&direction=down" 
                           class="move-btn" 
                           title="<?php echo $lang['move_down']; ?>">
                            <i class="las la-arrow-down"></i>
                        </a>
                        <a href="edit-bookmark.php?id=<?php echo $bookmark['id']; ?>" class="edit-btn" title="Bewerken">
                            <i class="las la-edit"></i>
                        </a>
                        <a href="#" onclick="event.preventDefault(); if(confirm('<?php echo $lang['confirm_delete_bookmark']; ?>')) document.getElementById('delete-bookmark-<?php echo $bookmark['id']; ?>-<?php echo $category['id']; ?>').submit();" 
                           class="delete-btn" title="Verwijderen">
                            <i class="las la-trash"></i>
                        </a>
                        <form id="delete-bookmark-<?php echo $bookmark['id']; ?>-<?php echo $category['id']; ?>" method="POST" action="delete-bookmark.php" style="display: none;">
                            <input type="hidden" name="id" value="<?php echo $bookmark['id']; ?>">
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <style>
        .admin-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .title-form {
            margin-top: 1rem;
        }

        .input-group {
            display: flex;
            gap: 0.5rem;
        }

        .form-control {
            flex: 1;
            padding: 0.5rem;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-control:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }

        .btn-primary {
            background: #007bff;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .category-title-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .add-bookmark-btn {
            color: #28a745;
            text-decoration: none;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            transition: transform 0.2s;
        }

        .add-bookmark-btn:hover {
            transform: scale(1.1);
        }

        .order-input-wrapper {
            margin: 0 10px;
        }

        .order-input {
            width: 50px;
            padding: 2px 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            font-size: 14px;
        }

        .order-input:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.1);
        }

        .visibility-btn {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 4px 8px;
            font-size: 1.2rem;
            transition: all 0.2s;
            border-radius: 4px;
        }

        .visibility-btn:hover {
            color: #007bff;
            background-color: rgba(0, 123, 255, 0.1);
            transform: translateY(-1px);
        }

        /* Zorg dat de visibility knop in lijn is met de andere knoppen */
        .action-buttons {
            display: flex;
            gap: 0.25rem;
            align-items: center;
        }

        .action-buttons > * {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>

    <script>
    function updateCategoryOrder(input) {
        const categoryId = input.dataset.categoryId;
        const newOrder = parseInt(input.value) || 99;

        fetch('update-category-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                categoryId: categoryId,
                order: newOrder
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating category order');
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function toggleVisibility(categoryId, hidden) {
        fetch('toggle-category-visibility.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                categoryId: categoryId,
                hidden: hidden
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating category visibility');
            }
        })
        .catch(error => console.error('Error:', error));
    }
    </script>

    <footer style="text-align: center; padding: 20px; color: #666; font-size: 0.9rem; margin-top: auto;">
        Made in Holland - bookmarkly.nl
    </footer>
</body>
</html> 