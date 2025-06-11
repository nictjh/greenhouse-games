<?php
/**
 * Direct Access Script
 * 
 * This script provides direct access to games without relying on URL rewriting
 */

// Define constant to prevent direct access to included files
define('ACCESS_CONTROL', true);

// Start session
session_start();

// Include utility functions
require_once __DIR__ . '/api/utils/storage.php';
require_once __DIR__ . '/api/utils/security.php';
require_once __DIR__ . '/api/utils/link_service.php';

// Set security headers
setSecurityHeaders();

// Get token from query parameter
$token = $_GET['token'] ?? '';

// Debug information
error_log("Direct Access Request: Token=" . (!empty($token) ? $token : 'missing'));

// Simple validation - just check if token exists in links.json
if (!empty($token)) {
    $link = getLinkByToken($token);
    
    if ($link !== null && $link['is_active']) {
        // Token is valid - redirect to access portal with token
        // Use relative path to avoid domain issues
        header('Location: access/index.php?token=' . urlencode($token));
        exit;
    }
}

// If we get here, token is invalid or missing
// Redirect to error page
header('Location: access/error.php');
exit;
