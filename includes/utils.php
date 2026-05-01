<?php
/**
 * Utility Functions
 * NexusChat - Professional Chat Application
 * 
 * Common helper functions and utilities
 * 
 * @package NexusChat
 * @version 1.0
 * @author Senior Software Engineer
 */

// Prevent direct access
defined('NEXUSCHAT') or die('Direct access not allowed');

/**
 * Generate a unique token
 * 
 * @param int $length Token length
 * @return string Random token
 */
function generateToken(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

/**
 * Generate UUID v4
 * 
 * @return string UUID v4
 */
function generateUuid(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Sanitize input data
 * 
 * @param mixed $data Data to sanitize
 * @return mixed Sanitized data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim(stripslashes($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 * 
 * @param string $email Email to validate
 * @return bool True if valid
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate username format
 * 
 * @param string $username Username to validate
 * @return bool True if valid
 */
function isValidUsername(string $username): bool {
    return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
}

/**
 * Get user IP address
 * 
 * @return string IP address
 */
function getUserIp(): string {
    $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = explode(',', $_SERVER[$key])[0];
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return '0.0.0.0';
}

/**
 * Get user agent
 * 
 * @return string User agent string
 */
function getUserAgent(): string {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

/**
 * Format file size
 * 
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function formatFileSize(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Format datetime for display
 * 
 * @param string $datetime DateTime string
 * @return string Formatted datetime
 */
function formatDateTime(string $datetime): string {
    $timestamp = strtotime($datetime);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return "{$mins} minute" . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "{$hours} hour" . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "{$days} day" . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y g:i A', $timestamp);
    }
}

/**
 * Check if request is over HTTPS
 * 
 * @return bool True if HTTPS
 */
function isHttps(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
           ($_SERVER['SERVER_PORT'] == 443) ||
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}

/**
 * Get base URL
 * 
 * @return string Base URL
 */
function getBaseUrl(): string {
    $protocol = isHttps() ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname($_SERVER['SCRIPT_NAME']);
    return "{$protocol}://{$host}{$path}";
}

/**
 * Create thumbnail from image
 * 
 * @param string $source Source image path
 * @param string $destination Destination path
 * @param int $width Thumbnail width
 * @param int $height Thumbnail height
 * @return bool Success status
 */
function createThumbnail(string $source, string $destination, int $width = 150, int $height = 150): bool {
    $imageInfo = getimagesize($source);
    if (!$imageInfo) {
        return false;
    }
    
    $imageType = $imageInfo[2];
    $sourceWidth = $imageInfo[0];
    $sourceHeight = $imageInfo[1];
    
    // Calculate aspect ratio
    $ratio = min($width / $sourceWidth, $height / $sourceHeight);
    $newWidth = (int)($sourceWidth * $ratio);
    $newHeight = (int)($sourceHeight * $ratio);
    
    // Create new image
    $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
    
    // Load source image based on type
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($source);
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($source);
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    if (!$sourceImage) {
        return false;
    }
    
    // Resize
    imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
    
    // Save thumbnail
    $result = imagejpeg($thumbnail, $destination, 85);
    
    // Cleanup
    imagedestroy($sourceImage);
    imagedestroy($thumbnail);
    
    return $result !== false;
}

/**
 * Get MIME type from file
 * 
 * @param string $filePath File path
 * @return string|false MIME type or false
 */
function getMimeType(string $filePath): string|false {
    if (function_exists('finfo_file')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        return $mimeType;
    }
    
    return mime_content_type($filePath);
}

/**
 * Check if file upload was successful
 * 
 * @param array $file File array from $_FILES
 * @return array Result with success status and message
 */
function checkFileUpload(array $file): array {
    if (!isset($file['error']) || !is_int($file['error'])) {
        return ['success' => false, 'error' => 'Unknown upload error'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server limit',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by extension'
        ];
        return ['success' => false, 'error' => $errors[$file['error']] ?? 'Unknown error'];
    }
    
    return ['success' => true];
}

/**
 * Rate limiting check
 * 
 * @param string $identifier Rate limit identifier (IP, user ID, etc.)
 * @param int $maxRequests Maximum requests allowed
 * @param int $timeWindow Time window in seconds
 * @return bool True if within limit
 */
function checkRateLimit(string $identifier, int $maxRequests = 60, int $timeWindow = 60): bool {
    static $requests = [];
    
    $key = $identifier . '_' . floor(time() / $timeWindow);
    
    if (!isset($requests[$key])) {
        $requests[$key] = 0;
    }
    
    $requests[$key]++;
    
    return $requests[$key] <= $maxRequests;
}

/**
 * Escape output for HTML
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function e(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Parse markdown-like syntax to HTML (basic)
 * 
 * @param string $text Text to parse
 * @return string Parsed HTML
 */
function parseMarkdown(string $text): string {
    // Bold
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    // Italic
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
    // Code
    $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);
    // Links
    $text = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $text);
    // Line breaks
    $text = nl2br($text);
    
    return $text;
}

/**
 * Get relative time ago
 * 
 * @param string $datetime DateTime string
 * @return string Relative time
 */
function timeAgo(string $datetime): string {
    return formatDateTime($datetime);
}

/**
 * Truncate text
 * 
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix to add
 * @return string Truncated text
 */
function truncate(string $text, int $length = 100, string $suffix = '...'): string {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Generate hash for verification
 * 
 * @param string $data Data to hash
 * @param string $salt Salt value
 * @return string Hash
 */
function generateHash(string $data, string $salt = ''): string {
    return hash('sha256', $data . $salt . config('app.name', ''));
}

/**
 * Verify hash
 * 
 * @param string $data Original data
 * @param string $hash Hash to verify
 * @param string $salt Salt value
 * @return bool True if valid
 */
function verifyHash(string $data, string $hash, string $salt = ''): bool {
    return generateHash($data, $salt) === $hash;
}

/**
 * Clean filename for safe storage
 * 
 * @param string $filename Original filename
 * @return string Cleaned filename
 */
function cleanFilename(string $filename): string {
    // Remove special characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    // Remove multiple underscores
    $filename = preg_replace('/_+/', '_', $filename);
    // Limit length
    return substr($filename, 0, 255);
}

/**
 * Check if string contains emoji
 * 
 * @param string $string String to check
 * @return bool True if contains emoji
 */
function containsEmoji(string $string): bool {
    return preg_match('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $string) === 1;
}

/**
 * Extract emoji from string
 * 
 * @param string $string String to extract from
 * @return array Array of emoji
 */
function extractEmoji(string $string): array {
    preg_match_all('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $string, $matches);
    return $matches[0] ?? [];
}
