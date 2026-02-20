<?php

namespace Upsoftware\Svarium\Panel;

use Illuminate\Support\Facades\File;
use Upsoftware\Svarium\Modules\ModuleRegistry;

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

    public function bootFromModules(ModuleRegistry $modules): void
    {
        foreach ($modules->all() as $module) {

            $panelPath = $module->path('Panel');

            if (!is_dir($panelPath)) {
                continue;
            }

            foreach (File::allFiles($panelPath) as $file) {

                dump([
                    'module' => get_class($module),
                    'path' => $module->path(),
                    'panelPath' => $module->path('Panel'),
                    'exists' => is_dir($module->path('Panel')),
                ]);

                $class = $this->classFromFile($file->getPathname());

                if (!class_exists($class) || !is_subclass_of($class, Operation::class)) {
                    continue;
                }

                foreach ((array) $class::$panels as $panel) {

                    dump('REGISTER', [
                        'class' => $class,
                        'panel' => $panel,
                        'uri'   => $class::uri(),
                    ]);

                    $this->register($panel, $class);
                }
            }

        }
    }

    protected function classFromFile(string $path): string
    {
        $relative = str_replace(app_path().DIRECTORY_SEPARATOR, '', $path);
        $relative = str_replace(['/', '.php'], ['\\', ''], $relative);

        return 'App\\'.$relative;
    }
}
