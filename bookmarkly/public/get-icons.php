<?php
header('Content-Type: application/json');

$file = '../src/dashboard-icons/tree.json';

// Debug informatie
error_log("Attempting to load file: " . realpath($file));
error_log("File exists: " . (file_exists($file) ? 'Yes' : 'No'));
if (file_exists($file)) {
    error_log("File permissions: " . substr(sprintf('%o', fileperms($file)), -4));
    error_log("File size: " . filesize($file) . " bytes");
}

if (!file_exists($file)) {
    http_response_code(500);
    echo json_encode(['error' => 'Icons bestand niet gevonden']);
    exit;
}

$response = file_get_contents($file);
$data = json_decode($response, true);

if ($data === null) {
    http_response_code(500);
    echo json_encode(['error' => 'Ongeldig JSON formaat']);
    exit;
}

// Stuur alleen de png array terug
echo json_encode($data['png']); 