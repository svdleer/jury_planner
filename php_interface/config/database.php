<?php
/**
 * Database Configuration for Water Polo Jury Planner
 * Connects to the same MySQL database used by the Python application
 */

class Database {
    private $host;
    private $username;
    private $password;
    private $database;
    private $connection;
    
    public function __construct() {
        // Load configuration from environment or use defaults
        $this->host = $_ENV['DB_HOST'] ?? 'vps.serial.nl';
        $this->username = $_ENV['DB_USER'] ?? 'mnc_jury';
        $this->password = $_ENV['DB_PASSWORD'] ?? '5j51_hE9r';
        $this->database = $_ENV['DB_NAME'] ?? 'mnc_jury';
    }
    
    public function connect() {
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->database};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            return $this->connection;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }
    
    public function getConnection() {
        if ($this->connection === null) {
            return $this->connect();
        }
        return $this->connection;
    }
    
    public function close() {
        $this->connection = null;
    }
}

/**
 * Load environment variables from .env file
 */
function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        return;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

// Load environment variables
loadEnv(__DIR__ . '/../.env');

// Global database instance
$database = new Database();
$db = $database->getConnection();
?>
