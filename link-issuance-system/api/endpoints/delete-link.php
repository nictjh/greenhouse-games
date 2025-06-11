<?php
/**
 * Delete Link Endpoint
 * 
 * Handles permanently deleting links from the system
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
error_log("Delete Link endpoint authentication: " . ($validAuth ? "Success" : "Failed"));

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

// Delete link
$result = deleteLink($data['link_id']);

// Return result
if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Link deleted successfully'
    ]);
} else {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Link not found or could not be deleted'
    ]);
}

/**
 * Delete a link permanently
 * 
 * @param string $linkId The link ID
 * @return bool True if successful, false otherwise
 */
function deleteLink($linkId) {
    $links = getLinks();
    $found = false;
    
    // Find and remove the link
    foreach ($links as $key => $link) {
        if ($link['id'] === $linkId) {
            array_splice($links, $key, 1);
            $found = true;
            break;
        }
    }
    
    if ($found) {
        // Save updated links
        return saveLinks($links);
    }
    
    return false;
}
