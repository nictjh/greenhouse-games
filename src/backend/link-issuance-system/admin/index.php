<?php
/**
 * Admin Dashboard
 * 
 * Main entry point for the admin dashboard
 */

// Set default timezone to Singapore
date_default_timezone_set('Asia/Singapore');

// Define constant to prevent direct access to included files
define('ACCESS_CONTROL', true);

// Start session
session_start();

// Include utility functions
require_once __DIR__ . '/../api/utils/storage.php';
require_once __DIR__ . '/../api/utils/security.php';

// Set security headers
setSecurityHeaders();

// Get settings
$settings = getSettings();

// Check if logged in
$isLoggedIn = false;
$loginError = '';

// Check for admin token cookie
if (isset($_COOKIE['admin_token'])) {
    $token = $_COOKIE['admin_token'];
    $payload = validateJWT($token);
    
    if ($payload !== false && isset($payload['role']) && $payload['role'] === 'admin') {
        $isLoggedIn = true;
    }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $password = $_POST['password'] ?? '';
    
    // Validate password
    if (!empty($password)) {
        $storedHash = $settings['system']['admin_password_hash'];
        
        if (password_verify($password, $storedHash)) {
            // Generate admin token
            $payload = [
                'role' => 'admin',
                'name' => 'Administrator'
            ];
            
            $token = generateJWT($payload, $settings['system']['token_expiry']);
            
            // Set cookie
            setcookie('admin_token', $token, [
                'expires' => time() + $settings['system']['token_expiry'],
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            // Redirect to avoid resubmission
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $loginError = 'Invalid password';
        }
    } else {
        $loginError = 'Password is required';
    }
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Clear admin token cookie
    setcookie('admin_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Redirect to login page
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get system name
$systemName = $settings['system']['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($systemName); ?> - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/notifications.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
</head>
<body>
    <?php if ($isLoggedIn): ?>
        <!-- Admin Dashboard -->
        <div class="admin-container">
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="sidebar-header">
                    <h1><?php echo htmlspecialchars($systemName); ?></h1>
                    <h2>Admin Dashboard</h2>
                </div>
                
                <ul class="nav-links">
                    <li class="nav-item active" data-target="dashboard">
                        <span class="icon"><i class="fas fa-chart-line"></i></span>
                        <span class="text">Dashboard</span>
                    </li>
                    <li class="nav-item" data-target="links">
                        <span class="icon"><i class="fas fa-link"></i></span>
                        <span class="text">Access Links</span>
                    </li>
                    <li class="nav-item" data-target="bin">
                        <span class="icon"><i class="fas fa-trash"></i></span>
                        <span class="text">Bin</span>
                    </li>
                    <li class="nav-item" data-target="games">
                        <span class="icon"><i class="fas fa-gamepad"></i></span>
                        <span class="text">Games</span>
                    </li>
                    <li class="nav-item" data-target="settings">
                        <span class="icon"><i class="fas fa-cog"></i></span>
                        <span class="text">Settings</span>
                    </li>
                </ul>
                
                <div class="sidebar-footer">
                    <a href="?action=logout" class="logout-button">
                        <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
                        <span class="text">Logout</span>
                    </a>
                </div>
            </div>
            
            <!-- Content -->
            <div class="content">
                <div class="content-header">
                    <h2 id="page-title">Dashboard</h2>
                    <button id="refresh-button" class="button secondary">
                        <span class="icon"><i class="fas fa-sync-alt"></i></span>
                        <span class="text">Refresh</span>
                    </button>
                </div>
                
                <div class="content-body">
                    <!-- Dashboard Page -->
                    <div id="dashboard" class="page active">
                        <div class="stats-grid">
                            <div class="stat-card">
                                <h3>Active Links</h3>
                                <div class="stat-value" id="active-links">0</div>
                            </div>
                            <div class="stat-card">
                                <h3>Active Sessions</h3>
                                <div class="stat-value" id="active-sessions">0</div>
                            </div>
                            <div class="stat-card">
                                <h3>Total Links</h3>
                                <div class="stat-value" id="total-links">0</div>
                            </div>
                            <div class="stat-card">
                                <h3>Total Sessions</h3>
                                <div class="stat-value" id="total-sessions">0</div>
                            </div>
                        </div>
                        
                        <div class="charts-grid">
                            <div class="chart-card">
                                <h3>Link Status</h3>
                                <div class="chart-container">
                                    <canvas id="link-status-chart"></canvas>
                                </div>
                            </div>
                            <div class="chart-card">
                                <h3>Game Usage</h3>
                                <div class="chart-container">
                                    <canvas id="game-usage-chart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="section-header">
                            <h3>Recent Links</h3>
                        </div>
                        
                        <div class="table-container">
                            <table id="recent-links-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Games</th>
                                        <th>Duration</th>
                                        <th>Concurrent Limit</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" class="loading">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Links Page -->
                    <div id="links" class="page">
                        <div class="section-header">
                            <h3>Access Links</h3>
                            <button id="create-link-button" class="button primary">
                                <span class="icon"><i class="fas fa-plus"></i></span>
                                <span class="text">Create Link</span>
                            </button>
                        </div>
                        
                        <div class="table-container">
                            <table id="links-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Games</th>
                                        <th>Session Name</th>
                                        <th>Duration</th>
                                        <th>Concurrent Limit</th>
                                        <th>Active Sessions</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Expires</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="9" class="loading">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Bin Page -->
                    <div id="bin" class="page">
                        <div class="section-header">
                            <h3>Archived Links</h3>
                            <p>This section contains expired and revoked links that have been moved to the bin.</p>
                        </div>
                        
                        <div class="table-container">
                            <table id="bin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Games</th>
                                        <th>Session Name</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Expires</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" class="loading">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Games Page -->
                    <div id="games" class="page">
                        <div class="section-header">
                            <h3>Games</h3>
                            <button id="edit-games-button" class="button primary">
                                <span class="icon"><i class="fas fa-edit"></i></span>
                                <span class="text">Edit Games</span>
                            </button>
                        </div>
                        
                        <div id="games-grid" class="games-grid">
                            <div class="loading">Loading...</div>
                        </div>
                    </div>
                    
                    <!-- Settings Page -->
                    <div id="settings" class="page">
                        <div class="section-header">
                            <h3>System Settings</h3>
                        </div>
                        
                        <form class="settings-form">
                            <div class="form-group">
                                <label for="system-name">System Name</label>
                                <input type="text" id="system-name" name="system-name" value="<?php echo htmlspecialchars($settings['system']['name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="admin-password">Change Admin Password</label>
                                <input type="password" id="admin-password" name="admin-password" placeholder="Enter new password">
                                <small>Leave blank to keep current password</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="session-check-interval">Session Check Interval (seconds)</label>
                                <input type="number" id="session-check-interval" name="session-check-interval" value="<?php echo htmlspecialchars($settings['system']['session_check_interval']); ?>" min="10">
                                <small>How often to check if a session is still valid</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="default-session-duration">Default Session Duration (seconds)</label>
                                <input type="number" id="default-session-duration" name="default-session-duration" value="<?php echo htmlspecialchars($settings['system']['default_session_duration']); ?>" min="60">
                                <small>Default duration for new access links (86400 = 1 day)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="default-concurrent-limit">Default Concurrent Limit</label>
                                <input type="number" id="default-concurrent-limit" name="default-concurrent-limit" value="<?php echo htmlspecialchars($settings['system']['default_concurrent_limit']); ?>" min="1">
                                <small>Default number of concurrent devices allowed</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="token-secret">Token Secret</label>
                                <input type="text" id="token-secret" name="token-secret" value="<?php echo htmlspecialchars($settings['security']['token_secret']); ?>">
                                <small>Secret key used for JWT token generation</small>
                            </div>
                            
                            <button type="button" id="save-settings-button" class="button primary">
                                <span class="icon"><i class="fas fa-save"></i></span>
                                <span class="text">Save Settings</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Create Link Modal -->
            <div id="create-link-modal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Create Access Link</h3>
                        <button class="close-button">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Select Games</label>
                            <div id="game-checkboxes" class="checkbox-group">
                                <div class="loading">Loading games...</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Duration</label>
                            <div class="duration-inputs">
                                <div class="duration-input">
                                    <input type="number" id="duration-days" min="0" value="1">
                                    <label>Days</label>
                                </div>
                                <div class="duration-input">
                                    <input type="number" id="duration-hours" min="0" max="23" value="0">
                                    <label>Hours</label>
                                </div>
                                <div class="duration-input">
                                    <input type="number" id="duration-minutes" min="0" max="59" value="0">
                                    <label>Minutes</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="link-concurrent-limit">Concurrent Device Limit</label>
                            <input type="number" id="link-concurrent-limit" min="1" value="1">
                            <small>Maximum number of devices that can use this link simultaneously</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="link-name">Session Name (Required)</label>
                            <input type="text" id="link-name" placeholder="Enter a name for this session" required>
                            <small>This name will be displayed to users when they access the link</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="link-note">Note (Optional)</label>
                            <textarea id="link-note" rows="3"></textarea>
                            <small>Add a note for your reference (not visible to users)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="button secondary close-button">Cancel</button>
                        <button id="create-link-submit" class="button primary">Create Link</button>
                    </div>
                </div>
            </div>
            
            <!-- Link Details Modal -->
            <div id="link-details-modal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Link Details</h3>
                        <button class="close-button">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="link-details">
                            <div class="loading">Loading link details...</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button id="copy-link-button" class="button secondary">
                            <span class="icon"><i class="fas fa-copy"></i></span>
                            <span class="text">Copy Link</span>
                        </button>
                        <button id="revoke-link-button" class="button danger">
                            <span class="icon"><i class="fas fa-ban"></i></span>
                            <span class="text">Revoke Link</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Edit Games Modal -->
            <div id="edit-games-modal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Edit Games</h3>
                        <button class="close-button">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="games-editor">
                            <div class="loading">Loading games...</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="button secondary close-button">Cancel</button>
                        <button id="save-games-button" class="button primary">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
        
        <script src="assets/js/admin.js"></script>
    <?php else: ?>
        <!-- Login Page -->
        <div class="login-container">
            <form class="login-form" method="post" action="">
                <h1><?php echo htmlspecialchars($systemName); ?></h1>
                <h2>Admin Login</h2>
                
                <?php if (!empty($loginError)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($loginError); ?>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <input type="hidden" name="action" value="login">
                <button type="submit" class="button primary">Login</button>
            </form>
        </div>
    <?php endif; ?>
</body>
</html>
