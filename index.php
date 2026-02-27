<?php
/**
 * index.php
 * Front Controller & Router
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/Router.php';
require_once __DIR__ . '/includes/SecurityMiddleware.php';
require_once __DIR__ . '/includes/AuthMiddleware.php';

$router = new Router(BASE_PATH);

// Global Middleware
$router->addGlobalMiddleware(SecurityMiddleware::class);

// Public Routes
$router->get('/', __DIR__ . '/pages/home.php');
$router->get('/home', __DIR__ . '/pages/home.php');
$router->get('/cars', __DIR__ . '/pages/cars.php');
$router->get('/car-detail/{slug}', __DIR__ . '/pages/car-detail.php');
$router->get('/contact', __DIR__ . '/pages/contact.php');
$router->post('/contact', __DIR__ . '/pages/contact.php');
$router->get('/login', __DIR__ . '/pages/login.php');
$router->post('/login', __DIR__ . '/pages/login.php');
$router->get('/logout', function() {
    logout();
});

// Admin Routes (Protected)
$router->get('/admin', __DIR__ . '/admin/dashboard.php')->middleware(AuthMiddleware::class);
$router->get('/admin/dashboard', __DIR__ . '/admin/dashboard.php')->middleware(AuthMiddleware::class);
$router->get('/admin/cars', __DIR__ . '/admin/cars/index.php')->middleware(AuthMiddleware::class);
$router->get('/admin/cars/add', __DIR__ . '/admin/cars/add.php')->middleware(AuthMiddleware::class);
$router->post('/admin/cars/add', __DIR__ . '/admin/cars/add.php')->middleware(AuthMiddleware::class);
$router->get('/admin/cars/edit/{id}', __DIR__ . '/admin/cars/edit.php')->middleware(AuthMiddleware::class);
$router->post('/admin/cars/edit/{id}', __DIR__ . '/admin/cars/edit.php')->middleware(AuthMiddleware::class);
$router->get('/admin/users', __DIR__ . '/admin/users/index.php')->middleware(AuthMiddleware::class);

// API Routes
$router->get('/api/language', __DIR__ . '/pages/api/language.php');
$router->post('/api/convert-currency', __DIR__ . '/pages/api/convert-currency.php');

// Dispatch
$router->dispatch();
?>
