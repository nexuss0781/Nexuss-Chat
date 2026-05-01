<?php
/**
 * Database Connection Class
 * NexusChat - Professional Chat Application
 * 
 * Singleton PDO database connection with error handling
 * 
 * @package NexusChat
 * @version 1.0
 * @author Senior Software Engineer
 */

// Prevent direct access
defined('NEXUSCHAT') or die('Direct access not allowed');

class Database {
    /**
     * @var Database|null Singleton instance
     */
    private static ?Database $instance = null;
    
    /**
     * @var PDO Database connection
     */
    private PDO $connection;
    
    /**
     * @var array Configuration settings
     */
    private array $config;
    
    /**
     * @var int Query count for debugging
     */
    private int $queryCount = 0;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->config = require __DIR__ . '/../config/database.php';
        $this->connect();
    }
    
    /**
     * Get singleton instance
     * 
     * @return Database
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection
     * 
     * @throws PDOException If connection fails
     */
    private function connect(): void {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                $this->config['host'],
                $this->config['database'],
                $this->config['charset']
            );
            
            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
            
            // Log successful connection in debug mode
            if (defined('DEBUG') && DEBUG) {
                error_log("Database connected successfully");
            }
            
        } catch (PDOException $e) {
            // Log error
            error_log("Database connection failed: " . $e->getMessage());
            
            // Show user-friendly error in production
            if (APP_ENV === 'production') {
                die("Service temporarily unavailable. Please try again later.");
            }
            
            throw $e;
        }
    }
    
    /**
     * Get PDO connection
     * 
     * @return PDO
     */
    public function getConnection(): PDO {
        return $this->connection;
    }
    
    /**
     * Execute a SELECT query
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Query parameters
     * @return array Query results
     */
    public function select(string $sql, array $params = []): array {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $this->queryCount++;
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logError($e, $sql, $params);
            return [];
        }
    }
    
    /**
     * Execute a SELECT query and return single row
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Query parameters
     * @return array|false Single row or false if not found
     */
    public function selectOne(string $sql, array $params = []): array|false {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $this->queryCount++;
            return $stmt->fetch() ?: false;
        } catch (PDOException $e) {
            $this->logError($e, $sql, $params);
            return false;
        }
    }
    
    /**
     * Execute an INSERT query
     * 
     * @param string $sql SQL INSERT query
     * @param array $params Query parameters
     * @return int|false Last insert ID or false on failure
     */
    public function insert(string $sql, array $params = []): int|false {
        try {
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute($params);
            $this->queryCount++;
            return $result ? $this->connection->lastInsertId() : false;
        } catch (PDOException $e) {
            $this->logError($e, $sql, $params);
            return false;
        }
    }
    
    /**
     * Execute an UPDATE query
     * 
     * @param string $sql SQL UPDATE query
     * @param array $params Query parameters
     * @return int Number of affected rows
     */
    public function update(string $sql, array $params = []): int {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $this->queryCount++;
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError($e, $sql, $params);
            return 0;
        }
    }
    
    /**
     * Execute a DELETE query
     * 
     * @param string $sql SQL DELETE query
     * @param array $params Query parameters
     * @return int Number of affected rows
     */
    public function delete(string $sql, array $params = []): int {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $this->queryCount++;
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError($e, $sql, $params);
            return 0;
        }
    }
    
    /**
     * Execute a raw query (use with caution)
     * 
     * @param string $sql SQL query
     * @return PDOStatement|false Statement or false on failure
     */
    public function raw(string $sql): PDOStatement|false {
        try {
            $stmt = $this->connection->query($sql);
            $this->queryCount++;
            return $stmt;
        } catch (PDOException $e) {
            $this->logError($e, $sql, []);
            return false;
        }
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool Success status
     */
    public function beginTransaction(): bool {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool Success status
     */
    public function commit(): bool {
        return $this->connection->commit();
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool Success status
     */
    public function rollback(): bool {
        return $this->connection->rollBack();
    }
    
    /**
     * Check if currently in a transaction
     * 
     * @return bool True if in transaction
     */
    public function inTransaction(): bool {
        return $this->connection->inTransaction();
    }
    
    /**
     * Get the last insert ID
     * 
     * @return string|false Last insert ID
     */
    public function lastInsertId(): string|false {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Quote a string for safe use in queries
     * 
     * @param string $string String to quote
     * @param int $type Parameter type
     * @return string Quoted string
     */
    public function quote(string $string, int $type = PDO::PARAM_STR): string {
        return $this->connection->quote($string, $type);
    }
    
    /**
     * Get query count
     * 
     * @return int Number of queries executed
     */
    public function getQueryCount(): int {
        return $this->queryCount;
    }
    
    /**
     * Reset query count
     */
    public function resetQueryCount(): void {
        $this->queryCount = 0;
    }
    
    /**
     * Log database error
     * 
     * @param PDOException $exception The exception
     * @param string $sql The SQL query
     * @param array $params Query parameters
     */
    private function logError(PDOException $exception, string $sql, array $params): void {
        $errorLog = [
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'sql' => $sql,
            'params' => $params,
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ];
        
        error_log("Database Error: " . json_encode($errorLog));
    }
    
    /**
     * Close database connection
     */
    public function close(): void {
        $this->connection = null;
        self::$instance = null;
    }
    
    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization of singleton
     * 
     * @throws Exception
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Helper function to get database instance
 * 
 * @return Database
 */
function db(): Database {
    return Database::getInstance();
}
