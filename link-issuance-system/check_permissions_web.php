<?php
/**
 * Permission Check Script (Web Version)
 * 
 * This script checks and fixes permissions for critical directories and files
 * and displays the results in a web browser.
 */

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');

// Start output buffering to capture all output
ob_start();

// Define directories that need to be writable
$writableDirs = [
    'data',
    'data/links.json',
    'data/sessions.json',
    'config',
    'config/games.json',
    'config/settings.json'
];

// Check if running on Windows or Unix-like system
$isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

// Function to check and fix permissions
function checkAndFixPermissions($path, $isWindows) {
    echo "<div class='check-item'>";
    echo "<strong>Checking:</strong> $path... ";
    
    if (!file_exists($path)) {
        echo "<span class='error'>NOT FOUND!</span>";
        echo "</div>";
        return;
    }
    
    if (is_writable($path)) {
        echo "<span class='success'>OK (Writable)</span>";
    } else {
        echo "<span class='error'>NOT WRITABLE!</span> Attempting to fix... ";
        
        if ($isWindows) {
            // On Windows, we can't use chmod effectively
            echo "<span class='warning'>Running on Windows. Please manually ensure '$path' is writable.</span>";
        } else {
            // On Unix-like systems, we can use chmod
            $mode = is_dir($path) ? 0755 : 0644;
            if (chmod($path, $mode)) {
                echo "<span class='success'>Fixed! Set mode to " . decoct($mode) . "</span>";
            } else {
                echo "<span class='error'>FAILED! Could not set permissions. Please manually chmod '$path'.</span>";
            }
        }
    }
    echo "</div>";
}

// Function to check if a file exists and is readable
function checkEndpoint($path) {
    echo "<div class='check-item'>";
    echo "<strong>Checking:</strong> $path... ";
    if (file_exists($path) && is_readable($path)) {
        echo "<span class='success'>OK</span>";
    } else {
        echo "<span class='error'>NOT FOUND or NOT READABLE!</span>";
    }
    echo "</div>";
}

// HTML header
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Link Issuance System - Permission Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2 {
            color: #4a6fa5;
        }
        .section {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .check-item {
            margin-bottom: 10px;
            padding: 8px;
            background-color: #fff;
            border-left: 3px solid #ddd;
            padding-left: 15px;
        }
        .success {
            color: #2e7d32;
            font-weight: bold;
        }
        .error {
            color: #c62828;
            font-weight: bold;
        }
        .warning {
            color: #f57c00;
            font-weight: bold;
        }
        .info-item {
            display: flex;
            margin-bottom: 10px;
        }
        .info-item strong {
            width: 150px;
            display: inline-block;
        }
        .actions {
            margin-top: 20px;
            text-align: center;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4a6fa5;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin: 0 10px;
        }
        .button:hover {
            background-color: #3a5a84;
        }
    </style>
</head>
<body>
    <h1>Link Issuance System - Permission Check</h1>
    <p>This tool checks and attempts to fix permissions for critical directories and files needed by the Link Issuance System.</p>
";

// Check and fix permissions
echo "<div class='section'>
    <h2>Directory and File Permissions</h2>";

foreach ($writableDirs as $dir) {
    checkAndFixPermissions($dir, $isWindows);
}

echo "</div>";

// Create data files if they don't exist
$dataFiles = [
    'data/links.json' => '{"links":[]}',
    'data/sessions.json' => '{"sessions":[]}'
];

echo "<div class='section'>
    <h2>Data Files</h2>";

foreach ($dataFiles as $file => $defaultContent) {
    echo "<div class='check-item'>";
    echo "<strong>Checking:</strong> $file... ";
    
    if (!file_exists($file)) {
        echo "<span class='error'>NOT FOUND!</span> Creating... ";
        
        // Make sure the directory exists
        $dir = dirname($file);
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "<span class='success'>Created directory $dir.</span> ";
            } else {
                echo "<span class='error'>FAILED to create directory $dir!</span> ";
            }
        }
        
        // Create the file with default content
        if (file_put_contents($file, $defaultContent)) {
            echo "<span class='success'>Created with default content.</span>";
        } else {
            echo "<span class='error'>FAILED to create file!</span>";
        }
    } else {
        echo "<span class='success'>EXISTS</span>";
    }
    echo "</div>";
}

echo "</div>";

// Server information
echo "<div class='section'>
    <h2>Server Information</h2>
    <div class='info-item'><strong>PHP Version:</strong> " . phpversion() . "</div>
    <div class='info-item'><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</div>
    <div class='info-item'><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</div>
    <div class='info-item'><strong>Script Path:</strong> " . __FILE__ . "</div>
    <div class='info-item'><strong>Request URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "</div>
    <div class='info-item'><strong>Server Name:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "</div>
</div>";

// Check API endpoints
echo "<div class='section'>
    <h2>API Endpoints</h2>";

$endpoints = [
    'api/index.php',
    'api/endpoints/generate.php',
    'api/endpoints/validate.php',
    'api/endpoints/check_session.php',
    'api/endpoints/revoke.php',
    'api/endpoints/list.php',
    'api/endpoints/stats.php'
];

foreach ($endpoints as $endpoint) {
    checkEndpoint($endpoint);
}

echo "</div>";

// Test API connection
echo "<div class='section'>
    <h2>API Connection Test</h2>";

// Try to make a request to the stats endpoint
$apiUrl = 'api/stats';
echo "<div class='check-item'>";
echo "<strong>Testing API connection to:</strong> $apiUrl<br>";

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<strong>HTTP Response Code:</strong> ";
if ($httpCode >= 200 && $httpCode < 300) {
    echo "<span class='success'>$httpCode (Success)</span>";
} else {
    echo "<span class='error'>$httpCode (Failed)</span>";
}

echo "<br><strong>Recommendation:</strong> ";
if ($httpCode >= 200 && $httpCode < 300) {
    echo "<span class='success'>API appears to be accessible.</span>";
} else if ($httpCode == 404) {
    echo "<span class='error'>API endpoint not found. Check your .htaccess configuration and URL rewriting.</span>";
} else if ($httpCode >= 500) {
    echo "<span class='error'>Server error. Check your PHP error logs for details.</span>";
} else {
    echo "<span class='warning'>Unexpected response. Further investigation needed.</span>";
}

echo "</div>";
echo "</div>";

// Actions
echo "<div class='actions'>
    <a href='admin/' class='button'>Go to Admin Dashboard</a>
    <a href='check_permissions_web.php?refresh=" . time() . "' class='button'>Run Check Again</a>
</div>";

// HTML footer
echo "</body></html>";

// Get the buffered content
$content = ob_get_clean();

// Output the content
echo $content;
