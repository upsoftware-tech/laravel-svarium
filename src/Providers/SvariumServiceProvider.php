<?php

namespace Upsoftware\Svarium\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Upsoftware\Svarium\Console\Commands\GenerateLangJson;
use Upsoftware\Svarium\Console\Commands\InitCommand;
use Upsoftware\Svarium\Console\Commands\LoginSocialCommand;
use Upsoftware\Svarium\Console\Commands\MergeLangCommand;

class SvariumServiceProvider extends ServiceProvider
{
    public function register(): void
    {

    }

    public function boot(): void
    {
        $langPath = __DIR__ . '/../lang';
        $this->loadJsonTranslationsFrom($langPath);
        $this->loadTranslationsFrom($langPath, 'svarium');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InitCommand::class,
                LoginSocialCommand::class,
                GenerateLangJson::class,
                MergeLangCommand::class,
            ]);
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        Route::middleware(['web'])
            ->namespace('Upsoftware\Svarium\Http\Controllers')
            ->group(__DIR__.'/../routes/web.php');
    }
}
