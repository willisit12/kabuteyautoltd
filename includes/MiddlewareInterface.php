<?php
declare(strict_types=1);

/**
 * includes/MiddlewareInterface.php
 * Interface for all route middlewares
 */
interface MiddlewareInterface
{
    /**
     * Handle the request
     * 
     * @param array $vars Route variables/parameters
     * @param callable $next The next middleware or the route handler
     * @return mixed
     */
    public function handle(array $vars, callable $next): mixed;
}
