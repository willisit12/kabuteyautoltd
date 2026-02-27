<?php
declare(strict_types=1);

require_once __DIR__ . '/MiddlewareInterface.php';
require_once __DIR__ . '/auth.php';

/**
 * includes/AuthMiddleware.php
 * Protects routes that require authentication
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(array $vars, callable $next): mixed
    {
        if (!isLoggedIn()) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            
            // Assuming url() helper is available from functions.php
            header('Location: ' . url('login'));
            exit;
        }

        return $next($vars);
    }
}
