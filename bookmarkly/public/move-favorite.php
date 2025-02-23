<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../src/Database.php';
$db = new Database();

if (isset($_GET['id']) && isset($_GET['direction'])) {
    $id = $_GET['id'];
    $direction = $_GET['direction'];
    
    if ($direction === 'up') {
        $db->moveFavoriteUp($id);
    } else if ($direction === 'down') {
        $db->moveFavoriteDown($id);
    }
}

header('Location: admin.php');
exit(); 