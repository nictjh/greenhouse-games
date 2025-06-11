<?php
/**
 * Link Service Functions
 * 
 * Handles generating, validating, and managing access links
 */

// Set default timezone to Singapore
date_default_timezone_set('Asia/Singapore');

// Prevent direct access
if (!defined('ACCESS_CONTROL')) {
    http_response_code(403);
    exit('Direct access forbidden');
}

/**
 * Generate a new access link
 * 
 * @param array $gameIds The IDs of the games to include
 * @param int $duration The duration in seconds
 * @param int $concurrentLimit The maximum number of concurrent devices
 * @param string $note Optional note for the link
 * @param string $name Optional session name
 * @return array The generated link data
 */
function generateLink($gameIds, $duration, $concurrentLimit, $note = '', $name = '') {
    // Validate game IDs
    $games = getGames();
    $validGameIds = [];
    
    foreach ($gameIds as $gameId) {
        foreach ($games['games'] as $game) {
            if ($game['id'] === $gameId) {
                $validGameIds[] = $gameId;
                break;
            }
        }
    }
    
    if (empty($validGameIds)) {
        return [
            'success' => false,
            'message' => 'No valid game IDs provided'
        ];
    }
    
    // Generate token
    $token = generateToken(32);
    
    // Create link data
    $linkData = [
        'id' => uniqid(),
        'token' => $token,
        'game_ids' => $validGameIds,
        'duration' => $duration,
        'concurrent_limit' => $concurrentLimit,
        'created_at' => time(),
        'expires_at' => null, // Will be set when first activated
        'activated_at' => null,
        'is_active' => true,
        'note' => $note,
        'name' => $name, // Session name
        'sessions' => []
    ];
    
    // Save link
    $links = getLinks();
    $links[] = $linkData;
    saveLinks($links);
    
    return [
        'success' => true,
        'link' => $linkData
    ];
}

/**
 * Get a link by token
 * 
 * @param string $token The link token
 * @return array|null The link data or null if not found
 */
function getLinkByToken($token) {
    $links = getLinks();
    
    foreach ($links as $link) {
        if ($link['token'] === $token) {
            return $link;
        }
    }
    
    return null;
}

/**
 * Update a link
 * 
 * @param array $link The link data to update
 * @return bool True if successful, false otherwise
 */
function updateLink($link) {
    $links = getLinks();
    
    foreach ($links as $key => $existingLink) {
        if ($existingLink['id'] === $link['id']) {
            $links[$key] = $link;
            return saveLinks($links);
        }
    }
    
    return false;
}

/**
 * Validate a link token and create a session
 * 
 * @param string $token The link token
 * @param string $deviceFingerprint The device fingerprint
 * @return array The validation result
 */
function validateLinkAndCreateSession($token, $deviceFingerprint) {
    // Debug information
    error_log("Validating link token: " . $token);
    
    $link = getLinkByToken($token);
    
    if ($link === null) {
        error_log("Link validation failed: Token not found");
        return [
            'success' => false,
            'message' => 'Invalid access link'
        ];
    }
    
    // Debug link data
    error_log("Link found: ID=" . $link['id'] . ", Active=" . ($link['is_active'] ? 'yes' : 'no') . 
              ", Games=" . count($link['game_ids']) . ", Name=" . ($link['name'] ?? 'unnamed'));
    
    if (!$link['is_active']) {
        error_log("Link validation failed: Link is not active");
        return [
            'success' => false,
            'message' => 'Access link has been revoked'
        ];
    }
    
    // Check if link has expired
    if ($link['activated_at'] !== null) {
        $expiresAt = $link['expires_at'];
        
        if ($expiresAt !== null && $expiresAt < time()) {
            error_log("Link validation failed: Link has expired");
            return [
                'success' => false,
                'message' => 'Access link has expired'
            ];
        }
    }
    
    // Check for existing session with this device fingerprint
    $existingSessionIndex = -1;
    foreach ($link['sessions'] as $index => $session) {
        if ($session['device_fingerprint'] === $deviceFingerprint) {
            $existingSessionIndex = $index;
            break;
        }
    }
    
    // If session exists, update last activity
    if ($existingSessionIndex !== -1) {
        $link['sessions'][$existingSessionIndex]['last_activity'] = time();
        updateLink($link);
        
        // Get game data
        $games = getGames();
        $gameData = [];
        
        foreach ($link['game_ids'] as $gameId) {
            foreach ($games['games'] as $game) {
                if ($game['id'] === $gameId) {
                    $gameData[] = $game;
                    break;
                }
            }
        }
        
        return [
            'success' => true,
            'session_id' => $link['sessions'][$existingSessionIndex]['id'],
            'games' => $gameData,
            'expires_at' => $link['expires_at'],
            'session_name' => $link['name'] ?? '', // Include session name
            'message' => 'Session updated'
        ];
    }
    
    // Check concurrent limit
    $activeSessions = 0;
    $now = time();
    $inactiveCutoff = $now - 300; // 5 minutes
    $activeSessionsList = [];
    
    foreach ($link['sessions'] as $session) {
        if ($session['last_activity'] > $inactiveCutoff) {
            $activeSessions++;
            $activeSessionsList[] = [
                'id' => $session['id'],
                'fingerprint' => substr($session['device_fingerprint'], 0, 8) . '...',
                'last_activity' => date('Y-m-d H:i:s', $session['last_activity'])
            ];
        }
    }
    
    // Log active sessions for debugging
    error_log("Active sessions: " . $activeSessions . " of " . $link['concurrent_limit'] . " allowed");
    error_log("Active sessions list: " . json_encode($activeSessionsList));
    error_log("Current device fingerprint: " . substr($deviceFingerprint, 0, 8) . "...");
    
    if ($activeSessions > $link['concurrent_limit']) {
        error_log("Concurrent limit reached. Access denied for device: " . substr($deviceFingerprint, 0, 8) . "...");
        return [
            'success' => false,
            'message' => 'Maximum number of concurrent devices reached (' . $link['concurrent_limit'] . ')'
        ];
    }
    
    // Activate link if not already activated
    if ($link['activated_at'] === null) {
        $link['activated_at'] = $now;
        $link['expires_at'] = $now + $link['duration'];
    }
    
    // Create new session
    $sessionId = generateToken(16);
    $session = [
        'id' => $sessionId,
        'device_fingerprint' => $deviceFingerprint,
        'created_at' => $now,
        'last_activity' => $now
    ];
    
    $link['sessions'][] = $session;
    updateLink($link);
    
    // Get game data
    $games = getGames();
    $gameData = [];
    
    foreach ($link['game_ids'] as $gameId) {
        foreach ($games['games'] as $game) {
            if ($game['id'] === $gameId) {
                $gameData[] = $game;
                break;
            }
        }
    }
    
    return [
        'success' => true,
        'session_id' => $sessionId,
        'games' => $gameData,
        'expires_at' => $link['expires_at'],
        'session_name' => $link['name'] ?? '', // Include session name
        'message' => 'Session created'
    ];
}

/**
 * Validate a session
 * 
 * @param string $token The link token
 * @param string $sessionId The session ID
 * @param string $deviceFingerprint The device fingerprint
 * @return array The validation result
 */
function validateSession($token, $sessionId, $deviceFingerprint) {
    error_log("Validating session: Token=" . substr($token, 0, 8) . "..., SessionID=" . substr($sessionId, 0, 8) . "..., Fingerprint=" . substr($deviceFingerprint, 0, 8) . "...");
    
    $link = getLinkByToken($token);
    
    if ($link === null) {
        error_log("Session validation failed: Token not found");
        return [
            'success' => false,
            'message' => 'Invalid access link'
        ];
    }
    
    if (!$link['is_active']) {
        error_log("Session validation failed: Link is not active");
        return [
            'success' => false,
            'message' => 'Access link has been revoked'
        ];
    }
    
    // Check if link has expired
    if ($link['expires_at'] !== null && $link['expires_at'] < time()) {
        error_log("Session validation failed: Link has expired");
        return [
            'success' => false,
            'message' => 'Access link has expired'
        ];
    }
    
    // Find session
    $sessionIndex = -1;
    foreach ($link['sessions'] as $index => $session) {
        if ($session['id'] === $sessionId) {
            $sessionIndex = $index;
            break;
        }
    }
    
    if ($sessionIndex === -1) {
        error_log("Session validation failed: Session ID not found");
        return [
            'success' => false,
            'message' => 'Invalid session'
        ];
    }
    
    // Verify device fingerprint
    $storedFingerprint = $link['sessions'][$sessionIndex]['device_fingerprint'];
    if ($storedFingerprint !== $deviceFingerprint) {
        error_log("Session validation failed: Device fingerprint mismatch");
        error_log("Stored fingerprint: " . substr($storedFingerprint, 0, 8) . "...");
        error_log("Current fingerprint: " . substr($deviceFingerprint, 0, 8) . "...");
        return [
            'success' => false,
            'message' => 'Device fingerprint mismatch'
        ];
    }
    
    // Update last activity
    $link['sessions'][$sessionIndex]['last_activity'] = time();
    updateLink($link);
    
    error_log("Session validation successful");
    return [
        'success' => true,
        'expires_at' => $link['expires_at'],
        'message' => 'Session valid'
    ];
}

/**
 * Revoke a link
 * 
 * @param string $linkId The link ID
 * @return bool True if successful, false otherwise
 */
function revokeLink($linkId) {
    $links = getLinks();
    
    foreach ($links as $key => $link) {
        if ($link['id'] === $linkId) {
            $links[$key]['is_active'] = false;
            return saveLinks($links);
        }
    }
    
    return false;
}

/**
 * Get all links
 * 
 * @return array The links
 */
function getAllLinks() {
    return getLinks();
}

/**
 * Clean up inactive sessions
 * 
 * @return int The number of cleaned up sessions
 */
function cleanupInactiveSessions() {
    $links = getLinks();
    $now = time();
    $inactiveCutoff = $now - 300; // 5 minutes
    $cleanedCount = 0;
    
    foreach ($links as $linkKey => $link) {
        $sessions = $link['sessions'];
        $newSessions = [];
        
        foreach ($sessions as $session) {
            if ($session['last_activity'] > $inactiveCutoff) {
                $newSessions[] = $session;
            } else {
                $cleanedCount++;
            }
        }
        
        if (count($newSessions) !== count($sessions)) {
            $links[$linkKey]['sessions'] = $newSessions;
        }
    }
    
    if ($cleanedCount > 0) {
        saveLinks($links);
    }
    
    return $cleanedCount;
}
