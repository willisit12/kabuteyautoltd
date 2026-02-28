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
require_once __DIR__ . '/includes/AdminMiddleware.php';
require_once __DIR__ . '/includes/CustomerMiddleware.php';

$router = new Router(BASE_PATH);

// Global Middleware
$router->addGlobalMiddleware(SecurityMiddleware::class);

// Public Routes
$router->get('/', __DIR__ . '/pages/home.php');
$router->get('/home', __DIR__ . '/pages/home.php');
$router->get('/cars', __DIR__ . '/pages/cars.php');
$router->get('/cars/{make_name}', function($vars) {
    $db = getDB();
    // Revert URL-friendly slug back to DB friendly name (e.g., land-rover -> Land Rover)
    // For simplicity, we search with LIKE since casing might differ
    $makeName = str_replace('-', ' ', $vars['make_name']);
    $stmt = $db->prepare("SELECT id FROM makes WHERE name LIKE ?");
    $stmt->execute([$makeName]);
    if ($make = $stmt->fetch()) {
        $_GET['make_id'] = $make['id']; // Inject for cars.php to pick up
        require_once __DIR__ . '/pages/cars.php';
    } else {
        http_response_code(404);
        $notFoundFile = __DIR__ . '/pages/404.php';
        if (file_exists($notFoundFile)) require_once $notFoundFile;
        else echo "404 Not Found";
    }
});
$router->get('/cars/type/{type_name}', function($vars) {
    $db = getDB();
    $typeName = str_replace('-', ' ', $vars['type_name']);
    $stmt = $db->prepare("SELECT id FROM body_types WHERE name LIKE ?");
    $stmt->execute([$typeName]);
    if ($type = $stmt->fetch()) {
        $_GET['body_type_id'] = $type['id']; // Inject for cars.php
        require_once __DIR__ . '/pages/cars.php';
    } else {
        http_response_code(404);
        $notFoundFile = __DIR__ . '/pages/404.php';
        if (file_exists($notFoundFile)) require_once $notFoundFile;
        else echo "404 Not Found";
    }
});
$router->get('/car-detail/{slug}', function($vars) {
    $_GET['slug'] = $vars['slug'];
    require __DIR__ . '/pages/car-detail.php';
});
$router->get('/about', __DIR__ . '/pages/about.php');
$router->get('/contact', __DIR__ . '/pages/contact.php');
$router->post('/contact', __DIR__ . '/pages/contact.php');
$router->get('/brand-selection', __DIR__ . '/pages/brand-selection.php');
$router->get('/login', __DIR__ . '/pages/customer/login.php');
$router->post('/login', __DIR__ . '/pages/customer/login.php');
$router->get('/register', __DIR__ . '/pages/customer/register.php');
$router->post('/register', __DIR__ . '/pages/customer/register.php');
$router->get('/dashboard', function($vars) {
    if (!isLoggedIn()) {
        redirect(url('login'));
    }
    $user = getUserInfo();
    if ($user['role'] === 'admin') {
        require_once __DIR__ . '/admin/dashboard.php';
    } else {
        require_once __DIR__ . '/pages/customer/dashboard.php';
    }
});
$router->get('/logout', function() {
    logout();
});

// Member Portal Routes (Protected)
$router->get('/customer/orders', __DIR__ . '/pages/customer/orders/index.php')->middleware(CustomerMiddleware::class);
$router->get('/customer/orders/view/{id}', __DIR__ . '/pages/customer/orders/view.php')->middleware(CustomerMiddleware::class);
$router->post('/customer/orders/delete', __DIR__ . '/pages/customer/orders/delete.php')->middleware(CustomerMiddleware::class);
$router->get('/customer/favorites', __DIR__ . '/pages/customer/favorites.php')->middleware(CustomerMiddleware::class);
$router->get('/customer/inquiries', __DIR__ . '/pages/customer/inquiries.php')->middleware(CustomerMiddleware::class);
$router->get('/customer/profile', __DIR__ . '/pages/customer/profile.php')->middleware(CustomerMiddleware::class);
$router->post('/customer/profile', __DIR__ . '/pages/customer/profile.php')->middleware(CustomerMiddleware::class);

// Admin Routes (Protected)
$router->get('/admin', __DIR__ . '/admin/dashboard.php')->middleware(AdminMiddleware::class);
$router->get('/admin/dashboard', __DIR__ . '/admin/dashboard.php')->middleware(AdminMiddleware::class);
$router->get('/admin/cars', __DIR__ . '/admin/cars/index.php')->middleware(AdminMiddleware::class);
$router->get('/admin/cars/add', __DIR__ . '/admin/cars/add.php')->middleware(AdminMiddleware::class);
$router->post('/admin/cars/add', __DIR__ . '/admin/cars/add.php')->middleware(AdminMiddleware::class);
$router->get('/admin/cars/edit/{id}', __DIR__ . '/admin/cars/edit.php')->middleware(AdminMiddleware::class);
$router->post('/admin/cars/edit/{id}', __DIR__ . '/admin/cars/edit.php')->middleware(AdminMiddleware::class);
$router->get('/admin/users', __DIR__ . '/admin/users/index.php')->middleware(AdminMiddleware::class);

// API Routes
$router->get('/api/language', __DIR__ . '/pages/api/language.php');
$router->get('/api/get-cars', __DIR__ . '/pages/api/get-cars.php');
$router->post('/api/convert-currency', __DIR__ . '/pages/api/convert-currency.php');
$router->post('/api/favorites', __DIR__ . '/pages/api/favorites.php')->middleware(AuthMiddleware::class);
$router->post('/api/orders', __DIR__ . '/pages/api/orders.php')->middleware(AuthMiddleware::class);

// Dispatch
$router->dispatch();
?>
