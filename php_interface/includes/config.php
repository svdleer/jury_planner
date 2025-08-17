<?php
/**
 * Configuration loader using .env file
 */

class EnvLoader {
    private static $loaded = false;
    private static $config = [];
    
    public static function load($path = null) {
        if (self::$loaded) {
            return;
        }
        
        if ($path === null) {
            $path = dirname(dirname(__DIR__)) . '/.env';
        }
        
        if (!file_exists($path)) {
            throw new Exception("Environment file not found: $path");
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                self::$config[$key] = $value;
                
                // Also set as environment variable
                if (!getenv($key)) {
                    putenv("$key=$value");
                }
            }
        }
        
        self::$loaded = true;
    }
    
    public static function get($key, $default = null) {
        self::load();
        return self::$config[$key] ?? $default;
    }
}

// Load environment variables
EnvLoader::load();

// Database configuration from .env
$db_config = [
    'host' => EnvLoader::get('DB_HOST', 'localhost'),
    'port' => EnvLoader::get('DB_PORT', '3306'),
    'dbname' => EnvLoader::get('DB_NAME', 'mnc_jury'),
    'username' => EnvLoader::get('DB_USER', 'root'),
    'password' => EnvLoader::get('DB_PASSWORD', ''),
    'charset' => 'utf8mb4'
];

// Create PDO connection
try {
    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // Log error but don't expose sensitive information
    error_log("Database connection failed: " . $e->getMessage());
    throw new Exception("Database connection failed. Please check configuration.");
}

// Application settings
$app_config = [
    'timezone' => EnvLoader::get('TIMEZONE', 'Europe/Amsterdam'),
    'max_planning_time' => (int)EnvLoader::get('MAX_PLANNING_TIME', 300),
    'default_planning_horizon_days' => (int)EnvLoader::get('DEFAULT_PLANNING_HORIZON_DAYS', 30),
    'export_directory' => EnvLoader::get('EXPORT_DIRECTORY', 'exports'),
    'temp_directory' => EnvLoader::get('TEMP_DIRECTORY', 'temp'),
    'log_level' => EnvLoader::get('LOG_LEVEL', 'INFO'),
    'log_file' => EnvLoader::get('LOG_FILE', 'logs/jury_planner.log'),
];

// Set timezone
date_default_timezone_set($app_config['timezone']);

// Helper function to get configuration
function config($key, $default = null) {
    return EnvLoader::get($key, $default);
}

// Make PDO available globally (for backward compatibility)
$GLOBALS['pdo'] = $pdo;
?>
