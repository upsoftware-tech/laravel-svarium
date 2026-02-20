<?php

namespace Upsoftware\Svarium\Providers;

use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Upsoftware\Svarium\Auth\AuthManager;
use Upsoftware\Svarium\Http\Middleware\AuthenticateMiddleware;
use Upsoftware\Svarium\Modules\ModuleRegistry;
use Upsoftware\Svarium\Panel\BindingRegistry;
use Upsoftware\Svarium\Panel\OperationRegistry;
use Upsoftware\Svarium\Panel\PanelRegistry;
use Upsoftware\Svarium\Routing\SvariumHttpKernel;
use Upsoftware\Svarium\Services\DeviceTracking\DeviceTracking;
use Upsoftware\Svarium\Services\LayoutService;

class SvariumServiceProvider extends ServiceProvider
{
    /*
    |--------------------------------------------------------------------------
    | REGISTER — tylko bindy
    |--------------------------------------------------------------------------
    */
    public function register(): void
    {
        $this->app->register(SvariumPluginAggregateServiceProvider::class);

        $this->app->singleton('layout', fn() => new LayoutService());
        $this->app->singleton('device-tracking', fn() => new DeviceTracking);

        $this->app->singleton('auth-manager', fn() =>
        (new AuthManager())->resolveHandler()
        );

        /*
        |-----------------------------
        | Module system
        |-----------------------------
        */

        $this->app->singleton(ModuleRegistry::class, function () {
            $registry = new ModuleRegistry();
            $registry->loadFromApp();
            $registry->registerPhase(); // tylko register
            return $registry;
        });

        $this->app->singleton(OperationRegistry::class);

        $this->app->singleton(PanelRegistry::class, function () {
            $registry = new PanelRegistry();

            foreach (require base_path('app/Svarium/panels.php') as $panel) {
                $registry->register($panel);
            }

            return $registry;
        });

        $this->app->singleton(BindingRegistry::class);

        $this->registerHelpers();
    }

    /*
    |--------------------------------------------------------------------------
    | BOOT — start systemu
    |--------------------------------------------------------------------------
    */
    public function boot(Router $router): void
    {
        /*
        |-----------------------------
        | Middleware
        |-----------------------------
        */
        $router->aliasMiddleware('auth.panel', AuthenticateMiddleware::class);

        /*
        |-----------------------------
        | Translations
        |-----------------------------
        */
        $langPath = __DIR__ . '/../lang';
        $this->loadJsonTranslationsFrom($langPath);
        $this->loadTranslationsFrom($langPath, 'svarium');

        /*
        |-----------------------------
        | Publish / migrations
        |-----------------------------
        */
        $this->publishes([
            __DIR__.'/../config/upsoftware.php' => config_path('upsoftware.php')
        ], 'upsoftware');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        /*
        |-----------------------------
        | Start Svarium kernel
        |-----------------------------
        */

        $modules = app(ModuleRegistry::class);

        // budujemy routing operacji z modułów
        app(OperationRegistry::class)->bootFromModules($modules);

        // dopiero po zbudowaniu systemu moduły bootują
        $modules->bootPhase();

        /*
        |-----------------------------
        | Model bindings
        |-----------------------------
        */
        app(BindingRegistry::class)->bind('page', fn($value) => Page::findOrFail($value));

        /*
        |-----------------------------
        | Fallback router Svarium
        |-----------------------------
        */
        Route::any('{path}', SvariumHttpKernel::class)->where('path', '.*');

        /*
        |-----------------------------
        | Default routes
        |-----------------------------
        */
        Route::middleware(['web'])
            ->namespace('Upsoftware\Svarium\Http\Controllers')
            ->group(__DIR__.'/../routes/web.php');

        /*
        |-----------------------------
        | Console
        |-----------------------------
        */
        $this->consoleCommands();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers autoload
    |--------------------------------------------------------------------------
    */
    protected function registerHelpers(): void
    {
        require_once(__DIR__ . '/../Helpers/index.php');

        if (!File::exists(svarium_resources())) {
            return;
        }

        foreach (File::directories(svarium_resources()) as $dir) {

            $helperDir = $dir . DIRECTORY_SEPARATOR . 'Helpers';

            if (!File::isDirectory($helperDir)) {
                continue;
            }

            foreach (File::files($helperDir) as $file) {
                if ($file->getExtension() === 'php') {
                    require_once($file->getRealPath());
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Console commands auto-discovery
    |--------------------------------------------------------------------------
    */
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

            $relative = str_replace(
                [DIRECTORY_SEPARATOR, '.php'],
                ['\\', ''],
                $file->getRelativePathname()
            );

            $class = trim($namespace, '\\').'\\'.$relative;

            if (!class_exists($class)) continue;

            $reflection = new \ReflectionClass($class);

            if ($reflection->isInstantiable() && $reflection->isSubclassOf(Command::class)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    public function consoleCommands(): void
    {
        if (!$this->app->runningInConsole()) return;

        $commands = $this->discoverCommands(
            __DIR__.'/../Console/Commands',
            'Upsoftware\\Svarium\\Console\\Commands'
        );

        $this->commands($commands);
    }
}
