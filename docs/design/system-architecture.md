# System Architecture Design Document

## 1. Overview

### 1.1 System Architecture
NexusChat follows a traditional three-tier architecture optimized for InfinityFree hosting:

```
┌─────────────────────────────────────────────────────────┐
│                    PRESENTATION LAYER                    │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
│  │   Login     │  │    Chat     │  │   Profile   │     │
│  │    Page     │  │   Interface │  │    Page     │     │
│  └─────────────┘  └─────────────┘  └─────────────┘     │
│                                                          │
│  Technologies: HTML5, TailwindCSS, Vanilla JS, Lucide   │
└─────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────┐
│                     APPLICATION LAYER                    │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
│  │   Auth      │  │    Chat     │  │    File     │     │
│  │   Module    │  │   Module    │  │   Module    │     │
│  └─────────────┘  └─────────────┘  └─────────────┘     │
│                                                          │
│  Technologies: Pure PHP 8.x, RESTful API                │
└─────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────┐
│                       DATA LAYER                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
│  │   MySQL     │  │   Session   │  │    File     │     │
│  │  Database   │  │   Storage   │  │   Storage   │     │
│  └─────────────┘  └─────────────┘  └─────────────┘     │
└─────────────────────────────────────────────────────────┘
```

### 1.2 Component Diagram

```
┌──────────────────────────────────────────────────────────────┐
│                        CLIENT SIDE                            │
├──────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌────────────────┐         ┌────────────────┐              │
│  │   index.php    │────────▶│   chat.php     │              │
│  │  (Landing)     │         │  (Main App)    │              │
│  └────────────────┘         └────────────────┘              │
│           │                      │                           │
│           ▼                      ▼                           │
│  ┌────────────────┐         ┌────────────────┐              │
│  │   login.php    │         │  profile.php   │              │
│  │  (Auth)        │         │  (User Mgmt)   │              │
│  └────────────────┘         └────────────────┘              │
│                                                               │
│  All pages use:                                                │
│  - TailwindCSS (CDN) for styling                              │
│  - Lucide Icons (CDN) for icons                               │
│  - Alpine.js for reactivity                                   │
│  - Vanilla JS for AJAX                                        │
└──────────────────────────────────────────────────────────────┘
                            │
                            │ HTTP/HTTPS
                            ▼
┌──────────────────────────────────────────────────────────────┐
│                      SERVER SIDE (PHP)                        │
├──────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────────────────────────────────────────────┐   │
│  │                  public/api/                          │   │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐           │   │
│  │  │ auth/    │  │ messages/│  │  users/  │           │   │
│  │  │ login    │  │ send     │  │ profile  │           │   │
│  │  │ register │  │ get      │  │ status   │           │   │
│  │  │ logout   │  │ delete   │  │ contacts │           │   │
│  │  └──────────┘  └──────────┘  └──────────┘           │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐   │
│  │                 includes/                             │   │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐           │   │
│  │  │  auth    │  │   db     │  │  utils   │           │   │
│  │  │  .php    │  │  .php    │  │  .php    │           │   │
│  │  └──────────┘  └──────────┘  └──────────┘           │   │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐           │   │
│  │  │  chat    │  │  file    │  │  notif   │           │   │
│  │  │  .php    │  │  .php    │  │  .php    │           │   │
│  │  └──────────┘  └──────────┘  └──────────┘           │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐   │
│  │                  config/                              │   │
│  │  ┌──────────────────────────────────────────┐        │   │
│  │  │  database.php (DB credentials)           │        │   │
│  │  │  app.php (App settings)                  │        │   │
│  │  └──────────────────────────────────────────┘        │   │
│  └──────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────┘
                            │
                            │ PDO
                            ▼
┌──────────────────────────────────────────────────────────────┐
│                      DATABASE (MySQL)                         │
├──────────────────────────────────────────────────────────────┤
│  Tables: users, sessions, conversations, messages, etc.      │
│  Views: active_users, conversation_list                       │
│  Triggers: update_conversation_last_message                   │
│  Events: cleanup_typing_indicators, cleanup_expired_sessions │
└──────────────────────────────────────────────────────────────┘
```

## 2. Frontend Architecture

### 2.1 Page Structure

```
public/
├── index.php              # Landing/Redirect page
├── login.php              # Login & Registration
├── register.php           # Registration (alternative)
├── chat.php               # Main chat interface
├── profile.php            # User profile management
├── settings.php           # Application settings
├── conversations.php      # Conversation list
└── api/
    ├── auth/
    │   ├── login.php
    │   ├── register.php
    │   ├── logout.php
    │   └── reset-password.php
    ├── messages/
    │   ├── send.php
    │   ├── get.php
    │   ├── delete.php
    │   └── edit.php
    ├── conversations/
    │   ├── create.php
    │   ├── get.php
    │   └── delete.php
    ├── users/
    │   ├── profile.php
    │   ├── status.php
    │   └── search.php
    └── files/
        ├── upload.php
        └── download.php
```

### 2.2 Component Architecture

#### 2.2.1 Authentication Components
- **LoginForm**: Email/password input, validation, submission
- **RegisterForm**: User registration with validation
- **PasswordReset**: Password recovery flow
- **SessionManager**: Handle session state and timeouts

#### 2.2.2 Chat Components
- **ConversationList**: Display all user conversations
- **MessageList**: Render messages with infinite scroll
- **MessageInput**: Text input with emoji picker
- **MessageItem**: Individual message component
- **TypingIndicator**: Show when others are typing
- **OnlineStatus**: Display user presence

#### 2.2.3 Profile Components
- **ProfileCard**: User information display
- **AvatarUpload**: Profile picture management
- **SettingsForm**: User preferences

### 2.3 State Management

Using Alpine.js for reactive state:

```javascript
// Global state
const appState = {
    user: null,
    conversations: [],
    activeConversation: null,
    messages: [],
    onlineUsers: [],
    typingUsers: [],
    notifications: [],
    theme: 'light',
    isLoading: false
};
```

### 2.4 Real-time Updates

Since InfinityFree doesn't support WebSockets, we use:
- **Long Polling**: For message updates (5-second intervals)
- **AJAX**: For user actions
- **Server-Sent Events (SSE)**: Alternative for notifications

## 3. Backend Architecture

### 3.1 API Design Pattern

RESTful API with JSON responses:

```php
// Response format
{
    "success": true,
    "data": { ... },
    "message": "Operation successful",
    "timestamp": "2025-01-01T00:00:00Z"
}

// Error format
{
    "success": false,
    "error": {
        "code": "AUTH_001",
        "message": "Invalid credentials"
    },
    "timestamp": "2025-01-01T00:00:00Z"
}
```

### 3.2 Authentication Flow

```
1. User submits credentials
2. Server validates input
3. Server checks database
4. Server creates session token
5. Server sets secure cookie
6. Client stores session info
7. Subsequent requests include session token
8. Server validates session on each request
```

### 3.3 Message Flow

```
1. User composes message
2. Client validates content
3. Client sends POST to /api/messages/send
4. Server validates authentication
5. Server validates conversation access
6. Server sanitizes input
7. Server inserts message to database
8. Server triggers notification
9. Server returns success response
10. Client updates UI
11. Other clients poll and receive new message
```

## 4. Security Architecture

### 4.1 Authentication Security

```php
// Password hashing
$passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Session security
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
```

### 4.2 Input Validation

```php
// Sanitization pipeline
function sanitizeInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

// Validation rules
$validationRules = [
    'email' => 'required|email|unique:users',
    'password' => 'required|min:8|contains:uppercase,lowercase,number,special',
    'username' => 'required|min:3|max:50|alphanumeric',
    'message' => 'required|max:4000'
];
```

### 4.3 SQL Injection Prevention

```php
// Prepared statements only
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
```

### 4.4 XSS Prevention

```php
// Output encoding
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// Content-Type headers
header('Content-Type: application/json; charset=utf-8');
```

### 4.5 CSRF Protection

```php
// Token generation
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Token validation
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF validation failed');
}
```

## 5. File Storage Architecture

### 5.1 Storage Strategy

```
uploads/
├── avatars/           # User profile pictures
│   ├── thumb/         # Thumbnail versions
│   └── full/          # Original versions
├── attachments/       # Message attachments
│   ├── images/
│   ├── documents/
│   ├── videos/
│   └── audio/
└── temp/              # Temporary uploads
```

### 5.2 File Upload Flow

```
1. Client selects file
2. Client validates file type and size
3. Client sends multipart form data
4. Server validates MIME type
5. Server scans for malware (if available)
6. Server generates unique filename
7. Server moves file to storage
8. Server creates database record
9. Server returns file URL
```

## 6. Performance Optimization

### 6.1 Caching Strategy

```php
// Query caching
$cacheKey = "conversation_{$userId}_{$page}";
$cached = apcu_fetch($cacheKey);
if ($cached !== false) {
    return $cached;
}

// Set cache for 5 minutes
apcu_store($cacheKey, $result, 300);
```

### 6.2 Database Optimization

- Indexed columns for frequent queries
- Covering indexes for common SELECT patterns
- Query optimization with EXPLAIN
- Connection pooling

### 6.3 Frontend Optimization

- Lazy loading for messages
- Virtual scrolling for long lists
- Image compression and lazy loading
- CDN for static assets
- Minified CSS and JS

## 7. Error Handling

### 7.1 Error Categories

```php
class ErrorCode {
    // Authentication errors (1000-1999)
    const AUTH_INVALID_CREDENTIALS = 1001;
    const AUTH_SESSION_EXPIRED = 1002;
    const AUTH_ACCOUNT_LOCKED = 1003;
    
    // Message errors (2000-2999)
    const MESSAGE_NOT_FOUND = 2001;
    const MESSAGE_SEND_FAILED = 2002;
    const MESSAGE_TOO_LONG = 2003;
    
    // File errors (3000-3999)
    const FILE_TOO_LARGE = 3001;
    const FILE_INVALID_TYPE = 3002;
    const FILE_UPLOAD_FAILED = 3003;
    
    // System errors (9000-9999)
    const SYSTEM_ERROR = 9001;
    const DATABASE_ERROR = 9002;
}
```

### 7.2 Logging System

```php
// Log levels: DEBUG, INFO, WARNING, ERROR, CRITICAL
function logEvent($level, $message, $context = []) {
    $logEntry = [
        'timestamp' => date('c'),
        'level' => $level,
        'message' => $message,
        'context' => $context,
        'user_id' => getCurrentUserId(),
        'ip' => $_SERVER['REMOTE_ADDR']
    ];
    
    file_put_contents(
        "logs/{$level}.log",
        json_encode($logEntry) . PHP_EOL,
        FILE_APPEND
    );
}
```

## 8. Deployment Architecture

### 8.1 InfinityFree Configuration

```
.htaccess configuration:
- Enable mod_rewrite for clean URLs
- Set proper MIME types
- Enable GZIP compression
- Set security headers
- Configure error pages
```

### 8.2 Environment Variables

```php
// config/database.php
return [
    'host' => getenv('DB_HOST') ?: 'sql123.infinityfree.com',
    'database' => getenv('DB_NAME') ?: 'epiz_12345678_nexuschat',
    'username' => getenv('DB_USER') ?: 'epiz_12345678',
    'password' => getenv('DB_PASS') ?: 'your_password',
    'charset' => 'utf8mb4'
];
```

### 8.3 Domain Configuration

```
DNS Settings for Nexuss-Chat.gt.tc:
- A Record: @ → InfinityFree IP
- CNAME: www → Nexuss-Chat.gt.tc
- SSL: Let's Encrypt via InfinityFree
```

## 9. Monitoring & Maintenance

### 9.1 Health Checks

```php
// health.php endpoint
$health = [
    'status' => 'healthy',
    'database' => checkDatabaseConnection(),
    'disk_space' => checkDiskSpace(),
    'memory' => checkMemoryUsage(),
    'uptime' => getUptime()
];
```

### 9.2 Metrics to Track

- Active users count
- Messages per day
- Average response time
- Error rate
- Database query performance
- File storage usage

---

Document Version: 1.0
Last Updated: 2025
Author: Senior Software Engineer
Status: Approved
