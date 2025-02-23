<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../src/Database.php';
$db = new Database();

if (isset($_POST['id'])) {
    try {
        // Check of we vanuit favorieten verwijderen
        $fromFavorites = isset($_POST['from_favorites']) && $_POST['from_favorites'] === '1';
        $db->deleteBookmark($_POST['id'], $fromFavorites);
    } catch (Exception $e) {
        error_log("Error deleting bookmark: " . $e->getMessage());
    }
}

header('Location: admin.php');
exit(); 