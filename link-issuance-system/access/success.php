<?php
/**
 * Success Page
 * 
 * Shown when a valid token is provided
 */

// Prevent direct access
if (!defined('ACCESS_CONTROL')) {
    http_response_code(403);
    exit('Direct access not allowed');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($systemName); ?></title>
    <link rel="stylesheet" href="access/styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo htmlspecialchars($systemName); ?></h1>
            <h2>Game Portal</h2>
        </header>
        
        <main>
            <?php if (!empty($games)): ?>
                <div class="success-message">
                    <h3>Access Granted</h3>
                    <p>You have access to the following games:</p>
                    <?php if (!empty($expiryFormatted)): ?>
                        <div class="expiry-info">Time remaining: <?php echo $expiryFormatted; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="games-list">
                    <div class="games-grid">
                        <?php foreach ($games as $game): ?>
                            <div class="game-card">
                                <h4><?php echo htmlspecialchars($game['name']); ?></h4>
                                <p><?php echo htmlspecialchars($game['description']); ?></p>
                                <button class="play-button" data-game-id="<?php echo htmlspecialchars($game['id']); ?>" data-game-url="<?php echo htmlspecialchars($game['url']); ?>" data-game-type="<?php echo htmlspecialchars($game['type']); ?>" data-game-name="<?php echo htmlspecialchars($game['name']); ?>">Play Game</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="error-message">
                    <h3>No Games Available</h3>
                    <p>Your access link is valid, but no games are available.</p>
                </div>
            <?php endif; ?>
        </main>
        
        <footer>
            <p>&copy; 2025 Jalan Journey. All rights reserved.</p>
        </footer>
    </div>
    
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
                    
                    // Load game
                    gameFrame.src = gameUrl;
                    
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
            let sessionCheckInterval = null;
            
            function startSessionCheck() {
                // Check session every 60 seconds
                sessionCheckInterval = setInterval(checkSession, <?php echo $settings['system']['session_check_interval'] * 1000; ?>);
            }
            
            function stopSessionCheck() {
                if (sessionCheckInterval) {
                    clearInterval(sessionCheckInterval);
                    sessionCheckInterval = null;
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
                fetch('api/check-session', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        session_token: sessionToken
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        handleExpiredSession();
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
                window.location.href = 'access/expired.php';
            }
            
            function getCookie(name) {
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${name}=`);
                if (parts.length === 2) return parts.pop().split(';').shift();
                return null;
            }
        });
    </script>
</body>
</html>
