<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../src/Database.php';
$db = new Database();

if (isset($_GET['id']) && isset($_GET['categoryId']) && isset($_GET['direction'])) {
    $id = $_GET['id'];
    $categoryId = $_GET['categoryId'];
    $direction = $_GET['direction'];
    
    if ($direction === 'up') {
        $db->moveBookmarkUp($id, $categoryId);
    } else if ($direction === 'down') {
        $db->moveBookmarkDown($id, $categoryId);
    }
}

header('Location: admin.php');
exit(); 