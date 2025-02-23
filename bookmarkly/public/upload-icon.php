<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$uploadDir = '../data/uploads/';

// Maak de upload directory als deze niet bestaat
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (isset($_FILES['icon'])) {
    $file = $_FILES['icon'];
    $fileName = basename($file['name']);
    $targetPath = $uploadDir . $fileName;
    
    // Controleer bestandstype
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid file type. Only JPG, PNG, GIF and SVG are allowed.'
        ]);
        exit;
    }
    
    // Verplaats het bestand
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode([
            'success' => true,
            'message' => 'File uploaded successfully',
            'path' => '../data/uploads/' . $fileName
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error uploading file'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No file uploaded'
    ]);
} 