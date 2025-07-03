<?php
/**
 * Permission Check Script
 * 
 * This script checks and fixes permissions for critical directories and files
 */

// Define directories that need to be writable
$writableDirs = [
    'data',
    'data/links.json',
    'data/sessions.json',
    'config',
    'config/games.json',
    'config/settings.json'
];

echo "=== Link Issuance System Permission Check ===\n\n";

// Check if running on Windows or Unix-like system
$isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

// Function to check and fix permissions
function checkAndFixPermissions($path, $isWindows) {
    echo "Checking: $path... ";
    
    if (!file_exists($path)) {
        echo "NOT FOUND!\n";
        return;
    }
    
    if (is_writable($path)) {
        echo "OK (Writable)\n";
    } else {
        echo "NOT WRITABLE! Attempting to fix... ";
        
        if ($isWindows) {
            // On Windows, we can't use chmod effectively
            echo "Running on Windows. Please manually ensure '$path' is writable.\n";
        } else {
            // On Unix-like systems, we can use chmod
            $mode = is_dir($path) ? 0755 : 0644;
            if (chmod($path, $mode)) {
                echo "Fixed! Set mode to " . decoct($mode) . "\n";
            } else {
                echo "FAILED! Could not set permissions. Please manually chmod '$path'.\n";
            }
        }
    }
}

// Check and fix permissions for each directory/file
foreach ($writableDirs as $dir) {
    checkAndFixPermissions($dir, $isWindows);
}

// Create data files if they don't exist
$dataFiles = [
    'data/links.json' => '{"links":[]}',
    'data/sessions.json' => '{"sessions":[]}'
];

echo "\n=== Checking Data Files ===\n\n";

foreach ($dataFiles as $file => $defaultContent) {
    echo "Checking: $file... ";
    
    if (!file_exists($file)) {
        echo "NOT FOUND! Creating... ";
        
        // Make sure the directory exists
        $dir = dirname($file);
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "Created directory $dir. ";
            } else {
                echo "FAILED to create directory $dir! ";
            }
        }
        
        // Create the file with default content
        if (file_put_contents($file, $defaultContent)) {
            echo "Created with default content.\n";
        } else {
            echo "FAILED to create file!\n";
        }
    } else {
        echo "EXISTS\n";
    }
}

echo "\n=== Server Information ===\n\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
echo "Script Path: " . __FILE__ . "\n";

echo "\n=== Checking API Endpoints ===\n\n";

// Function to check if a file exists and is readable
function checkEndpoint($path) {
    echo "Checking: $path... ";
    if (file_exists($path) && is_readable($path)) {
        echo "OK\n";
    } else {
        echo "NOT FOUND or NOT READABLE!\n";
    }
}

// Check API endpoints
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

echo "\n=== Completed ===\n";
echo "If you see any issues above, please fix them manually or contact your server administrator.\n";
