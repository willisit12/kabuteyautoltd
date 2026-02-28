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
        if ($user['role'] !== 'admin') {
            // Logically, a customer trying to access admin should be sent to their dashboard or a 403
            // Sending to /dashboard which will route them correctly
            header('Location: ' . url('dashboard'));
            exit;
        }

        return $next($vars);
    }
}
