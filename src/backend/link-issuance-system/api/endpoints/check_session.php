<?php
/**
 * Check Session Endpoint
 * 
 * Handles checking if a session is valid
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
if ($data === null) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data'
    ]);
    exit;
}

// Check if session token is provided
if (isset($data['session_token'])) {
    // Validate JWT
    $payload = validateJWT($data['session_token']);
    
    if ($payload === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid session token'
        ]);
        exit;
    }
    
    // Extract session data
    $token = $payload['token'] ?? '';
    $sessionId = $payload['session_id'] ?? '';
    $deviceFingerprint = $payload['device_fingerprint'] ?? '';
    
    if (empty($token) || empty($sessionId) || empty($deviceFingerprint)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid session data'
        ]);
        exit;
    }
    
    // Validate session
    $result = validateSession($token, $sessionId, $deviceFingerprint);
    
    // Return result
    if ($result['success']) {
        // Format expiry time
        $expiresAt = null;
        if (isset($result['expires_at'])) {
            $expiresAt = date('c', $result['expires_at']);
        }
        
        echo json_encode([
            'success' => true,
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
            echo json_encode([
                'success' => false,
                'message' => $result['message']
            ]);
        }
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No session token provided'
    ]);
}
