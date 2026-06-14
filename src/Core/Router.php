<?php
/**
 * API Router
 */

namespace Exqpay\Core;

use Exqpay\Core\Middleware\Pipeline;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    /**
     * Register GET route
     */
    public function get(string $path, $handler, array $middlewares = []): void
    {
        $this->registerRoute('GET', $path, $handler, $middlewares);
    }

    /**
     * Register POST route
     */
    public function post(string $path, $handler, array $middlewares = []): void
    {
        $this->registerRoute('POST', $path, $handler, $middlewares);
    }

    /**
     * Register PUT route
     */
    public function put(string $path, $handler, array $middlewares = []): void
    {
        $this->registerRoute('PUT', $path, $handler, $middlewares);
    }

    /**
     * Register DELETE route
     */
    public function delete(string $path, $handler, array $middlewares = []): void
    {
        $this->registerRoute('DELETE', $path, $handler, $middlewares);
    }

    /**
     * Register PATCH route
     */
    public function patch(string $path, $handler, array $middlewares = []): void
    {
        $this->registerRoute('PATCH', $path, $handler, $middlewares);
    }

    /**
     * Register route
     */
    private function registerRoute(string $method, string $path, $handler, array $middlewares = []): void
    {
        $key = $method . ' ' . $path;
        $this->routes[$key] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    /**
     * Register global middleware
     */
    public function middleware($middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Dispatch request
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        // Try to find matching route
        $route = $this->findRoute($method, $path, $request);

        if (!$route) {
            $response = new Response();
            return $response->status(404)->error('Route not found', 404);
        }

        return $this->executeRoute($route, $request);
    }

    /**
     * Find matching route
     */
    private function findRoute(string $method, string $path, Request $request): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->pathToRegex($route['path']);

            if (preg_match($pattern, $path, $matches)) {
                // Extract route parameters
                $params = [];
                foreach ($matches as $key => $value) {
                    if (!is_numeric($key)) {
                        $params[$key] = $value;
                    }
                }
                $request->setRouteParams($params);
                return $route;
            }
        }

        return null;
    }

    /**
     * Convert path to regex pattern
     */
    private function pathToRegex(string $path): string
    {
        $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Execute route with middleware pipeline
     */
    private function executeRoute(array $route, Request $request): Response
    {
        try {
            // Resolve handler
            [$controller, $method] = $this->resolveHandler($route['handler']);

            // Create pipeline
            $pipeline = new Pipeline($request, $controller, $method);

            // Add global middlewares
            foreach ($this->middlewares as $middleware) {
                $pipeline->pipe($middleware);
            }

            // Add route-specific middlewares
            foreach ($route['middlewares'] as $middleware) {
                $pipeline->pipe($middleware);
            }

            return $pipeline->execute();
        } catch (\Exception $e) {
            return ErrorHandler::handleException($e);
        }
    }

    /**
     * Resolve handler string to controller instance and method
     */
    private function resolveHandler($handler): array
    {
        if (is_array($handler)) {
            return $handler;
        }

        [$controllerClass, $method] = explode('@', $handler);
        $controller = new $controllerClass();

        return [$controller, $method];
    }
}
