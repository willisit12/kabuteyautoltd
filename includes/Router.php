<?php
declare(strict_types=1);

/**
 * includes/Router.php
 * Advanced Router for modern PHP applications
 */
class Router
{
    private array $routes = [];
    private array $globalMiddleware = [];
    private readonly string $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Register a GET route
     */
    public function get(string $path, string|callable $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, string|callable $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Add a route with a specific method
     */
    private function addRoute(string $method, string $path, string|callable $handler): self
    {
        $path = '/' . ltrim($path, '/');
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => []
        ];
        return $this;
    }

    /**
     * Add middleware to the last registered route
     */
    public function middleware(string|MiddlewareInterface $middleware): self
    {
        if (!empty($this->routes)) {
            $lastIndex = count($this->routes) - 1;
            $this->routes[$lastIndex]['middleware'][] = $middleware;
        }
        return $this;
    }

    /**
     * Add global middleware
     */
    public function addGlobalMiddleware(string|MiddlewareInterface $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    /**
     * Dispatch the current request
     */
    public function dispatch(): void
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Parse path and remove base path
        $parsedUrl = parse_url($requestUri);
        $path = $parsedUrl['path'] ?? '/';
        
        if ($this->basePath !== '' && strpos($path, $this->basePath) === 0) {
            $path = substr($path, strlen($this->basePath));
        }
        
        $path = '/' . ltrim($path, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            $pattern = $this->compilePattern($route['path']);
            if (preg_match($pattern, $path, $matches)) {
                $vars = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->handleFoundRoute($route, $vars);
                return;
            }
        }

        $this->handleNotFound();
    }

    /**
     * Compile route path into a regex pattern
     */
    private function compilePattern(string $path): string
    {
        // Fix regex to properly support alphanumeric, hyphens, and underscores
        return '#^' . preg_replace('#\{([a-zA-Z0-9\-_]+)\}#', '(?P<$1>[^/]+)', $path) . '$#';
    }

    /**
     * Handle the route with its middleware stack
     */
    private function handleFoundRoute(array $route, array $vars): void
    {
        $middlewareStack = array_merge($this->globalMiddleware, $route['middleware']);
        
        $handler = function ($vars) use ($route) {
            return $this->executeHandler($route['handler'], $vars);
        };

        $pipeline = $this->buildPipeline($middlewareStack, $handler);
        $pipeline($vars);
    }

    /**
     * Build the middleware execution pipeline
     */
    private function buildPipeline(array $middlewareStack, callable $handler): callable
    {
        return array_reduce(
            array_reverse($middlewareStack),
            function ($next, $middleware) {
                return function ($vars) use ($next, $middleware) {
                    if (is_string($middleware)) {
                        $middleware = new $middleware();
                    }
                    return $middleware->handle($vars, $next);
                };
            },
            $handler
        );
    }

    /**
     * Execute the route handler (either a closure or a file path)
     */
    private function executeHandler(string|callable $handler, array $vars): void
    {
        if (is_callable($handler)) {
            call_user_func($handler, $vars);
            return;
        }

        // Assume string is a file path
        if (file_exists($handler)) {
            // Extract variables to local scope for the included file
            extract($vars, EXTR_SKIP);
            require_once $handler;
        } else {
            $this->handleNotFound();
        }
    }

    private function handleNotFound(): void
    {
        http_response_code(404);
        $notFoundFile = dirname(__DIR__) . '/pages/404.php';
        if (file_exists($notFoundFile)) {
            require_once $notFoundFile;
        } else {
            echo "404 Not Found";
        }
    }
}
