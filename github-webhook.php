<?php
date_default_timezone_set('Asia/Kolkata'); // Adjust to your timezone
$logFilePath = '/home/officialmobile/nisan.co.in/deployment.log';
$logTime = date("Y-m-d H:i:s");

// Execute git pull and capture output
$output = shell_exec("cd /home/officialmobile/nisan.co.in && git pull origin main 2>&1 && git --no-pager log --name-status -1 && git --no-pager diff --stat -1");

// Write to log file in a more detailed format
$logEntry = "$logTime - Deployment attempted:\n";
$logEntry .= "```\n$output\n```\n\n"; // Wrapping in backticks for Markdown formatting in logs
file_put_contents($logFilePath, $logEntry, FILE_APPEND);

// Optionally, echo the output to the browser for immediate feedback
echo "<pre>$output</pre>";
?>