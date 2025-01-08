<!DOCTYPE html>
<html>
<head>
<title>Deployment Log Viewer</title>
</head>
<body>
<?php
$logFile = '/home/officialmobile/nisan.co.in/deployment.log';
if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'Deployment attempted:') !== false) {
            echo "<details><summary>" . htmlspecialchars(substr($line, 0, 50)) . "...</summary><pre>" . htmlspecialchars($line) . "\n";
        } elseif (isset($line) && strlen(trim($line)) > 0) {
            echo htmlspecialchars($line) . "\n";
        } else {
            echo "</pre></details>";
        }
    }
} else {
    echo "Log file not found.";
}
?>
</body>
</html>