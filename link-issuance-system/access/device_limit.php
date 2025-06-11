<?php
/**
 * Device Limit Page
 * 
 * Shown when the maximum number of concurrent devices has been reached
 */

// Define ACCESS_CONTROL constant if not already defined
if (!defined('ACCESS_CONTROL')) {
    define('ACCESS_CONTROL', true);
}

// Get system name from settings if available
if (!isset($systemName)) {
    // Include utility functions if not already included
    if (!function_exists('getSettings')) {
        require_once __DIR__ . '/../api/utils/storage.php';
    }
    $settings = getSettings();
    $systemName = $settings['system']['name'] ?? 'Link Issuance System';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($systemName); ?> - Device Limit Reached</title>
    <link rel="stylesheet" href="new-styles.css">
    <style>
        /* Override copyright color to white */
        .copyright {
            color: white !important;
        }
        
        /* Header styling */
        .stars-header {
            position: relative;
            margin-bottom: -30px; /* Create overhang effect */
            z-index: 2;
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
        }
        
        .contact-button:hover {
            background-color: #1b5e20;
        }
        
        .contact-section {
            background-color: #4B3372;
            color: white;
            padding: 20px 20px;
            text-align: center;
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
    </style>
</head>
<body>
    <!-- Header with stars and logo -->
    <div class="stars-header">
        <img src="../assets/img/JalanJourneyLogo.svg" alt="Jalan Journey" class="company-logo">
    </div>
    
    <!-- Main content area -->
    <div class="main-content">
        <!-- Error content -->
        <div class="error-container">
            <div class="error-content">
                <div class="error-text">
                    <div class="error-title">Uh oh!</div>
                    <div class="error-subtitle">Device Limit Reached</div>
                    <div class="error-code">Error code: 429</div>
                    <div class="error-message">
                        You have reached the maximum number of devices allowed for this access link. Please disconnect another device or contact the administrator for assistance.
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
    </div>
    
    <!-- Contact section with copyright -->
    <div class="contact-section">
        <div class="contact-title">Need any assistance?</div>
        <div class="contact-message">Feel free to reach out to us or your Event Organiser!</div>
        <a href="mailto:info@jalanjourney.com" class="contact-button">Contact Us</a>
        <p class="copyright">&copy; 2025 Jalan Journey. All Rights Reserved.</p>
    </div>
</body>
</html>
