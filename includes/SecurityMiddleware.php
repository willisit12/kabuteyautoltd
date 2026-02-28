<?php
declare(strict_types=1);

require_once __DIR__ . '/MiddlewareInterface.php';

/**
 * includes/SecurityMiddleware.php
 * Middleware for enhancing security via headers and CSRF checks
 */
class SecurityMiddleware implements MiddlewareInterface
{
    /**
     * Handle the request security
     * 
     * @param array $vars
     * @param callable $next
     * @return mixed
     */
    public function handle(array $vars, callable $next): mixed
    {
        // 1. Add Security Headers
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: SAMEORIGIN");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
        
        // Content Security Policy (Adjusted for external CDNs used in layout)
        header("Content-Security-Policy: default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://unpkg.com; " . 
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
               "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
               "img-src 'self' data: https://images.unsplash.com https://placehold.co; " .
               "connect-src 'self';");

        // Only if HTTPS is detected
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                   (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        if ($isHttps) {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        }

        // 2. CSRF Validation for POST/PUT/DELETE/PATCH
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            
            if (!isset($_SESSION['csrf_token']) || empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
                $this->abortCsrf();
            }
        }

        return $next($vars);
    }

    /**
     * Abort the request due to CSRF failure
     */
    private function abortCsrf(): void
    {
        http_response_code(419); // Page Expired/CSRF fail
        
        $isJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || 
                  str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') ||
                  (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($isJson) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'error' => 'Security token mismatch. Please refresh the page.',
                'code' => 419
            ]);
        } else {
            $errorFile = dirname(__DIR__) . '/pages/404.php';
            if (file_exists($errorFile)) {
                $errorTitle = "Security Check Failed";
                $errorMessage = "Your session has expired or the security token is invalid. Please try again.";
                require_once $errorFile;
            } else {
                echo "<h1>419 Security Token Mismatch</h1><p>The CSRF token is invalid or has expired.</p>";
            }
        }
        exit;
    }
}
