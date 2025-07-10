<?php
/**
 * Save Games Endpoint
 * 
 * Handles saving games configuration
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
error_log("Save Games endpoint authentication: " . ($validAuth ? "Success" : "Failed"));

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
if ($data === null || !isset($data['games']) || !is_array($data['games'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data'
    ]);
    exit;
}

// Validate games
foreach ($data['games'] as $game) {
    if (!isset($game['name']) || !isset($game['url'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Game name and URL are required'
        ]);
        exit;
    }
    
    // Ensure image_url is set (even if empty)
    if (!isset($game['image_url'])) {
        $game['image_url'] = '';
    }
}

// Preserve existing game IDs
$existingGames = getGames();
$existingGameIds = [];

foreach ($existingGames['games'] as $game) {
    $existingGameIds[$game['name']] = $game['id'];
}

// Update games with existing IDs
foreach ($data['games'] as &$game) {
    if (isset($existingGameIds[$game['name']])) {
        $game['id'] = $existingGameIds[$game['name']];
    } elseif (!isset($game['id'])) {
        // Generate new ID if not provided
        $game['id'] = 'game-' . uniqid();
    }
}

// Save games
$gamesConfig = [
    'games' => $data['games']
];

$result = saveGames($gamesConfig);

// Return result
if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Games saved successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save games'
    ]);
}

/**
 * Save games configuration
 * 
 * @param array $gamesConfig The games configuration
 * @return bool True if successful, false otherwise
 */
function saveGames($gamesConfig) {
    $gamesFile = __DIR__ . '/../../config/games.json';
    
    // Create backup
    $backupFile = $gamesFile . '.bak';
    if (file_exists($gamesFile)) {
        copy($gamesFile, $backupFile);
    }
    
    // Save games
    $result = file_put_contents($gamesFile, json_encode($gamesConfig, JSON_PRETTY_PRINT));
    
    return $result !== false;
}
