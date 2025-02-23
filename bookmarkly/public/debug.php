<?php
require_once '../src/Database.php';

$db = new Database();

echo "<h2>Database Debug Info</h2>";
echo "<pre>";

echo "Database bestand: " . $db->file . "\n";
echo "Bestand bestaat: " . (file_exists($db->file) ? "Ja" : "Nee") . "\n";
if (file_exists($db->file)) {
    echo "Bestand schrijfbaar: " . (is_writable($db->file) ? "Ja" : "Nee") . "\n";
    echo "Bestandsgrootte: " . filesize($db->file) . " bytes\n";
    echo "\nInhoud van bestand:\n";
    echo file_get_contents($db->file) . "\n";
}

echo "\nGeladen data:\n";
print_r($db->data);

echo "</pre>"; 