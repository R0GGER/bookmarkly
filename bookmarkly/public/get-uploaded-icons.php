<?php
header('Content-Type: application/json');

$iconsDir = '../data/uploads/';
$icons = [];

if (is_dir($iconsDir)) {
    $files = scandir($iconsDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_file($iconsDir . $file)) {
            // Alleen afbeeldingsbestanden toevoegen
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'svg'])) {
                $icons[] = $file;
            }
        }
    }
}

echo json_encode($icons); 