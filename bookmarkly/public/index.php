<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../src/Database.php';

$db = new Database();
$settings = $db->getSettings();

// Check of dashboard beveiliging aan staat
if (!empty($settings['protect_dashboard'])) {
    // Check voor remember me cookie
    if (!isset($_SESSION['logged_in']) && isset($_COOKIE['remember_me'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Vernieuw de cookie met de huidige duur instelling
        $duration = getDurationInSeconds($settings['remember_duration'] ?? '2w');
        setcookie('remember_me', '1', time() + $duration, '/');
    }

    // Als niet ingelogd, redirect naar login
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: login.php');
        exit();
    }
}

// Helper functie voor cookie duur
function getDurationInSeconds($duration) {
    switch($duration) {
        case '4w':
            return 60 * 60 * 24 * 28; // 4 weken
        case '3m':
            return 60 * 60 * 24 * 90; // 3 maanden
        case '6m':
            return 60 * 60 * 24 * 180; // 6 maanden
        default:
            return 60 * 60 * 24 * 14; // 2 weken (standaard)
    }
}

// Debug info alleen tonen als debug_mode aan staat
if ($settings['debug_mode'] ?? false) {
    echo "<pre>";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "Database file: " . $db->file . "\n";
    echo "File exists: " . (file_exists($db->file) ? "Yes" : "No") . "\n";
    if (file_exists($db->file)) {
        echo "File permissions: " . substr(sprintf('%o', fileperms($db->file)), -4) . "\n";
        echo "File size: " . filesize($db->file) . " bytes\n";
        echo "\nFile contents:\n";
        echo file_get_contents($db->file) . "\n";
    }
    echo "\nLoaded data:\n";
    var_dump($db->data);
    echo "\nCategories:\n";
    var_dump($db->getCategories());
    echo "</pre>";
}

$categories = $db->getCategories();
$themes = require '../src/config/themes.php';
$current_theme = $themes[$settings['theme']] ?? $themes['light'];
?>

<!DOCTYPE html>
<html lang="nl" data-theme="<?php echo $settings['theme']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookmarkly</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/themes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/font-awesome-line-awesome/css/all.min.css">
    <style>
        :root {
            --background: <?php echo $current_theme['background']; ?>;
            --card-bg: <?php echo $current_theme['card_bg']; ?>;
            --text: <?php echo $current_theme['text']; ?>;
            --text-secondary: <?php echo $current_theme['text_secondary']; ?>;
            --border: <?php echo $current_theme['border']; ?>;
            --shadow: <?php echo $current_theme['shadow']; ?>;
            --hover: <?php echo $current_theme['hover']; ?>;
        }

        .background-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
            background: var(--background);
            <?php if (!empty($settings['background_image'])): ?>
            background-image: url('<?php echo htmlspecialchars($settings['background_image']) . '?v=' . time(); ?>');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
            filter: brightness(<?php echo ($settings['background_brightness'] ?? 100) / 100; ?>) 
                    saturate(<?php echo ($settings['background_saturation'] ?? 100) / 100; ?>);
            <?php endif; ?>
        }

        body {
            background: transparent;
            color: var(--text);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .category-column, .favorites-section {
            background: var(--card-bg);
            border: 1px solid var(--border);
            box-shadow: 0 2px 4px var(--shadow);
        }

        .bookmark-card {
            background: var(--card-bg);
            color: var(--text);
        }

        .bookmark-card:hover {
            background: var(--hover);
        }

        .favorite-card {
            background: var(--card-bg);
            color: var(--text);
        }

        .favorite-url {
            color: var(--text-secondary);
        }

        .admin-link {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
            opacity: 0.3;
            transition: opacity 0.2s;
        }
        
        .admin-link:hover {
            opacity: 0.8;
        }
        
        .admin-link svg {
            fill: var(--text);
        }

        <?php echo $db->getCustomCss(); ?>

        .bi {
            margin-right: 0.25rem;
            font-size: 1.1em;
            vertical-align: -0.125em;
        }

        <?php if (!empty($settings['background_image'])): ?>
            body::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: -1;
                background-image: url('<?php echo htmlspecialchars($settings['background_image']) . '?v=' . time(); ?>');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                filter: brightness(<?php echo ($settings['background_brightness'] ?? 100) / 100; ?>);
            }
        <?php endif; ?>
    </style>
</head>
<body>
    <div class="background-wrapper"></div>
    <div class="dashboard">
        <!-- Favorieten Sectie -->
        <section class="favorites-section">
            <h2 class="favorites-title"><?php echo htmlspecialchars($settings['dashboard_title'] ?? 'BOOKMARKS'); ?></h2>
            <div class="favorites-grid">
                <?php 
                $favorites = $db->getFavorites();
                foreach ($favorites as $bookmark):
                ?>
                    <a href="<?php echo htmlspecialchars($bookmark['url']); ?>" 
                       class="favorite-card"
                       <?php echo (($bookmark['target_blank'] ?? $settings['target_blank']) === true) ? 'target="_blank"' : ''; ?>>
                        <div class="favorite-icon-wrapper">
                            <div class="favorite-icon">
                                <?php 
                                $icon = $bookmark['icon'];
                                if (strpos($icon, '../data/uploads/') === 0) {
                                    // Converteer het pad naar de serve-upload route
                                    $filename = basename($icon);
                                    $icon = "serve-upload.php?file=" . urlencode($filename);
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($icon); ?>" alt="">
                            </div>
                        </div>
                        <div class="favorite-content">
                            <h3 class="favorite-title"><?php echo htmlspecialchars($bookmark['title']); ?></h3>
                            <p class="favorite-url"><?php echo rtrim(preg_replace('#^https?://#', '', htmlspecialchars($bookmark['url'])), '/'); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Subcategorieën -->
        <?php foreach ($categories as $category): ?>
            <?php if (($category['hidden'] ?? false) === false): ?>
                <div class="category-column">
                    <div class="category-header">
                        <h5 class="category-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                    </div>
                    <div class="bookmarks-list">
                        <?php 
                        $bookmarks = $db->getBookmarksByCategory($category['id']);
                        foreach ($bookmarks as $bookmark):
                        ?>
                            <div class="bookmark-wrapper">
                                <a href="<?php echo htmlspecialchars($bookmark['url']); ?>" 
                                   class="bookmark-card"
                                   <?php echo (($bookmark['target_blank'] ?? $settings['target_blank']) === true) ? 'target="_blank"' : ''; ?>>
                                    <div class="bookmark-icon-wrapper">
                                        <div class="bookmark-icon">
                                            <?php 
                                            $icon = $bookmark['icon'];
                                            if (strpos($icon, '../data/uploads/') === 0) {
                                                // Converteer het pad naar de serve-upload route
                                                $filename = basename($icon);
                                                $icon = "serve-upload.php?file=" . urlencode($filename);
                                            }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($icon); ?>" alt="">
                                        </div>
                                    </div>
                                    <div class="bookmark-content">
                                        <h3 class="bookmark-title"><?php echo htmlspecialchars($bookmark['title']); ?></h3>
                                        <!--<p class="bookmark-url"><?php echo rtrim(preg_replace('#^https?://#', '', htmlspecialchars($bookmark['url'])), '/'); ?></p>-->
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
        <div style="text-align: center; margin: 1rem;">
        <a href="add-bookmark.php" style="display: inline-block; background: #007bff; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; margin-right: 1rem;">
            <i class="las la-bookmark"></i> Bookmark
        </a>
        <a href="add-category.php" style="display: inline-block; background: #28a745; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;">
            <i class="las la-th-large"></i> Category
        </a>
    </div>
    <?php endif; ?>

    <div style="position: fixed; bottom: 1rem; right: 1rem; opacity: 0.3; transition: opacity 0.2s;">
        <a href="<?php echo isset($_SESSION['logged_in']) ? 'admin.php' : 'login.php'; ?>" 
           style="color: inherit; text-decoration: none;" 
           title="<?php echo isset($_SESSION['logged_in']) ? 'Admin' : 'Login'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-gear-fill" viewBox="0 0 16 16">
                <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z"/>
            </svg>
        </a>
    </div>

    <footer style="text-align: center; padding: 20px; color: #666; font-size: 0.9rem; margin-top: auto;">
        Made in Holland - bookmarkly.nl
    </footer>
</body>
</html> 