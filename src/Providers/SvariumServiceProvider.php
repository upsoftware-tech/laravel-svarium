<?php

namespace Upsoftware\Svarium\Providers;

use Illuminate\Routing\Router;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Upsoftware\Svarium\Console\Commands\AddLanguageCommand;
use Upsoftware\Svarium\Console\Commands\GenerateLangJson;
use Upsoftware\Svarium\Console\Commands\InitCommand;
use Upsoftware\Svarium\Console\Commands\LayoutCommand;
use Upsoftware\Svarium\Console\Commands\LoginSocialCommand;
use Upsoftware\Svarium\Console\Commands\MakeResource;
use Upsoftware\Svarium\Console\Commands\MenuAddCommand;
use Upsoftware\Svarium\Console\Commands\MergeLangCommand;
use Upsoftware\Svarium\Console\Commands\SortLanguageCommand;
use Upsoftware\Svarium\Http\Middleware\AuthenticateMiddleware;
use Upsoftware\Svarium\Services\DeviceTracking\DeviceTracking;
use Upsoftware\Svarium\Services\LayoutService;
use Illuminate\Auth\Middleware\Authenticate;

class SvariumServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('layout', function($app) {
            return new LayoutService();
        });

        $this->app->singleton('device-tracking', function () {
            return new DeviceTracking;
        });

        if (config('upsoftware.tracking.detect_on_login')) {
            $this->app->register(EventServiceProvider::class);
        }
    }

    public function boot(Router $router): void
    {

        require_once(__DIR__ . '/../Helpers/index.php');

        $directory = svarium_resources();
        $subdirectories = collect(File::directories($directory))->map(fn($path) => basename($path))->toArray();
        foreach ($subdirectories as $subdirectory) {
            $helperDir = $directory . $subdirectory . DIRECTORY_SEPARATOR . 'Helpers';
            $helpersFile = collect(File::files($helperDir))->map(fn($path) => basename($path))->toArray();
            foreach($helpersFile as $helperFile) {
                $helperPath = $helperDir . DIRECTORY_SEPARATOR . $helperFile;
                require_once($helperPath);
            }
        }

        $router->aliasMiddleware('auth.panel', AuthenticateMiddleware::class);

        $this->app->booted(function () {
            if (!Route::has('login') && Route::has('panel.auth.login')) {
                Route::getRoutes()->refreshNameLookups();

                $loginRoute = Route::getRoutes()->getByName('panel.auth.login');
                if ($loginRoute) {
                    Route::getRoutes()->addRoute($loginRoute)->name('login');
                }
            }
        });

        $langPath = __DIR__ . '/../lang';
        $this->loadJsonTranslationsFrom($langPath);
        $this->loadTranslationsFrom($langPath, 'svarium');

        $this->publishes([__DIR__.'/../config/upsoftware.php' => config_path('upsoftware.php')], 'upsoftware');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InitCommand::class,
                LoginSocialCommand::class,
                GenerateLangJson::class,
                MergeLangCommand::class,
                AddLanguageCommand::class,
                SortLanguageCommand::class,
                MenuAddCommand::class,
                LayoutCommand::class,
                MakeResource::class,
            ]);
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        Route::middleware(['web'])
            ->namespace('Upsoftware\Svarium\Http\Controllers')
            ->group(__DIR__.'/../routes/web.php');
    }

    protected function resolveLoginRoute(Request $request): string
    {
        return Route::has('login') ? route('login') : route('login.panel');
    }
}
