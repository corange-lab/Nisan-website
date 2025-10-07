<?php
// Server diagnostic page
error_reporting(E_ALL);
ini_set('display_errors', 1);

$CFG = require __DIR__.'/lib/config.php';
require __DIR__.'/lib/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>OLT Server Diagnostics</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .test { margin: 10px 0; padding: 10px; background: white; border-left: 4px solid #666; }
        .pass { border-color: #0a8043; }
        .fail { border-color: #c00; }
        pre { background: #f9f9f9; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>OLT Server Diagnostics</h1>
    
    <div class="test">
        <h3>1. PHP Version</h3>
        <?php
        echo "PHP Version: " . PHP_VERSION;
        echo "<br>Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown');
        ?>
    </div>
    
    <div class="test">
        <h3>2. Database Connection</h3>
        <?php
        try {
            $pdo = db();
            echo "✅ Database connected: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM onu_cache");
            $count = $stmt->fetch()['count'] ?? 0;
            echo "<br>Total ONUs in cache: " . $count;
        } catch (Exception $e) {
            echo "❌ Database error: " . $e->getMessage();
        }
        ?>
    </div>
    
    <div class="test">
        <h3>3. API Endpoint Test</h3>
        <?php
        $baseUrl = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $baseUrl .= $_SERVER['HTTP_HOST'];
        $apiUrl = $baseUrl . '/olt/api/dashboard.php?pons=1';
        
        echo "Testing: <code>$apiUrl</code><br>";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($apiUrl, false, $context);
        
        if ($response) {
            $data = json_decode($response, true);
            if ($data) {
                echo "✅ API Response: " . strlen($response) . " bytes<br>";
                echo "Status: " . ($data['ok'] ? 'OK' : 'ERROR') . "<br>";
                if ($data['ok']) {
                    echo "ONUs: " . ($data['stats']['total_onus'] ?? 0);
                }
                echo "<br><details><summary>Show response</summary><pre>" . htmlspecialchars(substr($response, 0, 1000)) . "...</pre></details>";
            } else {
                echo "❌ Invalid JSON response<br>";
                echo "<details><summary>Show raw response</summary><pre>" . htmlspecialchars(substr($response, 0, 1000)) . "</pre></details>";
            }
        } else {
            echo "❌ API request failed";
        }
        ?>
    </div>
    
    <div class="test">
        <h3>4. JavaScript Fetch Test</h3>
        <div id="fetch-result">Testing...</div>
        <script>
            fetch('/olt/api/dashboard.php?pons=1')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.text();
                })
                .then(text => {
                    console.log('Fetch response:', text.substring(0, 200));
                    try {
                        const data = JSON.parse(text);
                        document.getElementById('fetch-result').innerHTML = 
                            '✅ JavaScript fetch works!<br>' +
                            'Response length: ' + text.length + ' bytes<br>' +
                            'ONUs: ' + (data.stats ? data.stats.total_onus : 0) +
                            '<br><details><summary>Show response</summary><pre>' + 
                            text.substring(0, 500).replace(/</g, '&lt;') + '...</pre></details>';
                    } catch (e) {
                        document.getElementById('fetch-result').innerHTML = 
                            '❌ JSON parse error: ' + e.message +
                            '<br><details><summary>Show raw</summary><pre>' + 
                            text.substring(0, 500).replace(/</g, '&lt;') + '</pre></details>';
                    }
                })
                .catch(error => {
                    document.getElementById('fetch-result').innerHTML = '❌ Fetch failed: ' + error.message;
                    console.error('Fetch error:', error);
                });
        </script>
    </div>
    
    <div class="test">
        <h3>5. File Paths</h3>
        <?php
        echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "<br>";
        echo "Script Filename: " . __FILE__ . "<br>";
        echo "OLT Directory: " . __DIR__ . "<br>";
        echo "Database File: " . __DIR__ . '/data/olt.sqlite' . "<br>";
        echo "Database Exists: " . (file_exists(__DIR__ . '/data/olt.sqlite') ? 'Yes' : 'No') . "<br>";
        ?>
    </div>
    
    <div class="test">
        <h3>6. Session & Headers</h3>
        <?php
        echo "Session Started: " . (session_status() === PHP_SESSION_ACTIVE ? 'Yes' : 'No') . "<br>";
        echo "Headers Sent: " . (headers_sent($file, $line) ? "Yes (at $file:$line)" : 'No') . "<br>";
        ?>
    </div>
    
    <hr>
    
    <h2>Recommendations:</h2>
    <ul>
        <li>If database connection fails: Check file permissions on <code>/data/olt.sqlite</code></li>
        <li>If API fails: Check that <code>/olt/api/</code> URLs are accessible</li>
        <li>If JavaScript fetch fails: Check browser console for CORS or security errors</li>
        <li>Check your web server error logs for more details</li>
    </ul>
    
    <p><a href="/olt/">← Back to OLT Dashboard</a></p>
</body>
</html>
