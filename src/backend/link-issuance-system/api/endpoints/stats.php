<?php
/**
 * Stats Endpoint
 * 
 * Handles retrieving system statistics
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
error_log("Stats endpoint authentication: " . ($validAuth ? "Success" : "Failed"));

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

// Get games
$games = getGames();

// Calculate statistics
$now = time();
$inactiveCutoff = $now - 300; // 5 minutes

$stats = [
    'links' => [
        'total' => count($links),
        'active' => 0,
        'pending' => 0,
        'expired' => 0,
        'revoked' => 0
    ],
    'sessions' => [
        'total' => 0,
        'active' => 0
    ],
    'games' => []
];

// Initialize game stats
foreach ($games['games'] as $game) {
    $stats['games'][] = [
        'id' => $game['id'],
        'name' => $game['name'],
        'type' => $game['type'],
        'url' => $game['url'],
        'description' => $game['description'],
        'image_url' => $game['image_url'] ?? '',
        'external_url' => $game['external_url'] ?? '',
        'active_links' => 0,
        'active_sessions' => 0,
        'total_links' => 0
    ];
}

// Process links
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
    
    // Update link stats
    $stats['links'][$status]++;
    
    // Count active sessions
    $activeSessions = 0;
    
    foreach ($link['sessions'] as $session) {
        $stats['sessions']['total']++;
        
        if ($session['last_activity'] > $inactiveCutoff) {
            $activeSessions++;
            $stats['sessions']['active']++;
        }
    }
    
    // Update game stats
    foreach ($link['game_ids'] as $gameId) {
        foreach ($stats['games'] as &$game) {
            if ($game['id'] === $gameId) {
                $game['total_links']++;
                
                if ($status === 'active') {
                    $game['active_links']++;
                    $game['active_sessions'] += $activeSessions;
                }
                
                break;
            }
        }
    }
}

// Return stats
echo json_encode([
    'success' => true,
    'stats' => $stats
]);
