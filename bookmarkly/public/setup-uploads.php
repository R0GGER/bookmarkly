<?php
// Maak de uploads directory als deze niet bestaat
$uploadsDir = '../data/uploads/';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}

// Maak de symlink in de public directory
$publicUploadsDir = 'uploads';
if (!file_exists($publicUploadsDir)) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows
        exec('mklink /D "' . $publicUploadsDir . '" "' . realpath($uploadsDir) . '"');
    } else {
        // Linux/Unix
        symlink(realpath($uploadsDir), $publicUploadsDir);
    }
}

echo "Setup complete!"; 