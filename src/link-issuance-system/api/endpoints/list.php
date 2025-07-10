<?php
/**
 * List Endpoint
 * 
 * Handles listing all access links
 */

// Prevent direct access
if (!defined('ACCESS_CONTROL')) {
    http_response_code(403);
    exit('Direct access forbidden');
}

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
error_log("List endpoint authentication: " . ($validAuth ? "Success" : "Failed"));

if (!$validAuth) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized - Admin access required'
    ]);
    exit;
}

// Get all links
$links = getAllLinks();

// Clean up inactive sessions
cleanupInactiveSessions();

// Format links for response
$formattedLinks = [];
$now = time();

foreach ($links as $link) {
    // Calculate status
    $status = 'pending';
    
    if (!$link['is_active']) {
        $status = 'revoked';
    } elseif ($link['activated_at'] !== null) {
        if ($link['expires_at'] !== null && $link['expires_at'] < $now) {
            $status = 'expired';
        } else {
            $status = 'active';
        }
    }
    
    // Count active sessions
    $activeSessions = 0;
    $inactiveCutoff = $now - 300; // 5 minutes
    
    foreach ($link['sessions'] as $session) {
        if ($session['last_activity'] > $inactiveCutoff) {
            $activeSessions++;
        }
    }
    
    // Format duration
    $durationFormatted = formatDuration($link['duration']);
    
    // Format dates
    $createdAt = date('Y-m-d H:i:s', $link['created_at']);
    $activatedAt = $link['activated_at'] !== null ? date('Y-m-d H:i:s', $link['activated_at']) : null;
    $expiresAt = $link['expires_at'] !== null ? date('Y-m-d H:i:s', $link['expires_at']) : null;
    
    // Generate access URL using direct-access.php with subdirectory
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $accessUrl = $baseUrl . '/Link-Issuance-System/direct-access.php?token=' . $link['token'];
    
    // Add to formatted links
    $formattedLinks[] = [
        'id' => $link['id'],
        'token' => $link['token'],
        'access_url' => $accessUrl,
        'game_ids' => $link['game_ids'],
        'duration' => $link['duration'],
        'duration_formatted' => $durationFormatted,
        'concurrent_limit' => $link['concurrent_limit'],
        'created_at' => $createdAt,
        'activated_at' => $activatedAt,
        'expires_at' => $expiresAt,
        'is_active' => $link['is_active'],
        'status' => $status,
        'active_sessions' => $activeSessions,
        'note' => $link['note'],
        'name' => $link['name'] ?? '' // Include session name in response
    ];
}

// Return links
echo json_encode([
    'success' => true,
    'links' => $formattedLinks
]);

/**
 * Format duration in seconds to a human-readable string
 * 
 * @param int $seconds The duration in seconds
 * @return string The formatted duration
 */
function formatDuration($seconds) {
    if ($seconds < 60) {
        return $seconds . ' seconds';
    }
    
    if ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        return $minutes . ' minute' . ($minutes !== 1 ? 's' : '');
    }
    
    if ($seconds < 86400) {
        $hours = floor($seconds / 3600);
        return $hours . ' hour' . ($hours !== 1 ? 's' : '');
    }
    
    $days = floor($seconds / 86400);
    return $days . ' day' . ($days !== 1 ? 's' : '');
}
