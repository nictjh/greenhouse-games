<?php
/**
 * Revoke Endpoint
 * 
 * Handles revoking access links
 */

// Prevent direct access
if (!defined('ACCESS_CONTROL')) {
    http_response_code(403);
    exit('Direct access forbidden');
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Check admin authentication using the new function
$validAuth = authenticateAdminRequest();

// Log authentication attempt
error_log("Revoke endpoint authentication: " . ($validAuth ? "Success" : "Failed"));

if (!$validAuth) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized - Admin access required'
    ]);
    exit;
}

// Get request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// Validate request data
if ($data === null || !isset($data['link_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data'
    ]);
    exit;
}

// Revoke link
$result = revokeLink($data['link_id']);

// Return result
if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Link revoked successfully'
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to revoke link'
    ]);
}
