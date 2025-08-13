<?php
header('Content-Type: text/plain; charset=utf-8');

echo "REQUEST_URI:      " . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
echo "SCRIPT_FILENAME:  " . ($_SERVER['SCRIPT_FILENAME'] ?? '') . "\n";
echo "DOCUMENT_ROOT:    " . ($_SERVER['DOCUMENT_ROOT'] ?? '') . "\n";
echo "getcwd():         " . getcwd() . "\n";
echo "File exists (index.php): " . (file_exists(__DIR__ . '/index.php') ? 'YES' : 'NO') . "\n";
echo "Dir listing:\n";
foreach (scandir(__DIR__) as $f) { echo "  - $f\n"; }
