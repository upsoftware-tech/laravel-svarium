<?php

namespace Upsoftware\Svarium\Console\Commands;

use Illuminate\Support\Facades\File;

class InitCommand extends CoreCommand
{

    protected $signature = 'svarium:init';

    protected $description = 'Iniciuje aplikację (dodaje niezbędną konfigurację)';

    public function handle()
    {
        $this->info('Publikowanie Spatie Permission...');
        $this->call('vendor:publish', [
            '--provider' => "Spatie\\Permission\\PermissionServiceProvider"
        ]);

        $this->info('Publikowanie Laravel Lang...');
        $this->call('vendor:publish', [
            '--provider' => "LaravelLang\Config\ServiceProvider"
        ]);

        $this->info('Publikowanie Hashids...');
        $this->call('vendor:publish', [
            '--provider' => "Vinkla\Hashids\HashidsServiceProvider"
        ]);

        if ($this->confirm('Czy opublikować zasoby konfiguracyjne Tenancy?', false)) {
            $this->info('Publikowanie Hashids...');
            $this->call('vendor:publish', [
                '--provider' => "Stancl\Tenancy\TenancyServiceProvider"
            ]);

            if ($this->addConfigKey('tenancy.php', 'enabled', true)) {
                $this->info('Dodano klucz "enabled" => true do config/tenancy.php');
            }
        }

        $currentLocale = config('app.locale');
        $selectedLocale = $this->ask('Podaj domyślny język aplikacji (APP_LOCALE)', $currentLocale);
        $this->info("Instalowanie plików językowych dla: $selectedLocale ...");
        passthru("php artisan lang:add $selectedLocale");

        if ($selectedLocale !== $currentLocale) {
            $this->updateEnvFile('APP_LOCALE', $selectedLocale, true);
            $this->info("Zaktualizowano APP_LOCALE w pliku .env na: $selectedLocale");

            config(['app.locale' => $selectedLocale]);
        }

        while (true) {
            if (! $this->confirm('Czy chcesz dodać język (lub kolejny)?', true)) {
                break;
            }

            while (true) {
                $code = $this->ask('Wpisz kod języka (np. pl, en, de, es)');

                if (empty($code)) {
                    $this->warn('Nie podano kodu języka. Spróbuj ponownie.');
                    continue;
                }

                $this->info("Dodawanie języka: $code ...");
                passthru("php artisan lang:add $code");
                $this->newLine();

                break;
            }
        }

        $this->call('svarium:lang.prepare');
        $this->call('svarium:lang.merge');
        $this->call('svarium:login.socials');

        $this->info('Gotowe!');
    }
}
