<?php

namespace Upsoftware\Svarium\Console\Commands;

use Illuminate\Support\Facades\File;
use Upsoftware\Svarium\Models\Setting;

class InitCommand extends CoreCommand
{

    protected $signature = 'svarium:init';

    protected $description = 'Iniciuje aplikację (dodaje niezbędną konfigurację)';

    protected function addLoginConfiguration() {
        $config = [];

        $config['title'] = $this->ask('Tytuł strony logowania', 'Welcome back!');
        $config['subtitle'] = $this->ask('Podtytuł strony logowania', 'Enter your email address and password');
        $config['submitLabel'] = $this->ask('Tytuł buttona logowania', 'Log in with your email address');
        if ($this->confirm('Czy chcesz dodać rejestrację uzytkownika?', true)) {
            $config['showRegisterLink'] = true;
            $config['registerLabel'] = $this->ask('Tytuł rejestracji', 'If you don’t have an account');
            $config['registerLinkLabel'] = $this->ask('Tytuł linku rejestracji', 'sign up here');
            $config['resetLink'] = $this->ask('Link do rejestracji', '/auth/register');
        } else {
            $config['showRegisterLink'] = false;
            $config['registerLabel'] = '';
            $config['registerLinkLabel'] = '';
            $config['resetLink'] = '';
        }

        if ($this->confirm('Czy chcesz dodać reset hasła uzytkownika?', true)) {
            $config['showResetLink'] = true;
            $config['resetLabel'] = $this->ask('Tytuł linku resetu hasła', 'Forgot your password?');
            $config['registerLink'] = $this->ask('Link do resetu hasła', '/auth/reseet');
        } else {
            $config['showResetLink'] = false;
            $config['resetLabel'] = '';
            $config['registerLink'] = '';
        }

        Setting::setSettingGlobal('login.config', $config);
    }

    public function handle()
    {
        passthru('php artisan ide-helper:generate');
        passthru('php artisan ide-helper:models -N');
        passthru('php artisan ide-helper:meta');

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

        passthru('php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config"');

        if ($this->confirm('Czy opublikować zasoby konfiguracyjne Tenancy?', false)) {
            $this->info('Publikowanie Hashids...');
            $this->call('vendor:publish', [
                '--provider' => "Stancl\Tenancy\TenancyServiceProvider"
            ]);

            if ($this->addConfigKey('tenancy.php', 'enabled', true)) {
                $this->info('Dodano klucz "enabled" => true do config/tenancy.php');
            }
        }

        passthru("php artisan native:install");

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
                passthru("php artisan svarium:lang.add $code");
                $this->newLine();

                break;
            }
        }

        $this->call('svarium:login.socials');

        $this->addLoginConfiguration();

        $this->info('Gotowe!');
    }
}
