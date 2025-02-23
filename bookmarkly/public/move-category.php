<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../src/Database.php';
$db = new Database();

$categoryId = $_GET['id'] ?? null;
$direction = $_GET['direction'] ?? '';

if ($categoryId && $direction) {
    if ($direction === 'up') {
        $db->moveCategoryUp($categoryId);
    } elseif ($direction === 'down') {
        $db->moveCategoryDown($categoryId);
    }
}

header('Location: admin.php');
exit(); 