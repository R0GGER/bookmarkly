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
    // Export data handler
    if (isset($_POST['export_data'])) {
        $zip = new ZipArchive();
        $zipName = 'bookmarkly_backup_' . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = sys_get_temp_dir() . '/' . $zipName;
        
        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            // Voeg database.json toe
            $zip->addFile($db->file, 'database.json');
            
            // Voeg uploads directory toe als deze bestaat
            $uploadsDir = dirname($db->file) . '/uploads';
            if (is_dir($uploadsDir)) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($uploadsDir),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                
                foreach ($files as $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = 'uploads/' . substr($filePath, strlen($uploadsDir) + 1);
                        $zip->addFile($filePath, $relativePath);
                    }
                }
            }
            
            // Voeg custom CSS toe als het bestaat
            $cssFile = dirname($db->file) . '/custom.css';
            if (file_exists($cssFile)) {
                $zip->addFile($cssFile, 'custom.css');
            }
            
            // Voeg auth.php toe als het bestaat
            $authFile = dirname($db->file) . '/auth.php';
            if (file_exists($authFile)) {
                $zip->addFile($authFile, 'auth.php');
            }
            
            $zip->close();
            
            // Download het ZIP bestand
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipName . '"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);
            unlink($zipPath);
            exit;
        }
    }
    
    // Import data handler
    if (isset($_FILES['import_data'])) {
        if ($_FILES['import_data']['error'] === UPLOAD_ERR_OK) {
            $zip = new ZipArchive();
            if ($zip->open($_FILES['import_data']['tmp_name']) === TRUE) {
                // Direct alle bestanden extraheren
                $zip->extractTo(dirname($db->file));
                $zip->close();
                
                // Maak uploads directory als die niet bestaat
                $uploadsDir = dirname($db->file) . '/uploads';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0775, true);
                }
                
                $_SESSION['success'] = $lang['import_successful'];
            } else {
                $_SESSION['error'] = $lang['import_failed'];
            }
        } else {
            $_SESSION['error'] = $lang['import_failed'];
        }
        header('Location: settings.php');
        exit;
    }

    // ... rest van de bestaande POST handlers ...
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    error_log('MAIN SETTINGS FORM SUBMITTED');
    error_log('POST DATA: ' . print_r($_POST, true));
    
    // Verzamel alle settings
    $settings = [
        'theme' => $_POST['theme'] ?? 'transparent',
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
    
    // Update de settings
    if ($db->updateSettings($settings)) {
        error_log('Settings updated successfully');
        
        // Redirect naar index als protect_dashboard is ingeschakeld
        if ($settings['protect_dashboard']) {
            header('Location: index.php');
            exit;
        }
        
        // Anders, redirect terug naar settings met success message
        header('Location: settings.php?saved=1');
        exit;
    } else {
        error_log('Failed to update settings');
        header('Location: settings.php?error=1');
        exit;
    }
}

$current_settings = $db->getSettings();
error_log('Current settings on page load: ' . print_r($current_settings, true));

// Voeg default waarden toe voor ontbrekende settings, maar behoud bestaande boolean waarden
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
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
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

        .button.button-green {
            background-color: #28a745;
            color: white;
        }
        
        .button.button-green:hover {
            background-color: #218838;
        }

        .button.button-green .la-check {
            margin-right: 5px;
        }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 15px 25px;
            border-radius: 4px;
            display: none;
            animation: slideIn 0.3s ease-in-out;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .toast.show {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
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

        <!-- Hoofdformulier voor settings -->
        <form method="POST" id="settingsForm">
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
                                   <?php echo $current_settings['protect_dashboard'] ? 'checked' : ''; ?>>
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

            <div class="form-group">
                <div class="debug-section">
                    <label class="debug-label">
                        <input type="checkbox" 
                               name="target_blank" 
                               class="debug-checkbox" 
                               <?php echo ($current_settings['target_blank'] ?? true) ? 'checked' : ''; ?>>
                        <div class="debug-text">
                            <strong><?php echo $lang['target_blank_title']; ?></strong>
                            <p class="debug-description"><?php echo $lang['target_blank_description']; ?></p>
                        </div>
                    </label>
                </div>
            </div>
            <h2><?php echo $lang['background_image']; ?></h2>
            <div class="form-group">
                <select name="background_image" class="background-select" onchange="previewBackground(this.value + '?v=' + Date.now())">
                    <option value=""><?php echo $lang['no_background']; ?></option>
                    <?php 
                    clearstatcache();
                    $bg_files = glob('bg/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
                    
                    foreach ($bg_files as $bg): 
                        $bg_name = basename($bg);
                        $bg_name = pathinfo($bg_name, PATHINFO_FILENAME);
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
                        <label for="background_brightness"><?php echo $lang['background_brightness']; ?></label>
                        <input type="range" 
                               id="background_brightness" 
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
                        <label for="background_saturation"><?php echo $lang['background_saturation']; ?></label>
                        <input type="range" 
                               id="background_saturation" 
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
                         src="<?php echo htmlspecialchars($current_settings['background_image']) . '?v=' . time(); ?>"
                         alt="<?php echo $lang['background_preview_alt']; ?>"
                         style="<?php echo empty($current_settings['background_image']) ? 'display: none;' : ''; ?>
                                filter: brightness(<?php echo ($current_settings['background_brightness'] ?? '100') / 100; ?>);">
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
        </form>

        <!-- Voor de backup/restore grid, voeg de header toe -->
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
                    <input type="file" name="import_data" id="import_data" accept=".zip" style="display: none;" onchange="this.form.submit()">
                    <button type="button" class="button" onclick="document.getElementById('import_data').click()">
                        <?php echo $lang['import_data']; ?>
                    </button>
                    <p class="backup-description"><?php echo $lang['import_description']; ?></p>
                </form>
            </div>
        </div>

        <div style="margin-top: 2rem; text-align: left;">
            <button type="submit" name="save_settings" form="settingsForm" class="button button-green">
                <i class="las la-check"></i> <?php echo $lang['save_settings']; ?>
            </button>
        </div>
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

    <div id="settingsToast" class="toast">
        <i class="las la-check"></i> Settings saved successfully
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
            const brightnessValue = document.getElementById('background_brightness').value;
            const saturationValue = document.getElementById('background_saturation').value;
            
            preview.style.filter = `brightness(${brightnessValue / 100}) saturate(${saturationValue / 100})`;
        }

        function previewBackground(url) {
            const preview = document.getElementById('backgroundPreview');
            const brightnessValue = document.getElementById('background_brightness').value;
            const saturationValue = document.getElementById('background_saturation').value;
            
            if (url) {
                preview.src = url + '?v=' + Date.now();
                preview.style.display = 'block';
                preview.style.filter = `brightness(${brightnessValue / 100}) saturate(${saturationValue / 100})`;
            } else {
                preview.style.display = 'none';
            }
        }

        // Event listeners voor beide sliders
        document.getElementById('background_brightness').addEventListener('input', function() {
            updateSliderValue(this, '%');
        });

        document.getElementById('background_saturation').addEventListener('input', function() {
            updateSliderValue(this, '%');
        });

        // Initialiseer de preview met de huidige waarden bij het laden van de pagina
        window.addEventListener('load', function() {
            const preview = document.getElementById('backgroundPreview');
            if (preview) {
                const brightnessValue = document.getElementById('background_brightness').value;
                const saturationValue = document.getElementById('background_saturation').value;
                preview.style.filter = `brightness(${brightnessValue / 100}) saturate(${saturationValue / 100})`;
            }
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

        function showToast() {
            const toast = document.getElementById('settingsToast');
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000); // Verberg na 3 seconden
        }

        // Check of er een saved parameter in de URL staat
        if (new URLSearchParams(window.location.search).has('saved')) {
            showToast();
            // Verwijder de saved parameter uit de URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>

    <footer style="text-align: center; padding: 20px; color: #666; font-size: 0.9rem; margin-top: auto;">
        Made in Holland - bookmarkly.nl
    </footer>
</body>
</html> 