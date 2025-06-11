<?php
/**
 * Preview Content
 * 
 * This file renders the content for the preview tool
 */

// Start session
session_start();

// Include utility functions
require_once __DIR__ . '/api/utils/storage.php';

// Get settings
$settings = getSettings();

// Get system name
$systemName = $settings['system']['name'];

// Get page to preview
$page = $_GET['page'] ?? 'expired';
$validPages = ['expired', 'error', 'success'];

if (!in_array($page, $validPages)) {
    $page = 'expired';
}

// Sample game data for testing
$sampleGames = [
    [
        'id' => 'game1',
        'name' => 'Adventure Quest',
        'description' => 'Embark on an epic journey through mystical lands.',
        'url' => 'games/sample-game.html',
        'type' => 'html'
    ],
    [
        'id' => 'game2',
        'name' => 'Math Challenge',
        'description' => 'Test your mathematical skills with fun puzzles.',
        'url' => 'games/sample-game.html',
        'type' => 'html'
    ],
    [
        'id' => 'game3',
        'name' => 'Word Master',
        'description' => 'Expand your vocabulary and become a word master.',
        'url' => 'games/sample-game.html',
        'type' => 'html'
    ]
];

// Set up variables for the pages
$games = $sampleGames;
$expiresAt = time() + 86400; // 24 hours from now
$expiryFormatted = '23 hours, 59 minutes';

// Define constant to prevent direct access to included files
define('ACCESS_CONTROL', true);

// Include the requested page
switch ($page) {
    case 'expired':
        include __DIR__ . '/access/expired.php';
        break;
    case 'error':
        include __DIR__ . '/access/error.php';
        break;
    case 'success':
        include __DIR__ . '/access/success.php';
        break;
    default:
        include __DIR__ . '/access/expired.php';
}
