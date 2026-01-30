<?php

namespace Upsoftware\Svarium\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CoreCommand extends Command
{
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

        return var_export($value, true);
    }

    protected function addConfigKey(string $path, string $key, mixed $value): ?bool
    {
        $configPath = config_path($path);

        if (! File::exists($configPath)) {
            return false;
        }

        $content = File::get($configPath);

        if (str_contains($content, "'$key' =>")) {
            $this->comment("Klucz '$key' już istnieje.");
            return false;
        }

        $formattedValue = $this->formatConfigValue($value);

        $pattern = "/\n\];\s*$/";
        $replacement = "\n\n    '$key' => $formattedValue,\n];";

        $newContent = preg_replace($pattern, $replacement, $content, 1);

        if ($newContent === null || $newContent === $content) {
            $this->error("Nie udało się dodać klucza '$key'.");
            return false;
        }

        File::put($configPath, $newContent);
        $this->comment("Dodano klucz '$key' do $path");

        return true;
    }
}
