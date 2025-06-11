<?php
/**
 * Login Diagnostic Script
 * 
 * This script helps diagnose issues with the login process and 403 Forbidden errors
 */

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');

// Start session
session_start();

// Include utility functions if available
$storageFile = __DIR__ . '/api/utils/storage.php';
$securityFile = __DIR__ . '/api/utils/security.php';

$storageExists = file_exists($storageFile);
$securityExists = file_exists($securityFile);

if ($storageExists) {
    require_once $storageFile;
}

if ($securityExists) {
    require_once $securityFile;
}

// Check for admin token cookie
$adminToken = isset($_COOKIE['admin_token']) ? $_COOKIE['admin_token'] : '';
$tokenValid = false;
$tokenPayload = null;

if (!empty($adminToken) && $securityExists && function_exists('validateJWT')) {
    $tokenPayload = validateJWT($adminToken);
    $tokenValid = ($tokenPayload !== false && isset($tokenPayload['role']) && $tokenPayload['role'] === 'admin');
}

// Get settings if available
$settings = null;
if ($storageExists && function_exists('getSettings')) {
    $settings = getSettings();
}

// Check if we should test login
$testLogin = isset($_POST['test_login']) && $_POST['test_login'] === '1';
$loginSuccess = false;
$loginError = '';

if ($testLogin) {
    $password = $_POST['password'] ?? '';
    
    if (!empty($password) && $settings !== null) {
        $storedHash = $settings['system']['admin_password_hash'] ?? '';
        
        if (!empty($storedHash) && password_verify($password, $storedHash)) {
            // Generate admin token
            $payload = [
                'role' => 'admin',
                'name' => 'Administrator'
            ];
            
            $tokenExpiry = $settings['system']['token_expiry'] ?? 3600;
            $token = generateJWT($payload, $tokenExpiry);
            
            // Set cookie
            setcookie('admin_token', $token, [
                'expires' => time() + $tokenExpiry,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            $loginSuccess = true;
        } else {
            $loginError = 'Invalid password or hash not found';
        }
    } else {
        $loginError = 'Password is required';
    }
}

// Check redirect after login
$testRedirect = isset($_GET['test_redirect']) && $_GET['test_redirect'] === '1';
if ($testRedirect) {
    header('Location: admin/');
    exit;
}

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Diagnostic Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2, h3 {
            color: #4a6fa5;
        }
        .section {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .success {
            color: #2e7d32;
            font-weight: bold;
        }
        .error {
            color: #c62828;
            font-weight: bold;
        }
        .warning {
            color: #f57c00;
            font-weight: bold;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-item strong {
            display: inline-block;
            width: 200px;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4a6fa5;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        .button:hover {
            background-color: #3a5a84;
        }
        form {
            margin-top: 20px;
        }
        input[type="password"] {
            padding: 8px;
            width: 250px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Login Diagnostic Tool</h1>
    <p>This tool helps diagnose issues with the login process and 403 Forbidden errors.</p>
    
    <div class="section">
        <h2>System Files Check</h2>
        
        <div class="info-item">
            <strong>Storage Utility:</strong>
            <?php if ($storageExists): ?>
                <span class="success">Found</span>
            <?php else: ?>
                <span class="error">Not Found</span> - Check if api/utils/storage.php exists
            <?php endif; ?>
        </div>
        
        <div class="info-item">
            <strong>Security Utility:</strong>
            <?php if ($securityExists): ?>
                <span class="success">Found</span>
            <?php else: ?>
                <span class="error">Not Found</span> - Check if api/utils/security.php exists
            <?php endif; ?>
        </div>
        
        <div class="info-item">
            <strong>Settings Available:</strong>
            <?php if ($settings !== null): ?>
                <span class="success">Yes</span>
            <?php else: ?>
                <span class="error">No</span> - Check if config/settings.json exists and is readable
            <?php endif; ?>
        </div>
        
        <?php if ($settings !== null): ?>
        <div class="info-item">
            <strong>Admin Password Hash:</strong>
            <?php if (!empty($settings['system']['admin_password_hash'])): ?>
                <span class="success">Set</span>
            <?php else: ?>
                <span class="error">Not Set</span> - Admin password hash is missing
            <?php endif; ?>
        </div>
        
        <div class="info-item">
            <strong>Token Secret:</strong>
            <?php if (!empty($settings['security']['token_secret'])): ?>
                <span class="success">Set</span>
            <?php else: ?>
                <span class="error">Not Set</span> - Token secret is missing
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>Current Session Status</h2>
        
        <div class="info-item">
            <strong>Admin Token Cookie:</strong>
            <?php if (!empty($adminToken)): ?>
                <span class="success">Present</span>
            <?php else: ?>
                <span class="warning">Not Present</span> - No admin_token cookie found
            <?php endif; ?>
        </div>
        
        <div class="info-item">
            <strong>Token Valid:</strong>
            <?php if ($tokenValid): ?>
                <span class="success">Yes</span>
            <?php else: ?>
                <span class="error">No</span> - Token is invalid or expired
            <?php endif; ?>
        </div>
        
        <?php if ($tokenPayload !== null && $tokenPayload !== false): ?>
        <div class="info-item">
            <strong>Token Payload:</strong>
            <pre><?php print_r($tokenPayload); ?></pre>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>Test Login Process</h2>
        
        <?php if ($loginSuccess): ?>
            <div class="info-item">
                <span class="success">Login successful!</span> The admin token cookie has been set.
            </div>
        <?php elseif (!empty($loginError)): ?>
            <div class="info-item">
                <span class="error">Login failed:</span> <?php echo htmlspecialchars($loginError); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="info-item">
                <strong>Admin Password:</strong>
                <input type="password" name="password" placeholder="Enter admin password">
            </div>
            
            <input type="hidden" name="test_login" value="1">
            <button type="submit" class="button">Test Login</button>
        </form>
    </div>
    
    <div class="section">
        <h2>Test Redirect</h2>
        <p>Click the button below to test the redirect to the admin dashboard. This will help diagnose 403 Forbidden errors after login.</p>
        
        <a href="?test_redirect=1" class="button">Test Redirect to Admin</a>
    </div>
    
    <div class="section">
        <h2>Server Information</h2>
        
        <div class="info-item">
            <strong>PHP Version:</strong> <?php echo phpversion(); ?>
        </div>
        
        <div class="info-item">
            <strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>
        </div>
        
        <div class="info-item">
            <strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?>
        </div>
        
        <div class="info-item">
            <strong>Script Path:</strong> <?php echo __FILE__; ?>
        </div>
        
        <div class="info-item">
            <strong>Cookie Settings:</strong>
            <pre>
Path: /
Secure: <?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'Yes' : 'No'; ?>
HttpOnly: Yes
SameSite: Lax
            </pre>
        </div>
    </div>
    
    <div class="section">
        <h2>Recommendations</h2>
        
        <ul>
            <?php if (!$storageExists || !$securityExists): ?>
                <li>Make sure all utility files are present in the api/utils/ directory.</li>
            <?php endif; ?>
            
            <?php if ($settings === null): ?>
                <li>Check if config/settings.json exists and is readable.</li>
            <?php endif; ?>
            
            <?php if ($settings !== null && empty($settings['system']['admin_password_hash'])): ?>
                <li>Set a valid admin password hash in config/settings.json.</li>
            <?php endif; ?>
            
            <?php if ($settings !== null && empty($settings['security']['token_secret'])): ?>
                <li>Set a token secret in config/settings.json.</li>
            <?php endif; ?>
            
            <?php if (!$tokenValid && !empty($adminToken)): ?>
                <li>Your admin token is invalid. Try logging out and logging in again.</li>
            <?php endif; ?>
            
            <li>Check the .htaccess file to ensure it's not blocking access to the admin directory.</li>
            <li>Make sure file permissions are set correctly (755 for directories, 644 for files).</li>
            <li>Clear your browser cache and cookies, then try logging in again.</li>
        </ul>
    </div>
    
    <div class="section">
        <h2>Next Steps</h2>
        
        <p>After addressing any issues found by this diagnostic tool:</p>
        
        <ol>
            <li>Try accessing the <a href="admin/">admin dashboard</a> directly.</li>
            <li>Run the <a href="check_permissions_web.php">permission check tool</a> to verify file permissions.</li>
            <li>Check your browser's developer tools (F12) for any JavaScript errors.</li>
        </ol>
    </div>
</body>
</html>
