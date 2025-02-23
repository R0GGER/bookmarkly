<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../src/Database.php';
$db = new Database();
$translations = require '../src/config/translations.php';
$current_settings = $db->getSettings();
$lang = $translations[$current_settings['language'] ?? 'nl'];

$error = '';
$category = null;

if (isset($_GET['id'])) {
    $categories = $db->getCategories();
    foreach ($categories as $cat) {
        if ($cat['id'] === $_GET['id']) {
            $category = $cat;
            break;
        }
    }
}

if (!$category) {
    header('Location: admin.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $updatedCategory = [
            'id' => $category['id'],
            'name' => $_POST['name']
        ];
        
        $db->updateCategory($updatedCategory);
        header('Location: admin.php');
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_settings['language'] ?? 'nl'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['edit_category']; ?></title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="form-container">
        <div class="admin-header">
            <h1><?php echo $lang['edit_category']; ?></h1>
            <a href="admin.php" class="back-link"><?php echo $lang['back_to_admin']; ?></a>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="name"><?php echo $lang['category_name']; ?></label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="<?php echo htmlspecialchars($category['name']); ?>" 
                       required>
            </div>

            <button type="submit"><?php echo $lang['save_changes']; ?></button>
        </form>
    </div>
</body>
</html> 