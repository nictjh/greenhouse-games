<?php
// Output header
echo "<!DOCTYPE html>
<html>
<head>
    <title>.htaccess Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2, h3 { color: #333; }
        .success { color: green; }
        .error { color: red; }
        .section { margin-bottom: 20px; padding: 10px; border: 1px solid #ddd; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>.htaccess Test</h1>";

// Check if .htaccess file exists
echo "<div class='section'>";
echo "<h2>.htaccess File</h2>";
$htaccessFile = __DIR__ . '/.htaccess';
if (file_exists($htaccessFile) && is_readable($htaccessFile)) {
    echo "<p class='success'>.htaccess file exists and is readable</p>";
    echo "<h3>Content:</h3>";
    echo "<pre>" . htmlspecialchars(file_get_contents($htaccessFile)) . "</pre>";
} else {
    echo "<p class='error'>.htaccess file does not exist or is not readable</p>";
}
echo "</div>";

// Check if mod_rewrite is enabled
echo "<div class='section'>";
echo "<h2>mod_rewrite Status</h2>";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    $mod_rewrite = in_array('mod_rewrite', $modules);
    if ($mod_rewrite) {
        echo "<p class='success'>mod_rewrite is enabled</p>";
    } else {
        echo "<p class='error'>mod_rewrite is not enabled</p>";
    }
} else {
    echo "<p>Unable to check if mod_rewrite is enabled. This could be because you're not using Apache or the apache_get_modules() function is not available.</p>";
}
echo "</div>";

// Test URL rewriting
echo "<div class='section'>";
echo "<h2>URL Rewriting Test</h2>";
echo "<p>Current URL: " . htmlspecialchars($_SERVER['REQUEST_URI']) . "</p>";
echo "<p>Try accessing the following URLs to test URL rewriting:</p>";
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
echo "<ul>";
echo "<li><a href='" . $baseUrl . "/api/list' target='_blank'>API List Endpoint</a></li>";
echo "<li><a href='" . $baseUrl . "/api/stats' target='_blank'>API Stats Endpoint</a></li>";
echo "<li><a href='" . $baseUrl . "/Link-Issuance-System/direct-access.php?token=test-token-123456' target='_blank'>Access with Test Token</a></li>";
echo "</ul>";
echo "</div>";

// Server information
echo "<div class='section'>";
echo "<h2>Server Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p>Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p>Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'Unknown') . "</p>";
echo "<p>Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "</p>";
echo "<p>HTTP Host: " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "</p>";
echo "</div>";

// Check file permissions
echo "<div class='section'>";
echo "<h2>File Permissions</h2>";
$directories = [
    'data' => __DIR__ . '/data',
    'config' => __DIR__ . '/config',
    'access' => __DIR__ . '/access',
    'api' => __DIR__ . '/api'
];

foreach ($directories as $name => $path) {
    echo "<h3>$name Directory</h3>";
    if (is_dir($path)) {
        echo "<p class='success'>Directory exists</p>";
        echo "<p>Permissions: " . substr(sprintf('%o', fileperms($path)), -4) . "</p>";
        echo "<p>Writable: " . (is_writable($path) ? 'Yes' : 'No') . "</p>";
        
        // Check some files in the directory
        $files = glob($path . '/*');
        if (!empty($files)) {
            echo "<p>Files:</p>";
            echo "<ul>";
            foreach ($files as $file) {
                if (is_file($file)) {
                    echo "<li>" . basename($file) . " (Permissions: " . substr(sprintf('%o', fileperms($file)), -4) . ", Writable: " . (is_writable($file) ? 'Yes' : 'No') . ")</li>";
                }
            }
            echo "</ul>";
        } else {
            echo "<p>No files found in directory</p>";
        }
    } else {
        echo "<p class='error'>Directory does not exist</p>";
    }
}
echo "</div>";

echo "</body></html>";
