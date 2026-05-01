<?php
/**
 * Database Configuration
 * NexusChat - Professional Chat Application
 * 
 * Configure these settings for your InfinityFree hosting
 * 
 * @package NexusChat
 * @version 1.0
 * @author Senior Software Engineer
 */

// Prevent direct access
defined('NEXUSCHAT') or die('Direct access not allowed');

return [
    /**
     * Database Connection Settings
     * Update these with your InfinityFree database credentials
     */
    'host' => 'sql123.infinityfree.com',      // Your MySQL host
    'database' => 'epiz_12345678_nexuschat',  // Your database name
    'username' => 'epiz_12345678',            // Your database username
    'password' => 'your_secure_password',     // Your database password
    'charset' => 'utf8mb4',                   // Character set
    'collation' => 'utf8mb4_unicode_ci',      // Collation
    
    /**
     * PDO Options
     * Security and performance configurations
     */
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,           // Throw exceptions on errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      // Fetch as associative array
        PDO::ATTR_EMULATE_PREPARES => false,                    // Use native prepared statements
        PDO::ATTR_PERSISTENT => false,                          // Disable persistent connections
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ],
    
    /**
     * Connection Pool Settings
     */
    'pool' => [
        'max_connections' => 10,
        'timeout' => 5,  // Connection timeout in seconds
        'retry_attempts' => 3
    ],
    
    /**
     * Backup Settings
     */
    'backup' => [
        'enabled' => true,
        'frequency' => 'daily',  // daily, weekly, monthly
        'retention_days' => 30,
        'path' => __DIR__ . '/../backups/'
    ]
];
