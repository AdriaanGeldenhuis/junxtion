<?php
/**
 * API Router
 *
 * Simple router for API endpoints
 */

class Router
{
    private array $routes = [];
    private array $middleware = [];

    /**
     * Register GET route
     */
    public function get(string $path, callable $handler, array $middleware = []): void
    {
        $this->routes['GET'][$path] = ['handler' => $handler, 'middleware' => $middleware];
    }

    /**
     * Register POST route
     */
    public function post(string $path, callable $handler, array $middleware = []): void
    {
        $this->routes['POST'][$path] = ['handler' => $handler, 'middleware' => $middleware];
    }

    /**
     * Register PUT route
     */
    public function put(string $path, callable $handler, array $middleware = []): void
    {
        $this->routes['PUT'][$path] = ['handler' => $handler, 'middleware' => $middleware];
    }

    /**
     * Register DELETE route
     */
    public function delete(string $path, callable $handler, array $middleware = []): void
    {
        $this->routes['DELETE'][$path] = ['handler' => $handler, 'middleware' => $middleware];
    }

    /**
     * Register route for multiple methods
     */
    public function match(array $methods, string $path, callable $handler, array $middleware = []): void
    {
        foreach ($methods as $method) {
            $this->routes[strtoupper($method)][$path] = ['handler' => $handler, 'middleware' => $middleware];
        }
    }

    /**
     * Dispatch request to appropriate handler
     */
    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);

        // Normalize path
        $path = '/' . trim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        // Try exact match first
        if (isset($this->routes[$method][$path])) {
            $route = $this->routes[$method][$path];
            $this->runMiddleware($route['middleware']);
            call_user_func($route['handler']);
            return;
        }

        // Try pattern matching
        foreach ($this->routes[$method] ?? [] as $pattern => $route) {
            $params = $this->matchPattern($pattern, $path);
            if ($params !== null) {
                $this->runMiddleware($route['middleware']);
                call_user_func($route['handler'], $params);
                return;
            }
        }

        // Check if route exists for other methods (405 vs 404)
        foreach ($this->routes as $m => $routes) {
            if ($m === $method) continue;
            if (isset($routes[$path])) {
                Response::methodNotAllowed(array_keys(array_filter(
                    $this->routes,
                    fn($routes) => isset($routes[$path])
                )));
                return;
            }
        }

        // Not found
        Response::notFound('Endpoint not found');
    }

    /**
     * Match URL pattern with parameters
     * Supports: /users/{id}, /orders/{orderId}/items/{itemId}
     */
    private function matchPattern(string $pattern, string $path): ?array
    {
        // Convert pattern to regex
        $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $path, $matches)) {
            // Filter out numeric keys
            return array_filter($matches, fn($k) => !is_numeric($k), ARRAY_FILTER_USE_KEY);
        }

        return null;
    }

    /**
     * Run middleware stack
     */
    private function runMiddleware(array $middleware): void
    {
        foreach ($middleware as $mw) {
            if (is_callable($mw)) {
                $mw();
            } elseif (is_string($mw)) {
                switch ($mw) {
                    case 'auth':
                        Auth::requireAuth();
                        break;
                    case 'staff':
                        Auth::requireStaff();
                        break;
                    case 'manager':
                        Auth::requireAnyRole(['manager', 'super_admin']);
                        break;
                    case 'admin':
                        Auth::requireRole('super_admin');
                        break;
                    case 'rate_limit':
                        RateLimit::enforce(RateLimit::ipKey());
                        break;
                    case 'rate_limit_strict':
                        RateLimit::enforce(RateLimit::ipKey(), 10, 60);
                        break;
                }
            }
        }
    }
}

// Create router instance FIRST, then set to global
$GLOBALS['router'] = new Router();

/**
 * Helper functions for registering routes
 */
function route_get(string $path, callable $handler, array $middleware = []): void
{
    $GLOBALS['router']->get($path, $handler, $middleware);
}

function route_post(string $path, callable $handler, array $middleware = []): void
{
    $GLOBALS['router']->post($path, $handler, $middleware);
}

function route_put(string $path, callable $handler, array $middleware = []): void
{
    $GLOBALS['router']->put($path, $handler, $middleware);
}

function route_delete(string $path, callable $handler, array $middleware = []): void
{
    $GLOBALS['router']->delete($path, $handler, $middleware);
}

// Register core routes
route_get('/health', function () {
    Response::success([
        'status' => 'ok',
        'timestamp' => date('c'),
        'version' => '1.0.0'
    ]);
});

route_get('/', function () {
    Response::success([
        'name' => 'Junxtion API',
        'version' => '1.0.0',
        'documentation' => 'https://junxtionapp.co.za/api/docs'
    ]);
});

// Load route files AFTER router is set to global
$routeFiles = [
    'auth_customer.php',
    'auth_staff.php',
    'menu.php',
    'orders.php',
    'admin_menu.php',
    'admin_orders.php',
    'admin_notifications.php',
    'notifications.php',
    'webhooks.php',
];

foreach ($routeFiles as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        require_once $path;
    }
}
