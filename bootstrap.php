<?php
/**
 * Bootstrap File
 * NexusChat - Professional Chat Application
 * 
 * Main initialization and autoloader
 * 
 * @package NexusChat
 * @version 1.0
 * @author Senior Software Engineer
 */

// Define application constant
define('NEXUSCHAT', true);

// Define root path
define('ROOT_PATH', __DIR__);
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('CONFIG_PATH', ROOT_PATH . '/config');

// Define environment
define('APP_ENV', 'production'); // Change to 'development' for debugging
define('DEBUG', APP_ENV === 'development');

// Error reporting based on environment
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set default timezone
date_default_timezone_set('UTC');

// Autoloader function
spl_autoload_register(function ($class) {
    $file = INCLUDES_PATH . '/' . strtolower($class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load core files
require_once INCLUDES_PATH . '/db.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/utils.php';

// Start output buffering
ob_start();

// Set security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Check maintenance mode
$config = require CONFIG_PATH . '/app.php';
if ($config['maintenance']['enabled']) {
    $allowedIps = $config['maintenance']['allowed_ips'];
    $userIp = $_SERVER['REMOTE_ADDR'];
    
    if (!in_array($userIp, $allowedIps)) {
        http_response_code(503);
        echo file_get_contents(ROOT_PATH . '/public/maintenance.html');
        exit;
    }
}

/**
 * Helper function to get configuration value
 * 
 * @param string $key Configuration key (dot notation)
 * @param mixed $default Default value
 * @return mixed Configuration value
 */
function config(string $key, $default = null) {
    static $configs = [];
    
    $parts = explode('.', $key);
    $file = array_shift($parts);
    
    if (!isset($configs[$file])) {
        $configFile = CONFIG_PATH . "/{$file}.php";
        if (file_exists($configFile)) {
            $configs[$file] = require $configFile;
        } else {
            return $default;
        }
    }
    
    $value = $configs[$file];
    foreach ($parts as $part) {
        if (is_array($value) && isset($value[$part])) {
            $value = $value[$part];
        } else {
            return $default;
        }
    }
    
    return $value;
}

/**
 * Helper function to redirect
 * 
 * @param string $url URL to redirect to
 */
function redirect(string $url): void {
    header("Location: {$url}");
    exit;
}

/**
 * Helper function to return JSON response
 * 
 * @param mixed $data Response data
 * @param int $statusCode HTTP status code
 */
function jsonResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Helper function to check AJAX request
 * 
 * @return bool True if AJAX request
 */
function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Helper function to get CSRF token
 * 
 * @return string CSRF token
 */
function csrfToken(): string {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Helper function to verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if valid
 */
function verifyCsrf(string $token): bool {
    return isset($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], $token);
}

/**
 * Helper function to sanitize output
 * 
 * @param string $string String to sanitize
 * @return string Sanitized string
 */
function e(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Helper function to get asset URL
 * 
 * @param string $path Asset path
 * @return string Full asset URL
 */
function asset(string $path): string {
    $baseUrl = config('app.url');
    $path = ltrim($path, '/');
    return "{$baseUrl}/assets/{$path}";
}

/**
 * Helper function to log message
 * 
 * @param string $level Log level
 * @param string $message Log message
 * @param array $context Additional context
 */
function logMessage(string $level, string $message, array $context = []): void {
    $logConfig = config('app.logging');
    
    if (!$logConfig['enabled']) {
        return;
    }
    
    $logPath = $logConfig['path'];
    
    if (!is_dir($logPath)) {
        mkdir($logPath, 0755, true);
    }
    
    $logFile = $logPath . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    $userId = Auth::getInstance()?->getUserId() ?? 'guest';
    
    $logEntry = "[{$timestamp}] [{$level}] [IP:{$ip}] [User:{$userId}] {$message}";
    
    if (!empty($context)) {
        $logEntry .= ' ' . json_encode($context);
    }
    
    file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND);
}
