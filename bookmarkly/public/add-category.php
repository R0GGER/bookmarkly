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
$translations = require '../src/config/translations.php';
$current_settings = $db->getSettings();
$lang = $translations[$current_settings['language'] ?? 'nl'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    
    if (!empty($name)) {
        $db->addCategory($name);
        header('Location: admin.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_settings['language'] ?? 'nl'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['add_category_title']; ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/font-awesome-line-awesome/css/all.min.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .back-link {
            color: #666;
            text-decoration: none;
        }

        .back-link:hover {
            color: #333;
        }

        .settings-link {
            color: #007bff;
            text-decoration: none;
        }

        .settings-link:hover {
            color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="admin-header">
            <h2><i class="las la-border-all"></i> <?php echo $lang['add_category_title']; ?></h2>
            <div class="header-links">
                <a href="admin.php" class="back-link"><?php echo $lang['back_to_admin']; ?></a>
            </div>
        </div>
        <form method="POST">
            <div class="form-group">
                <label for="name"><?php echo $lang['category_name']; ?></label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <button type="submit"><?php echo $lang['add_button']; ?></button>
        </form>
    </div>
</body>
</html> 