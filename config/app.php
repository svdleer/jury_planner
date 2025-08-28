<?php
/**
 * Configuration for PHP Jury Planner Interface
 */

// Application settings
define('APP_NAME', 'Waterpolo Jury Planner');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'Modern interface for managing water polo matches and jury team assignments');

// Database settings (loaded from .env file)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $envVars = parse_ini_file($envFile);
    foreach ($envVars as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Default database settings if not in .env
define('DB_HOST', $_ENV['DB_HOST'] ?? 'vps.serial.nl');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'mnc_jury');
define('DB_USER', $_ENV['DB_USER'] ?? 'mnc_jury');
define('DB_PASS', $_ENV['DB_PASS'] ?? '5j51_hE9r');

// Interface settings
define('ITEMS_PER_PAGE', 25);
define('MAX_UPLOAD_SIZE', '5M');
define('ALLOWED_FILE_TYPES', ['pdf', 'csv', 'xlsx']);

// UI settings
define('THEME_COLOR', '#2563eb'); // Water blue-600
define('DEFAULT_TIMEZONE', 'Europe/Amsterdam');

// Features flags
define('ENABLE_EXPORTS', true);
define('ENABLE_PLANNING', true);
define('ENABLE_NOTIFICATIONS', true);
define('ENABLE_AUDIT_LOG', true);

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.gc_maxlifetime', 3600); // 1 hour

// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Error reporting (adjust for production)
if ($_ENV['APP_ENV'] ?? 'development' === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>
