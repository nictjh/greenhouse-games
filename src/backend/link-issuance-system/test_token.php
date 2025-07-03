<?php
/**
 * Token Test Script
 * 
 * This script tests token validation directly
 */

// Define constant to prevent direct access to included files
define('ACCESS_CONTROL', true);

// Include utility functions
require_once __DIR__ . '/api/utils/storage.php';
require_once __DIR__ . '/api/utils/security.php';
require_once __DIR__ . '/api/utils/link_service.php';

// Get token from query parameter
$token = $_GET['token'] ?? '';

echo "<h1>Token Test</h1>";
echo "<p>Testing token: " . htmlspecialchars($token) . "</p>";

if (empty($token)) {
    echo "<p style='color: red;'>No token provided. Use ?token=YOUR_TOKEN in the URL.</p>";
    echo "<p>Example: <a href='test_token.php?token=test-token-123456'>test_token.php?token=test-token-123456</a></p>";
    exit;
}

// Get link by token
$link = getLinkByToken($token);

echo "<h2>Link Data:</h2>";
if ($link === null) {
    echo "<p style='color: red;'>No link found with this token.</p>";
    
    // List all available tokens for debugging
    $links = getLinks();
    echo "<h3>Available Tokens:</h3>";
    echo "<ul>";
    foreach ($links as $availableLink) {
        echo "<li>" . htmlspecialchars($availableLink['token']) . " (ID: " . htmlspecialchars($availableLink['id']) . ")</li>";
    }
    echo "</ul>";
    
    exit;
}

// Display link details
echo "<pre>";
print_r($link);
echo "</pre>";

// Generate device fingerprint
$deviceFingerprint = generateDeviceFingerprint();
echo "<h2>Device Fingerprint:</h2>";
echo "<p>" . htmlspecialchars($deviceFingerprint) . "</p>";

// Validate link and create session
echo "<h2>Validation Result:</h2>";
$result = validateLinkAndCreateSession($token, $deviceFingerprint);

echo "<pre>";
print_r($result);
echo "</pre>";

if ($result['success']) {
    echo "<p style='color: green;'>Token is valid!</p>";
    
    // Get games
    echo "<h2>Games:</h2>";
    echo "<ul>";
    foreach ($result['games'] as $game) {
        echo "<li>" . htmlspecialchars($game['name']) . " - <a href='" . htmlspecialchars($game['url']) . "' target='_blank'>" . htmlspecialchars($game['url']) . "</a></li>";
    }
    echo "</ul>";
    
    // Create direct access URL
    $directAccessUrl = "direct-access.php?token=" . urlencode($token);
    echo "<p>Direct Access URL: <a href='" . htmlspecialchars($directAccessUrl) . "'>" . htmlspecialchars($directAccessUrl) . "</a></p>";
} else {
    echo "<p style='color: red;'>Token validation failed: " . htmlspecialchars($result['message']) . "</p>";
}
