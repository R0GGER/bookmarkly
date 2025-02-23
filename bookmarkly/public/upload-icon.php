<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die(json_encode(['success' => false, 'message' => 'Not authorized']));
}

if (!isset($_FILES['icon'])) {
    die(json_encode(['success' => false, 'message' => 'No file uploaded']));
}

$uploadDir = '../data/uploads/';

// Create uploads directory if it doesn't exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$file = $_FILES['icon'];
$fileName = basename($file['name']);
$targetPath = $uploadDir . $fileName;

// Check file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowedTypes)) {
    die(json_encode(['success' => false, 'message' => 'Invalid file type']));
}

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Geef het pad terug relatief aan de public directory
    echo json_encode(['success' => true, 'file' => '../data/uploads/' . $fileName]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error uploading file']);
} 