<?php
/**
 * Application Configuration
 * NexusChat - Professional Chat Application
 * 
 * Main application settings and constants
 * 
 * @package NexusChat
 * @version 1.0
 * @author Senior Software Engineer
 */

// Prevent direct access
defined('NEXUSCHAT') or die('Direct access not allowed');

return [
    /**
     * Application Info
     */
    'name' => 'NexusChat',
    'version' => '1.0.0',
    'environment' => 'production',  // development, staging, production
    'debug' => false,               // Set to true for development only
    
    /**
     * URL Configuration
     * Update for your domain: Nexuss-Chat.gt.tc
     */
    'url' => 'https://nexuss-chat.gt.tc',
    'base_path' => '/',
    'asset_url' => 'https://nexuss-chat.gt.tc/assets',
    
    /**
     * Timezone Settings
     */
    'timezone' => 'UTC',
    'date_format' => 'Y-m-d',
    'datetime_format' => 'Y-m-d H:i:s',
    'display_timezone' => 'UTC',
    
    /**
     * Session Configuration
     */
    'session' => [
        'name' => 'NEXUSCHAT_SESSION',
        'lifetime' => 1800,              // 30 minutes in seconds
        'expire_on_close' => false,
        'encrypt' => false,
        'files' => __DIR__ . '/../storage/sessions/',
        'connection' => null,
        'table' => 'sessions',
        'store_failure_at' => null,
        'lottery' => [2, 100],           // [chance, denominator]
        'cookie' => [
            'path' => '/',
            'domain' => 'nexuss-chat.gt.tc',
            'secure' => true,            // HTTPS only
            'http_only' => true,         // No JavaScript access
            'same_site' => 'Strict'      // CSRF protection
        ]
    ],
    
    /**
     * Security Settings
     */
    'security' => [
        'password_min_length' => 8,
        'password_require_uppercase' => true,
        'password_require_lowercase' => true,
        'password_require_number' => true,
        'password_require_special' => true,
        'max_login_attempts' => 5,
        'lockout_duration' => 900,       // 15 minutes
        'csrf_token_name' => '_csrf_token',
        'csrf_token_lifetime' => 3600,   // 1 hour
        'bcrypt_cost' => 12,
        'allowed_file_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'video/mp4',
            'audio/mpeg'
        ],
        'max_file_size' => 52428800,     // 50MB in bytes
        'max_message_length' => 4000,
        'rate_limit' => [
            'enabled' => true,
            'requests_per_minute' => 60,
            'api_requests_per_minute' => 30
        ]
    ],
    
    /**
     * Upload Settings
     */
    'upload' => [
        'base_path' => __DIR__ . '/../uploads/',
        'max_file_size' => 52428800,     // 50MB
        'allowed_extensions' => [
            'jpg', 'jpeg', 'png', 'gif', 'webp',
            'pdf', 'doc', 'docx',
            'mp4', 'avi', 'mov',
            'mp3', 'wav', 'ogg'
        ],
        'image_quality' => 85,
        'thumbnail_sizes' => [
            'small' => [150, 150],
            'medium' => [300, 300],
            'large' => [600, 600]
        ],
        'virus_scan' => false,           // Enable if ClamAV available
        'auto_compress' => true
    ],
    
    /**
     * Pagination Settings
     */
    'pagination' => [
        'per_page' => 20,
        'max_per_page' => 100,
        'message_batch_size' => 50
    ],
    
    /**
     * Cache Settings
     */
    'cache' => [
        'driver' => 'file',              // file, array, apcu
        'prefix' => 'nexuschat_',
        'path' => __DIR__ . '/../storage/cache/',
        'default_ttl' => 300             // 5 minutes
    ],
    
    /**
     * Logging Settings
     */
    'logging' => [
        'enabled' => true,
        'path' => __DIR__ . '/../logs/',
        'level' => 'error',              // debug, info, warning, error, critical
        'max_files' => 30,               // Keep last 30 days
        'rotate_daily' => true
    ],
    
    /**
     * Email Settings (Configure for password reset)
     * InfinityFree: Use external SMTP service
     */
    'mail' => [
        'driver' => 'smtp',
        'host' => 'smtp.gmail.com',      // Or your SMTP provider
        'port' => 587,
        'encryption' => 'tls',
        'username' => '',                // Your email
        'password' => '',                // Your app password
        'from' => [
            'address' => 'noreply@nexuss-chat.gt.tc',
            'name' => 'NexusChat'
        ],
        'reply_to' => [
            'address' => 'support@nexuss-chat.gt.tc',
            'name' => 'NexusChat Support'
        ]
    ],
    
    /**
     * Feature Flags
     */
    'features' => [
        'registration_enabled' => true,
        'email_verification_required' => false,
        'file_upload_enabled' => true,
        'group_chat_enabled' => true,
        'message_edit_enabled' => true,
        'message_delete_enabled' => true,
        'typing_indicator_enabled' => true,
        'read_receipts_enabled' => true,
        'online_status_enabled' => true,
        'dark_mode_enabled' => true,
        'emoji_picker_enabled' => true,
        'markdown_enabled' => true,
        'notifications_enabled' => true
    ],
    
    /**
     * API Settings
     */
    'api' => [
        'prefix' => '/api',
        'version' => 'v1',
        'rate_limit' => 100,             // Requests per minute
        'timeout' => 30,                 // Request timeout in seconds
        'cors' => [
            'enabled' => false,
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With']
        ]
    ],
    
    /**
     * Maintenance Mode
     */
    'maintenance' => [
        'enabled' => false,
        'message' => 'NexusChat is currently under maintenance. Please check back soon.',
        'retry_after' => 3600,           // Seconds until retry
        'allowed_ips' => []              // IPs that can access during maintenance
    ]
];
