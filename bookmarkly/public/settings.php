<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../src/Database.php';
$db = new Database();
$themes = require '../src/config/themes.php';
$translations = require '../src/config/translations.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_credentials'])) {
        $auth_config = require '../data/auth.php';
        $current_password = $_POST['current_password'];
        $new_username = $_POST['new_username'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Verifieer het huidige wachtwoord
        if (password_verify($current_password, $auth_config['password'])) {
            $new_auth = [
                'username' => $new_username ?: $auth_config['username']
            ];

            // Update wachtwoord alleen als er een nieuw wachtwoord is ingevoerd
            if (!empty($new_password)) {
                if ($new_password === $confirm_password) {
                    $new_auth['password'] = password_hash($new_password, PASSWORD_DEFAULT);
                } else {
                    $_SESSION['error'] = $lang['passwords_dont_match'];
                    header('Location: settings.php');
                    exit;
                }
            } else {
                $new_auth['password'] = $auth_config['password'];
            }

            // Schrijf de nieuwe configuratie naar het bestand
            file_put_contents('../data/auth.php', "<?php\nreturn " . var_export($new_auth, true) . ";\n");
            $_SESSION['success'] = $lang['credentials_updated'];
        } else {
            $_SESSION['error'] = $lang['invalid_current_password'];
        }
        header('Location: settings.php');
        exit;
    }
    if (isset($_POST['export_data'])) {
        $zip = new ZipArchive();
        $zipName = 'bookmarkly_backup_' . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = sys_get_temp_dir() . '/' . $zipName;

        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            // Voeg alle bestanden uit de data map toe
            $dataFolder = realpath('../data');
            $files = glob($dataFolder . '/*');
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    $zip->addFile($file, basename($file));
                }
            }

            // Voeg uploads map toe
            $uploadsFolder = realpath('../data/uploads');
            if ($uploadsFolder && is_dir($uploadsFolder)) {
                $uploadFiles = glob($uploadsFolder . '/*');
                foreach ($uploadFiles as $file) {
                    if (is_file($file)) {
                        $zip->addFile($file, 'uploads/' . basename($file));
                    }
                }
            }
            
            $zip->close();

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipName . '"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);
            unlink($zipPath);
            exit;
        }
    }
    if (isset($_FILES['import_data'])) {
        $zip = new ZipArchive();
        $uploadedFile = $_FILES['import_data']['tmp_name'];
        
        if ($zip->open($uploadedFile) === TRUE) {
            // Zorg ervoor dat de doelmap bestaat
            if (!file_exists('../data/uploads')) {
                mkdir('../data/uploads', 0777, true);
            }

            // Extract nieuwe bestanden
            $zip->extractTo('../data');
            $zip->close();

            $_SESSION['success'] = $lang['import_successful'];
            header('Location: settings.php');
            exit;
        } else {
            $_SESSION['error'] = $lang['import_failed'];
            header('Location: settings.php');
            exit;
        }
    }
    $settings = [
        'theme' => $_POST['theme'] ?? 'light',
        'language' => $_POST['language'] ?? 'en',
        'background_image' => $_POST['background_image'] ?? '',
        'background_brightness' => $_POST['background_brightness'] ?? '100',
        'background_saturation' => $_POST['background_saturation'] ?? '100',
        'debug_mode' => isset($_POST['debug_mode']),
        'target_blank' => isset($_POST['target_blank']),
        'dashboard_title' => $_POST['dashboard_title'] ?? 'BOOKMARKS',
        'protect_dashboard' => isset($_POST['protect_dashboard']),
        'remember_duration' => $_POST['remember_duration'] ?? '2w'
    ];
    $db->updateSettings($settings);
    header('Location: settings.php?saved=1');
    exit;
}

$current_settings = $db->getSettings();
// Voeg default waarden toe voor ontbrekende settings
$current_settings = array_merge([
    'theme' => 'transparent',
    'language' => 'en',
    'background_image' => 'bg/mist.jpg',
    'background_brightness' => '100',
    'background_saturation' => '100',
    'debug_mode' => false,
    'target_blank' => true,
    'dashboard_title' => 'BOOKMARKS',
    'protect_dashboard' => false,
    'remember_duration' => '2w'
], $current_settings ?? []);

$lang = $translations[$current_settings['language'] ?? 'en'];
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookmarkly - <?php echo $lang['settings']; ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <style>


        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .admin-title {
            margin: 0;
            font-size: 1.8rem;
            color: #333;
        }

        .back-link {
            color: #666;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-link:hover {
            color: #333;
        }

        h2 {
            color: #333;
            margin: 2rem 0 1rem;
            font-size: 1.4rem;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .button {
            background: #007bff;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
        }

        .button:hover {
            background: #0056b3;
        }

        body {
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .background-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 1.5rem 0;
        }

        .background-option {
            cursor: pointer;
            position: relative;
            aspect-ratio: 16/9;
            display: block;
            transition: transform 0.2s ease;
        }

        .background-option:hover {
            transform: translateY(-2px);
        }

        .background-option input {
            position: absolute;
            opacity: 0;
            z-index: -1;
        }

        .background-preview {
            width: 100%;
            height: 100%;
            border-radius: 12px;
            border: 3px solid #ddd;
            background-size: cover;
            background-position: center;
            transition: all 0.2s ease;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .no-background {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            color: #666;
            font-weight: 500;
        }

        .background-option input:checked + .background-preview {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.3), 0 4px 12px rgba(0,0,0,0.15);
        }

        .background-preview:hover {
            border-color: #007bff;
        }

        .background-name {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 8px 12px;
            font-size: 0.9em;
            border-bottom-left-radius: 9px;
            border-bottom-right-radius: 9px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .background-preview:hover .background-name {
            opacity: 1;
        }

        .theme-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .theme-option {
            cursor: pointer;
            position: relative;
            border: 3px solid #ddd;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.2s ease;
            padding: 1rem;
            text-align: center;
        }

        .theme-option.active {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.3);
        }

        .theme-option:hover {
            border-color: #007bff;
            transform: translateY(-2px);
        }

        .theme-input {
            position: absolute;
            opacity: 0;
            z-index: -1;
        }

        .theme-preview {
            padding: 0.2rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .theme-card {
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 6px;
        }

        .theme-card div {
            margin: 0.5rem 0;
            font-size: 0.9rem;
        }

        .debug-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .debug-label {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            cursor: pointer;
        }

        .debug-checkbox {
            margin-top: 0.3rem;
        }

        .debug-text {
            flex: 1;
        }

        .debug-description {
            margin: 0.5rem 0 0;
            color: #666;
            font-size: 0.9rem;
        }

        .language-select {
            width: 200px;
            padding: 0.5rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        .language-description {
            margin-top: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }

        .background-select {
            width: 100%;
            max-width: 400px;
            padding: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            margin-bottom: 1rem;
            background-color: white;
        }

        .background-select:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }

        .background-preview-container {
            margin-top: 1rem;
            border-radius: 12px;
            overflow: hidden;
            background-color: #f8f9fa;
            padding: 1rem;
            text-align: center;
        }

        #backgroundPreview {
            max-width: 800px;
            width: 100%;
            height: auto;
            min-height: 200px;
            max-height: 400px;
            object-fit: cover;
            display: block;
            margin: 0 auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .text-input {
            width: 100%;
            max-width: 400px;
            padding: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .text-input:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }

        .slider-group {
            position: relative;
            flex: 1;
            margin-bottom: 1rem;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
        }

        .slider-group::after {
            content: '';
            position: absolute;
            left: calc(50% - 1px);  /* 100% is in het midden omdat de range 0-200 is */
            top: 2.5rem;  /* Aanpassen aan de exacte positie van de slider */
            width: 2px;
            height: 1rem;
            background-color: #666;
            pointer-events: none;
        }

        .slider {
            width: 100%;
            margin: 0.5rem 0;
            background: transparent;
        }

        /* Specifieke styling voor Chrome/Safari */
        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            height: 16px;
            width: 16px;
            border-radius: 50%;
            background: #007bff;
            cursor: pointer;
            margin-top: -6px;
        }

        .slider::-webkit-slider-runnable-track {
            width: 100%;
            height: 4px;
            background: #ddd;
            border-radius: 2px;
        }

        /* Specifieke styling voor Firefox */
        .slider::-moz-range-thumb {
            height: 16px;
            width: 16px;
            border-radius: 50%;
            background: #007bff;
            cursor: pointer;
            border: none;
        }

        .slider::-moz-range-track {
            width: 100%;
            height: 4px;
            background: #ddd;
            border-radius: 2px;
        }

        .slider-value {
            display: inline-block;
            min-width: 40px;
            text-align: right;
            color: #666;
        }

        .slider-description {
            margin: 0.5rem 0;
            color: #666;
            font-size: 0.9rem;
        }

        .background-settings {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .credentials-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .input-group {
            margin-bottom: 1rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
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

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h2 {
            margin: 0;
        }

        .close-button {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            line-height: 1;
        }

        .close-button:hover {
            color: #dc3545;
        }

        <?php if (isset($_SESSION['error']) || isset($_SESSION['success'])): ?>
        .modal {
            display: flex;
        }
        <?php endif; ?>

        .login-settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            .login-settings-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        .backup-restore-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 1rem;
        }

        .backup-description {
            margin-top: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .backup-restore-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <div class="admin-header">
            <h1 class="admin-title"><?php echo $lang['settings']; ?></h1>
            <div class="header-links">
                <a href="admin.php" class="back-link"><?php echo $lang['back_to_admin']; ?></a>
            </div>
        </div>

        <form method="POST">
            <h2><?php echo $lang['login_credentials']; ?></h2>
            <button type="button" class="button" onclick="openCredentialsModal()">
                <?php echo $lang['change_username']; ?> / <?php echo $lang['change_password']; ?>
            </button>

            <div class="login-settings-grid">
                <div class="form-group">
                    <div class="debug-section">
                        <label class="debug-label">
                            <input type="checkbox" 
                                   name="protect_dashboard" 
                                   class="debug-checkbox" 
                                   value="1" 
                                   <?php echo ($current_settings['protect_dashboard'] ?? false) ? 'checked' : ''; ?>>
                            <div class="debug-text">
                                <strong><?php echo $lang['protect_dashboard_title']; ?></strong>
                                <p class="debug-description"><?php echo $lang['protect_dashboard_description']; ?></p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="remember_duration"><?php echo $lang['remember_duration_title']; ?></label>
                    <select name="remember_duration" id="remember_duration" class="language-select">
                        <option value="2w" <?php echo ($current_settings['remember_duration'] ?? '2w') === '2w' ? 'selected' : ''; ?>>
                            <?php echo $lang['remember_2w']; ?>
                        </option>
                        <option value="4w" <?php echo ($current_settings['remember_duration'] ?? '2w') === '4w' ? 'selected' : ''; ?>>
                            <?php echo $lang['remember_4w']; ?>
                        </option>
                        <option value="3m" <?php echo ($current_settings['remember_duration'] ?? '2w') === '3m' ? 'selected' : ''; ?>>
                            <?php echo $lang['remember_3m']; ?>
                        </option>
                        <option value="6m" <?php echo ($current_settings['remember_duration'] ?? '2w') === '6m' ? 'selected' : ''; ?>>
                            <?php echo $lang['remember_6m']; ?>
                        </option>
                    </select>
                    <p class="language-description"><?php echo $lang['remember_duration_description']; ?></p>
                </div>
            </div>
            <hr style="border: 0; border-top: 1px solid #ddd;">
            <h2><?php echo $lang['theme']; ?></h2>
            <div class="theme-grid">
                <?php foreach ($themes as $id => $theme): ?>
                    <label class="theme-option <?php echo $current_settings['theme'] === $id ? 'active' : ''; ?>" 
                           data-theme="<?php echo $id; ?>">
                        <input type="radio" name="theme" value="<?php echo $id; ?>" 
                               <?php echo $current_settings['theme'] === $id ? 'checked' : ''; ?>
                               class="theme-input">
                        <div class="theme-preview" style="background-color: <?php echo $theme['background']; ?>">
                            <div class="theme-card">
                                <?php if ($id === 'transparent'): ?>
                                    <div style="color: #333333"><?php echo $lang['theme_sample']; ?></div>
                                    <div style="color: #666666"><?php echo $lang['theme_text']; ?></div>
                                <?php else: ?>
                                    <div style="color: <?php echo $theme['text']; ?>"><?php echo $lang['theme_sample']; ?></div>
                                    <div style="color: <?php echo $theme['text_secondary']; ?>"><?php echo $lang['theme_text']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="color: <?php echo $id === 'transparent' ? '#333333' : $theme['text']; ?>">
                            <?php 
                            $theme_name = 'theme_' . $id;
                            echo $lang[$theme_name]; 
                            ?>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>

            <h2><?php echo $lang['background_image']; ?></h2>
            <div class="form-group">
                <select name="background_image" class="background-select" onchange="previewBackground(this.value)">
                    <option value=""><?php echo $lang['no_background']; ?></option>
                    <?php 
                    $bg_files = glob('bg/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
                    foreach ($bg_files as $bg): 
                        $bg_name = basename($bg, '.jpg');
                        $bg_name = ucwords(str_replace(['_', '-'], ' ', $bg_name));
                    ?>
                        <option value="<?php echo htmlspecialchars($bg); ?>"
                                <?php echo $current_settings['background_image'] === $bg ? 'selected' : ''; ?>>
                            <?php echo $bg_name; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="background-settings">
                    <div class="slider-group">
                        <label for="brightness"><?php echo $lang['background_brightness']; ?></label>
                        <input type="range" 
                               id="brightness" 
                               name="background_brightness" 
                               class="slider" 
                               min="0" 
                               max="200" 
                               value="<?php echo $current_settings['background_brightness']; ?>"
                               oninput="updateSliderValue(this, '%')">
                        <span class="slider-value"><?php echo $current_settings['background_brightness']; ?>%</span>
                        <p class="slider-description"><?php echo $lang['brightness_description']; ?></p>
                    </div>

                    <div class="slider-group">
                        <label for="saturation"><?php echo $lang['background_saturation']; ?></label>
                        <input type="range" 
                               id="saturation" 
                               name="background_saturation" 
                               class="slider" 
                               min="0" 
                               max="200" 
                               value="<?php echo $current_settings['background_saturation'] ?? 100; ?>"
                               oninput="updateSliderValue(this, '%')">
                        <span class="slider-value"><?php echo $current_settings['background_saturation'] ?? 100; ?>%</span>
                        <p class="slider-description"><?php echo $lang['saturation_description']; ?></p>
                    </div>
                </div>

                <div class="background-preview-container">
                    <img id="backgroundPreview" 
                         src="<?php echo htmlspecialchars($current_settings['background_image']); ?>"
                         alt="<?php echo $lang['background_preview_alt']; ?>"
                         style="<?php echo empty($current_settings['background_image']) ? 'display: none;' : ''; ?>
                                filter: brightness(<?php echo ($current_settings['background_brightness'] ?? '100') / 100; ?>);">
                </div>
            </div>

            <div class="form-group">
                <div class="debug-section">
                    <label class="debug-label">
                        <input type="checkbox" 
                               name="target_blank" 
                               class="debug-checkbox" 
                               value="1" 
                               <?php echo ($current_settings['target_blank'] ?? true) ? 'checked' : ''; ?>>
                        <div class="debug-text">
                            <strong><?php echo $lang['target_blank_title']; ?></strong>
                            <p class="debug-description"><?php echo $lang['target_blank_description']; ?></p>
                        </div>
                    </label>
                </div>
            </div>

            <h2><?php echo $lang['language']; ?></h2>
            <div class="form-group form-group-margin">
                <select name="language" class="language-select">
                    <option value="nl" <?php echo ($current_settings['language'] ?? 'nl') === 'nl' ? 'selected' : ''; ?>>
                        Nederlands
                    </option>
                    <option value="en" <?php echo ($current_settings['language'] ?? 'nl') === 'en' ? 'selected' : ''; ?>>
                        English
                    </option>
                </select>
                <p class="language-description">
                    <?php echo $lang['choose_language']; ?>
                </p>
            </div>

            <h2><?php echo $lang['debug_mode']; ?></h2>
            <div class="debug-section">            
                <label class="debug-label">
                    <input type="checkbox" name="debug_mode" value="1" 
                           class="debug-checkbox"
                           <?php echo ($current_settings['debug_mode'] ?? false) ? 'checked' : ''; ?>>
                    <span class="debug-text"><?php echo $lang['debug_mode_description']; ?></span>
                </label>
            </div>

            <h2><?php echo $lang['backup_restore']; ?></h2>
            <div class="backup-restore-grid">
                <div class="form-group">
                    <form method="POST">
                        <button type="submit" name="export_data" class="button">
                            <?php echo $lang['export_data']; ?>
                        </button>
                        <p class="backup-description"><?php echo $lang['export_description']; ?></p>
                    </form>
                </div>
                
                <div class="form-group">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="file" 
                               name="import_data" 
                               id="import_data" 
                               accept=".zip"
                               style="display: none;"
                               onchange="this.form.submit()">
                        <button type="button" 
                                class="button" 
                                onclick="document.getElementById('import_data').click()">
                            <?php echo $lang['import_data']; ?>
                        </button>
                        <p class="backup-description"><?php echo $lang['import_description']; ?></p>
                    </form>
                </div>
            </div>
            <hr style="border: 0; border-top: 1px solid #ddd; margin: 2rem 0;">

            <button type="submit" class="button"><?php echo $lang['save_settings']; ?></button>
        </form>
    </div>

    <div class="modal" id="credentialsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $lang['login_credentials']; ?></h2>
                <button type="button" class="close-button" onclick="closeCredentialsModal()">&times;</button>
            </div>
            <form method="POST" id="credentialsForm">
                <div class="credentials-section">
                    <div class="input-group">
                        <label for="current_password"><?php echo $lang['current_password']; ?></label>
                        <input type="password" 
                               id="current_password" 
                               name="current_password" 
                               class="form-control" 
                               required>
                    </div>

                    <div class="input-group">
                        <label for="new_username"><?php echo $lang['new_username']; ?></label>
                        <input type="text" 
                               id="new_username" 
                               name="new_username" 
                               class="form-control" 
                               placeholder="<?php echo $lang['username']; ?>">
                    </div>

                    <div class="input-group">
                        <label for="new_password"><?php echo $lang['new_password']; ?></label>
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               class="form-control">
                    </div>

                    <div class="input-group">
                        <label for="confirm_password"><?php echo $lang['confirm_password']; ?></label>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="form-control">
                    </div>

                    <button type="submit" 
                            name="update_credentials" 
                            class="button">
                        <?php echo $lang['save_changes']; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Theme selection
        document.querySelectorAll('.theme-option').forEach(option => {
            option.addEventListener('click', () => {
                document.querySelectorAll('.theme-option').forEach(opt => opt.classList.remove('active'));
                option.classList.add('active');
                option.querySelector('input').checked = true;
            });
        });

        function updateSliderValue(slider, unit = '') {
            const valueDisplay = slider.nextElementSibling;
            valueDisplay.textContent = slider.value + unit;
            
            const preview = document.getElementById('backgroundPreview');
            const brightnessValue = document.getElementById('brightness').value;
            const saturationValue = document.getElementById('saturation').value;
            
            preview.style.filter = `brightness(${brightnessValue / 100}) saturate(${saturationValue / 100})`;
        }

        function previewBackground(url) {
            const preview = document.getElementById('backgroundPreview');
            const brightnessValue = document.getElementById('brightness').value;
            const saturationValue = document.getElementById('saturation').value;
            
            if (url) {
                preview.src = url;
                preview.style.display = 'block';
                preview.style.filter = `brightness(${brightnessValue / 100}) saturate(${saturationValue / 100})`;
            } else {
                preview.style.display = 'none';
            }
        }

        // Voeg event listener toe voor saturatie
        document.getElementById('saturation').addEventListener('input', function() {
            updateSliderValue(this, '%');
        });

        function openCredentialsModal() {
            document.getElementById('credentialsModal').classList.add('active');
        }

        function closeCredentialsModal() {
            document.getElementById('credentialsModal').classList.remove('active');
            // Reset het formulier
            document.getElementById('credentialsForm').reset();
        }

        // Sluit modal als er buiten wordt geklikt
        document.getElementById('credentialsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCredentialsModal();
            }
        });

        <?php if (isset($_SESSION['error']) || isset($_SESSION['success'])): ?>
        openCredentialsModal();
        <?php endif; ?>
    </script>

    <footer style="text-align: center; padding: 20px; color: #666; font-size: 0.9rem; margin-top: auto;">
        Made in Holland - bookmarkly.nl
    </footer>
</body>
</html> 