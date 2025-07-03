<?php
/**
 * Validate Endpoint
 * 
 * Handles validating access links and creating sessions
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

// Get request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// Validate request data
if ($data === null || !isset($data['token'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data'
    ]);
    exit;
}

// Generate device fingerprint
$deviceFingerprint = generateDeviceFingerprint();

// Validate link and create session
$result = validateLinkAndCreateSession($data['token'], $deviceFingerprint);

// Return result
if ($result['success']) {
    // Format game data
    $games = [];
    foreach ($result['games'] as $game) {
        $games[] = [
            'id' => $game['id'],
            'name' => $game['name'],
            'type' => $game['type'],
            'url' => $game['url'],
            'description' => $game['description']
        ];
    }
    
    // Format expiry time
    $expiresAt = null;
    if (isset($result['expires_at'])) {
        $expiresAt = date('c', $result['expires_at']);
    }
    
    // Set session cookie
    $sessionData = [
        'token' => $data['token'],
        'session_id' => $result['session_id'],
        'device_fingerprint' => $deviceFingerprint
    ];
    
    $sessionToken = generateJWT($sessionData, 86400); // 24 hours
    
    setcookie('session_token', $sessionToken, [
        'expires' => time() + 86400,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    echo json_encode([
        'success' => true,
        'games' => $games,
        'expires_at' => $expiresAt,
        'message' => $result['message']
    ]);
} else {
    // Check if the error is due to concurrent device limit
    if (strpos($result['message'], 'concurrent devices') !== false || 
        strpos($result['message'], 'device limit') !== false) {
        http_response_code(429); // Too Many Requests
        echo json_encode([
            'success' => false,
            'error_type' => 'device_limit_reached',
            'message' => $result['message']
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
}
