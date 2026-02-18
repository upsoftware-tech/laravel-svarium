<?php

namespace Upsoftware\Svarium\Panel;

class OperationRegistry
{
    protected array $routes = [];

    public function register(string $panel, string $operation): void
    {
        foreach ($operation::methods() as $method) {

            [$pattern, $names] = $this->compile($operation::uri());

            $this->routes[$panel][$method][] = [
                'operation' => $operation,
                'pattern'   => $pattern,
                'names'     => $names,
            ];
        }
    }

    public function resolve(string $panel, string $method, string $uri): ?array
    {
        foreach ($this->routes[$panel][$method] ?? [] as $route) {

            if (preg_match($route['pattern'], $uri, $matches)) {

                array_shift($matches);

                $params = array_combine($route['names'], $matches);

                return [
                    'operation' => $route['operation'],
                    'params'    => $params,
                ];
            }
        }

        return null;
    }

    protected function compile(string $uri): array
    {
        preg_match_all('/\{([^}]+)\}/', $uri, $paramNames);

        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $uri);
        $pattern = '#^' . $pattern . '$#';

        return [$pattern, $paramNames[1]];
    }
}
