<?php

namespace Upsoftware\Svarium\Providers;

use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Upsoftware\Svarium\Auth\AuthManager;
use Upsoftware\Svarium\Console\Commands\AddLanguageCommand;
use Upsoftware\Svarium\Console\Commands\GenerateLangJson;
use Upsoftware\Svarium\Console\Commands\InitCommand;
use Upsoftware\Svarium\Console\Commands\LayoutCommand;
use Upsoftware\Svarium\Console\Commands\LoginSocialCommand;
use Upsoftware\Svarium\Console\Commands\MakePermissionCommand;
use Upsoftware\Svarium\Console\Commands\MakeResource;
use Upsoftware\Svarium\Console\Commands\MenuAddCommand;
use Upsoftware\Svarium\Console\Commands\MergeLangCommand;
use Upsoftware\Svarium\Console\Commands\SortLanguageCommand;
use Upsoftware\Svarium\Http\Middleware\AuthenticateMiddleware;
use Upsoftware\Svarium\Panel\BindingRegistry;
use Upsoftware\Svarium\Panel\OperationRegistry;
use Upsoftware\Svarium\Panel\Operation;
use Upsoftware\Svarium\Panel\PanelRegistry;
use Upsoftware\Svarium\Routing\SvariumHttpKernel;
use Upsoftware\Svarium\Services\DeviceTracking\DeviceTracking;
use Upsoftware\Svarium\Services\LayoutService;

class SvariumServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(SvariumPluginAggregateServiceProvider::class);

        $this->app->singleton('layout', function () {
            return new LayoutService();
        });

        $this->app->singleton('device-tracking', function () {
            return new DeviceTracking;
        });

        if (config('upsoftware.tracking.detect_on_login')) {
            $this->app->register(EventServiceProvider::class);
        }

        $this->app->singleton('auth-manager', function () {
            return (new AuthManager())->resolveHandler();
        });

        $this->app->singleton(PanelRegistry::class, function () {
            $registry = new PanelRegistry();

            foreach (require base_path('app/Svarium/panels.php') as $panel) {
                $registry->register($panel);
            }

            return $registry;
        });

        $this->app->singleton(OperationRegistry::class, function () {

            $registry = new OperationRegistry();

            $path = app_path('Svarium/Panel/Operations');

            if (!is_dir($path)) {
                return $registry;
            }

            foreach (File::allFiles($path) as $file) {

                $class = 'App\\Svarium\\Panel\\Operations\\' . $file->getFilenameWithoutExtension();

                if (!class_exists($class)) {
                    continue;
                }

                if (!is_subclass_of($class, Operation::class)) {
                    continue;
                }
                $panels = (array) $class::$panels;

                foreach ($panels as $panel) {
                    $registry->register($panel, $class);
                }
            }
            return $registry;
        });

        $this->app->singleton(BindingRegistry::class);

        $this->registerHelpers();
    }

    public function boot(Router $router): void
    {
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

        $this->consoleCommands();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        app(BindingRegistry::class)->bind('page', function ($value) {
            return Page::findOrFail($value);
        });

        Route::any('{path}', SvariumHttpKernel::class)->where('path', '.*');

        Route::middleware(['web'])
            ->namespace('Upsoftware\Svarium\Http\Controllers')
            ->group(__DIR__.'/../routes/web.php');
    }

    public function registerHelpers(): void
    {
        require_once(__DIR__ . '/../Helpers/index.php');

        if (!File::exists(svarium_resources())) {
            return;
        }

        $directory = svarium_resources();
        $subdirectories = collect(File::directories($directory))->map(fn($path) => basename($path));

        foreach ($subdirectories as $subdirectory) {
            $helperDir = $directory . $subdirectory . DIRECTORY_SEPARATOR . 'Helpers';
            if (File::isDirectory($helperDir)) {
                $helpersFiles = File::files($helperDir);
                foreach ($helpersFiles as $file) {
                    if ($file->getExtension() === 'php') {
                        require_once($file->getRealPath());
                    }
                }
            }
        }
    }

    protected function discoverCommands(string $path, string $namespace): array
    {
        if (!is_dir($path)) return [];

        $classes = [];
        $exclude = ['CoreCommand'];

        foreach (File::allFiles($path) as $file) {
            $className = $file->getFilenameWithoutExtension();

            if (in_array($className, $exclude)) {
                continue;
            }

            $relativePath = $file->getRelativePathname();
            $classSuffix = str_replace(
                [DIRECTORY_SEPARATOR, '.php'],
                ['\\', ''],
                $relativePath
            );

            $class = trim($namespace, '\\') . '\\' . $classSuffix;

            if (class_exists($class)) {
                $reflection = new \ReflectionClass($class);

                if ($reflection->isInstantiable() && $reflection->isSubclassOf(Command::class)) {
                    $classes[] = $class;
                }
            }
        }
        return $classes;
    }

    public function consoleCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $commands = $this->discoverCommands(__DIR__.'/../Console/Commands', 'Upsoftware\\Svarium\\Console\\Commands');

        $this->commands($commands);
    }
}
