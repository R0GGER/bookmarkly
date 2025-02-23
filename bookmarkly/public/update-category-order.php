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
$order = $data['order'] ?? 99;

if ($categoryId) {
    $success = $db->updateCategoryOrder($categoryId, $order);
    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
} 