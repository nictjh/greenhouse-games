<?php
// Define constant to prevent direct access to included files
define('ACCESS_CONTROL', true);

// Include utility functions
require_once __DIR__ . '/api/utils/storage.php';
require_once __DIR__ . '/api/utils/security.php';
require_once __DIR__ . '/api/utils/link_service.php';

// Get token from query parameter
$token = $_GET['token'] ?? 'test-token-123456';

// Get link by token
$link = getLinkByToken($token);

// Output result
echo "<h1>Link Test</h1>";
echo "<p>Testing token: " . htmlspecialchars($token) . "</p>";

if ($link === null) {
    echo "<p style='color: red;'>Link not found</p>";
} else {
    echo "<p style='color: green;'>Link found!</p>";
    echo "<pre>" . print_r($link, true) . "</pre>";
    
    // Get games
    $games = getGames();
    $gameData = [];
    
    foreach ($link['game_ids'] as $gameId) {
        foreach ($games['games'] as $game) {
            if ($game['id'] === $gameId) {
                $gameData[] = $game;
                break;
            }
        }
    }
    
    echo "<h2>Games</h2>";
    if (empty($gameData)) {
        echo "<p>No games found for this link</p>";
    } else {
        echo "<ul>";
        foreach ($gameData as $game) {
            echo "<li>" . htmlspecialchars($game['name']) . " (" . htmlspecialchars($game['id']) . ")</li>";
        }
        echo "</ul>";
    }
    
    // Generate access URL
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $accessUrl = $baseUrl . '/Link-Issuance-System/direct-access.php?token=' . $link['token'];
    
    echo "<h2>Access URL</h2>";
    echo "<p><a href='" . htmlspecialchars($accessUrl) . "'>" . htmlspecialchars($accessUrl) . "</a></p>";
}

// Check if games.json exists and is readable
echo "<h2>Games Configuration</h2>";
$gamesFile = __DIR__ . '/config/games.json';
if (file_exists($gamesFile) && is_readable($gamesFile)) {
    echo "<p style='color: green;'>games.json exists and is readable</p>";
    
    // Get games
    $games = getGames();
    echo "<pre>" . print_r($games, true) . "</pre>";
} else {
    echo "<p style='color: red;'>games.json does not exist or is not readable</p>";
}

// Check if access directory exists
echo "<h2>Access Directory</h2>";
$accessDir = __DIR__ . '/access';
if (is_dir($accessDir)) {
    echo "<p style='color: green;'>access directory exists</p>";
    
    // Check if index.php exists
    $indexFile = $accessDir . '/index.php';
    if (file_exists($indexFile) && is_readable($indexFile)) {
        echo "<p style='color: green;'>access/index.php exists and is readable</p>";
    } else {
        echo "<p style='color: red;'>access/index.php does not exist or is not readable</p>";
    }
} else {
    echo "<p style='color: red;'>access directory does not exist</p>";
}

// Server information
echo "<h2>Server Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p>Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p>Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'Unknown') . "</p>";
echo "<p>Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "</p>";
echo "<p>HTTP Host: " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "</p>";
