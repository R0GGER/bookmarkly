<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../src/Database.php';
$db = new Database();

if (isset($_POST['id'])) {
    $categoryId = $_POST['id'];
    try {
        $db->deleteCategory($categoryId);
    } catch (Exception $e) {
        // Log error if needed
    }
}

header('Location: admin.php');
exit(); 