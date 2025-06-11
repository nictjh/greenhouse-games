<?php
/**
 * Data Directory Permissions Check
 * 
 * This script checks if the system can write to the data directory and files
 */

// Define constant to prevent direct access to included files
define('ACCESS_CONTROL', true);

// Include utility functions
require_once __DIR__ . '/api/utils/storage.php';

// Function to check if a directory is writable
function checkDirectoryWritable($dir) {
    if (!is_dir($dir)) {
        echo "Directory does not exist: $dir<br>";
        return false;
    }
    
    if (!is_writable($dir)) {
        echo "Directory is not writable: $dir<br>";
        return false;
    }
    
    echo "Directory is writable: $dir<br>";
    return true;
}

// Function to check if a file is writable
function checkFileWritable($file) {
    if (!file_exists($file)) {
        echo "File does not exist: $file<br>";
        return false;
    }
    
    if (!is_writable($file)) {
        echo "File is not writable: $file<br>";
        return false;
    }
    
    echo "File is writable: $file<br>";
    return true;
}

// Function to test writing to a file
function testWriteToFile($file) {
    $testData = ['test' => true, 'timestamp' => time()];
    $result = file_put_contents($file, json_encode($testData));
    
    if ($result === false) {
        echo "Failed to write to file: $file<br>";
        return false;
    }
    
    echo "Successfully wrote to file: $file<br>";
    return true;
}

// Function to test the storage functions
function testStorageFunctions() {
    echo "<h3>Testing Storage Functions</h3>";
    
    // Test getLinks
    echo "Testing getLinks()...<br>";
    $links = getLinks();
    echo "getLinks() returned: " . print_r($links, true) . "<br>";
    
    // Test saveLinks
    echo "Testing saveLinks()...<br>";
    $testLink = [
        'id' => 'test-' . uniqid(),
        'token' => 'test-token-' . uniqid(),
        'game_ids' => ['test-game'],
        'duration' => 3600,
        'concurrent_limit' => 1,
        'created_at' => time(),
        'expires_at' => null,
        'activated_at' => null,
        'is_active' => true,
        'note' => 'Test link',
        'sessions' => []
    ];
    
    $result = saveLinks([$testLink]);
    echo "saveLinks() result: " . ($result ? 'Success' : 'Failed') . "<br>";
    
    // Verify the link was saved
    echo "Verifying link was saved...<br>";
    $links = getLinks();
    $found = false;
    
    foreach ($links as $link) {
        if ($link['id'] === $testLink['id']) {
            $found = true;
            break;
        }
    }
    
    echo "Link found in storage: " . ($found ? 'Yes' : 'No') . "<br>";
    
    return $result && $found;
}

// Get settings
$settings = getSettings();
$dataDir = $settings['system']['data_directory'];
$dataPath = __DIR__ . '/' . $dataDir;
$linksFile = $dataPath . '/links.json';
$sessionsFile = $dataPath . '/sessions.json';

// Output header
echo "<!DOCTYPE html>
<html>
<head>
    <title>Data Directory Permissions Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2, h3 { color: #333; }
        .success { color: green; }
        .error { color: red; }
        .section { margin-bottom: 20px; padding: 10px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Data Directory Permissions Check</h1>";

// Check data directory
echo "<div class='section'>";
echo "<h2>Directory Checks</h2>";
$dirWritable = checkDirectoryWritable($dataPath);
echo "</div>";

// Check files
echo "<div class='section'>";
echo "<h2>File Checks</h2>";
$linksWritable = checkFileWritable($linksFile);
$sessionsWritable = checkFileWritable($sessionsFile);
echo "</div>";

// Test writing to files
echo "<div class='section'>";
echo "<h2>Write Tests</h2>";
if ($linksWritable) {
    // Backup the file first
    copy($linksFile, $linksFile . '.bak');
    $linksWriteTest = testWriteToFile($linksFile);
    // Restore from backup
    copy($linksFile . '.bak', $linksFile);
    unlink($linksFile . '.bak');
} else {
    echo "Skipping write test for links.json as it's not writable<br>";
    $linksWriteTest = false;
}

if ($sessionsWritable) {
    // Backup the file first
    copy($sessionsFile, $sessionsFile . '.bak');
    $sessionsWriteTest = testWriteToFile($sessionsFile);
    // Restore from backup
    copy($sessionsFile . '.bak', $sessionsFile);
    unlink($sessionsFile . '.bak');
} else {
    echo "Skipping write test for sessions.json as it's not writable<br>";
    $sessionsWriteTest = false;
}
echo "</div>";

// Test storage functions
echo "<div class='section'>";
$storageFunctionsTest = testStorageFunctions();
echo "</div>";

// Summary
echo "<div class='section'>";
echo "<h2>Summary</h2>";
if ($dirWritable && $linksWritable && $sessionsWritable && $linksWriteTest && $sessionsWriteTest && $storageFunctionsTest) {
    echo "<p class='success'>All checks passed! The system should be able to write to the data directory and files.</p>";
} else {
    echo "<p class='error'>Some checks failed. Please fix the issues above.</p>";
    
    echo "<h3>Possible Solutions:</h3>";
    echo "<ol>";
    echo "<li>Make sure the data directory exists and is writable: <code>chmod 755 $dataPath</code></li>";
    echo "<li>Make sure the files exist and are writable: <code>chmod 644 $linksFile $sessionsFile</code></li>";
    echo "<li>If using a hosting service, make sure PHP has write permissions to these directories and files.</li>";
    echo "<li>Check if there are any PHP errors in the error log.</li>";
    echo "</ol>";
}
echo "</div>";

// Server information
echo "<div class='section'>";
echo "<h2>Server Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p>Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p>Current Script: " . __FILE__ . "</p>";
echo "<p>Data Directory: " . $dataPath . "</p>";
echo "</div>";

echo "</body></html>";
