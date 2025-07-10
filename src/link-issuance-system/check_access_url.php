<?php
/**
 * Access URL Check
 * 
 * This script checks if the access URL is correctly formed and if the token is valid
 */

// Define constant to prevent direct access to included files
define('ACCESS_CONTROL', true);

// Include utility functions
require_once __DIR__ . '/api/utils/storage.php';
require_once __DIR__ . '/api/utils/security.php';
require_once __DIR__ . '/api/utils/link_service.php';

// Get token from query parameter
$token = $_GET['token'] ?? '';

// Output header
echo "<!DOCTYPE html>
<html>
<head>
    <title>Access URL Check</title>
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
    <h1>Access URL Check</h1>";

// Check if token is provided
echo "<div class='section'>";
echo "<h2>Token Check</h2>";

if (empty($token)) {
    echo "<p class='error'>No token provided. Please add ?token=YOUR_TOKEN to the URL.</p>";
    echo "<p>Example: " . $_SERVER['PHP_SELF'] . "?token=abc123</p>";
} else {
    echo "<p>Token: " . htmlspecialchars($token) . "</p>";
    
    // Check if token exists in links.json
    $link = getLinkByToken($token);
    
    if ($link === null) {
        echo "<p class='error'>Token not found in links.json</p>";
        
        // List all tokens in links.json
        $links = getLinks();
        
        if (empty($links)) {
            echo "<p>No links found in links.json</p>";
        } else {
            echo "<p>Available tokens in links.json:</p>";
            echo "<ul>";
            foreach ($links as $existingLink) {
                echo "<li>" . htmlspecialchars($existingLink['token']) . "</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p class='success'>Token found in links.json</p>";
        
        // Display link details
        echo "<h3>Link Details</h3>";
        echo "<pre>" . htmlspecialchars(print_r($link, true)) . "</pre>";
        
        // Check if link is active
        if (!$link['is_active']) {
            echo "<p class='error'>Link is not active (has been revoked)</p>";
        } else {
            echo "<p class='success'>Link is active</p>";
        }
        
        // Check if link has expired
        if ($link['activated_at'] !== null) {
            $expiresAt = $link['expires_at'];
            
            if ($expiresAt !== null && $expiresAt < time()) {
                echo "<p class='error'>Link has expired</p>";
            } else {
                echo "<p class='success'>Link has not expired</p>";
            }
        } else {
            echo "<p>Link has not been activated yet</p>";
        }
        
        // Check concurrent limit
        $activeSessions = 0;
        $now = time();
        $inactiveCutoff = $now - 300; // 5 minutes
        
        foreach ($link['sessions'] as $session) {
            if ($session['last_activity'] > $inactiveCutoff) {
                $activeSessions++;
            }
        }
        
        echo "<p>Active sessions: $activeSessions / " . $link['concurrent_limit'] . "</p>";
        
        if ($activeSessions >= $link['concurrent_limit']) {
            echo "<p class='error'>Maximum number of concurrent devices reached</p>";
        } else {
            echo "<p class='success'>Below concurrent device limit</p>";
        }
        
        // Check games
        $games = getGames();
        $gameData = [];
        
        foreach ($link['game_ids'] as $gameId) {
            $found = false;
            
            foreach ($games['games'] as $game) {
                if ($game['id'] === $gameId) {
                    $gameData[] = $game;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                echo "<p class='error'>Game ID not found: " . htmlspecialchars($gameId) . "</p>";
            }
        }
        
        if (empty($gameData)) {
            echo "<p class='error'>No valid games found for this link</p>";
        } else {
            echo "<p class='success'>Valid games found: " . count($gameData) . "</p>";
            
            echo "<h3>Game Details</h3>";
            echo "<pre>" . htmlspecialchars(print_r($gameData, true)) . "</pre>";
        }
    }
}
echo "</div>";

// Check access URL formation
echo "<div class='section'>";
echo "<h2>Access URL Formation</h2>";

// Get base URL
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$accessUrl = $baseUrl . '/Link-Issuance-System/direct-access.php?token=' . $token;

echo "<p>Base URL: " . htmlspecialchars($baseUrl) . "</p>";
echo "<p>Access URL: " . htmlspecialchars($accessUrl) . "</p>";

// Check if access directory exists
$accessDir = __DIR__ . '/access';
if (!is_dir($accessDir)) {
    echo "<p class='error'>Access directory does not exist: $accessDir</p>";
} else {
    echo "<p class='success'>Access directory exists</p>";
    
    // Check if index.php exists in access directory
    $accessIndex = $accessDir . '/index.php';
    if (!file_exists($accessIndex)) {
        echo "<p class='error'>Access index.php does not exist: $accessIndex</p>";
    } else {
        echo "<p class='success'>Access index.php exists</p>";
    }
}

// Check URL rewriting
echo "<h3>URL Rewriting Check</h3>";
echo "<p>Current script: " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>";

// Check .htaccess
$htaccess = __DIR__ . '/.htaccess';
if (!file_exists($htaccess)) {
    echo "<p class='error'>.htaccess file does not exist: $htaccess</p>";
} else {
    echo "<p class='success'>.htaccess file exists</p>";
    
    // Display .htaccess content
    echo "<h3>.htaccess Content</h3>";
    echo "<pre>" . htmlspecialchars(file_get_contents($htaccess)) . "</pre>";
}
echo "</div>";

// Server information
echo "<div class='section'>";
echo "<h2>Server Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p>Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p>Current Script: " . __FILE__ . "</p>";
echo "</div>";

echo "</body></html>";
