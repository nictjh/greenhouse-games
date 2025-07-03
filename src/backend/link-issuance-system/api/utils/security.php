<?php
/**
 * Security Utility Functions
 * 
 * Handles token generation, validation, and other security-related operations
 */

// Prevent direct access
if (!defined('ACCESS_CONTROL')) {
    http_response_code(403);
    exit('Direct access forbidden');
}

/**
 * Generate a secure random token
 * 
 * @param int $length The length of the token
 * @return string The generated token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Generate a device fingerprint from request data
 * 
 * @return string The device fingerprint
 */
function generateDeviceFingerprint() {
    // Basic server data
    $data = [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
        'accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
    ];
    
    // Add more specific browser data if available
    if (isset($_SERVER['HTTP_SEC_CH_UA'])) {
        $data['browser_brands'] = $_SERVER['HTTP_SEC_CH_UA'];
    }
    
    if (isset($_SERVER['HTTP_SEC_CH_UA_PLATFORM'])) {
        $data['platform'] = $_SERVER['HTTP_SEC_CH_UA_PLATFORM'];
    }
    
    // Add X-Forwarded-For if available (for clients behind proxies)
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $data['forwarded_ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    
    // Add a salt from the settings
    $settings = getSettings();
    $salt = $settings['security']['token_secret'] ?? 'default_salt';
    $data['salt'] = $salt;
    
    // Add a timestamp component that changes only every 24 hours
    // This ensures the fingerprint stays consistent for a day
    // but will eventually change to prevent permanent tracking
    $data['day_component'] = floor(time() / 86400);
    
    // Log the fingerprint data for debugging
    error_log("Device fingerprint data: " . json_encode($data));
    
    // Generate the fingerprint
    $fingerprint = hash('sha256', json_encode($data));
    error_log("Generated fingerprint: " . $fingerprint);
    
    return $fingerprint;
}

/**
 * Generate a JWT token
 * 
 * @param array $payload The payload to encode
 * @param int $expiry The expiry time in seconds
 * @return string The JWT token
 */
function generateJWT($payload, $expiry = 3600) {
    $settings = getSettings();
    $secret = $settings['security']['token_secret'];
    
    // Header
    $header = [
        'typ' => 'JWT',
        'alg' => 'HS256'
    ];
    
    // Set expiry time
    $payload['exp'] = time() + $expiry;
    $payload['iat'] = time();
    
    // Encode header and payload
    $headerEncoded = base64UrlEncode(json_encode($header));
    $payloadEncoded = base64UrlEncode(json_encode($payload));
    
    // Create signature
    $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
    $signatureEncoded = base64UrlEncode($signature);
    
    // Create JWT
    return "$headerEncoded.$payloadEncoded.$signatureEncoded";
}

/**
 * Validate a JWT token
 * 
 * @param string $token The JWT token to validate
 * @return array|false The payload if valid, false otherwise
 */
function validateJWT($token) {
    $settings = getSettings();
    $secret = $settings['security']['token_secret'];
    
    // Split token
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    
    list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
    
    // Verify signature
    $signature = base64UrlDecode($signatureEncoded);
    $expectedSignature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
    
    if (!hash_equals($signature, $expectedSignature)) {
        return false;
    }
    
    // Decode payload
    $payload = json_decode(base64UrlDecode($payloadEncoded), true);
    if ($payload === null) {
        return false;
    }
    
    // Check expiry
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}

/**
 * Base64Url encode
 * 
 * @param string $data The data to encode
 * @return string The encoded data
 */
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64Url decode
 * 
 * @param string $data The data to decode
 * @return string The decoded data
 */
function base64UrlDecode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * Validate CSRF token
 * 
 * @param string $token The CSRF token to validate
 * @return bool True if valid, false otherwise
 */
function validateCSRF($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF token
 * 
 * @return string The CSRF token
 */
function generateCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Check rate limit
 * 
 * @param string $key The rate limit key
 * @return bool True if within limit, false otherwise
 */
function checkRateLimit($key) {
    $settings = getSettings();
    $rateLimit = $settings['security']['rate_limit'];
    
    if (!$rateLimit['enabled']) {
        return true;
    }
    
    $maxRequests = $rateLimit['max_requests'];
    $timeWindow = $rateLimit['time_window'];
    
    $rateLimitFile = 'rate_limits.json';
    $rateLimits = readData($rateLimitFile);
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $limitKey = $ip . '_' . $key;
    
    $now = time();
    
    // Clean up old rate limits
    foreach ($rateLimits as $k => $limit) {
        if ($limit['expires'] < $now) {
            unset($rateLimits[$k]);
        }
    }
    
    // Check if rate limit exists
    if (isset($rateLimits[$limitKey])) {
        $limit = $rateLimits[$limitKey];
        
        // Check if expired
        if ($limit['expires'] < $now) {
            $rateLimits[$limitKey] = [
                'count' => 1,
                'expires' => $now + $timeWindow
            ];
            writeData($rateLimitFile, $rateLimits);
            return true;
        }
        
        // Check if over limit
        if ($limit['count'] >= $maxRequests) {
            return false;
        }
        
        // Increment count
        $rateLimits[$limitKey]['count']++;
        writeData($rateLimitFile, $rateLimits);
        return true;
    }
    
    // Create new rate limit
    $rateLimits[$limitKey] = [
        'count' => 1,
        'expires' => $now + $timeWindow
    ];
    writeData($rateLimitFile, $rateLimits);
    return true;
}

/**
 * Set security headers
 */
function setSecurityHeaders() {
    $settings = getSettings();
    $frameAncestors = $settings['security']['frame_ancestors'];
    
    // Content Security Policy
    header("Content-Security-Policy: frame-ancestors $frameAncestors;");
    
    // X-Frame-Options
    if ($frameAncestors === 'self') {
        header('X-Frame-Options: SAMEORIGIN');
    } elseif ($frameAncestors === 'none') {
        header('X-Frame-Options: DENY');
    }
    
    // Other security headers
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/**
 * Sanitize input
 * 
 * @param string $input The input to sanitize
 * @return string The sanitized input
 */
function sanitizeInput($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Get token from request
 * 
 * Extracts the token from the Authorization header or from cookies
 * 
 * @return string|null The token or null if not found
 */
function getTokenFromRequest() {
    // Check Authorization header first
    $headers = getallheaders();
    
    // Log headers for debugging
    error_log("Headers: " . print_r($headers, true));
    
    // Check for Authorization header (case-insensitive)
    $authHeader = null;
    foreach ($headers as $name => $value) {
        if (strtolower($name) === 'authorization') {
            $authHeader = $value;
            break;
        }
    }
    
    // Extract token from Authorization header
    if ($authHeader) {
        // Check if it's a Bearer token
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            error_log("Token found in Authorization header: " . substr($matches[1], 0, 10) . '...');
            return $matches[1];
        }
    }
    
    // Check for token in cookies
    if (isset($_COOKIE['admin_token'])) {
        error_log("Token found in admin_token cookie: " . substr($_COOKIE['admin_token'], 0, 10) . '...');
        return $_COOKIE['admin_token'];
    }
    
    // Check for token in session token cookie
    if (isset($_COOKIE['session_token'])) {
        error_log("Token found in session_token cookie: " . substr($_COOKIE['session_token'], 0, 10) . '...');
        return $_COOKIE['session_token'];
    }
    
    // No token found
    error_log("No token found in request");
    return null;
}

/**
 * Authenticate admin request
 * 
 * Checks if the request has a valid admin token
 * 
 * @return bool True if authenticated, false otherwise
 */
function authenticateAdminRequest() {
    $token = getTokenFromRequest();
    
    if (!$token) {
        error_log("No token found for admin authentication");
        return false;
    }
    
    $payload = validateJWT($token);
    
    if (!$payload) {
        error_log("Invalid token for admin authentication");
        return false;
    }
    
    // Check if it's an admin token
    if (!isset($payload['role']) || $payload['role'] !== 'admin') {
        error_log("Token is not an admin token");
        return false;
    }
    
    error_log("Admin authentication successful");
    return true;
}
