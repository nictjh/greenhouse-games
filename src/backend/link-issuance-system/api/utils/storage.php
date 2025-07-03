<?php
/**
 * Storage Utility Functions
 * 
 * Handles reading and writing to JSON data files
 */

// Set default timezone to Singapore
date_default_timezone_set('Asia/Singapore');

// Prevent direct access
if (!defined('ACCESS_CONTROL')) {
    http_response_code(403);
    exit('Direct access forbidden');
}

/**
 * Read data from a JSON file
 * 
 * @param string $filename The name of the file to read
 * @return array The data from the file
 */
function readData($filename) {
    $settings = getSettings();
    $dataDir = $settings['system']['data_directory'];
    $filePath = __DIR__ . '/../../' . $dataDir . '/' . $filename;
    
    if (!file_exists($filePath)) {
        return [];
    }
    
    $fileContent = file_get_contents($filePath);
    if ($fileContent === false) {
        return [];
    }
    
    $data = json_decode($fileContent, true);
    if ($data === null) {
        return [];
    }
    
    return $data;
}

/**
 * Write data to a JSON file
 * 
 * @param string $filename The name of the file to write to
 * @param array $data The data to write
 * @return bool True if successful, false otherwise
 */
function writeData($filename, $data) {
    $settings = getSettings();
    $dataDir = $settings['system']['data_directory'];
    $filePath = __DIR__ . '/../../' . $dataDir . '/' . $filename;
    
    // Create directory if it doesn't exist
    $dirPath = dirname($filePath);
    if (!is_dir($dirPath)) {
        mkdir($dirPath, 0755, true);
    }
    
    // Acquire a lock for writing
    $fp = fopen($filePath, 'c+');
    if (!$fp) {
        return false;
    }
    
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }
    
    // Truncate the file
    ftruncate($fp, 0);
    
    // Write the data
    $jsonData = json_encode($data, JSON_PRETTY_PRINT);
    $result = fwrite($fp, $jsonData);
    
    // Release the lock
    flock($fp, LOCK_UN);
    fclose($fp);
    
    return $result !== false;
}

/**
 * Get the system settings
 * 
 * @return array The system settings
 */
function getSettings() {
    static $settings = null;
    
    if ($settings === null) {
        $settingsPath = __DIR__ . '/../../config/settings.json';
        if (!file_exists($settingsPath)) {
            die('Settings file not found');
        }
        
        $settingsContent = file_get_contents($settingsPath);
        if ($settingsContent === false) {
            die('Could not read settings file');
        }
        
        $settings = json_decode($settingsContent, true);
        if ($settings === null) {
            die('Invalid settings file format');
        }
    }
    
    return $settings;
}

/**
 * Get the games configuration
 * 
 * @return array The games configuration
 */
function getGames() {
    static $games = null;
    
    if ($games === null) {
        $gamesPath = __DIR__ . '/../../config/games.json';
        if (!file_exists($gamesPath)) {
            die('Games configuration file not found');
        }
        
        $gamesContent = file_get_contents($gamesPath);
        if ($gamesContent === false) {
            die('Could not read games configuration file');
        }
        
        $gamesData = json_decode($gamesContent, true);
        if ($gamesData === null) {
            die('Invalid games configuration file format');
        }
        
        $games = $gamesData;
    }
    
    return $games;
}

/**
 * Get all access links
 * 
 * @return array The access links
 */
function getLinks() {
    $data = readData('links.json');
    return isset($data['links']) ? $data['links'] : [];
}

/**
 * Save access links
 * 
 * @param array $links The access links to save
 * @return bool True if successful, false otherwise
 */
function saveLinks($links) {
    return writeData('links.json', ['links' => $links]);
}

/**
 * Get all active sessions
 * 
 * @return array The active sessions
 */
function getSessions() {
    $data = readData('sessions.json');
    return isset($data['sessions']) ? $data['sessions'] : [];
}

/**
 * Save active sessions
 * 
 * @param array $sessions The active sessions to save
 * @return bool True if successful, false otherwise
 */
function saveSessions($sessions) {
    return writeData('sessions.json', ['sessions' => $sessions]);
}
