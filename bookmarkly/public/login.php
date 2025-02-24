<?php
session_start();
require_once '../src/Database.php';
$db = new Database();

$error = '';
$settings = $db->getSettings();
$translations = require '../src/config/translations.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    $auth = require '../data/auth.php';
    
    if ($username === $auth['username'] && password_verify($password, $auth['password'])) {
        // Stel de sessie levensduur in op 2 weken (in seconden)
        ini_set('session.gc_maxlifetime', 14 * 24 * 60 * 60); // 14 dagen
        session_set_cookie_params(14 * 24 * 60 * 60); // 14 dagen
        
        // Start een nieuwe sessie met de nieuwe instellingen
        session_regenerate_id(true);
        
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        if ($remember) {
            $settings = $db->getSettings();
            $duration = getDurationInSeconds($settings['remember_duration'] ?? '2w');
            setcookie('remember_me', '1', time() + $duration, '/');
        }
        
        header('Location: ' . ($_SESSION['redirect_url'] ?? 'admin.php'));
        unset($_SESSION['redirect_url']);
        exit();
    } else {
        $error = $translations[$settings['language']]['invalid_credentials'];
    }
}

$themes = require '../src/config/themes.php';
$current_theme = $themes[$settings['theme']] ?? $themes['light'];

// Helper functie voor cookie duur (zelfde als in index.php)
function getDurationInSeconds($duration) {
    switch($duration) {
        case '4w':
            return 60 * 60 * 24 * 28;
        case '3m':
            return 60 * 60 * 24 * 90;
        case '6m':
            return 60 * 60 * 24 * 180;
        default:
            return 60 * 60 * 24 * 14;
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $settings['language']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookmarkly - <?php echo $translations[$settings['language']]['login_title']; ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css">
    <style>
    .remember-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 1rem 0;
    }

    .remember-group input[type="checkbox"] {
        margin: 0;
        width: auto;
    }

    .remember-group label {
        margin: 0;
        cursor: pointer;
    }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <i class="las la-lock"></i>
            <h1><?php echo $translations[$settings['language']]['login_header']; ?></h1>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="login-form">
            <div class="form-group">
                <label for="username"><?php echo $translations[$settings['language']]['username_label']; ?></label>
                <input type="text" id="username" name="username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password"><?php echo $translations[$settings['language']]['password_label']; ?></label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group remember-group">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember"><?php echo $translations[$settings['language']]['remember_me']; ?></label>
            </div>

            <button type="submit" class="button"><?php echo $translations[$settings['language']]['login_button']; ?></button>
        </form>

        <a href="index.php" class="login-back-link">
            <?php echo $translations[$settings['language']]['back_to_dashboard']; ?>
        </a>
    </div>
</body>
</html> 