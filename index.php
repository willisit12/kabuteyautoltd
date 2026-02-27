<?php
/**
 * index.php
 * Front Controller & Router
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Simple Router
$request = $_SERVER['REQUEST_URI'];
$basePath = BASE_PATH;
$path = parse_url($request, PHP_URL_PATH);
if ($basePath !== '' && strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}
$path = trim($path, '/');

// Optional: strip .php extension if present in URI
if (str_ends_with($path, '.php')) {
    $path = substr($path, 0, -4);
}

// Default home or admin base
if ($path === '' || $path === 'index') {
    $page = 'home';
} elseif ($path === 'admin') {
    $page = 'admin/dashboard';
} elseif (strpos($path, 'car-detail/') === 0) {
    $page = 'car-detail';
    $slug = substr($path, 11);
    $_GET['slug'] = $slug; // Set it in $_GET for the page to pick up
} else {
    $page = $path;
}

// Security: limit allowed characters in page names (allow slashes for API)
if (!preg_match('/^[a-zA-Z0-0\-_ \/.]+$/', $page)) {
    $page = '404';
}

// Load the page
if (strpos($page, 'admin/') === 0) {
    $pageFile = __DIR__ . "/{$page}.php";
} elseif (strpos($page, 'api/') === 0) {
    $pageFile = __DIR__ . "/pages/{$page}.php";
} else {
    $pageFile = __DIR__ . "/pages/{$page}.php";
}

if (!file_exists($pageFile)) {
    $pageFile = __DIR__ . "/pages/404.php";
}

require_once $pageFile;
?>
