<?php
/**
 * Start Local Server
 * 
 * This script starts a PHP development server for local testing
 */

// Check if PHP CLI is available
if (PHP_SAPI !== 'cli') {
    echo "This script must be run from the command line.";
    exit(1);
}

// Configuration
$host = 'localhost';
$port = 8000;
$rootDir = __DIR__;

// Display banner
echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║                                                           ║\n";
echo "║   Link Issuance System - Local Development Server         ║\n";
echo "║                                                           ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "Starting PHP development server at http://{$host}:{$port}\n";
echo "Document root: {$rootDir}\n";
echo "Press Ctrl+C to stop the server\n\n";

// Display preview URLs
echo "Preview URLs:\n";
echo "- Main Preview Tool: http://{$host}:{$port}/preview.php\n";
echo "- Expired Page: http://{$host}:{$port}/preview.php?page=expired\n";
echo "- Error Page: http://{$host}:{$port}/preview.php?page=error\n";
echo "- Success Page: http://{$host}:{$port}/preview.php?page=success\n\n";

// Start the server
$command = "php -S {$host}:{$port} -t {$rootDir}";
passthru($command);
