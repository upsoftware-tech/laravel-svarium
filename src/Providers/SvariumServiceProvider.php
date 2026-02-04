<?php

namespace Upsoftware\Svarium\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Upsoftware\Svarium\Console\Commands\AddLanguageCommand;
use Upsoftware\Svarium\Console\Commands\GenerateLangJson;
use Upsoftware\Svarium\Console\Commands\InitCommand;
use Upsoftware\Svarium\Console\Commands\LayoutCommand;
use Upsoftware\Svarium\Console\Commands\LoginSocialCommand;
use Upsoftware\Svarium\Console\Commands\MenuAddCommand;
use Upsoftware\Svarium\Console\Commands\MergeLangCommand;
use Upsoftware\Svarium\Console\Commands\SortLanguageCommand;
use Upsoftware\Svarium\Services\LayoutService;

class SvariumServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('layout', function($app) {
            return new LayoutService();
        });
    }

    public function boot(): void
    {
        require_once(__DIR__ . '/../Helpers/index.php');

        $langPath = __DIR__ . '/../lang';
        $this->loadJsonTranslationsFrom($langPath);
        $this->loadTranslationsFrom($langPath, 'svarium');

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
            ]);
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        Route::middleware(['web'])
            ->namespace('Upsoftware\Svarium\Http\Controllers')
            ->group(__DIR__.'/../routes/web.php');
    }
}
