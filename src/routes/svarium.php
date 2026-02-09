<?php

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Upsoftware\Svarium\Http\Middleware\LocaleMiddleware;
use Upsoftware\Svarium\Http\Middleware\HandleInertiaRequests;

$middleware = ['web'];
$middleware[] = LocaleMiddleware::class;
$middleware[] = HandleInertiaRequests::class;

if (config('tenancy.enabled', false)) {
    $middleware[] = InitializeTenancyByDomain::class;
    $middleware[] = PreventAccessFromCentralDomains::class;
}

$resourceDir = svarium_resources();
if (File::exists($resourceDir)) {
    $directories = File::directories($resourceDir);
    foreach ($directories as $path) {
        $resourceName = basename($path);

        $resourceName = basename($path);
        $className = "App\\Svarium\\Resources\\{$resourceName}\\{$resourceName}Resource";

        if (class_exists($className)) {
            $routeName = $className::getRouteName();
            $pages = $className::getPages();

            Route::middleware($middleware)->group(function () use ($routeName, $className, $pages) {
                Route::prefix($routeName)->as("svarium.{$routeName}.")->group(function () use ($className, $routeName, $pages) {
                    foreach ($pages as $key => $pageClass) {
                        $method = $pageClass::getMethod();
                        $routeName = $pageClass::getRouteName();
                        $action = $pageClass::getAction();

                        $url = ($key === 'index') ? '/' : '/' . $routeName;

                        if (in_array($key, ['edit', 'update', 'delete', 'restore', 'duplicate'])) {
                            $url .= '/{record}';
                        }

                        Route::$method($url, [$pageClass, $action])->name($key);
                    }
                });
            });
        }
    }
}

