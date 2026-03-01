<?php
declare(strict_types=1);

require_once __DIR__ . '/MiddlewareInterface.php';
require_once __DIR__ . '/auth.php';

/**
 * includes/AdminMiddleware.php
 * Protects routes that require administrative privileges
 */
class AdminMiddleware implements MiddlewareInterface
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
        if (!in_array($user['role'], ['admin', 'user'])) {
            // Customers should go to their own dashboard
            header('Location: ' . url('dashboard'));
            exit;
        }

        return $next($vars);
    }
}
