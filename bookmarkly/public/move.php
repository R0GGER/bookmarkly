<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../src/Database.php';
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $id = $_POST['id'] ?? '';
    $direction = $_POST['direction'] ?? '';
    $categoryId = $_POST['categoryId'] ?? null;

    $success = false;

    if ($type === 'favorite' && $id && $direction) {
        if ($direction === 'up') {
            $success = $db->moveFavoriteUp($id);
        } else {
            $success = $db->moveFavoriteDown($id);
        }
    } elseif ($type === 'bookmark' && $id && $direction && $categoryId) {
        if ($direction === 'up') {
            $success = $db->moveBookmarkUp($id, $categoryId);
        } else {
            $success = $db->moveBookmarkDown($id, $categoryId);
        }
    } elseif ($type === 'category' && $id && $direction) {
        if ($direction === 'up') {
            $success = $db->moveCategoryUp($id);
        } else {
            $success = $db->moveCategoryDown($id);
        }
    }

    // Voeg een timestamp toe aan de URL om caching te voorkomen
    header('Location: admin.php?t=' . time());
    exit();
}

// Als er geen POST request is, redirect terug
header('Location: admin.php');
exit(); 