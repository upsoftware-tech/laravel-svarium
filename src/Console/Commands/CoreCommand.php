<?php

namespace Upsoftware\Svarium\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CoreCommand extends Command
{
    protected $settingModel;

    public function __construct()
    {
        parent::__construct();
        $this->settingModel = config('svarium.models.setting', \Upsoftware\Svarium\Models\Setting::class);
    }

    protected function updateEnvFile(string $key, string $value, $force = false, $newGroup = false): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            return;
        }

        $content = File::get($envPath);
        $keyPosition = strpos($content, "{$key}=");

        if ($keyPosition !== false) {
            if ($force) {
                $content = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $content
                );
            }
        } else {
            if ($newGroup) { $content .= "\n"; }
            $content .= "\n{$key}={$value}";
        }

        File::put($envPath, $content);
    }

    protected function formatConfigValue(mixed $value): string
    {
        if (is_array($value)) {
            $lines = [];

            foreach ($value as $k => $v) {
                $lines[] = "        '$k' => ".$this->formatConfigValue($v).",";
            }

            return "[\n".implode("\n", $lines)."\n    ]";
        }

        if (is_string($value) && str_starts_with($value, '@env(')) {
            $key = trim($value, '@env()');
            return "env('$key')";
        }

        if (str_ends_with($value, '::class')) {
            // Zwracamy czysty string bez opakowywania go w var_export (bez apostrofów)
            // Trim na wypadek, gdyby ktoś przekazał '\Klasa::class' w apostrofach
            return trim($value, "'\"");
        }

        return var_export($value, true);
    }

    protected function addConfigKey(string $path, string $key, mixed $value, $force = false): ?bool
    {
        $configPath = config_path($path); // Zakładam dodanie .php jeśli brakuje

        if (! File::exists($configPath)) {
            $this->error("Plik konfiguracyjny $path nie istnieje.");
            return false;
        }

        $content = File::get($configPath);
        $formattedValue = $this->formatConfigValue($value);

        if (str_contains($content, "'$key' =>")) {
            if (! $force) {
                $this->comment("Klucz '$key' już istnieje. Użyj force, aby nadpisać.");
                return false;
            }

            $quotedKey = preg_quote($key, '/');
            $pattern = '/([\'"]' . $quotedKey . '[\'"]\s*=>\s*).*?(,?\n)/';
            $replacement = "$1$formattedValue$2";
            $newContent = preg_replace($pattern, $replacement, $content);

            $this->comment("Zaktualizowano (force) klucz '$key' w $path.");
        } else {
            // Logika DODAWANIA: Wstawianie przed zamknięciem tablicy
            $pattern = "/\n\];\s*$/";
            $replacement = "\n\n    '$key' => $formattedValue,\n];";
            $newContent = preg_replace($pattern, $replacement, $content, 1);

            $this->comment("Dodano klucz '$key' do $path.");
        }

        if ($newContent === null || $newContent === $content) {
            $this->error("Nie udało się zmodyfikować klucza '$key'.");
            return false;
        }

        File::put($configPath, $newContent);

        return true;
    }
}
