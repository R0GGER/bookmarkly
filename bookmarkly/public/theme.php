<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../src/Database.php';
$db = new Database();

// Voeg deze regels toe voor de taalondersteuning
$translations = require '../src/config/translations.php';
$current_settings = $db->getSettings();
$lang = $translations[$current_settings['language'] ?? 'nl'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db->updateCustomCss($_POST['custom_css'] ?? '');
    header('Location: theme.php?saved=1');
    exit();
}

$custom_css = $db->getCustomCss();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom CSS - Dashboard</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="theme-container">
        <div class="admin-header">
            <h1 class="admin-title">Custom CSS</h1>
            <div class="header-links">
                <a href="settings.php" class="back-link"><?php echo $lang['back_to_settings']; ?></a>
            </div>
        </div>

        <div class="help-text">
            <h3>Beschikbare CSS Variabelen:</h3>
            <div class="code-example">
                --background: Achtergrondkleur van de pagina<br>
                --card-bg: Achtergrondkleur van kaarten<br>
                --text: Primaire tekstkleur<br>
                --text-secondary: Secundaire tekstkleur<br>
                --border: Randkleur<br>
                --shadow: Schaduwkleur<br>
                --hover: Hover kleur voor kaarten
            </div>
            <p>Voorbeeld:</p>
            <div class="code-example">
                :root {<br>
                &nbsp;&nbsp;--background: #f0f2f5;<br>
                &nbsp;&nbsp;--card-bg: #ffffff;<br>
                &nbsp;&nbsp;--text: #333333;<br>
                &nbsp;&nbsp;--text-secondary: #666666;<br>
                &nbsp;&nbsp;--border: #e0e0e0;<br>
                &nbsp;&nbsp;--shadow: rgba(0,0,0,0.1);<br>
                &nbsp;&nbsp;--hover: #f8f9fa;<br>
                }
            </div>
        </div>

        <div id="successMessage" class="success-message <?php echo isset($_GET['saved']) ? 'visible' : ''; ?>">
            CSS is succesvol opgeslagen!
        </div>

        <form method="POST" id="cssForm">
            <div class="editor-container">
                <div>
                    <h2>CSS Editor</h2>
                    <textarea name="custom_css" class="css-editor" id="cssEditor" spellcheck="false"><?php echo htmlspecialchars($custom_css); ?></textarea>
                </div>
                
                <div>
                    <h2>Live Preview</h2>
                    <div class="preview" id="preview">
                        <div class="sample-card">
                            <h3>Voorbeeld Kaart</h3>
                            <p>Dit is een voorbeeld van hoe je CSS eruit ziet.</p>
                        </div>
                        <div class="sample-card">
                            <h3>Nog een kaart</h3>
                            <p>Met wat meer tekst om het effect te zien.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="login-button button">CSS Opslaan</button>
        </form>
    </div>

    <script>
        const editor = document.getElementById('cssEditor');
        const preview = document.getElementById('preview');
        const styleElement = document.createElement('style');
        document.head.appendChild(styleElement);

        // Live preview
        editor.addEventListener('input', updatePreview);

        function updatePreview() {
            styleElement.textContent = editor.value;
        }

        // Initialize preview
        updatePreview();

        // Auto-hide success message
        setTimeout(() => {
            const successMessage = document.getElementById('successMessage');
            if (successMessage.classList.contains('visible')) {
                successMessage.classList.remove('visible');
            }
        }, 3000);

        // Tab support in textarea
        editor.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                const start = this.selectionStart;
                const end = this.selectionEnd;
                this.value = this.value.substring(0, start) + '  ' + this.value.substring(end);
                this.selectionStart = this.selectionEnd = start + 2;
            }
        });
    </script>
</body>
</html> 