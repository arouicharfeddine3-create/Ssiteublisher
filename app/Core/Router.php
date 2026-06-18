<?php
namespace App\Core;

class Router
{
    private array $routes = [];
    private array $groupStack = [];

    public function group(string $prefix, callable $callback): void
    {
        $this->groupStack[] = $prefix;
        $callback($this);
        array_pop($this->groupStack);
    }

    public function addRoute(string $method, string $uri, mixed $handler, array $middleware = []): void
    {
        $uri = $this->buildUri($uri);
        $this->routes[] = [
            'method' => strtoupper($method),
            'uri' => $uri,
            'handler' => $handler,
            'middleware' => $middleware,
            'pattern' => $this->convertToRegex($uri),
        ];
    }

    public function get(string $uri, mixed $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $uri, $handler, $middleware);
    }

    public function post(string $uri, mixed $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $uri, $handler, $middleware);
    }

    public function put(string $uri, mixed $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $uri, $handler, $middleware);
    }

    public function delete(string $uri, mixed $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $uri, $handler, $middleware);
    }

    private function buildUri(string $uri): string
    {
        $prefix = implode('', $this->groupStack);
        return '/' . trim($prefix . '/' . trim($uri, '/'), '/');
    }

    private function convertToRegex(string $uri): string
    {
        $pattern = preg_quote($uri, '/');
        $pattern = str_replace('\{id\}', '([^\/]+)', $pattern);
        $pattern = str_replace('\{slug\}', '([a-zA-Z0-9\-]+)', $pattern);
        return '/^' . $pattern . '$/';
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';
        $method = strtoupper($method);
        if ($method === 'POST') {
            $override = strtoupper((string) ($_POST['_method'] ?? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? ''));
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method && $route['method'] !== 'ANY') continue;
            if (preg_match($route['pattern'], $uri, $matches)) {
                array_shift($matches);
                // Execute middleware
                foreach ($route['middleware'] as $mwClass) {
                    $mw = new $mwClass();
                    if (!$mw->handle()) {
                        return; // Middleware stopped execution
                    }
                }
                // Call handler
                if (is_callable($route['handler'])) {
                    call_user_func_array($route['handler'], $matches);
                } elseif (is_array($route['handler']) && count($route['handler']) === 2) {
                    [$controller, $action] = $route['handler'];
                    if (class_exists($controller)) {
                        $instance = new $controller();
                        call_user_func_array([$instance, $action], $matches);
                    }
                }
                return;
            }
        }
        // 404
        http_response_code(404);
        echo View::render('errors/404');
    }
}