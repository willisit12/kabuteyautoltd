<?php
declare(strict_types=1);

require_once __DIR__ . '/MiddlewareInterface.php';
require_once __DIR__ . '/auth.php';

/**
 * includes/CustomerMiddleware.php
 * Protects routes that specifically require customer/member privileges
 */
class CustomerMiddleware implements MiddlewareInterface
{
    public function handle(array $vars, callable $next): mixed
    {
        if (!isLoggedIn()) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . url('login'));
            exit;
        }

        $user = getUserInfo();
        // If they are an admin, they should probably be in the admin panel
        if ($user['role'] === 'admin') {
            header('Location: ' . url('admin/dashboard'));
            exit;
        }

        return $next($vars);
    }
}
