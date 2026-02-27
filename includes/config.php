<?php
/**
 * includes/config.php
 * Core configuration and environment setup
 */

// Load Composer Autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load Environment Variables
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    
    session_start();
}

// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'williams_auto');

// Site Configuration
define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'Williams Auto');
define('SITE_TAGLINE', $_ENV['SITE_TAGLINE'] ?? 'Toronto’s Trusted Source for Hand-Picked Used Cars');
define('BASE_PATH', $_ENV['BASE_PATH'] ?? '');
define('SITE_URL', $_ENV['SITE_URL'] ?? 'http://localhost' . BASE_PATH);
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Local settings
date_default_timezone_set('UTC');
?>
