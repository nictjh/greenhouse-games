<?php
/**
 * API Entry Point
 * 
 * Handles all API requests and routes them to the appropriate handler
 */

// Set default timezone to Singapore
date_default_timezone_set('Asia/Singapore');

// Define constant to prevent direct access to included files
define('ACCESS_CONTROL', true);

// Start session
session_start();

// Set headers for CORS and content type
header('Content-Type: application/json');

// Get the origin
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';

// Allow the specific origin or all origins
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Credentials: true'); // Allow credentials (cookies)
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400'); // Cache preflight for 24 hours

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Debug information - log request headers
error_log("--- API Request Headers ---");
foreach (getallheaders() as $name => $value) {
    error_log("$name: $value");
}

// Include utility functions
require_once __DIR__ . '/utils/storage.php';
require_once __DIR__ . '/utils/security.php';
require_once __DIR__ . '/utils/link_service.php';

// Set security headers
setSecurityHeaders();

// Debug information - log all request details to help diagnose issues
error_log("--- API Request Debug Info ---");
error_log("REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not set'));
error_log("QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? 'Not set'));
error_log("PATH_INFO: " . ($_SERVER['PATH_INFO'] ?? 'Not set'));
error_log("REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'Not set'));
error_log("HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'Not set'));
error_log("SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'Not set'));
error_log("PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'Not set'));
error_log("GET params: " . print_r($_GET, true));

// Get endpoint from query parameter (preferred method)
$path = '';

// Method 1: Direct query parameter (from .htaccess rewrite)
if (isset($_GET['endpoint'])) {
    $path = $_GET['endpoint'];
    error_log("Endpoint from query parameter: " . $path);
}

// Method 2: Extract from REQUEST_URI if method 1 fails
if (empty($path)) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $basePath = '/api/';
    
    if (strpos($requestUri, $basePath) !== false) {
        $path = substr($requestUri, strpos($requestUri, $basePath) + strlen($basePath));
        $path = strtok($path, '?');
        error_log("Endpoint from REQUEST_URI: " . $path);
    }
}

// Method 3: Check if we're in a subdirectory structure
if (empty($path)) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $uriParts = explode('/', trim($requestUri, '/'));
    $apiIndex = array_search('api', $uriParts);
    
    if ($apiIndex !== false && isset($uriParts[$apiIndex + 1])) {
        $path = $uriParts[$apiIndex + 1];
        error_log("Endpoint from URI parts: " . $path);
    }
}

// Method 4: Try to get from PATH_INFO
if (empty($path) && isset($_SERVER['PATH_INFO'])) {
    $pathInfo = trim($_SERVER['PATH_INFO'], '/');
    if (!empty($pathInfo)) {
        $path = $pathInfo;
        error_log("Endpoint from PATH_INFO: " . $path);
    }
}

// Clean up the path - remove file extensions if present
if (!empty($path)) {
    // Remove .php extension if present
    $path = str_replace('.php', '', $path);
    
    // Remove any trailing slashes
    $path = rtrim($path, '/');
    
    // Convert underscores to hyphens for consistency
    $path = str_replace('_', '-', $path);
}

// Log the final extracted path
error_log("Final API endpoint: " . $path);

// Route request
switch ($path) {
    case 'generate':
        require_once __DIR__ . '/endpoints/generate.php';
        break;
    
    case 'validate':
        require_once __DIR__ . '/endpoints/validate.php';
        break;
    
    case 'check-session':
        require_once __DIR__ . '/endpoints/check_session.php';
        break;
    
    case 'revoke':
        require_once __DIR__ . '/endpoints/revoke.php';
        break;
    
    case 'list':
        require_once __DIR__ . '/endpoints/list.php';
        break;
    
    case 'stats':
        require_once __DIR__ . '/endpoints/stats.php';
        break;
    
    case 'save-games':
        require_once __DIR__ . '/endpoints/save-games.php';
        break;
    
    case 'delete-link':
        require_once __DIR__ . '/endpoints/delete-link.php';
        break;
    
    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint not found'
        ]);
        break;
}
