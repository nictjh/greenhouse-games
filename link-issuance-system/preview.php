<?php
/**
 * Preview Tool
 * 
 * This tool allows previewing and testing the front-facing pages
 * without needing actual tokens or uploading to Hostinger.
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

// For expired page, we don't need to set any special variables

// Define constant to prevent direct access to included files
define('ACCESS_CONTROL', true);

// Preview header
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Tool - ' . htmlspecialchars($systemName) . '</title>
    <style>
        .preview-header {
            background-color: #333;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .preview-header h1 {
            margin: 0;
            font-size: 18px;
        }
        .preview-nav {
            display: flex;
            gap: 10px;
        }
        .preview-nav a {
            color: #fff;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            background-color: #555;
        }
        .preview-nav a.active {
            background-color: #4a6fa5;
        }
        .preview-nav a:hover {
            background-color: #666;
        }
        .preview-content {
            margin-top: 60px;
            border: none;
            width: 100%;
            height: calc(100vh - 60px);
        }
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }
        .device-toggle {
            display: flex;
            gap: 10px;
            margin-left: 20px;
        }
        .device-toggle button {
            background-color: #555;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .device-toggle button.active {
            background-color: #4a6fa5;
        }
        .device-toggle button:hover {
            background-color: #666;
        }
        .preview-frame-container {
            margin-top: 60px;
            width: 100%;
            height: calc(100vh - 60px);
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f0f0f0;
            padding: 20px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        .preview-frame {
            border: none;
            width: 100%;
            height: 100%;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .mobile-view .preview-frame {
            width: 375px;
            height: 667px;
        }
        .tablet-view .preview-frame {
            width: 768px;
            height: 1024px;
        }
        .desktop-view .preview-frame {
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body>
    <div class="preview-header">
        <h1>Preview Tool - ' . htmlspecialchars($systemName) . '</h1>
        <div style="display: flex; align-items: center;">
            <div class="device-toggle">
                <button class="device-btn active" data-device="desktop">Desktop</button>
                <button class="device-btn" data-device="tablet">Tablet</button>
                <button class="device-btn" data-device="mobile">Mobile</button>
            </div>
            <div class="preview-nav">
                <a href="?page=expired" class="' . ($page === 'expired' ? 'active' : '') . '">Expired</a>
                <a href="?page=error" class="' . ($page === 'error' ? 'active' : '') . '">Error</a>
                <a href="?page=success" class="' . ($page === 'success' ? 'active' : '') . '">Success</a>
            </div>
        </div>
    </div>
    <div class="preview-frame-container desktop-view">
        <iframe class="preview-frame" id="previewFrame" src="preview-content.php?page=' . $page . '" frameborder="0"></iframe>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const deviceButtons = document.querySelectorAll(".device-btn");
            const frameContainer = document.querySelector(".preview-frame-container");
            
            deviceButtons.forEach(button => {
                button.addEventListener("click", function() {
                    // Remove active class from all buttons
                    deviceButtons.forEach(btn => btn.classList.remove("active"));
                    
                    // Add active class to clicked button
                    this.classList.add("active");
                    
                    // Update frame container class
                    frameContainer.className = "preview-frame-container " + this.dataset.device + "-view";
                });
            });
        });
    </script>
</body>
</html>';
