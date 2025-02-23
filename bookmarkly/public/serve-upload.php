<?php
// Controleer of er een bestandsnaam is opgegeven
if (!isset($_GET['file'])) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

// Haal de bestandsnaam uit het pad als het een volledig pad is
$filename = $_GET['file'];
if (strpos($filename, '../data/uploads/') === 0) {
    $filename = basename($filename);
}
$filename = basename($filename); // Extra veiligheid tegen directory traversal

$filepath = "../data/uploads/" . $filename;

// Controleer of het bestand bestaat
if (!file_exists($filepath)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

// Bepaal het MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $filepath);
finfo_close($finfo);

// Stuur de juiste headers
header("Content-Type: " . $mime_type);
header("Content-Length: " . filesize($filepath));

// Stuur het bestand
readfile($filepath);
exit; 