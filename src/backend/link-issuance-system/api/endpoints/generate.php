<?php
/**
 * Generate Endpoint
 * 
 * Handles generating new access links
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
error_log("Generate endpoint authentication: " . ($validAuth ? "Success" : "Failed"));

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
if ($data === null || !isset($data['game_ids']) || !isset($data['duration']) || !isset($data['concurrent_limit']) || !isset($data['name']) || empty($data['name'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data. Game IDs, duration, concurrent limit, and session name are required.'
    ]);
    exit;
}

// Validate game IDs
if (!is_array($data['game_ids']) || empty($data['game_ids'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Game IDs must be a non-empty array'
    ]);
    exit;
}

// Validate duration
if (!is_numeric($data['duration']) || $data['duration'] <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Duration must be a positive number'
    ]);
    exit;
}

// Validate concurrent limit
if (!is_numeric($data['concurrent_limit']) || $data['concurrent_limit'] <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Concurrent limit must be a positive number'
    ]);
    exit;
}

// Generate link
$note = $data['note'] ?? '';
$name = $data['name'] ?? ''; // Get session name if provided
$result = generateLink($data['game_ids'], $data['duration'], $data['concurrent_limit'], $note, $name);

// Return result
if ($result['success']) {
    $link = $result['link'];
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $accessUrl = $baseUrl . '/Link-Issuance-System/direct-access.php?token=' . $link['token'];
    
    echo json_encode([
        'success' => true,
        'link' => [
            'id' => $link['id'],
            'token' => $link['token'],
            'access_url' => $accessUrl,
            'game_ids' => $link['game_ids'],
            'duration' => $link['duration'],
            'concurrent_limit' => $link['concurrent_limit'],
            'created_at' => $link['created_at'],
            'note' => $link['note'],
            'name' => $link['name'] ?? '' // Include session name in response
        ],
        'message' => 'Link generated successfully'
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}
