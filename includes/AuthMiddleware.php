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

            $isJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || 
                      str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') ||
                      (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

            if ($isJson) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'error' => 'Authentication required',
                    'code' => 401
                ]);
            } else {
                $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
                header('Location: ' . url('login'));
            }
            exit;
        }

        return $next($vars);
    }
}
