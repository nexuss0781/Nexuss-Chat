<?php
/**
 * Authentication Module
 * NexusChat - Professional Chat Application
 * 
 * Complete authentication system with registration, login, session management
 * 
 * @package NexusChat
 * @version 1.0
 * @author Senior Software Engineer
 */

// Prevent direct access
defined('NEXUSCHAT') or die('Direct access not allowed');

class Auth {
    /**
     * @var Database Database instance
     */
    private Database $db;
    
    /**
     * @var array Configuration
     */
    private array $config;
    
    /**
     * @var int|null Current user ID
     */
    private ?int $userId = null;
    
    /**
     * @var array|null Current user data
     */
    private ?array $userData = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../config/app.php';
        $this->initializeSession();
    }
    
    /**
     * Initialize session and check for logged-in user
     */
    private function initializeSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $this->configureSession();
            session_start();
        }
        
        // Check if user is logged in via session
        if (isset($_SESSION['user_id'])) {
            $this->userId = $_SESSION['user_id'];
            $this->userData = $this->getUserById($this->userId);
            
            // Update last activity
            $this->updateLastActivity();
        }
        // Check for remember me cookie
        elseif (isset($_COOKIE['remember_token'])) {
            $this->authenticateViaRememberToken();
        }
    }
    
    /**
     * Configure secure session settings
     */
    private function configureSession(): void {
        $sessionConfig = $this->config['session'];
        
        session_name($sessionConfig['name']);
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $sessionConfig['cookie']['secure'] ? '1' : '0');
        ini_set('session.cookie_samesite', $sessionConfig['cookie']['same_site']);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_maxlifetime', $sessionConfig['lifetime']);
        ini_set('session.cookie_lifetime', $sessionConfig['expire_on_close'] ? '0' : $sessionConfig['lifetime']);
    }
    
    /**
     * Register a new user
     * 
     * @param string $email User email
     * @param string $username Username
     * @param string $password Password
     * @param string $displayName Display name
     * @return array Result with success status and message
     */
    public function register(string $email, string $username, string $password, string $displayName): array {
        // Validate input
        $validation = $this->validateRegistration($email, $username, $password, $displayName);
        if (!$validation['valid']) {
            return $validation;
        }
        
        // Check if email already exists
        if ($this->emailExists($email)) {
            return [
                'success' => false,
                'error' => 'Email already registered',
                'code' => 'AUTH_EMAIL_EXISTS'
            ];
        }
        
        // Check if username already exists
        if ($this->usernameExists($username)) {
            return [
                'success' => false,
                'error' => 'Username already taken',
                'code' => 'AUTH_USERNAME_EXISTS'
            ];
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, [
            'cost' => $this->config['security']['bcrypt_cost']
        ]);
        
        // Generate UUID
        $uuid = $this->generateUuid();
        
        try {
            // Insert user
            $sql = "INSERT INTO users (uuid, email, username, password_hash, display_name, created_at) 
                    VALUES (:uuid, :email, :username, :password_hash, :display_name, NOW())";
            
            $userId = $this->db->insert($sql, [
                'uuid' => $uuid,
                'email' => $email,
                'username' => $username,
                'password_hash' => $passwordHash,
                'display_name' => $displayName
            ]);
            
            if ($userId) {
                // Create verification token if required
                if ($this->config['features']['email_verification_required']) {
                    $this->createEmailVerificationToken($userId);
                }
                
                // Log the registration
                $this->logActivity($userId, 'registration');
                
                return [
                    'success' => true,
                    'message' => 'Registration successful',
                    'user_id' => $userId,
                    'uuid' => $uuid
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Registration failed. Please try again.',
                'code' => 'AUTH_REGISTER_FAILED'
            ];
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An unexpected error occurred',
                'code' => 'AUTH_SYSTEM_ERROR'
            ];
        }
    }
    
    /**
     * Authenticate user login
     * 
     * @param string $email User email
     * @param string $password User password
     * @param bool $rememberMe Remember me option
     * @return array Result with success status and user data
     */
    public function login(string $email, string $password, bool $rememberMe = false): array {
        // Check for account lockout
        if ($this->isAccountLocked($email)) {
            return [
                'success' => false,
                'error' => 'Account temporarily locked due to multiple failed attempts',
                'code' => 'AUTH_ACCOUNT_LOCKED'
            ];
        }
        
        // Find user by email
        $user = $this->getUserByEmail($email);
        
        if (!$user) {
            $this->recordFailedAttempt($email);
            return [
                'success' => false,
                'error' => 'Invalid credentials',
                'code' => 'AUTH_INVALID_CREDENTIALS'
            ];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $this->recordFailedAttempt($email);
            return [
                'success' => false,
                'error' => 'Invalid credentials',
                'code' => 'AUTH_INVALID_CREDENTIALS'
            ];
        }
        
        // Check if account is active
        if (!$user['is_active']) {
            return [
                'success' => false,
                'error' => 'Account is deactivated',
                'code' => 'AUTH_ACCOUNT_DEACTIVATED'
            ];
        }
        
        // Check if account is banned
        if ($user['is_banned']) {
            return [
                'success' => false,
                'error' => 'Account is banned: ' . ($user['ban_reason'] ?? 'No reason provided'),
                'code' => 'AUTH_ACCOUNT_BANNED'
            ];
        }
        
        // Clear failed attempts on successful login
        $this->clearFailedAttempts($email);
        
        // Create session
        $this->createSession($user['id']);
        
        // Update last login
        $this->updateLastLogin($user['id']);
        
        // Set remember me cookie if requested
        if ($rememberMe) {
            $this->setRememberToken($user['id']);
        }
        
        // Log the login
        $this->logActivity($user['id'], 'login');
        
        // Return user data (excluding sensitive info)
        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'uuid' => $user['uuid'],
                'email' => $user['email'],
                'username' => $user['username'],
                'display_name' => $user['display_name'],
                'avatar_url' => $user['avatar_url'],
                'status_message' => $user['status_message']
            ]
        ];
    }
    
    /**
     * Logout current user
     * 
     * @return bool Success status
     */
    public function logout(): bool {
        if ($this->isLoggedIn()) {
            // Log the logout
            $this->logActivity($this->userId, 'logout');
            
            // Invalidate session
            $_SESSION = [];
            
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }
            
            session_destroy();
            
            // Remove remember token
            $this->removeRememberToken();
            
            // Reset state
            $this->userId = null;
            $this->userData = null;
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool True if logged in
     */
    public function isLoggedIn(): bool {
        return $this->userId !== null && $this->userData !== null;
    }
    
    /**
     * Get current user ID
     * 
     * @return int|null User ID or null
     */
    public function getUserId(): ?int {
        return $this->userId;
    }
    
    /**
     * Get current user data
     * 
     * @return array|null User data or null
     */
    public function getUserData(): ?array {
        return $this->userData;
    }
    
    /**
     * Get user by ID
     * 
     * @param int $userId User ID
     * @return array|null User data or null
     */
    public function getUserById(int $userId): ?array {
        $sql = "SELECT id, uuid, email, username, display_name, avatar_url, bio, 
                       status_message, is_verified, is_active, last_login_at, last_seen_at, created_at
                FROM users 
                WHERE id = :id AND is_active = TRUE AND is_banned = FALSE";
        
        return $this->db->selectOne($sql, ['id' => $userId]) ?: null;
    }
    
    /**
     * Get user by email
     * 
     * @param string $email User email
     * @return array|null User data or null
     */
    public function getUserByEmail(string $email): ?array {
        $sql = "SELECT * FROM users WHERE email = :email";
        return $this->db->selectOne($sql, ['email' => $email]) ?: null;
    }
    
    /**
     * Get user by username
     * 
     * @param string $username Username
     * @return array|null User data or null
     */
    public function getUserByUsername(string $username): ?array {
        $sql = "SELECT * FROM users WHERE username = :username";
        return $this->db->selectOne($sql, ['username' => $username]) ?: null;
    }
    
    /**
     * Request password reset
     * 
     * @param string $email User email
     * @return array Result with success status
     */
    public function requestPasswordReset(string $email): array {
        $user = $this->getUserByEmail($email);
        
        // Always return success to prevent email enumeration
        if (!$user || !$user['is_active']) {
            return [
                'success' => true,
                'message' => 'If the email is registered, you will receive a password reset link'
            ];
        }
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        try {
            // Invalidate existing tokens
            $this->db->update(
                "UPDATE password_reset_tokens SET is_used = TRUE WHERE user_id = :user_id",
                ['user_id' => $user['id']]
            );
            
            // Create new token
            $sql = "INSERT INTO password_reset_tokens (user_id, token, expires_at, created_at) 
                    VALUES (:user_id, :token, :expires_at, NOW())";
            
            $this->db->insert($sql, [
                'user_id' => $user['id'],
                'token' => hash('sha256', $token),
                'expires_at' => $expiresAt
            ]);
            
            // Send email (implement your email service)
            $resetUrl = $this->config['url'] . "/reset-password.php?token={$token}";
            $this->sendPasswordResetEmail($user['email'], $user['display_name'], $resetUrl);
            
            return [
                'success' => true,
                'message' => 'If the email is registered, you will receive a password reset link'
            ];
            
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred. Please try again later.',
                'code' => 'AUTH_RESET_FAILED'
            ];
        }
    }
    
    /**
     * Reset password with token
     * 
     * @param string $token Reset token
     * @param string $newPassword New password
     * @return array Result with success status
     */
    public function resetPassword(string $token, string $newPassword): array {
        // Validate password
        $validation = $this->validatePassword($newPassword);
        if (!$validation['valid']) {
            return $validation;
        }
        
        // Find valid token
        $tokenHash = hash('sha256', $token);
        $sql = "SELECT prt.*, u.email 
                FROM password_reset_tokens prt
                JOIN users u ON prt.user_id = u.id
                WHERE prt.token = :token 
                AND prt.is_used = FALSE 
                AND prt.expires_at > NOW()";
        
        $tokenData = $this->db->selectOne($sql, ['token' => $tokenHash]);
        
        if (!$tokenData) {
            return [
                'success' => false,
                'error' => 'Invalid or expired reset token',
                'code' => 'AUTH_INVALID_TOKEN'
            ];
        }
        
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // Hash new password
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, [
                'cost' => $this->config['security']['bcrypt_cost']
            ]);
            
            // Update password
            $this->db->update(
                "UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :user_id",
                ['password_hash' => $passwordHash, 'user_id' => $tokenData['user_id']]
            );
            
            // Mark token as used
            $this->db->update(
                "UPDATE password_reset_tokens SET is_used = TRUE, used_at = NOW() WHERE id = :id",
                ['id' => $tokenData['id']]
            );
            
            // Invalidate all sessions for this user
            $this->db->update(
                "UPDATE sessions SET is_active = FALSE WHERE user_id = :user_id",
                ['user_id' => $tokenData['user_id']]
            );
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Password reset successful. Please login with your new password.'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Password reset execution error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred. Please try again later.',
                'code' => 'AUTH_RESET_FAILED'
            ];
        }
    }
    
    /**
     * Update user profile
     * 
     * @param int $userId User ID
     * @param array $data Profile data
     * @return array Result with success status
     */
    public function updateProfile(int $userId, array $data): array {
        $allowedFields = ['display_name', 'bio', 'status_message'];
        $updates = [];
        $params = ['id' => $userId];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return [
                'success' => false,
                'error' => 'No valid fields to update',
                'code' => 'AUTH_NO_UPDATES'
            ];
        }
        
        $sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = :id";
        
        $affected = $this->db->update($sql, $params);
        
        if ($affected > 0) {
            // Refresh user data
            $this->userData = $this->getUserById($userId);
            
            return [
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $this->userData
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Profile update failed',
            'code' => 'AUTH_UPDATE_FAILED'
        ];
    }
    
    /**
     * Change password
     * 
     * @param int $userId User ID
     * @param string $currentPassword Current password
     * @param string $newPassword New password
     * @return array Result with success status
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array {
        // Get current user
        $user = $this->db->selectOne(
            "SELECT password_hash FROM users WHERE id = :id",
            ['id' => $userId]
        );
        
        if (!$user) {
            return [
                'success' => false,
                'error' => 'User not found',
                'code' => 'AUTH_USER_NOT_FOUND'
            ];
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password_hash'])) {
            return [
                'success' => false,
                'error' => 'Current password is incorrect',
                'code' => 'AUTH_WRONG_PASSWORD'
            ];
        }
        
        // Validate new password
        $validation = $this->validatePassword($newPassword);
        if (!$validation['valid']) {
            return $validation;
        }
        
        // Hash new password
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, [
            'cost' => $this->config['security']['bcrypt_cost']
        ]);
        
        // Update password
        $affected = $this->db->update(
            "UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id",
            ['password_hash' => $passwordHash, 'id' => $userId]
        );
        
        if ($affected > 0) {
            // Invalidate all sessions
            $this->db->update(
                "UPDATE sessions SET is_active = FALSE WHERE user_id = :user_id",
                ['user_id' => $userId]
            );
            
            // Logout current user
            $this->logout();
            
            return [
                'success' => true,
                'message' => 'Password changed successfully. Please login again.'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Password change failed',
            'code' => 'AUTH_CHANGE_FAILED'
        ];
    }
    
    /**
     * Validate registration input
     */
    private function validateRegistration(string $email, string $username, string $password, string $displayName): array {
        // Email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'error' => 'Invalid email format', 'field' => 'email'];
        }
        
        // Username validation
        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['valid' => false, 'error' => 'Username must be 3-50 characters', 'field' => 'username'];
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['valid' => false, 'error' => 'Username can only contain letters, numbers, and underscores', 'field' => 'username'];
        }
        
        // Password validation
        $passwordValidation = $this->validatePassword($password);
        if (!$passwordValidation['valid']) {
            $passwordValidation['field'] = 'password';
            return $passwordValidation;
        }
        
        // Display name validation
        if (strlen($displayName) < 2 || strlen($displayName) > 100) {
            return ['valid' => false, 'error' => 'Display name must be 2-100 characters', 'field' => 'display_name'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Validate password strength
     */
    private function validatePassword(string $password): array {
        $security = $this->config['security'];
        
        if (strlen($password) < $security['password_min_length']) {
            return ['valid' => false, 'error' => "Password must be at least {$security['password_min_length']} characters"];
        }
        
        if ($security['password_require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            return ['valid' => false, 'error' => 'Password must contain at least one uppercase letter'];
        }
        
        if ($security['password_require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            return ['valid' => false, 'error' => 'Password must contain at least one lowercase letter'];
        }
        
        if ($security['password_require_number'] && !preg_match('/[0-9]/', $password)) {
            return ['valid' => false, 'error' => 'Password must contain at least one number'];
        }
        
        if ($security['password_require_special'] && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            return ['valid' => false, 'error' => 'Password must contain at least one special character'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Check if email exists
     */
    private function emailExists(string $email): bool {
        $result = $this->db->selectOne("SELECT id FROM users WHERE email = :email", ['email' => $email]);
        return $result !== false;
    }
    
    /**
     * Check if username exists
     */
    private function usernameExists(string $username): bool {
        $result = $this->db->selectOne("SELECT id FROM users WHERE username = :username", ['username' => $username]);
        return $result !== false;
    }
    
    /**
     * Check if account is locked
     */
    private function isAccountLocked(string $email): bool {
        // Implement rate limiting logic here
        // For now, return false
        return false;
    }
    
    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt(string $email): void {
        // Implement failed attempt tracking
    }
    
    /**
     * Clear failed login attempts
     */
    private function clearFailedAttempts(string $email): void {
        // Implement failed attempt clearing
    }
    
    /**
     * Create session for user
     */
    private function createSession(int $userId): void {
        $_SESSION['user_id'] = $userId;
        $_SESSION['logged_in_at'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        
        // Store session in database
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + $this->config['session']['lifetime']);
        
        $this->db->insert(
            "INSERT INTO sessions (user_id, session_token, ip_address, user_agent, expires_at, created_at) 
             VALUES (:user_id, :session_token, :ip_address, :user_agent, :expires_at, NOW())",
            [
                'user_id' => $userId,
                'session_token' => hash('sha256', $sessionToken),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'expires_at' => $expiresAt
            ]
        );
    }
    
    /**
     * Authenticate via remember token
     */
    private function authenticateViaRememberToken(): void {
        $token = $_COOKIE['remember_token'];
        
        $session = $this->db->selectOne(
            "SELECT s.*, u.* FROM sessions s 
             JOIN users u ON s.user_id = u.id 
             WHERE s.session_token = :token 
             AND s.is_active = TRUE 
             AND s.expires_at > NOW()",
            ['token' => hash('sha256', $token)]
        );
        
        if ($session && $session['is_active'] && !$session['is_banned']) {
            $this->userId = $session['user_id'];
            $this->userData = $session;
            $_SESSION['user_id'] = $session['user_id'];
            
            // Extend session
            $this->updateLastActivity();
        } else {
            $this->removeRememberToken();
        }
    }
    
    /**
     * Set remember me token
     */
    private function setRememberToken(int $userId): void {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (30 * 24 * 60 * 60); // 30 days
        
        setcookie(
            'remember_token',
            $token,
            $expires,
            '/',
            parse_url($this->config['url'], PHP_URL_HOST),
            true,  // Secure
            true   // HttpOnly
        );
    }
    
    /**
     * Remove remember token
     */
    private function removeRememberToken(): void {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin(int $userId): void {
        $this->db->update(
            "UPDATE users SET last_login_at = NOW(), last_seen_at = NOW() WHERE id = :id",
            ['id' => $userId]
        );
    }
    
    /**
     * Update last activity timestamp
     */
    private function updateLastActivity(): void {
        if ($this->userId) {
            $this->db->update(
                "UPDATE users SET last_seen_at = NOW() WHERE id = :id",
                ['id' => $this->userId]
            );
        }
    }
    
    /**
     * Create email verification token
     */
    private function createEmailVerificationToken(int $userId): void {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 86400); // 24 hours
        
        $this->db->insert(
            "INSERT INTO email_verification_tokens (user_id, token, expires_at, created_at) 
             VALUES (:user_id, :token, :expires_at, NOW())",
            [
                'user_id' => $userId,
                'token' => hash('sha256', $token),
                'expires_at' => $expiresAt
            ]
        );
    }
    
    /**
     * Log user activity
     */
    private function logActivity(int $userId, string $action): void {
        // Implement activity logging
        error_log("User {$userId} performed action: {$action}");
    }
    
    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail(string $email, string $displayName, string $resetUrl): void {
        // Implement email sending using your preferred method
        // For InfinityFree, use external SMTP service
        error_log("Password reset email would be sent to {$email} with URL: {$resetUrl}");
    }
    
    /**
     * Generate UUID v4
     */
    private function generateUuid(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
