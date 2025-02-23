<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../src/Database.php';
$db = new Database();

$data = json_decode(file_get_contents('php://input'), true);
$categoryId = $data['categoryId'] ?? null;
$hidden = $data['hidden'] ?? false;

if ($categoryId) {
    foreach ($db->data['categories'] as &$category) {
        if ($category['id'] === $categoryId) {
            $category['hidden'] = $hidden;
            break;
        }
    }
    $db->save();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
} 