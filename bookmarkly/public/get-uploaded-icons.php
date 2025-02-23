<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die(json_encode(['error' => 'Not authorized']));
}

$uploadDir = '../data/uploads/';
$icons = [];

if (is_dir($uploadDir)) {
    $files = glob($uploadDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
    foreach ($files as $file) {
        $icons[] = '../data/uploads/' . basename($file);
    }
}

echo json_encode($icons); 