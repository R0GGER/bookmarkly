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
    try {
        $bookmark = [
            'title' => $_POST['title'],
            'url' => $_POST['url'],
            'icon' => $_POST['icon'],
            'categoryId' => !empty($_POST['categoryId']) ? $_POST['categoryId'] : null,
            'favorite' => isset($_POST['favorite']) && $_POST['favorite'] === '1',
            'target_blank' => isset($_POST['target_blank']) && $_POST['target_blank'] === '1'
        ];

        // Debug output
        error_log("Adding bookmark: " . print_r($bookmark, true));
        
        $db->addBookmark($bookmark);
        header('Location: admin.php');
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Error adding bookmark: " . $e->getMessage());
    }
}

$categories = $db->getCategories();
?>

<!DOCTYPE html>
<html lang="<?php echo $current_settings['language'] ?? 'nl'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookmarkly - <?php echo $lang['new_bookmark']; ?></title>
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

        input, select {
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

        .no-categories {
            color: #dc3545;
            margin-bottom: 1rem;
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

        .icon-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .icon-modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid #ddd;
            padding-bottom: 0.5rem;
        }
        
        .tab {
            padding: 0.5rem 1rem;
            cursor: pointer;
            border-radius: 4px;
        }
        
        .tab.active {
            background: #007bff;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .icon-option {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
        }
        
        .icon-option:hover {
            border-color: #007bff;
        }
        
        .icon-option img {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }
        
        .icon-preview {
            width: 32px;
            height: 32px;
            object-fit: contain;
            margin-right: 0.5rem;
            vertical-align: middle;
            flex-shrink: 0;
        }
        
        .icon-select-btn {
            background: none;
            border: 1px solid #ddd;
            padding: 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            min-width: 150px;
            white-space: nowrap;
            color: #333;
        }

        .icon-select-btn:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }

        .icon-select-btn span {
            margin-left: 0.5rem;
            display: inline-block;
            color: #333;
        }

        .search-container {
            margin-bottom: 1rem;
        }
        
        .search-suggestions {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .suggestion-item {
            padding: 0.5rem 1rem;
            cursor: pointer;
        }
        
        .suggestion-item:hover {
            background: #f8f9fa;
        }
        
        .highlight {
            background-color: #fff3cd;
        }

        .upload-section {
            margin-bottom: 1rem;
        }

        .upload-zone {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-zone:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }

        .upload-zone.dragover {
            border-color: #28a745;
            background: #e9ecef;
        }

        .upload-message {
            color: #666;
        }

        .upload-message p {
            margin: 0.5rem 0 0;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="admin-header">
            <h2><i class="las la-bookmark"></i> <?php echo $lang['new_bookmark']; ?></h2>
            <div class="header-links">
                <a href="add-category.php" class="back-link" style="margin-right: 1rem;">
                <i class="las la-border-all"></i> <?php echo $lang['add_category']; ?>
                </a>
                <a href="admin.php" class="back-link">
                    <?php echo $lang['back_to_admin']; ?>
                </a>
            </div>
        </div>
        <?php if (empty($categories)): ?>
            <p class="no-categories">Er zijn nog geen categorieën. <a href="add-category.php">Voeg eerst een categorie toe</a>.</p>
        <?php endif; ?>
        
        <form method="POST" <?php if (empty($categories)) echo 'style="display: none;"'; ?>>
            <div class="form-group">
                <label for="title"><?php echo $lang['form_title']; ?></label>
                <input type="text" id="title" name="title" required>
            </div>
            
            <div class="form-group">
                <label for="url"><?php echo $lang['form_url']; ?></label>
                <input type="url" id="url" name="url" required>
            </div>
            
            <div class="form-group">
                <label for="icon"><?php echo $lang['form_icon_url']; ?></label>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" class="icon-select-btn" onclick="openIconModal()">
                        <img id="selectedIcon" src="" class="icon-preview" style="display: none;">
                        <span><?php echo $lang['choose_icon']; ?></span>
                    </button>
                    <input type="text" id="icon" name="icon" required style="flex: 1;">
                </div>
            </div>
            
            <div class="form-group">
                <label for="categoryId"><?php echo $lang['form_category']; ?></label>
                <select id="categoryId" name="categoryId">
                    <option value=""><?php echo $lang['select_category']; ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="debug-label">
                    <input type="checkbox" 
                           name="favorite" 
                           class="debug-checkbox" 
                           value="1">
                    <div class="debug-text">
                        <strong><?php echo $lang['add_to_favorites']; ?></strong>
                        <p class="debug-description"><?php echo $lang['add_to_favorites_description']; ?></p>
                    </div>
                </label>
            </div>
            
            <div class="form-group">
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
            
            <button type="submit"><?php echo $lang['form_add']; ?></button>
        </form>
    </div>

    <div class="icon-modal">
        <div class="modal-content">
            <div class="tabs">
                <div class="tab active" data-tab="homarrTab">App Icons</div>
                <div class="tab" data-tab="faviconTab">Favicon Extractor</div>
                <div class="tab" data-tab="uploadTab">Upload Icon</div>
            </div>
            
            <div id="homarrTab" class="tab-content active">
                <input type="text" 
                       id="homarrSearch" 
                       placeholder="<?php echo $lang['search_app']; ?>" 
                       class="search-input">
                <div id="searchSuggestions" class="search-suggestions"></div>
                <div id="homarrIcons" class="icon-grid"></div>
            </div>

            <div id="faviconTab" class="tab-content">
                <p><?php echo $lang['enter_domain_favicon']; ?></p>
                <div class="input-group" style="display: flex; flex-direction: column; gap: 1rem;">
                    <input type="text" 
                           id="domainInput" 
                           placeholder="<?php echo $lang['domain_placeholder']; ?>" 
                           class="form-control">
                    <div style="display: flex; justify-content: flex-start;">
                        <button onclick="previewFavicon()" class="btn btn-primary" style="min-width: 100px;">Preview</button>
                    </div>
                </div>
                <div id="faviconPreview" class="mt-3"></div>
            </div>

            <div id="uploadTab" class="tab-content">
                <div class="upload-section">
                    <div class="upload-zone" id="uploadZone">
                        <input type="file" id="iconUpload" accept="image/*" style="display: none;">
                        <div class="upload-message">
                            <i class="las la-cloud-upload-alt" style="font-size: 2em;"></i>
                            <p><?php echo $lang['drop_files']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="icon-grid" id="uploadedIcons"></div>
            </div>
        </div>
    </div>

    <script>
    let homarrIcons = [];

    async function loadHomarrIcons() {
        try {
            console.log('Fetching icons...');
            const response = await fetch('get-icons.php');
            console.log('Response status:', response.status);
            const data = await response.json();
            console.log('Received data:', data);
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            // Data is nu direct de array van iconen
            homarrIcons = data;
            console.log('Loaded icons:', homarrIcons.length);
            displayHomarrIcons(homarrIcons);
        } catch (error) {
            console.error('Error loading icons:', error);
            document.getElementById('homarrSearch').placeholder = 'Fout bij laden van iconen: ' + error.message;
        }
    }

    function displayHomarrIcons(icons) {
        const container = document.getElementById('homarrIcons');
        container.innerHTML = icons.map(icon => `
            <div class="icon-option" onclick="selectIcon('https://cdn.jsdelivr.net/gh/homarr-labs/dashboard-icons/png/${icon}')">
                <img src="https://cdn.jsdelivr.net/gh/homarr-labs/dashboard-icons/png/${icon}" alt="${icon}">
                <div class="small">${icon.replace('.png', '')}</div>
            </div>
        `).join('');
    }

    function previewFavicon() {
        const domain = document.getElementById('domainInput').value.trim();
        if (domain) {
            const faviconUrl = `https://favicon.bookmarkly.nl/favicon/${domain}?larger=true`;
            document.getElementById('faviconPreview').innerHTML = `
                <div class="icon-option" onclick="selectIcon('${faviconUrl}')">
                    <img src="${faviconUrl}" alt="${domain} favicon">
                    <div>${domain}</div>
                </div>
            `;
        }
    }

    function selectIcon(url) {
        const iconInput = document.getElementById('icon');
        const selectedIcon = document.getElementById('selectedIcon');
        
        iconInput.value = url;
        selectedIcon.src = url.startsWith('../data/uploads/') 
            ? 'serve-upload.php?file=' + encodeURIComponent(url.split('/').pop())
            : url;
        selectedIcon.style.display = 'inline';
        
        closeIconModal();
    }

    function openIconModal() {
        document.querySelector('.icon-modal').classList.add('active');
    }

    function closeIconModal() {
        document.querySelector('.icon-modal').classList.remove('active');
    }

    // Tab switching
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            tab.classList.add('active');
            document.getElementById(tab.dataset.tab).classList.add('active');
        });
    });

    function highlightText(text, query) {
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<span class="highlight">$1</span>');
    }

    function showSuggestions(query) {
        const suggestionsDiv = document.getElementById('searchSuggestions');
        if (!query) {
            suggestionsDiv.style.display = 'none';
            return;
        }

        const matches = homarrIcons
            .filter(icon => icon.toLowerCase().replace('.png', '').includes(query.toLowerCase()))
            .slice(0, 10);

        if (matches.length > 0) {
            const html = matches.map(icon => `
                <div class="suggestion-item" onclick="selectFromSuggestion('${icon}')">
                    <img src="https://cdn.jsdelivr.net/gh/homarr-labs/dashboard-icons/png/${icon}" 
                         style="width: 24px; height: 24px; margin-right: 8px; vertical-align: middle;">
                    ${highlightText(icon.replace('.png', ''), query)}
                </div>
            `).join('');
            
            suggestionsDiv.innerHTML = html;
            suggestionsDiv.style.display = 'block';
        } else {
            suggestionsDiv.style.display = 'none';
        }
    }

    function selectFromSuggestion(icon) {
        const iconUrl = `https://cdn.jsdelivr.net/gh/homarr-labs/dashboard-icons/png/${icon}`;
        selectIcon(iconUrl);
        document.getElementById('searchSuggestions').style.display = 'none';
        document.getElementById('homarrSearch').value = icon.replace('.png', '');
    }

    // Update de bestaande search event listener
    document.getElementById('homarrSearch').addEventListener('input', (e) => {
        const search = e.target.value.toLowerCase();
        showSuggestions(search);
        
        const filtered = homarrIcons.filter(icon => 
            icon.toLowerCase().includes(search)
        );
        displayHomarrIcons(filtered);
    });

    // Sluit suggesties als er buiten wordt geklikt
    document.addEventListener('click', (e) => {
        const suggestionsDiv = document.getElementById('searchSuggestions');
        const searchInput = document.getElementById('homarrSearch');
        
        if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
            suggestionsDiv.style.display = 'none';
        }
    });

    // Initialize
    loadHomarrIcons();

    // Close modal when clicking outside
    document.querySelector('.icon-modal').addEventListener('click', (e) => {
        if (e.target === document.querySelector('.icon-modal')) {
            closeIconModal();
        }
    });

    function loadUploadedIcons() {
        fetch('get-uploaded-icons.php')
            .then(response => response.json())
            .then(icons => {
                const container = document.getElementById('uploadedIcons');
                container.innerHTML = icons.map(icon => `
                    <div class="icon-option" onclick="selectIcon('../data/uploads/${icon}')">
                        <img src="serve-upload.php?file=${encodeURIComponent(icon)}" 
                             alt="Uploaded icon" 
                             style="width: 32px; height: 32px; object-fit: contain;">
                        <div class="small">${icon.replace(/\.[^/.]+$/, '')}</div>
                    </div>
                `).join('');
            })
            .catch(error => console.error('Error loading uploaded icons:', error));
    }

    const uploadZone = document.getElementById('uploadZone');
    const iconUpload = document.getElementById('iconUpload');

    uploadZone.addEventListener('click', () => iconUpload.click());

    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.classList.add('dragover');
    });

    uploadZone.addEventListener('dragleave', () => {
        uploadZone.classList.remove('dragover');
    });

    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });

    iconUpload.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        const file = files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('icon', file);

        fetch('upload-icon.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadUploadedIcons();
            } else {
                alert(data.message || 'Error uploading file');
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Voeg deze regel toe aan de initialize sectie
    loadUploadedIcons();

    // Update de input event listener
    document.getElementById('icon').addEventListener('input', function() {
        const selectedIcon = document.getElementById('selectedIcon');
        const url = this.value;
        
        if (url) {
            selectedIcon.src = url.startsWith('../data/uploads/') 
                ? 'serve-upload.php?file=' + encodeURIComponent(url.split('/').pop())
                : url;
            selectedIcon.style.display = 'inline';
        } else {
            selectedIcon.style.display = 'none';
        }
    });
    </script>
</body>
</html> 