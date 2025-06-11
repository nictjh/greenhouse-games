<?php
/**
 * Access Gateway
 * 
 * Main entry point for users accessing games through access links
 */

// Define constant to prevent direct access to included files
define('ACCESS_CONTROL', true);

// Start session
session_start();

// Include utility functions
require_once __DIR__ . '/../api/utils/storage.php';
require_once __DIR__ . '/../api/utils/security.php';
require_once __DIR__ . '/../api/utils/link_service.php';

// Set security headers
setSecurityHeaders();

// Get settings
$settings = getSettings();

// Check for token in URL
$token = $_GET['token'] ?? '';
$validToken = false;
$games = [];
$expiresAt = null;
$errorMessage = '';
$sessionName = ''; // Added for session name

// Debug information
error_log("Access Portal Request: Token=" . (!empty($token) ? $token : 'missing'));
error_log("Access Portal Server variables: " . json_encode($_SERVER));
error_log("Access Portal GET parameters: " . json_encode($_GET));
error_log("Access Portal Cookie parameters: " . json_encode($_COOKIE));

if (!empty($token)) {
    // Simplified validation - first check if token exists
    $link = getLinkByToken($token);
    
    if ($link !== null && $link['is_active']) {
        // Check if link has expired
        if ($link['expires_at'] !== null && $link['expires_at'] < time()) {
            $validToken = false;
            $errorMessage = 'Access link has expired';
            
            // Redirect to expired page
            header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/expired.php');
            exit;
        }
        
        // Token exists, is active, and not expired
        $validToken = true;
        
        // Get session name if available
        $sessionName = $link['name'] ?? '';
        
        // Get expiry time
        $expiresAt = $link['expires_at'];
        
        // Get game data
        $gamesConfig = getGames();
        foreach ($link['game_ids'] as $gameId) {
            foreach ($gamesConfig['games'] as $game) {
                if ($game['id'] === $gameId) {
                    $games[] = $game;
                    break;
                }
            }
        }
        
        // Generate device fingerprint
        $deviceFingerprint = generateDeviceFingerprint();
        
        // Create or update session
        $result = validateLinkAndCreateSession($token, $deviceFingerprint);
        
        if ($result['success']) {
            // Set session cookie
            $sessionData = [
                'token' => $token,
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
        } else {
            error_log("Session creation failed but token is valid: " . $result['message']);
            
            // Check if the error is due to expired link
            if (strpos($result['message'], 'expired') !== false) {
                $validToken = false;
                $errorMessage = $result['message'];
                
                // Redirect to expired page
                header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/expired.php');
                exit;
            }
            // Check if the error is due to concurrent device limit
            else if (strpos($result['message'], 'concurrent devices') !== false || 
                strpos($result['message'], 'device limit') !== false) {
                // Set error message and redirect to device limit page
                $validToken = false;
                $errorMessage = $result['message'];
                
                // Redirect to device limit page with absolute path
                header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/device_limit.php');
                exit;
            }
            // Continue anyway for other errors since we already validated the token exists
        }
    } else {
        $errorMessage = 'Invalid or inactive access link';
        error_log("Token validation failed: Token exists in database? " . ($link !== null ? 'Yes' : 'No') . 
                  ", Is active? " . (($link !== null && $link['is_active']) ? 'Yes' : 'No'));
    }
} else {
    // Check for session cookie
    $sessionToken = $_COOKIE['session_token'] ?? '';
    
    if (!empty($sessionToken)) {
        // Validate JWT
        $payload = validateJWT($sessionToken);
        
        if ($payload !== false) {
            // Extract session data
            $token = $payload['token'] ?? '';
            $sessionId = $payload['session_id'] ?? '';
            $deviceFingerprint = $payload['device_fingerprint'] ?? '';
            
            if (!empty($token) && !empty($sessionId) && !empty($deviceFingerprint)) {
                // Validate session
                $result = validateSession($token, $sessionId, $deviceFingerprint);
                
                if ($result['success']) {
                    $validToken = true;
                    
                    // Get link
                    $link = getLinkByToken($token);
                    
                    if ($link !== null) {
                        // Check if link has expired
                        if ($link['expires_at'] !== null && $link['expires_at'] < time()) {
                            $validToken = false;
                            $errorMessage = 'Access link has expired';
                            
                            // Clear session cookie
                            setcookie('session_token', '', [
                                'expires' => time() - 3600,
                                'path' => '/',
                                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                                'httponly' => true,
                                'samesite' => 'Lax'
                            ]);
                            
                            // Redirect to expired page
                            header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/expired.php');
                            exit;
                        }
                        
                        // Get session name if available
                        $sessionName = $link['name'] ?? '';
                        
                        // Get game data
                        $gamesConfig = getGames();
                        
                        foreach ($link['game_ids'] as $gameId) {
                            foreach ($gamesConfig['games'] as $game) {
                                if ($game['id'] === $gameId) {
                                    $games[] = $game;
                                    break;
                                }
                            }
                        }
                        
                        // Get expiry time
                        $expiresAt = $link['expires_at'];
                    }
                } else {
                    $errorMessage = $result['message'];
                    
                    // Check if the error is due to expired link
                    if (strpos($result['message'], 'expired') !== false) {
                        $validToken = false;
                        
                        // Clear session cookie
                        setcookie('session_token', '', [
                            'expires' => time() - 3600,
                            'path' => '/',
                            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                            'httponly' => true,
                            'samesite' => 'Lax'
                        ]);
                        
                        // Redirect to expired page
                        header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/expired.php');
                        exit;
                    }
                    // Check if the error is due to concurrent device limit
                    else if (strpos($result['message'], 'concurrent devices') !== false || 
                        strpos($result['message'], 'device limit') !== false) {
                        // Redirect to device limit page with absolute path
                        header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/device_limit.php');
                        exit;
                    }
                    
                    // Clear session cookie for other errors
                    setcookie('session_token', '', [
                        'expires' => time() - 3600,
                        'path' => '/',
                        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                }
            }
        }
    }
}

// Get system name
$systemName = $settings['system']['name'];

// Format expiry time
$expiryFormatted = '';
if ($expiresAt !== null) {
    $expiryTimestamp = is_numeric($expiresAt) ? $expiresAt : strtotime($expiresAt);
    $now = time();
    $timeRemaining = $expiryTimestamp - $now;
    
    if ($timeRemaining <= 0) {
        $expiryFormatted = 'Expired';
    } else {
        $days = floor($timeRemaining / 86400);
        $hours = floor(($timeRemaining % 86400) / 3600);
        $minutes = floor(($timeRemaining % 3600) / 60);
        
        if ($days > 0) {
            $expiryFormatted = $days . ' day' . ($days !== 1 ? 's' : '') . ', ' . $hours . ' hour' . ($hours !== 1 ? 's' : '');
        } elseif ($hours > 0) {
            $expiryFormatted = $hours . ' hour' . ($hours !== 1 ? 's' : '') . ', ' . $minutes . ' minute' . ($minutes !== 1 ? 's' : '');
        } else {
            $expiryFormatted = $minutes . ' minute' . ($minutes !== 1 ? 's' : '');
        }
    }
}

// Get other games for recommendations (excluding current games)
$otherGames = [];
if ($validToken) {
    $gamesConfig = getGames();
    $currentGameIds = array_map(function($game) { return $game['id']; }, $games);
    
    foreach ($gamesConfig['games'] as $game) {
        if (!in_array($game['id'], $currentGameIds)) {
            $otherGames[] = $game;
        }
    }
    
    // Limit to 3 games for display
    $otherGames = array_slice($otherGames, 0, 3);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($systemName); ?></title>
    <link rel="stylesheet" href="new-styles.css">
    <style>
        /* Game container styles */
        #game-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #fff;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        
        #game-container.hidden {
            display: none;
        }
        
        .game-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: #4B3372;
            color: white;
        }
        
        .game-header button {
            background-color: #2C5247;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            min-width: 120px; /* Ensure buttons have equal width */
        }
        
        .game-header button:hover {
            background-color: #1b5e20;
        }
        
        .game-info {
            flex: 1;
            text-align: center; /* Center the text */
        }
        
        #game-title {
            font-weight: bold;
            font-size: 18px;
        }
        
        #game-frame {
            flex: 1;
            border: none;
            width: 100%;
            height: calc(100% - 50px);
        }
        
        /* Additional styles for the game portal */
        .stars-header {
            background-image: url('../assets/img/Header.svg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            width: 100vw; 
            margin: 0; 
            padding: 0;
            height: 100px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 0;
            margin-bottom: -30px; /* Create overhang effect */
            z-index: 2;
            overflow: hidden;
        }
        
        .company-logo {
            height: 60px;
            position: relative;
            z-index: 2;
            margin-left: 20px;
        }
        
        .portal-banner {
            position: relative;
            background-image: url('../assets/img/PortalBanner.svg');
            background-size: cover;
            background-position: center;
            padding: 100px 0 30px 20px;
            z-index: 0;
        }
        
        .banner-title {
            font-family: 'Garet', sans-serif;
            font-weight: 700;
            font-size: 60px;
            color: white;
            margin-top: -70px;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
            text-transform: uppercase;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            text-align: left;
            max-width: 60%;
            line-height: 1;
        }
        
        .banner-title::after {
            content: "EMBARK\A ON YOUR\A JOURNEY";
            white-space: pre;
            position: absolute;
            top: 4px;
            left: 4px;
            color: #1e3d35;
            z-index: -1;
            text-transform: uppercase;
        }
        
        .portal-info {
            padding: 20px;
            background-color: #BBAFCB;
            margin-bottom: 0;
        }
        
        .portal-subtitle {
            font-family: 'Garet', sans-serif;
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .portal-message {
            font-family: 'Garet', sans-serif;
            font-size: 18px;
            color: #333;
            margin-bottom: 0;
        }
        
        .main-content {
            padding: 30px;
            background-color: #BBAFCB;
            padding-top: 0;
        }
        
        /* Encouraging Mascot Animation */
        .mascot-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100px;
            overflow: visible;
            z-index: 0;
        }
        
        .encouraging-mascot {
            position: absolute;
            right: -130px;
            top: -200px;
            width: 300px;
            height: auto;
            transform: rotate(-60deg) translateX(150px);
            animation: mascotSwishIn 1s ease-out forwards, mascotBounce 1s ease-out 1s forwards;
            z-index: 1;
            opacity: 0;
        }
        
        @keyframes mascotSwishIn {
            0% {
                transform: rotate(-60deg) translateX(150px);
                opacity: 0;
            }
            50% {
                transform: rotate(-50deg) translateX(40px);
                opacity: 1;
            }
            100% {
                transform: rotate(-30deg) translateX(0);
                opacity: 1;
            }
        }

        @keyframes mascotBounce {
            0% {
                transform: rotate(-30deg) translateY(0);
            }
            30% {
                transform: rotate(-30deg) translateY(-20px);
            }
            60% {
                transform: rotate(-30deg) translateY(10px);
            }
            100% {
                transform: rotate(-30deg) translateY(0);
            }
        }
        
        .access-granted {
            background-color: #e8f5e9;
            border-left: 5px solid #4caf50;
            padding: 20px;
            margin: -10px;
            border-radius: 5px;
            position: relative;
            z-index: 2;
            opacity: 90%;
        }
        
        .access-granted h3 {
            color: #2e7d32;
            margin-top: 0;
            font-size: 24px;
        }
        
        .session-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .device-info, .session-name, .expiry-info {
            font-size: 16px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .device-info strong, .session-name strong, .expiry-info strong {
            font-weight: bold;
        }
        
        .session-value, .expiry-value {
            font-weight: normal;
        }
        
        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .game-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .game-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .game-image {
            width: 100%;
            height: 180px;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .game-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .game-content {
            padding: 20px;
        }
        
        .game-content h4 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 20px;
            color: #333;
        }
        
        .game-content p {
            margin-bottom: 20px;
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .play-button {
            background-color: #4B3372;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
            width: 100%;
            text-align: center;
            display: block;
            text-decoration: none;
        }
        
        .play-button:hover {
            background-color: #372658;
        }
        
        .other-games-section {
            background-color: #7C67A2;
            padding: 20px;
            margin: 40px -30px 0;
        }
        
        .section-title {
            font-size: 18px;
            color: #fff;
            margin: 0 0 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #BCAED0;
        }
        
        .other-games-slider {
            display: flex;
            overflow-x: auto;
            gap: 15px;
            padding: 5px 0;
            scroll-behavior: smooth;
            max-height: 220px;
        }

        .other-games-slider .game-card img {
            width: 100%;
            height: 120px; /* smaller height for image */
            object-fit: cover;
            margin: 0;
        }
        
        .other-games-slider .game-card {
            display: flex;
            flex-direction: column;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            min-width: 200px;
        }
        
        .other-games-slider .game-content {
            padding: 10px;
            background-color: white;
            text-align: center;
        }
        
        .other-games-slider .game-content h4 {
            font-size: 16px;
            margin: 5px 0 5px 0;
            color: #333;
        }
        
        .other-games-slider .game-content p {
            font-size: 12px;
            margin-bottom: 0;
            color: #666
        }
        
        .slider-controls {
            display: flex;
            justify-content: center;
            margin-top: 10px;
            gap: 10px;
        }
        
        .slider-control {
            background-color: #4B3372;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
        }
        
        .slider-control:hover {
            background-color: #372658;
        }
        
        .contact-section {
            background-color: #4B3372;
            color: white;
            padding: 20px 20px;
            text-align: center;
            margin-top: -20px;
        }
        
        .contact-title {
            font-family: 'Garet', sans-serif;
            font-weight: 700;
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .contact-message {
            font-family: 'Garet', sans-serif;
            font-weight: 400;
            font-size: 20px;
            margin-bottom: 20px;
            opacity: 0.9;
        }
        
        .contact-button {
            font-family: 'Garet', sans-serif;
            display: inline-block;
            background-color: #2C5247;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-weight: bold;
            margin-bottom: 20px;
            transition: background-color 0.3s ease;
            border: 2px solid white;
            font-family: 'Garet', sans-serif;
        }
        
        .contact-button:hover {
            background-color: #1b5e20;
        }
        
        .copyright {
            font-size: 14px;
            opacity: 1;
            margin-top: 20px;
            color: white !important;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .banner-title {
                font-size: 40px;
            }
            
            .portal-subtitle {
                font-size: 20px;
            }
            
            .session-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .games-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .banner-title {
                font-size: 30px;
            }
            
            .portal-subtitle {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <?php if ($validToken): ?>
    <!-- Valid token - show games -->
    <div class="container">
        <!-- Header with stars and logo -->
        <div class="stars-header">
            <img src="../assets/img/JalanJourneyLogo.svg" alt="Jalan Journey" class="company-logo">
        </div>
        
        <!-- Portal Banner -->
        <div class="portal-banner">
            <div class="banner-title">EMBARK<br>ON YOUR<br>JOURNEY</div>
        </div>
        
        <!-- Portal Info -->
        <div class="portal-info">
            <div class="portal-subtitle">Jalan Journey Game Portal</div>
            <div class="portal-message">Let's get started! Choose a game below to begin.</div>
        </div>
        
        <div class="main-content">
            <?php if (!empty($games)): ?>
                <div class="parent-container" style="position: relative;">
                    <!--
                    <div class="mascot-container">
                        <img src="../assets/img/EncouragingMascot.svg" alt="Encouraging Mascot" class="encouraging-mascot">
                    </div>
                    -->
                
                    <div class="access-granted">
                        <h3>YOU'RE ALL SET!</h3>
                        <div class="session-info">
                            <?php
                            // Calculate active sessions count
                            $activeSessions = 0;
                            $now = time();
                            $inactiveCutoff = $now - 300; // 5 minutes
                            
                            if (isset($link['sessions'])) {
                                foreach ($link['sessions'] as $session) {
                                    if ($session['last_activity'] > $inactiveCutoff) {
                                        $activeSessions++;
                                    }
                                }
                            }
                            
                            // Get concurrent limit
                            $concurrentLimit = $link['concurrent_limit'] ?? 1;
                            ?>
                            <div class="device-info"><strong>Active Devices:</strong> <?php echo $activeSessions; ?>/<?php echo $concurrentLimit; ?></div>
                            <div class="session-name"><strong>Session:</strong> <span class="session-value"><?php echo !empty($sessionName) ? htmlspecialchars($sessionName) : 'N/A'; ?></span></div>
                            <div class="expiry-info"><strong>Time remaining:</strong> <span class="expiry-value"><?php echo !empty($expiryFormatted) ? $expiryFormatted : 'N/A'; ?></span></div>
                        </div>
                    </div>
                </div>
                
                <div class="games-grid">
                    <?php foreach ($games as $game): ?>
                        <div class="game-card">
                            <div class="game-image">
                                <?php if (!empty($game['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($game['image_url']); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>">
                                <?php else: ?>
                                    <div style="background-color: #FFEB3B; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-weight: bold;">GAME IMAGE</div>
                                <?php endif; ?>
                            </div>
                            <div class="game-content">
                                <h4><?php echo htmlspecialchars($game['name']); ?></h4>
                                <p><?php echo htmlspecialchars($game['description']); ?></p>
                                <button class="play-button" data-game-id="<?php echo htmlspecialchars($game['id']); ?>" data-game-url="<?php echo htmlspecialchars($game['url']); ?>" data-game-type="<?php echo htmlspecialchars($game['type']); ?>" data-game-name="<?php echo htmlspecialchars($game['name']); ?>">PLAY</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (!empty($otherGames)): ?>
                    <div class="other-games-section">
                        <h3 class="section-title">Other Games You Might Like</h3>
                        <div class="other-games-slider">
                            <?php foreach ($otherGames as $game): ?>
                                <?php 
                                    $hasExternalUrl = !empty($game['external_url']);
                                    $gameCardContent = '
                                        <div class="game-image">
                                            ' . (!empty($game['image_url']) ? 
                                                '<img src="' . htmlspecialchars($game['image_url']) . '" alt="' . htmlspecialchars($game['name']) . '">' : 
                                                '<div style="background-color: #BCAED0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-weight: bold;">OTHER GAME IMAGE</div>') . '
                                        </div>
                                        <div class="game-content">
                                            <h4>' . htmlspecialchars($game['name']) . '</h4>
                                            <p>' . htmlspecialchars($game['description']) . '</p>
                                        </div>
                                    ';
                                    
                                    if ($hasExternalUrl) {
                                        echo '<a href="' . htmlspecialchars($game['external_url']) . '" target="_blank" class="game-card" style="min-width: 200px; text-decoration: none; color: inherit;">' . $gameCardContent . '</a>';
                                    } else {
                                        echo '<div class="game-card" style="min-width: 200px;">' . $gameCardContent . '</div>';
                                    }
                                ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="slider-controls">
                        <button class="slider-control" id="prev-button">←</button>
                        <button class="slider-control" id="next-button">→</button>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="error-message">
                    <h3>No Games Available</h3>
                    <p>Your access link is valid, but no games are available.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Contact section with copyright -->
        <div class="contact-section">
            <div class="contact-title">Need any assistance?</div>
            <div class="contact-message">Feel free to reach out to us or your Event Organiser!</div>
            <a href="mailto:info@jalanjourney.com" class="contact-button">Contact Us</a>
            <p class="copyright">&copy; 2025 Jalan Journey. All Rights Reserved.</p>
        </div>
    </div>
    <?php else: ?>
    <!-- Invalid token - show error page -->
    <div class="stars-header">
        <img src="../assets/img/JalanJourneyLogo.svg" alt="Jalan Journey" class="company-logo">
    </div>
    
    <!-- Error content -->
    <div class="error-container">
        <div class="error-content">
            <div class="error-text">
                <div class="error-title">Uh oh!</div>
                <div class="error-subtitle">ACCESS DENIED</div>
                <div class="error-code">Error code: 403</div>
                <div class="error-message">
                    <?php echo !empty($errorMessage) ? htmlspecialchars($errorMessage) : 'Please Contact the Game Organiser'; ?>
                </div>
                <div class="button-container">
                    <a href="https://www.jalanjourney.com" class="back-to-homepage">Back to Homepage</a>
                </div>
            </div>
            <div class="error-image">
                <img src="../assets/img/SadMascot.svg" alt="Sad Mascot" class="mascot-image">
            </div>
        </div>
    </div>
    
    <!-- Contact section with copyright -->
    <div class="contact-section">
        <div class="contact-title">Need any assistance?</div>
        <div class="contact-message">Feel free to reach out to us or your Event Organiser!</div>
        <a href="mailto:info@jalanjourney.com" class="contact-button">Contact Us</a>
        <p class="copyright">&copy; 2025 Jalan Journey. All Rights Reserved.</p>
    </div>
    <?php endif; ?>
    
    <!-- Game Container -->
    <div id="game-container" class="hidden">
        <div class="game-header">
            <button id="back-button">← Back to Games</button>
            <div class="game-info">
                <div id="game-title"></div>
            </div>
            <button id="fullscreen-button">Fullscreen</button>
        </div>
        <iframe id="game-frame" src="about:blank" allowfullscreen></iframe>
    </div>
    
    <script>
        // Game launcher
        document.addEventListener('DOMContentLoaded', function() {
            const gameContainer = document.getElementById('game-container');
            const gameFrame = document.getElementById('game-frame');
            const gameTitle = document.getElementById('game-title');
            const backButton = document.getElementById('back-button');
            const fullscreenButton = document.getElementById('fullscreen-button');
            const playButtons = document.querySelectorAll('.play-button');
            
            // Play button click
            playButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const gameId = this.dataset.gameId;
                    const gameUrl = this.dataset.gameUrl;
                    const gameType = this.dataset.gameType;
                    const gameName = this.dataset.gameName;
                    
                    // Set game title
                    gameTitle.textContent = gameName;
                    
                    // Load game - Fix URL path if needed
                    console.log("Original game URL:", gameUrl);
                    console.log("Current location:", window.location.href);
                    console.log("Base URL:", window.location.origin);
                    
                    let finalUrl = gameUrl;
                    
                    // Get the base URL of the site
                    const baseUrl = window.location.origin;
                    
                    // Get token from session cookie
                    const sessionToken = getCookie('session_token');
                    let token = '';
                    
                    if (sessionToken) {
                        // Try to extract token from JWT payload
                        try {
                            const base64Url = sessionToken.split('.')[1];
                            const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
                            const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
                                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                            }).join(''));
                            
                            const payload = JSON.parse(jsonPayload);
                            token = payload.token || '';
                            console.log("Extracted token from session:", token);
                        } catch (e) {
                            console.error("Error extracting token from session:", e);
                        }
                    }
                    
                    // Handle different URL formats
                    if (gameUrl) {
                        // For the sample game specifically
                        if (gameUrl.includes('sample-game.html')) {
                            // Try direct path to the games directory
                            finalUrl = '../games/sample-game.html';
                            console.log("Using direct path to games directory");
                        }
                        // For URLs with Link-Issuance-System
                        else if (gameUrl.startsWith('/Link-Issuance-System/')) {
                            finalUrl = baseUrl + gameUrl;
                            console.log("Using absolute path for Link-Issuance-System URL");
                        }
                        // For relative URLs without leading slash
                        else if (!gameUrl.startsWith('http') && !gameUrl.startsWith('/')) {
                            finalUrl = baseUrl + '/' + gameUrl;
                            console.log("Adding base URL to relative path");
                        }
                        
                        // Add token parameter to URL
                        if (token) {
                            const separator = finalUrl.includes('?') ? '&' : '?';
                            finalUrl += separator + 'token=' + encodeURIComponent(token);
                            console.log("Added token parameter to URL");
                        }
                    }
                    
                    console.log("Final iframe src:", finalUrl);
                    
                    // Add error handling for iframe loading
                    gameFrame.onerror = function() {
                        console.error("Error loading iframe content");
                    };
                    
                    gameFrame.onload = function() {
                        console.log("Iframe loaded successfully");
                    };
                    
                    gameFrame.src = finalUrl;
                    
                    // Show game container
                    gameContainer.classList.remove('hidden');
                    
                    // Start session check
                    startSessionCheck();
                });
            });
            
            // Back button click
            backButton.addEventListener('click', function() {
                // Hide game container
                gameContainer.classList.add('hidden');
                
                // Stop session check
                stopSessionCheck();
                
                // Clear iframe
                gameFrame.src = 'about:blank';
            });
            
            // Fullscreen button click
            fullscreenButton.addEventListener('click', function() {
                if (gameFrame.requestFullscreen) {
                    gameFrame.requestFullscreen();
                } else if (gameFrame.mozRequestFullScreen) {
                    gameFrame.mozRequestFullScreen();
                } else if (gameFrame.webkitRequestFullscreen) {
                    gameFrame.webkitRequestFullscreen();
                } else if (gameFrame.msRequestFullscreen) {
                    gameFrame.msRequestFullscreen();
                }
            });
            
            // Session check
            let sessionCheckTimeout = null;
            
            function startSessionCheck() {
                // Get expiry time from the page
                const expiryElement = document.querySelector('.expiry-value');
                if (!expiryElement) {
                    console.error("Could not find expiry time element");
                    return;
                }
                
                const expiryText = expiryElement.textContent.trim();
                if (expiryText === 'N/A' || expiryText === 'Expired') {
                    console.log("No valid expiry time found");
                    return;
                }
                
                // Parse the expiry time text (format: "X days, Y hours" or "X hours, Y minutes" or "X minutes")
                let totalSeconds = 0;
                
                if (expiryText.includes('day')) {
                    const daysMatch = expiryText.match(/(\d+)\s+day/);
                    if (daysMatch) {
                        totalSeconds += parseInt(daysMatch[1]) * 86400;
                    }
                }
                
                if (expiryText.includes('hour')) {
                    const hoursMatch = expiryText.match(/(\d+)\s+hour/);
                    if (hoursMatch) {
                        totalSeconds += parseInt(hoursMatch[1]) * 3600;
                    }
                }
                
                if (expiryText.includes('minute')) {
                    const minutesMatch = expiryText.match(/(\d+)\s+minute/);
                    if (minutesMatch) {
                        totalSeconds += parseInt(minutesMatch[1]) * 60;
                    }
                }
                
                if (totalSeconds <= 0) {
                    console.log("Could not parse expiry time or already expired");
                    return;
                }
                
                console.log(`Session will expire in ${totalSeconds} seconds`);
                
                // Set timeout to check session when it expires
                sessionCheckTimeout = setTimeout(() => {
                    console.log("Session expiry time reached, checking session status");
                    checkSession();
                }, totalSeconds * 1000);
            }
            
            function stopSessionCheck() {
                if (sessionCheckTimeout) {
                    clearTimeout(sessionCheckTimeout);
                    sessionCheckTimeout = null;
                }
            }
            
            function checkSession() {
                // Get session token
                const sessionToken = getCookie('session_token');
                
                if (!sessionToken) {
                    handleExpiredSession();
                    return;
                }
                
                // Send request to check session
                fetch('../api/endpoints/check_session.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        session_token: sessionToken
                    })
                })
                .then(response => {
                    // Check if response status is 429 (Too Many Requests)
                    if (response.status === 429) {
                        handleDeviceLimitReached();
                        return { success: false };
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.success) {
                        // Check if error is due to device limit
                        if (data.error_type === 'device_limit_reached') {
                            handleDeviceLimitReached();
                        } else {
                            handleExpiredSession();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error checking session:', error);
                });
            }
            
            function handleExpiredSession() {
                // Stop session check
                stopSessionCheck();
                
                // Redirect to expired page
                window.location.href = 'expired.php';
            }
            
            function handleDeviceLimitReached() {
                // Stop session check
                stopSessionCheck();
                
                // Redirect to device limit page with absolute path
                window.location.href = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1) + 'device_limit.php';
            }
            
            function getCookie(name) {
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${name}=`);
                if (parts.length === 2) return parts.pop().split(';').shift();
                return null;
            }
            
            // Slider controls
            const slider = document.querySelector('.other-games-slider');
            const prevButton = document.getElementById('prev-button');
            const nextButton = document.getElementById('next-button');
            
            if (slider && prevButton && nextButton) {
                prevButton.addEventListener('click', () => {
                    slider.scrollBy({ left: -320, behavior: 'smooth' });
                });
                
                nextButton.addEventListener('click', () => {
                    slider.scrollBy({ left: 320, behavior: 'smooth' });
                });
            }
        });
    </script>
</body>
</html>
