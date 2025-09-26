<?php

namespace App\Core;

class App
{
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [$method, $pattern, $handler];
    }

    public function dispatch(): void
    {
        $request = new Request();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        foreach ($this->routes as [$routeMethod, $pattern, $handler]) {
            if (strtoupper($method) !== strtoupper($routeMethod)) {
                continue;
            }

            $regex = $this->convertPatternToRegex($pattern);
            if (preg_match($regex, $uri, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (!is_int($key)) {
                        $params[$key] = $value;
                    }
                }
                $handler($request, $params);
                return;
            }
        }

        Response::json(['error' => 'Not found'], 404);
    }

    private function convertPatternToRegex(string $pattern): string
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }
}
